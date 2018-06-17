<?php
/**
 * Created by PhpStorm.
 * User: nishant
 * Date: 12/03/18
 * Time: 10:39 AM
 */

class Consignee_Notification
{
    public static $obj = NULL;
    public static $modelObj = NULL;

    private $headerMsg = "Icargo Shipment Tracking Notification";

    public

    static function _getInstance(){
        if(self::$obj==NULL){
            self::$obj = new Consignee_Notification();
        }
        return self::$obj;
    }

    public

    static function _getModelInstance(){
        if(self::$modelObj==NULL){
            self::$modelObj = new Notification_Model_Index();
        }
        return self::$modelObj;
    }

    private

    function getShipmentDetail($shipment_ticket){

    }

    public

    function sendRouteStartNotification($param){
        $libraryObj = new Library();

        //get company detail
        $company_info = $this->_getModelInstance()->getCompanyInfo($param["company_id"]);
        $company_address = implode(",<br>",array_filter($company_info));

        $loadInfo = $this->_getModelInstance()->getLoadGroupTypeCodeByShipmentRouteId($param["shipment_route_id"]);
        $loadGroupTypeCode = strtolower($loadInfo["load_group_type_code"]);

        if($loadGroupTypeCode=="same"){
            $trigger_code = "samedayCollectionStarted";
            $recipient_name_and_email = array();
            $next_shipment_tickets = array();


            $shipmentData = $this->_getModelInstance()->getSamedayCollectionShipmentByShipmentRouteId($param["shipment_route_id"]);

            //check email already sent or not
            foreach($shipmentData as $item){
                $emailSentStatus = $this->_getModelInstance()->checkShipmentNotificationAlreadySentByShipmentTicket($item["shipment_ticket"], implode("','", array('samedayCollectionStarted')));

                if(count($emailSentStatus)==0){
                    array_push($next_shipment_tickets, $item["shipment_ticket"]);
                }
            }

            if(count($next_shipment_tickets)==0){
                $trigger_code = "samedayDeliveryStarted";
                $shipmentData = $this->_getModelInstance()->getSamedayDeliveryShipmentByShipmentRouteId($param["shipment_route_id"]);

                //check email already sent or not
                foreach($shipmentData as $item){
                    $emailSentStatus = $this->_getModelInstance()->checkShipmentNotificationAlreadySentByShipmentTicket($item["shipment_ticket"], implode("','", array('samedayDeliveryStarted')));

                    if(count($emailSentStatus)==0){
                        array_push($next_shipment_tickets, $item["shipment_ticket"]);
                    }
                }
            }

            if(count($next_shipment_tickets)>0){
                $recepientEmail = $this->_getModelInstance()->getRecepientEmailByShipmentTicket(implode("','", $next_shipment_tickets));
                array_push($recipient_name_and_email, array("name"=>$recepientEmail["customer_name"],"email"=>$recepientEmail["customer_email"],"shipment_ticket"=>$recepientEmail["shipment_ticket"]));
            }

            if(count($recipient_name_and_email)>0){
                $emailObj = new Notification_Email();
                $notificationData = $this->_getModelInstance()->getCompanyNotificationSetting($param["company_id"], $trigger_code);

                $subject_msg = $this->headerMsg;//"This is system generated message";

                foreach($recipient_name_and_email as $item){
                    $shipmentItem = $this->_getModelInstance()->getShipmentDetailByShipmentTicket($item["shipment_ticket"]);

                    //tracking url
                    $tracking_url = $libraryObj->get_tracking_url_by_env()."?loadIdentity=".$shipmentItem["instaDispatch_loadIdentity"];

                    foreach($notificationData as $notificationItem){
                        if($notificationItem["trigger_type"]=="email"){
                            $driverInfo = $this->_getModelInstance()->getUserInfo($shipmentItem["assigned_driver"]);

                            $template_msg = str_replace(array("__customer_name__","__driver_name__","__shipment_ticket__","__eta__","__contact_person_name__","__address_line_1__","__shipment_postcode__","__contact_persion_email_id__","__company_address__","__shipment_tracking_link__"), array($shipmentItem["shipment_customer_name"],$driverInfo["name"],$shipmentItem["instaDispatch_jobIdentity"],$shipmentItem["driver_pickuptime"],$shipmentItem["shipment_customer_name"],$shipmentItem["shipment_address1"],$shipmentItem["shipment_postcode"],$shipmentItem["shipment_customer_email"],$company_address,'<a href="'.$tracking_url.'"> click here </a>'), $notificationItem["template"]);


                            $status = $emailObj->sendMail(array("recipient_name_and_email"=>array(array("name"=>$shipmentItem["shipment_customer_name"],"email"=>$shipmentItem["shipment_customer_email"])),"template_msg"=>$template_msg, "subject_msg"=>$subject_msg));

                            $notificationHistory = array("trigger_code"=>$trigger_code,"route_id"=>$param["shipment_route_id"],"shipment_ticket"=>$shipmentItem["instaDispatch_jobIdentity"],"name"=>$shipmentItem["shipment_customer_name"],"email"=>$shipmentItem["shipment_customer_email"],"body"=>$template_msg,"subject"=>$subject_msg);

                            if($status["status"]){
                                $notificationHistory["status"] = 1;
                            }else{
                                $notificationHistory["status"] = 0;
                                $notificationHistory["body"] = $status["message"];
                            }
                            $notificationHistory["shipment_ticket"] = $shipmentItem["shipment_ticket"];
                            $this->_getModelInstance()->saveNotificationHistory($notificationHistory);
                        }
                    }
                }
            }
        }else{

        }
    }

