<?php
/**
 * Created by PhpStorm.
 * User: nishant
 * Date: 26/03/18
 * Time: 4:24 PM
 */

class Booking_Notification_Courier
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

        $trigger_code = "samedayBookingConfirmation";

        //get company notification setting
        $notificationData = $this->_getModelInstance()->getCompanyNotificationSetting($param["company_id"], $trigger_code);

        if(count($notificationData)>0){
            $libraryObj = new Library();

            $collectionShipment = $this->_getModelInstance()->getCollectionShipmentDetailByLoadIdentity($param["load_identity"]);

            $deliveryShipment = $this->_getModelInstance()->getAllDeliveryShipmentDetailByLoadIdentity($param["load_identity"]);
          
            //tracking url
            $tracking_url = $libraryObj->get_tracking_url_by_env()."?loadIdentity=".$collectionShipment["instaDispatch_loadIdentity"];

            //get company detail
            $company_info = $this->_getModelInstance()->getCompanyInfo($param["company_id"]);

            $services = $this->_getModelInstance()->getServiceName($collectionShipment["instaDispatch_loadIdentity"]);

            $service_name = $services["service_name"];

            //get customer detail
            $customer_info = $this->_getModelInstance()->getUserInfo($param["customer_id"]);

            $from_address = array();
            $to_address = array();

            $route_id = (isset($collectionShipment["shipment_routed_id"])) ? $collectionShipment["shipment_routed_id"] : $deliveryShipment["shipment_routed_id"];

            array_push($from_address, implode(",",array_filter(array($collectionShipment["shipment_address1"], $collectionShipment["shipment_address2"]))));
            array_push($from_address, implode(",",array_filter(array($collectionShipment["shipment_customer_city"], $collectionShipment["shipment_postcode"]))));
            array_push($from_address, implode(",",array_filter(array($collectionShipment["shipment_customer_country"]))));
            array_push($from_address, implode("",array_filter(array("Shipment Instruction : ", (!empty($collectionShipment["shipment_instruction"])) ? $collectionShipment["shipment_instruction"] : "Not Available"))));

            $from_address = implode("<br>", $from_address);

            foreach($deliveryShipment as $key=> $shipment){
                array_push($to_address,'<p>');
                array_push($to_address, implode(",",array_filter(array($shipment["shipment_address1"], $shipment["shipment_address2"]))));
                array_push($to_address, implode(",",array_filter(array($shipment["shipment_customer_city"], $shipment["shipment_postcode"]))));
                array_push($to_address, implode(",",array_filter(array($shipment["shipment_customer_country"]))));
                array_push($to_address, implode("",array_filter(array("Shipment Instruction : ", (!empty($shipment["shipment_instruction"])) ? $shipment["shipment_instruction"] : "Not Available"))));
                array_push($to_address,'</p>');
            }

            $delivery_count = count($deliveryShipment);
            $to_address = implode("<br>", array_filter($to_address));

            //$company_address = implode(",<br>",array_filter($company_info));
            $subject_msg = $this->headerMsg;

            foreach($notificationData as $item){
                if($item["trigger_type"]=="email"){
                    $template_msg = str_replace(array("__controller_name__","__customer_name__","__from_address__","__to_address__","__service__"), array($company_info["name"], $customer_info["name"], $from_address, $to_address, $service_name), $item["template"]);
                   
                    $emailObj = new Notification_Email();

                    $status = $emailObj->sendMail(array("recipient_name_and_email"=>array(array("name"=>$company_info["name"],"email"=>$company_info["email"])),"template_msg"=>$template_msg, "subject_msg"=>$subject_msg));

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