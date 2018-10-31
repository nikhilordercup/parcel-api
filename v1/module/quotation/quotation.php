<?php
require_once "../v1/module/notification/Sameday/Quotation_Notification.php";

class Quotation extends Icargo
{
    private $headerMsg = "Icargo Quotation - __quote_number__";
    public

    function __construct($param = array())
    {
        parent::__construct(array("email"=>$param->email, "access_token"=>$param->access_token));
        $this->db = new DbHandler();
        $this->postcodeObj = new Postcode();
        if (isset($param->company_id))
        {
            $this->company_id = $param->company_id;
        }

        if (isset($param->warehouse_id))
        {
            $this->warehouse_id = $param->warehouse_id;
        }

        if (isset($param->customer_id))
        {
            $this->customer_id = $param->customer_id;
        }

        if (isset($param->user_level))
        {
            $this->user_level = $param->user_level;
        }
    }

    private
    function _saveSamedayQuote($param)
    {
        $shipmentQuoteNumber = $this->_save_quote($param);
        if ($shipmentQuoteNumber)
        {
            return array(
                "status" => "success",
                "quote_number" => $shipmentQuoteNumber
            );
        }
        else
        {
            return array(
                "status" => "error",
                "message" => "error while saving quote, please try again"
            );
        }
    }

    private
    function _save_quote($param)
    {
        $shipmentId = $this->db->save("quote_shipment", $param["shipment_data"]);
        $quote_number = $this->db->getRowRecord("SELECT quote_number FROM " . DB_PREFIX . "quote_shipment WHERE id = '$shipmentId'");
        if ($shipmentId)
        {
            $param['parcel_data']['quote_number'] = $quote_number['quote_number'];
            $param['parcel_data']['quote_shipment_id'] = $shipmentId;
            $parcelData = $this->db->save("quote_parcel", $param['parcel_data']);
        }

        return $quote_number['quote_number'];
    }

    private
    function _saveQuoteService($param)
    {
        $service_id = $this->db->save("quote_service", $param);
        return $service_id;
    }

