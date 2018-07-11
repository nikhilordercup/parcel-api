<?php
/**
 * Created by PhpStorm.
 * User: nishant
 * Date: 26/03/18
 * Time: 4:24 PM
 */

class Booking_Notification_Consignee
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
            $subject_msg = $this->headerMsg;

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