<?php
/**
 * Created by PhpStorm.
 * User: nishant
 * Date: 26/03/18
 * Time: 4:24 PM
 */

class Collection_Delivery_Notification_Consignee
{
    public static $modelObj = NULL;

    private $headerMsg = "Icargo Shipment Tracking Notification";

    public

    static function _getModelInstance(){
        if(self::$modelObj==NULL){
            self::$modelObj = new Notification_Model_Index();
        }
        return self::$modelObj;
    }

    public

    function send($param){
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

        $subject_msg = $this->headerMsg;
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
                    if($temp_trigger_code) {

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
}