    public

    function sendShipmentCollectionDeliverNotification($param){
        $libraryObj = new Library();

        //get company detail
        $company_info = $this->_getModelInstance()->getCompanyInfo($param["company_id"]);
        $company_address = implode(",<br>",array_filter($company_info));

        //get shipment detail
        $shipmentData = $this->_getModelInstance()->getShipmentDetailByShipmentTicket($param["shipment_ticket"]);

        //tracking url
        $tracking_url = $libraryObj->get_tracking_url_by_env()."?loadIdentity=".$shipmentData["instaDispatch_loadIdentity"];

        //get customer detail
        $customer_info = $this->_getModelInstance()->getUserInfo($shipmentData["customer_id"]);

        //get driver detail
        $driver_info = $this->_getModelInstance()->getUserInfo($shipmentData["assigned_driver"]);

        $type = ($shipmentData["instaDispatch_loadGroupTypeCode"]=="SAME") ? "sameday" : "nextday";

        $job_type = ($shipmentData["shipment_service_type"]=="P") ? "collection" : "delivery";

        $temp_trigger_code = false;

        $trigger_code = "";

        if($type=="sameday" and $job_type=="collection"){
            if($param["trigger_code"]=="failed") {
                $trigger_code = "samedayCollectionFailed";
                $temp_trigger_code = false;
            }
            else{
                $trigger_code = "samedayCollectionSuccessful";
                $temp_trigger_code = true;
            }

        }elseif($type=="sameday" and $job_type=="delivery"){
            if($param["trigger_code"]=="failed"){
                $trigger_code = "samedayDeliveryFailed";
                $temp_trigger_code = false;
            }
            else{
                $trigger_code = "samedayDeliverySuccessful";
                $temp_trigger_code = true;
            }
        }

        //get company notification setting
        $notificationData = $this->_getModelInstance()->getCompanyNotificationSetting($param["company_id"], $trigger_code);

        $subject_msg = $this->headerMsg;//"This is system generated message";
        $dateTime = date("d-M-Y h:i:s");

        $emailSentStatus = $this->_getModelInstance()->checkShipmentNotificationAlreadySentByShipmentTicket($shipmentData["shipment_ticket"], implode("','", array($trigger_code)));
        $emailObj = new Notification_Email();

        $reasonOffailure = (isset($param["service_message"])) ? $param["service_message"] : "";

        if(!$emailSentStatus){
            foreach($notificationData as $item){
                if($item["trigger_type"]=="email"){
                    
                    $template_msg = str_replace(array("__customer_name__","__shipment_ticket__","__driver_name__","__company_address__","__date_time__","__shipment_postcode__","__address_line_1__","__contact_person_name__","__contact_persion_email_id__","__shipment_tracking_link__","__reason_of_failure__"), array($shipmentData["shipment_customer_name"],$shipmentData["shipment_ticket"],$driver_info["name"],$company_address,$dateTime,$shipmentData["shipment_postcode"],$shipmentData["shipment_address1"],$shipmentData["shipment_customer_name"],$shipmentData["shipment_customer_email"],'<a href="'.$tracking_url.'">click here</a>',$reasonOffailure), $item["template"]);

                    $status = $emailObj->sendMail(array("recipient_name_and_email"=>array(array("name"=>$shipmentData["shipment_customer_name"],"email"=>$shipmentData["shipment_customer_email"])),"template_msg"=>$template_msg, "subject_msg"=>$subject_msg));

                    $notificationHistory = array("trigger_code"=>$trigger_code,"route_id"=>$shipmentData["shipment_routed_id"],"shipment_ticket"=>$param["shipment_ticket"],"name"=>$shipmentData["shipment_customer_name"],"email"=>$shipmentData["shipment_customer_email"],"body"=>$template_msg,"subject"=>$subject_msg);
                    if($status["status"]){
                        $notificationHistory["status"] = 1;
                    }else{
                        $notificationHistory["status"] = 0;
                        $notificationHistory["body"] = $status["message"];
                    }

                    $this->_getModelInstance()->saveNotificationHistory($notificationHistory);
                   
                }
            }
        }


        //send mail to next execution order only delivery shipment
        $nextRecipientNameAndEmail = array();
        $nextShipmentTickets = array();
        $deliveryShipments = $this->_getModelInstance()->getDeliveryShipmentDetailByLoadIdentity($shipmentData["instaDispatch_loadIdentity"]);

        $emailSentStatus = $this->_getModelInstance()->checkShipmentNotificationAlreadySentByShipmentTicket($deliveryShipments["shipment_ticket"], implode("','", array('samedayDeliverySuccessful')));
        if(!$emailSentStatus){
            array_push($nextShipmentTickets, $deliveryShipments["shipment_ticket"]);
        }
      
        if($nextShipmentTickets){
            $recepientEmail = $this->_getModelInstance()->getRecepientEmailByShipmentTicket(implode("','", $nextShipmentTickets));
            array_push($nextRecipientNameAndEmail, array("name"=>$recepientEmail["customer_name"],"email"=>$recepientEmail["customer_email"],"shipment_ticket"=>$recepientEmail["shipment_ticket"]));
        
            foreach($notificationData as $item){
                if($item["trigger_type"]=="email"){
                    if($temp_trigger_code=="successful") {
                        //send mail to next execution order only delivery shipment
                        $subject_msg = $this->headerMsg;//
                        if($nextRecipientNameAndEmail){
                            $trigger_code = 'samedayDeliveryStarted';
                            $template_msg = "Dear __customer_name__, driver __driver_name__ is on his way to deliver/collect your package. His estimated arrival time is __date_time__. Please click the link to see full tracking __shipment_tracking_link__.
                            <br><br><br>
                            Team PnP";

                            $template_msg = str_replace(array("__customer_name__","__shipment_ticket__","__driver_name__","__company_address__","__date_time__","__shipment_postcode__","__address_line_1__","__contact_person_name__","__contact_persion_email_id__","__shipment_tracking_link__"), array($deliveryShipments["shipment_customer_name"],$deliveryShipments["shipment_ticket"],$driver_info["name"],$company_address,$dateTime,$deliveryShipments["shipment_postcode"],$deliveryShipments["shipment_address1"],$deliveryShipments["shipment_customer_name"],$deliveryShipments["shipment_customer_email"],'<a href="'.$tracking_url.'">Click here</a>'), $template_msg);

                            $status = $emailObj->sendMail(array("recipient_name_and_email" => $nextRecipientNameAndEmail, "template_msg" => $template_msg, "subject_msg" => $this->headerMsg));

                            foreach($nextRecipientNameAndEmail as $item){
                                $notificationHistory = array("trigger_code" => $trigger_code, "route_id" => $shipmentData["shipment_routed_id"], "shipment_ticket" => $item["shipment_ticket"], "name" => $item["name"], "email" => $item["email"], "body" => $template_msg, "subject" => $subject_msg);

                                if ($status["status"]) {
                                    $notificationHistory["status"] = 1;
                                } else {
                                    $notificationHistory["status"] = 0;
                                    $notificationHistory["body"] = $status["message"];
                                }
                                $this->_getModelInstance()->saveNotificationHistory($notificationHistory);
                            }
                        }
                    }
                }
            }
        }
    }

