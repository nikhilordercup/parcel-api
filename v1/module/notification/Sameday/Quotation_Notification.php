<?php
class Quotation_Notification{
    public static $modelObj = NULL;
    private $headerMsg = "Icargo Quotation - __QUOTATION_NUMBER__";

    public

    static function _getModelInstance(){
        if(self::$modelObj==NULL){
            self::$modelObj = new Notification_Model_Index();
        }
        return self::$modelObj;
    }

    public

    function send($param){
        $trigger_code = "samedayQuotation";

        //get company notification setting
        $notificationData = $this->_getModelInstance()->getCompanyNotificationSetting($param["company_id"], $trigger_code);

        $shipmentData = Quotation_Notification::_getModelInstance()->getShipmentDataByQuoteNumber($param["quote_number"]);
        $serviceData  = Quotation_Notification::_getModelInstance()->getServiceDataByQuoteNumber($param["quote_number"]);
        $courierData  = Quotation_Notification::_getModelInstance()->getCourierDataByCompanyId($param["company_id"]);
        $quoteExpiry  = Quotation_Notification::_getModelInstance()->getCustomerQuoteExpiryDays($param["customer_id"]);

        $courierImage = (isset($courierData["icon"]) and !empty($courierData["icon"])) ? Library::_getInstance()->base_url() . "/assets/logo/".$courierData["icon"] : Library::_getInstance()->base_url() . "/assets/logo/iCargo-Logo.png";

        foreach($shipmentData as $value)
        {
            if ($value['shipment_service_type'] == 'P')
            {
                $templateMsg['collection_postcode'][] = $value['shipment_postcode'];
            }
            else
            {
                $templateMsg['delivery_postcode'][] = $value['shipment_postcode'];
            }
        }

        $serviceDetail = json_decode($serviceData['service_opted']);

        $collectionTimeStamp = strtotime($param["service_date"]);
        $transitTimeStamp = $serviceData['transit_time'];
        $deliveryTimeStamp = $collectionTimeStamp + $transitTimeStamp;
        $deliveryDateTime = date("D d M H:i:s Y", $deliveryTimeStamp);

        $counter = 1;
        $html = '';
        foreach($serviceDetail as $service)
        {
            if ($counter % 2)
            {
                $color = '#bfbfbf';
            }
            else
            {
                $color = '#FFF';
            }

            $html.= '<tr height="20" bgcolor="' . $color . '"><td style="font-family:arial,sans-serif;margin:0px">';
            $html.= $service->service_name;
            $html.= '</td><td style="font-family:arial,sans-serif;margin:0px">&nbsp;</td><td style="font-family:arial,sans-serif;margin:0px">';
            $html.= $courierData['name'];
            $html.= '</td><td style="font-family:arial,sans-serif;margin:0px">&nbsp;';
            $html.= $deliveryDateTime;
            $html.= '</td><td style="font-family:arial,sans-serif;margin:0px">';
            $html.= $service->total_price;
            $html.= '</td><td style="font-family:arial,sans-serif;margin:0px"><br /></td><td style="font-family:arial,sans-serif;margin:0px"><span class="aBn" data-term="goog_2083724664" tabindex="0"><span class="aQJ">';
            $html.= $serviceData['collection_time'];
            $html.= '</span></span></td></tr>';
            $counter++;
        }

        $service_detail_str = $html;
        $subject_msg = str_replace(array(
            "__QUOTATION_NUMBER__"
        ) , array(
            $param["quote_number"]
        ) , $this->headerMsg);

        $collection_postcode = implode("", $templateMsg['collection_postcode']);
        $delivery_postcode = implode(",", $templateMsg['delivery_postcode']);

        foreach($notificationData as $item){
            if($item["trigger_type"]=="email"){
                $template_msg = str_replace(array(
                  "__quote_number__",
                  "__shipping_date__",
                  "__collection_postcode__",
                  "__delivery_postcode__",
                  "__service_detail__",
                  "__quote_expiry__",
                  "__courier_name__",
                  "__img_src__"
                ), array(
                  $param['quote_number'],
                  $serviceData['collection_date'],
                  $collection_postcode,
                  $delivery_postcode,
                  $service_detail_str,
                  $quoteExpiry,
                  $courierData['name'],
                  $courierImage
                ), $item["template"]);

                $emailObj = new Notification_Email();

                $status = $emailObj->sendMail(array("recipient_name_and_email"=>array(array("name"=>$courierData["name"],"email"=>$param["quote_email"])),"template_msg"=>$template_msg, "subject_msg"=>$subject_msg));

                $notificationHistory = array("trigger_code" => $trigger_code,"route_id"=>"0","shipment_ticket"=>$param["quote_number"],"name"=>$courierData["name"],"email"=>$param["quote_email"],"body"=>$template_msg,"subject"=>$subject_msg);
                if($status["status"]){
                    $notificationHistory["status"] = 1;
                }else{
                    $notificationHistory["status"] = 0;
                }
                $status = $this->_getModelInstance()->saveNotificationHistory($notificationHistory);
            }
        }

        if($status){
            return array(
                "status"=>"success",
                "message"=>"Email sent successfully"
            );
        } else{
            return array(
                "status"=>"error",
                "message"=>"Email not sent"
            );
        }
    }
}