    private
    function _saveQuote($data)
    {
        $this->company_id = $data->company_id;
        $this->warehouse_id = $data->warehouse_id;

        $postcodeObj = new Postcode();

        // $this->service_date = $data->service_date;

        $this->service_date = date("Y-m-d", strtotime($data->service_date));

        // $service_date  = explode(" ",$this->service_date);

        $this->service_time = date("H:i:s", strtotime($data->service_date));
        $expiry_date = $this->_getCustomerQuoteExpiryDate($data->customer_id, $this->service_date);
        $counter = 1;
        $quoteNumber = $this->_generate_quote_no();
        $this->db->startTransaction();
        foreach($data->collection_shipment_address as $collection_data)
        {
			$postcode = $postcodeObj->validate($collection_data->postcode);
            $shipmentData = array(
                "shipment_postcode" => $postcode[0],//$postcodeObj->validate($collection_data->postcode),
                "shipment_address" => $collection_data->formatted_address,
                "quote_number" => $quoteNumber,
                "shipment_service_type" => "P",
                "customer_id" => $data->customer_id,
                "user_id" => $data->collection_user_id,
                "execution_order" => $counter,
                "expiry_date" => $expiry_date,
                "booking_ip" => $_SERVER['REMOTE_ADDR'],
                "email_id" => $data->quote_email,
                "company_id" => $this->company_id,
                "warehouse_id" => $this->warehouse_id
            );
            $parcelData = array(
                "parcel_weight" => 1,
                "parcel_height" => 1,
                "parcel_length" => 1,
                "parcel_width" => 1,
                "parcel_type" => "P",
                "company_id" => $this->company_id,
                "warehouse_id" => $this->warehouse_id
            );

            $shipmentStatus = $this->_saveSamedayQuote(array(
                "shipment_data" => $shipmentData,
                "parcel_data" => $parcelData
            ));
            ++$counter;
        }

        foreach($data->delivery_shipment_address as $delivery_data)
        {
		    $postcode = $postcodeObj->validate($delivery_data->postcode);
            $shipmentData = array(
                "shipment_postcode" => $postcode[0],//$postcodeObj->validate($delivery_data->postcode),
                "shipment_address" => $delivery_data->formatted_address,
                "quote_number" => $quoteNumber,
                "shipment_service_type" => "D",
                "customer_id" => $data->customer_id,
                "user_id" => $data->collection_user_id,
                "execution_order" => $counter,
                "expiry_date" => $expiry_date,
                "booking_ip" => $_SERVER['REMOTE_ADDR'],
                "email_id" => $data->quote_email,
                "company_id" => $this->company_id,
                "warehouse_id" => $this->warehouse_id
            );
            $parcelData = array(
                "parcel_weight" => 1,
                "parcel_height" => 1,
                "parcel_length" => 1,
                "parcel_width" => 1,
                "parcel_type" => "D",
                "company_id" => $this->company_id,
                "warehouse_id" => $this->warehouse_id
            );
            $shipmentStatus = $this->_saveSamedayQuote(array(
                "shipment_data" => $shipmentData,
                "parcel_data" => $parcelData
            ));

            ++$counter;
        }

        if ($shipmentStatus['status'] == 'success')
        {
            $shipmentService['quote_number'] = $shipmentStatus["quote_number"];
            $shipmentService['customer_id'] = $data->customer_id;
            $shipmentService['user_id'] = $data->collection_user_id;
            $shipmentService['collection_date'] = $this->service_date; //$service_date[0];
            $shipmentService['collection_time'] = $this->service_time; //$service_date[1];
            $shipmentService['expiry_date'] = $this->_getCustomerQuoteExpiryDate($data->customer_id, $this->service_date);
            $shipmentService['service_opted'] = json_encode($data->service_detail); //$data->service_detail;
            $shipmentService['service_request_string'] = json_encode($data->service_request_string); //$data->service_request_string;
            $shipmentService['service_response_string'] = json_encode($data->service_response_string); //$data->service_response_string;
            $shipmentService['transit_time'] = $data->transit_time;
            $shipmentService['transit_distance'] = $data->transit_distance;
            $shipmentService['transit_time_text'] = $data->transit_time_text;
            $shipmentService['transit_distance_text'] = $data->transit_distance_text;
            $shipmentService['shipment_address_json'] = $data->shipment_address_json;
            $shipmentService['company_id'] = $data->company_id;
            $shipmentService['warehouse_id'] = $data->warehouse_id;
            $shipmentService['booking_type'] = "sameday";

            $serviceId = $this->_saveQuoteService($shipmentService);
            if ($serviceId)
            {
                $this->db->commitTransaction();
                $response = array(
                    "status" => "success",
                    "quote_number" => $shipmentStatus["quote_number"],
                    "message" => "Quotation saved successfully. Quote Number -" . $shipmentStatus["quote_number"]
                );
            }
            else
            {
                $response = array(
                    "status" => "error",
                    "quote_number" => "",
                    "message" => "Quotation not saved, please try again."
                );
            }
        }

        return $response;
    }

    public

    function saveQuoteForCustomer($data)
    {
        $quoteSave = $this->_saveQuote($data);
        if ($quoteSave['status'] == "success") return array(
            "status" => "success",
            "message" => "quote saved successfully.Quote Number - " . $quoteSave['quote_number'] . " "
        );
        else return array(
            "status" => "error",
            "message" => "quote not saved,please try again"
        );
    }

    private
    function _getCustomerQuoteExpiryDate($customer_id, $service_date)
    {
        $record = $this->db->getRowRecord("SELECT quote_expiry_days FROM " . DB_PREFIX . "customer_info WHERE `user_id` = '$customer_id'");
        //$expiry = date('Y-m-d', strtotime("+5 days")); //$record['quote_expiry_days']
        $expiry_day = $record['quote_expiry_days'];
        return date("Y-m-d H:i:s", strtotime($service_date . "+$expiry_day day"));
    }

    private
    function _getShipmentDataByQuoteNumber($quote_number)
    {
        return $this->db->getAllRecords("SELECT * FROM " . DB_PREFIX . "quote_shipment WHERE quote_number = '" . $quote_number . "'");
    }

    private
    function _getServiceDataByQuoteNumber($quote_number)
    {
        return $this->db->getRowRecord("SELECT service_opted,collection_date,collection_time,service_response_string,transit_time FROM " . DB_PREFIX . "quote_service WHERE quote_number = '" . $quote_number . "'");
    }