    public

    function sendSamedayBookingConfirmationNotification($param){

        $trigger_code = "samedayBookingReceived";

        //get company notification setting
        $notificationData = $this->_getModelInstance()->getCompanyNotificationSetting($param["company_id"], $trigger_code);

        if(count($notificationData)>0){
            $libraryObj = new Library();

            $collectionShipment = $this->_getModelInstance()->getCollectionShipmentDetailByLoadIdentity($param["load_identity"]);
           
            $deliveryShipment = $this->_getModelInstance()->getShipmentDetailByLoadIdentity($param["load_identity"]);

            //tracking url
            $tracking_url = $libraryObj->get_tracking_url_by_env()."?loadIdentity=".$collectionShipment["instaDispatch_loadIdentity"];

            //get company detail
            $company_info = $this->_getModelInstance()->getCompanyInfo($param["company_id"]);

            //get customer detail
            $customer_info = $this->_getModelInstance()->getUserInfo($param["customer_id"]);
            $customer_info["email"] = $collectionShipment["shipment_customer_email"];



            $collection_detail = array();
            $delivery_detail = array();

            $route_id = (isset($collectionShipment["shipment_routed_id"])) ? $collectionShipment["shipment_routed_id"] : $deliveryShipment["shipment_routed_id"];

            array_push($collection_detail, implode(",",array_filter(array($collectionShipment["shipment_customer_name"]))));
            array_push($collection_detail, implode(",",array_filter(array($collectionShipment["shipment_address1"], $collectionShipment["shipment_postcode"]))));
            array_push($collection_detail, implode(",",array_filter(array($collectionShipment["shipment_customer_email"]))));
            $collection_detail = implode("<br>", $collection_detail);


            foreach($deliveryShipment as $key=> $shipment){

                array_push($delivery_detail,'<p><style="font-size: 12.8px;">Delivery Detail : '. ++$key .'</p>');
                array_push($delivery_detail, $shipment["shipment_customer_name"]);
                array_push($delivery_detail, $shipment["shipment_address1"], $shipment["shipment_postcode"]);
                array_push($delivery_detail, $shipment["shipment_customer_email"]);
            }
            $delivery_count = count($deliveryShipment);
            $delivery_detail = implode("<br>", array_filter($delivery_detail));

            $company_address = implode(",<br>",array_filter($company_info));
            $subject_msg = $this->headerMsg;//"This is system generated message";

            foreach($notificationData as $item){
                if($item["trigger_type"]=="email"){
                    $template_msg = str_replace(array("__customer_name__","__booking_reference_no__","__collection_detail__","__delivery_count__","__delivery_detail__","__company_address__","__shipment_tracking_link__"), array($customer_info["name"], $param["load_identity"], $collection_detail, $delivery_count, $delivery_detail, $company_address,'<a href="'.$tracking_url.'">Click here</a>'), $item["template"]);

                    $emailObj = new Notification_Email();

                    $status = $emailObj->sendMail(array("recipient_name_and_email"=>array(array("name"=>$customer_info["name"],"email"=>$customer_info["email"])),"template_msg"=>$template_msg, "subject_msg"=>$subject_msg));

                    $notificationHistory = array("trigger_code" => $trigger_code,"route_id"=>$route_id,"shipment_ticket"=>$param["load_identity"],"name"=>$customer_info["name"],"email"=>$customer_info["email"],"body"=>$template_msg,"subject"=>$subject_msg);
                    if($status["status"]){
                        $notificationHistory["status"] = 1;
                    }else{
                        $notificationHistory["status"] = 0;
                    }
                    $status = $this->_getModelInstance()->saveNotificationHistory($notificationHistory);
                }
            }
        }
    }
}