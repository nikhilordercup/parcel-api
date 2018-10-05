<?php
/**
 * Created by PhpStorm.
 * User: nishant
 * Date: 26/03/18
 * Time: 4:24 PM
 */

class Quotation_Notification_Consignee
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

        $trigger_code = "nextdayQuotation";

        //get company notification setting
        $notificationData = $this->_getModelInstance()->getCompanyNotificationSetting($param["company_id"], $trigger_code);

        if(count($notificationData)>0){
            $key = 0;

            $libraryObj = new Library();

            $quoteData = $this->_getModelInstance()->getQuotationByQuotationNumber($param["quote_number"]);

            $jsonData = json_decode($quoteData["service_opted"]);

            $companyInfo = $this->_getModelInstance()->getUserInfo($param["company_id"]);

            $service_selected = array();

            foreach($jsonData->service_selected as $service){//print_r($service->service_info->service_options->max_delivery_time);die;

                $maxDeliveryTime = (isset($service->service_info->service_options->max_delivery_time) and $service->service_info->service_options->max_delivery_time!="") ? $service->service_info->service_options->max_delivery_time : 0;

                $expectedDeliveryDate = date("Y-m-d", strtotime($jsonData->collection_date. ' + '.$maxDeliveryTime.' days'));

                array_push($service_selected, "<tr>");
                array_push($service_selected, "<td>"); array_push($service_selected, $service->service_name); array_push($service_selected, "</td>");

                array_push($service_selected, "<td>"); array_push($service_selected, $service->carrier_code); array_push($service_selected, "</td>");

                array_push($service_selected, "<td>"); array_push($service_selected, $expectedDeliveryDate); array_push($service_selected, "</td>");

                array_push($service_selected, "<td>"); array_push($service_selected,$service->customer_rate); array_push($service_selected, "</td>");
               
                array_push($service_selected, "<td>"); array_push($service_selected,date("H:m:s", strtotime($service->collection_date_time))); array_push($service_selected, "</td>");
                array_push($service_selected, "</tr>");

            }

            $subject_msg = $this->headerMsg;

            foreach($notificationData as $item){
                if($item["trigger_type"]=="email"){ 
                     $template_msg = str_replace(array(
                        "__img_src__",
                        "__quote_number__",
                        "__shipping_date__",
                        "__collection_postcode__",
                        "__delivery_postcode__",
                        "__service_detail__",
                        "__quote_expiry__",
                        "__courier_name__"
                    ), 
                    array(
                        "http://app-tree.co.uk/icargoN/assets/img/pnp_logo.png",
                        $param["quote_number"],
                        $jsonData->collection_date,
                        $jsonData->collection->$key->postcode,
                        $jsonData->delivery->$key->postcode,
                        implode("", $service_selected),
                        $quoteData["expiry_date"],
                        $companyInfo["name"]
                    ), $item["template"]);

                    $emailObj = new Notification_Email();

                    $status = $emailObj->sendMail(array("recipient_name_and_email"=>array(array("name"=>"Customer","email"=>$quoteData["email"])),"template_msg"=>$template_msg, "subject_msg"=>$subject_msg));

                    $notificationHistory = array("trigger_code" => $trigger_code,"route_id"=>0,"shipment_ticket"=>$param["quote_number"],"name"=>"Customer","email"=>$quoteData["email"],"body"=>$template_msg,"subject"=>$subject_msg);
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