    private
    function _getTemplateByTemplateCode($company_id, $template_code)
    {
        return $this->db->getRowRecord("SELECT * FROM `" . DB_PREFIX . "email_template` AS ET WHERE `company_id`='$company_id' AND template_code = '$template_code' AND status=1");
    }

    private
    function _getCourierDataByCompanyId($company_id)
    {
        return $this->db->getRowRecord("SELECT name,icon FROM `" . DB_PREFIX . "courier` AS CT WHERE `company_id`='$company_id'");
    }

    private
    function _getCustomerQuoteExpiryDays($customer_id)
    {
        $record = $this->db->getRowRecord("SELECT quote_expiry_days FROM " . DB_PREFIX . "customer_info WHERE `user_id` = '$customer_id'");
        return $record['quote_expiry_days'];
    }

    private
    function _test_shipment_quote($quote_number)
    {
        $record = $this->db->getOneRecord("SELECT COUNT(1) AS exist FROM " . DB_PREFIX . "quote_shipment WHERE quote_number = '" . $quote_number . "'");
        if ($record['exist'] > 0) return true;
        else return false;
    }

    private
    function _generate_quote_no()
    {
        $record = $this->db->getRowRecord("SELECT (shipment_end_number + 1) AS shipment_ticket_no, quote_prefix AS quote_prefix FROM " . DB_PREFIX . "configuration WHERE company_id = " . $this->company_id);
        if ($record)
        {
            $quote_number = $record['quote_prefix'] . str_pad($record['shipment_ticket_no'], 6, 0, STR_PAD_LEFT);
            $check_digit = Library::_getInstance()->generateCheckDigit($quote_number);
            $quote_number = "$quote_number$check_digit";
            $this->db->updateData("UPDATE " . DB_PREFIX . "configuration SET shipment_end_number = shipment_end_number + 1 WHERE company_id = " . $this->company_id);
            if ($this->_test_shipment_quote($quote_number))
            {
                $this->_generate_quote_no();
            }

            return $quote_number;
        }
        else
        {
            return false;
        }
    }

    public

    function sendQuoteEmail($data)
    {
        $quoteSave = $this->_saveQuote($data);

        if ($data->quote_email != '')
        {
            if ($quoteSave['status'] == "success")
            {
                $notificationObj = new Quotation_Notification();
                $status = $notificationObj->send(array("quote_email"=>$data->quote_email,"quote_number"=>$quoteSave['quote_number'],"company_id"=>$data->company_id,"customer_id"=>$data->customer_id,"service_date"=>$data->service_date));

                if($status["status"]=="success"){
                    $response = array(
                        "status" => "success",
                        "message" => $quoteSave['message']
                    );
                }else{
                    $response = array(
                        "status" => "error",
                        "message" => $status['message']
                    );
                }
            }
            else
            {
                $response = array(
                    "status" => "error",
                    "message" => $quoteSave['message']
                );
            }

            return $response;
        }
        else
        {
            if ($quoteSave['status'] == "success") $response = array(
                "status" => "success",
                "message" => $quoteSave['message']
            );
            else $response = array(
                "status" => "error",
                "message" => $quoteSave['message']
            );
            return $response;
        }
    }

    public

    function getAllSavedQuotes($param)
    {
        return $this->db->getAllRecords("SELECT t1.id,t1.quote_number,GROUP_CONCAT(t1.shipment_postcode) as postcode,t1.expiry_date,t1.email_id,t3.name,t2.service_opted FROM " . DB_PREFIX . "quote_shipment as t1 INNER JOIN " . DB_PREFIX . "quote_service AS t2 ON t1.quote_number = t2.quote_number INNER JOIN " . DB_PREFIX . "users AS t3 ON t1.customer_id = t3.id GROUP BY t1.quote_number");
    }

    public

