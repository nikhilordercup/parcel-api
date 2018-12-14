<?php
/**
 * Created by PhpStorm.
 * User: nishant
 * Date: 26/03/18
 * Time: 4:24 PM
 */

class Route_Start_Notification_Consignee
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

                $subject_msg = $this->headerMsg;

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
}