    function getAllSavedQuotesByCompanyId($param)
    {
        $quoteArr = array();

        $sql = "SELECT t1.quote_number,t1.shipment_postcode as postcode,t1.expiry_date,t1.email_id,t3.name,t2.service_opted, t2.booking_type FROM " . DB_PREFIX . "quote_shipment as t1 INNER JOIN " . DB_PREFIX . "quote_service AS t2 ON t1.quote_number = t2.quote_number INNER JOIN " . DB_PREFIX . "users AS t3 ON t1.customer_id = t3.id WHERE t2.expiry_date>=CURDATE() AND t2.booking_type='sameday' AND t1.company_id = " . $param["company_id"];
        //echo $sql;die;
        $quoteData = $this->db->getAllRecords($sql);
        foreach($quoteData as $value)
        {
            $serviceArr = json_decode($value['service_opted']);
            $quoteArr[$value['quote_number']]["quote_number"] = $value['quote_number'];
            $quoteArr[$value['quote_number']]["name"] = $value['name'];
            $quoteArr[$value['quote_number']]["email"] = $value['email_id'];
            $quoteArr[$value['quote_number']]["expiry_date"] = Library::_getInstance()->date_format($value['expiry_date']);
            $quoteArr[$value['quote_number']]["postcode"][] = $value['postcode'];
            $quoteArr[$value['quote_number']]["booking_type"] = $value['booking_type'];
            //$quoteArr[$value['quote_number']]["service"] = $serviceArr;
        }

        $sql = "SELECT QST.quote_number, QST.expiry_date, QST.email AS email_id, QST.service_opted, UT.name, QST.booking_type FROM " . DB_PREFIX . "quote_service AS QST INNER JOIN " . DB_PREFIX . "users AS UT ON UT.id=QST.customer_id WHERE QST.expiry_date>=CURDATE() AND QST.company_id='".$param['company_id']."' AND booking_type='nextday'";
        $quoteData = $this->db->getAllRecords($sql);
        $key = 0;
        foreach($quoteData as $value)
        {
            $json_data = json_decode($value["service_opted"]);

            if(isset($json_data->collection->$key->postcode)){
                $quoteArr[$value['quote_number']]["quote_number"] = $value['quote_number'];
                $quoteArr[$value['quote_number']]["name"] = $value['name'];
                $quoteArr[$value['quote_number']]["email"] = $value['email_id'];
                $quoteArr[$value['quote_number']]["expiry_date"] = Library::_getInstance()->date_format($value['expiry_date']);
                $quoteArr[$value['quote_number']]["postcode"] = array($json_data->collection->$key->postcode, $json_data->delivery->$key->postcode);
                $quoteArr[$value['quote_number']]["booking_type"] = $value['booking_type'];
            }
        }
        return $quoteArr;
    }

    public

    function getQuoteDataByQuoteNumber($param)
    {
        $quoteArr = array();
        $customerData = $this->db->getRowRecord("SELECT c.available_credit,t1.name,t1.id,email FROM " . DB_PREFIX . "users as t1
            INNER JOIN " . DB_PREFIX . "quote_shipment as t2 ON t1.id = t2.customer_id
            INNER JOIN " . DB_PREFIX . "customer_info as c on t2.customer_id = c.user_id
            WHERE t2.quote_number = '" . $param->quote_number . "'");
        $quoteArr['customer'] = array(
            "id" => $customerData['id'],
            "name" => $customerData['name'],
            "email" => $customerData['email'],
            "availiable_balence"=>$customerData['available_credit']
        );
        $userData = $this->db->getRowRecord("SELECT t1.name,t1.id FROM " . DB_PREFIX . "users as t1 INNER JOIN " . DB_PREFIX . "quote_shipment as t2 ON t1.id = t2.user_id WHERE t2.quote_number = '" . $param->quote_number . "'");
        $quoteArr['user'] = array(
            "id" => $userData['id'],
            "name" => $userData['name']
        );
        $shipmentData = $this->db->getAllRecords("SELECT shipment_postcode,shipment_service_type,shipment_address FROM " . DB_PREFIX . "quote_shipment WHERE quote_number = '" . $param->quote_number . "'");

        foreach($shipmentData as $data)
        {
            if ($data['shipment_service_type'] == 'P')
            {
                $quoteArr['collection'] = $data['shipment_address'];
            }
            else
            {
                $quoteArr['delivery'][] = $data['shipment_address'];
            }
        }

        $serviceData = $this->db->getRowRecord("SELECT service_opted,expiry_date,collection_date,collection_time,transit_time_text,transit_distance_text,transit_time,transit_distance,shipment_address_json FROM " . DB_PREFIX . "quote_service WHERE quote_number = '" . $param->quote_number . "'");

        $quoteArr['serviceDate'] = $serviceData['collection_date'];
        $quoteArr['serviceTime'] = date('h:i:s', strtotime($serviceData['collection_time']));
        $quoteArr['collectionDateTime'] = $quoteArr['serviceDate'] . ' ' . $quoteArr['serviceTime'];
        $quoteArr['serviceList'] = $serviceData['service_opted'];
        $quoteArr['transit_time_text'] = $serviceData['transit_time_text'];
        $quoteArr['transit_distance_text'] = $serviceData['transit_distance_text'];
        $quoteArr['transit_time'] = $serviceData['transit_time'];
        $quoteArr['transit_distance'] = $serviceData['transit_distance'];
        $quoteArr['shipment_address_json'] = $serviceData['shipment_address_json'];

        return array(
            "status" => "success",
            "quoteData" => $quoteArr
        );
    }

    public

    function saveAndSendNextdayQuotation($param){
        $this->db->startTransaction();
        $param->service_selected = array_values(array_filter($param->service_selected));
        $this->company_id = $param->company_id;
        $expiry_date = $this->_getCustomerQuoteExpiryDate($param->customer_id,$param->collection_date);
        $quote_number = $this->_generate_quote_no();

        $data = array(
            "service_request_string"=>json_encode($param->service_request_string),
            "service_response_string"=>json_encode($param->service_response_string),
            "email"=>$param->quotation_email,
            "collection_date"=>date("Y-m-d", strtotime($param->collection_date)),
            "collection_time"=>date("H:i:s", strtotime($param->collection_date)),
            "customer_id"=>$param->customer_id,
            "user_id"=>$param->customer_user_id,
            "expiry_date"=>$expiry_date,
            "quote_number"=>$quote_number,
            "shipment_address_json"=>json_encode(array()),
            "transit_time"=>"0.00",
            "transit_distance"=>"0.00",
            "transit_distance_text"=>"",
            "transit_time_text"=>"",
            "booking_type"=>"nextday",
            "warehouse_id"=>$param->warehouse_id,
            "company_id"=>$param->company_id
        );

        $service_opted = $param;
        unset($param->service_request_string);
        unset($param->service_response_string);
        unset($param->quotation_email);
        $data["service_opted"] = json_encode($service_opted);

        $shipmentId = $this->db->save("quote_service", $data);
        if($shipmentId>0){
            $this->db->commitTransaction();
            //send an email
            Consignee_Notification::_getInstance()->sendNextdayQuotationEmailToConsignee(array("quote_number"=>$quote_number, "company_id"=>$param->company_id, "warehouse_id"=>$param->warehouse_id));

            return array("status"=>"success", "message"=>"You quotation has been booked successfully - $quote_number");
        }
        $this->db->rollBackTransaction();
        return array("status"=>"error", "message"=>"Quote not saved");
    }

    public

    function loadQuotationByQuotationId($param){
        $sql = "SELECT QST.service_opted, QST.service_request_string, QST.service_response_string FROM " . DB_PREFIX . "quote_service AS QST INNER JOIN " . DB_PREFIX . "users AS UT ON UT.id=QST.customer_id WHERE QST.expiry_date>=CURDATE() AND QST.company_id='".$param->company_id."' AND quote_number='".$param->quotation_id."'";

        $quoteData = $this->db->getRowRecord($sql);
        if(count($quoteData)>0){
            $quoteData["service_opted"] = json_decode($quoteData["service_opted"]);
            $quoteData["service_request_string"] = json_decode($quoteData["service_request_string"]);
            $quoteData["service_response_string"] = json_decode($quoteData["service_response_string"]);
            $quoteData["availablebalance"]      = $this->_getCustomerAvailableBalance($quoteData["service_opted"]->customer_id);
            $quoteData["status"] = "success";
        }else{
            $quoteData["status"] = "error";
            $quoteData["message"] = "Quotation not found or expired";
        }
        return $quoteData;
    }
    private
    function _getCustomerAvailableBalance($customer_id)
    {
        $record = $this->db->getRowRecord("SELECT available_credit FROM " . DB_PREFIX . "customer_info WHERE `user_id` = '$customer_id'");
        $balance = $record['available_credit'];
        return $balance;
    }
}
?>
