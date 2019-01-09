<?php 
final class Nextday extends Booking
{
    public $modelObj        = null;
    public $customerccf     = null;
    public $googleApi       = null;
    public $googleRequest   = array();
    public $bookparam       = array();
    public $endpoint        = null;
    public $request         = null;
    public $allShipmentsObj = null;
    public $webApiToken     = null;
    public $collectionModel = null;
    public $nextDay         = null;
    public function __construct($data){
        $this->_parentObj = parent::__construct(array("email" => $data->email, "access_token" => $data->access_token));
        $this->_param = $data;
        $this->customerccf              = new CustomerCostFactor();
        $this->googleApi                = new Module_Google_Api((object) array("email" => $data->email, "access_token" => $data->access_token));
        $this->modelObjCorePrime        = new Coreprime_Model_Api();
        $this->resrServiceModel         = new restservices_Model();
        $this->allShipmentsObj          = new allShipments((object) array("email" => $data->email, "access_token" => $data->access_token));
        $this->endpoint                 = $data->endpoint;
        $this->webApiToken              = $data->web_token;
        $this->collectionModel = Collection::_getInstance(); //new Collection();
    }
    //================
    public      function isValidServiceDate($date, $format = 'Y-m-d H:i'){
                    $d = DateTime::createFromFormat($format, $date);
                    return $d && $d->format($format) == $date;
        }
    private function getGeoLocationByPostCode($postcode,$counter=0){
       $geoData =  $this->googleApi->getGeoPositionFromPostcode((object) array('postcode'=>$postcode));
       // print_r($geoData);die;
       if($geoData['status']!='success'){
          $counter++;
          if($counter>3){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Invalid postcode '.$postcode;
                 $response["error_code"] = "ERROR006";
                 return $response;
          }
          return $this->getGeoLocationByPostCode($postcode,$counter);
       }
       return $geoData;
    }
    //================
    private function _getCarrierInfo($data,$param){
        foreach($data as $carrier_code => $lists){
            foreach($lists as $key1 => $list){
                foreach($list as $accountNumber => $items){
                    foreach($items as $key3 => $item){
                        foreach($item as $service_code => $services) {
                            //calculate service ccf
                            if (!isset($services[0]->rate->error)) {
                                $accountId = isset($this->carrierList[$accountNumber]["account_id"]) ? $this->carrierList[$accountNumber]["account_id"] :
                                $this->carrierList[$accountNumber]["carrier_id"];
                                $serviceCcf = $this->customerccf->calculateServiceCcf($service_code, $services[0]->rate->price, $accountId, $param->customer_id, $param->company_id);//$services[0]->rate
                                $services[0]->rate->price = $serviceCcf["price_with_ccf"];
                                $services[0]->rate->info = $serviceCcf;

                                foreach ($services as $key5 => $service) {
                                    if (isset($service->rate->error)) {
                                        return (object)array("status" => "error", "message" => $service->rate->error);
                                    }

                                    //set tax number format
                                    if (isset($service->taxes)) {
                                        if (isset($service->taxes->total_tax)) {
                                            $service->taxes->total_tax = number_format($service->taxes->total_tax, 2);
                                        }
                                        if (isset($service->taxes->tax_percentage)) {
                                            $service->taxes->tax_percentage = number_format($service->taxes->tax_percentage, 2);
                                        }
                                    }

                                    //calculate surcharge ccf
                                    $surchargeWithCcfPrice = 0;
                                    $surchargePrice = 0;
                                    $service->collected_by = $this->carrierList[$accountNumber]["collected_by"];

                                    foreach ($service->collected_by as $collected_key => $collected_item) {
                                        $surchargeWithCcfPrice = 0;
                                        $surchargePrice = 0;
                                        foreach ($service->surcharges as $surcharge_code => $surcharge_price) {
                                            if ($collected_item["carrier_code"] != $carrier_code and $surcharge_code == "collection_surcharge") {
                                                $surchargeCcf["original_price"] = $collected_item["pickup_surcharge"];
                                                $surchargeCcf["surcharge_value"] = $collected_item["pickup_surcharge"];
                                                $surchargeCcf["operator"] = "FLAT";
                                                $surchargeCcf["price"] = $collected_item["pickup_surcharge"];
                                                $surchargeCcf["level"] = "level 1";
                                                $surchargeCcf["surcharge_id"] = "0";
                                                $surchargeCcf["price_with_ccf"] = $collected_item["pickup_surcharge"];

                                                $surchargeCcf["company_surcharge_code"] = "collection_surcharge";
                                                $surchargeCcf["company_surcharge_name"] = "Collection Surcharge";
                                                $surchargeCcf["courier_surcharge_code"] = "collection_surcharge";
                                                $surchargeCcf["courier_surcharge_name"] = "Collection Surcharge";
                                                $surchargeCcf["carrier_id"] = $collected_item["carrier_id"];
                                            } else {
                                                $surchargeCcf = $this->customerccf->calculateSurchargeCcf($surcharge_code, $param->customer_id, $param->company_id, $this->carrierList[$accountNumber]["account_id"], $surcharge_price);
                                            }

                                            $collected_item["surcharges"][$surcharge_code] = $surchargeCcf;

                                            $surchargeWithCcfPrice += $surchargeCcf["price_with_ccf"];

                                            if ($surchargeCcf["operator"] != "FLAT") {
                                                $surchargePrice += $surchargeCcf["original_price"];
                                            }

                                        }

                                        $collected_item["carrier_price_info"]["price"] = $serviceCcf["original_price"];
                                        $collected_item["customer_price_info"]["price"] = $serviceCcf["price_with_ccf"];

                                        $collected_item["carrier_price_info"]["surcharges"] = number_format($surchargePrice, 2);
                                        $collected_item["customer_price_info"]["surcharges"] = number_format($surchargeWithCcfPrice, 2);

                                        //$collected_item["carrier_price_info"]["taxes"] = number_format($service->taxes->total_tax, 2);
                                        $collected_item["carrier_price_info"]["taxes"] = number_format((($serviceCcf["original_price"] + $surchargePrice) * $service->taxes->tax_percentage / 100), 2);

                                        $collected_item["customer_price_info"]["taxes"] = number_format((($serviceCcf["price_with_ccf"] + $surchargeWithCcfPrice) * $service->taxes->tax_percentage / 100), 2);

                                        $collected_item["carrier_price_info"]["grand_total"] = number_format($serviceCcf["original_price"] + $surchargePrice + $service->taxes->total_tax, 2);
                                        $collected_item["customer_price_info"]["grand_total"] = number_format($serviceCcf["price_with_ccf"] + $surchargeWithCcfPrice + $collected_item["customer_price_info"]["taxes"], 2);


                                        $service->collected_by[$collected_key] = $collected_item;
                                    }

                                    $service->carrier_info = array(
                                        "carrier_id" => $this->carrierList[$accountNumber]["carrier_id"],
                                        "name" => $this->carrierList[$accountNumber]["name"],
                                        "icon" => $this->carrierList[$accountNumber]["icon"],
                                        "code" => $this->carrierList[$accountNumber]["carrier_code"],
                                        "description" => $this->carrierList[$accountNumber]["description"],
                                        "account_number" => $this->carrierList[$accountNumber]["account_number"],
                                        "is_internal" => $this->carrierList[$accountNumber]["internal"]
                                    );
                                    $service->service_info = array(
                                        "code" => $this->carrierList[$accountNumber]["services"][$service_code]["service_code"],
                                        "name" => $this->carrierList[$accountNumber]["services"][$service_code]["service_name"]
                                    );
                                }
                            }else{
                                unset($item->$service_code);
                            }
                        }
                    }
                }
            }
        }
        return $data;
    }
    
    
    public function saveBooking(){ 
        
        $accountStatus = $this->_checkCustomerAccountStatus($this->_param->customer_id);
        if($accountStatus["status"]=="error"){
            return $accountStatus;
        }
        $bookingShipPrice = $this->_param->service_opted->collection_carrier->customer_price_info->grand_total;
        $available_credit = $this->_getCustomerAccountBalence($this->_param->customer_id,$bookingShipPrice);
        if($available_credit["status"]=="error"){
            return $available_credit;
        }
        
        $company_code = $this->_getCompanyCode($this->_param->company_id);

        $customerWarehouseId = $this->getCustomerWarehouseIdByCustomerId($this->_param->company_id, $this->_param->customer_id);

        $this->_param->service_opted->rate->shipment_type = "Next";

        $this->serviceRequestString  = $this->_param->service_request_string;
        $this->serviceResponseString = $this->_param->service_response_string;

        $this->startTransaction();
        //save collection address and collection job
        $execution_order = 0;
        $collection_date_time = $this->_param->service_opted->collection_carrier->collection_date_time;//$this->_param->service_opted->collected_by[0]->collection_date_time;
        $collection_end_at = $this->_param->service_opted->collection_carrier->collection_end_at;//$this->_param->service_opted->collected_by[0]->collection_end_at;
        $carrier_account_number = $this->_param->service_opted->collection_carrier->account_number;//$this->_param->service_opted->collected_by[0]->account_number;
        $is_internal = $this->_param->service_opted->collection_carrier->is_internal;//$this->_param->service_opted->collected_by[0]->is_internal;


        foreach($this->_param->collection as $key=> $item){
            $execution_order++;
            $addressInfo = $this->_saveAddressData($item, $this->_param->customer_id);

            if($addressInfo["status"]=="error"){
                $this->rollBackTransaction();
                return $addressInfo;
            }
            $shipmentStatus = $this->_saveShipment($this->_param, $this->_param->collection->$key, $this->_param->parcel, $addressInfo["address_data"], $customerWarehouseId, $this->_param->company_id, $company_code, $collection_date_time, $collection_end_at, "next","COLL","NEXT","P",$execution_order, $carrier_account_number,$is_internal);
            if($shipmentStatus["status"]=="error"){
                $this->rollBackTransaction();
                return $shipmentStatus;
            }
            if($key==0)
                $loadIdentity = $shipmentStatus["shipment_ticket"];

            foreach($this->_param->parcel as $item){
                for($i=0; $i< $item->quantity; $i++){
                    $parcelStatus = $this->_saveParcel($shipmentStatus["shipment_id"],$shipmentStatus["shipment_ticket"],$customerWarehouseId,$this->_param->company_id,$company_code,$item,"P",$loadIdentity);
                    if($parcelStatus["status"]=="error"){
                        $this->rollBackTransaction();
                        return $parcelStatus;
                    }
                }
            }

            //get shipment volume and heighest dimension
            $shipmentDimension = $this->_getParcelDimesionByShipmentId($shipmentStatus["shipment_id"]);

            $this->_saveShipmentDimension($shipmentDimension, $shipmentStatus["shipment_id"]);

            $serviceStatus = $this->_saveShipmentService($this->_param->service_opted, $this->_param->service_opted->collection_carrier->surcharges, $loadIdentity, $this->_param->customer_id,"pending");

            if($serviceStatus["status"]=="error"){
                $this->rollBackTransaction();
                return $serviceStatus;
            }
            
            $paymentStatus = $this->_manageAccounts($serviceStatus["service_id"], $loadIdentity, $this->_param->customer_id,$this->_param->company_id);

            if($paymentStatus["status"]=="error"){
                $this->rollBackTransaction();
                return $paymentStatus;
            }
            
            
            $collectedBy = $this->_param->service_opted->collection_carrier;
            $collectedBy->service_id = $serviceStatus["service_id"];

            $collectedByStatus = $this->_saveShipmentCollection($collectedBy);

            if($collectedByStatus["status"]=="error"){
                $this->rollBackTransaction();
                return $collectedByStatus;
            }

            $attributeStatus = $this->_saveShipmentAttribute($this->_param->service_opted->service_options, $loadIdentity);
            if($attributeStatus["status"]=="error"){
                $this->rollBackTransaction();
                return $attributeStatus;
            }
        }

        $collection_date_time = "1970-01-01 00:00:00";
        $collection_end_at = "00:00:00";

        $carrier_account_number = $this->_param->service_opted->carrier_info->account_number;
        $is_internal = $this->_param->service_opted->carrier_info->is_internal;

        //save delivery address and delivery job
        foreach($this->_param->delivery as $key=> $item){
            $execution_order++;
            $addressInfo = $this->_saveAddressData($item, $this->_param->customer_id);

            if($addressInfo["status"]=="error"){
                $this->rollBackTransaction();
                return $addressInfo;
            }

            $this->_param->delivery->$key->load_identity = $loadIdentity;
            $shipmentStatus = $this->_saveShipment($this->_param, $this->_param->delivery->$key, $this->_param->parcel, $addressInfo["address_data"], $customerWarehouseId, $this->_param->company_id, $company_code, $collection_date_time, $collection_end_at, "next","DELV","NEXT","D",$execution_order,$carrier_account_number,$is_internal);
            if($shipmentStatus["status"]=="error"){
                $this->rollBackTransaction();
                return $shipmentStatus;
            }

            foreach($this->_param->parcel as $item){
                for($i=0; $i< $item->quantity; $i++){
                    $parcelStatus = $this->_saveParcel($shipmentStatus["shipment_id"],$shipmentStatus["shipment_ticket"],$customerWarehouseId,$this->_param->company_id,$company_code,$item,"D",$loadIdentity);
                    if($parcelStatus["status"]=="error"){
                        $this->rollBackTransaction();
                        return $parcelStatus;
                    }
                }
            }

            //get shipment volume and heighest dimension
            $shipmentDimension = $this->_getParcelDimesionByShipmentId($shipmentStatus["shipment_id"]);

            $this->_saveShipmentDimension($shipmentDimension, $shipmentStatus["shipment_id"]);
        }
        $this->commitTransaction();
        // call label generation method
        return array("status"=>"success","message"=>"Shipment booked successful. Shipment ticket $loadIdentity");
    }
     
//-----------------------------------------------------------------------------------------------------------------
    private function _setPostRequest__($param){ 
        $this->data = array();
        $carrierLists = $this->_getCustomerCarrierAccount($param);
        if($carrierLists["status"]=="success"){
            $key = 0;
            $this->data = array(
                "carriers" => $carrierLists["data"],
                "from" => $this->_getAddress($param->collection),
                "to" => $this->_getAddress($param->delivery[0]),
                "ship_date" => date("Y-m-d", strtotime($param->service_date)),
                "extra" => array(),
                "currency" => $param->collection->address->currency_code);
            $this->data["package"] = array();
            foreach($param->parcel as $item){
                for($i=0; $i<$item->quantity; $i++){
                    array_push($this->data["package"], array(
                        "packaging_type" => $item->package_code,
                        "width" => $item->width,
                        "length" => $item->length,
                        "height" => $item->height,
                        "dimension_unit" => "CM",
                        "weight" => $item->weight,
                        "weight_unit" => "KG"
                    ));
                }
            }
            $this->data["transit"][] = array(
                "transit_distance" => 0,
                "transit_time" => 0,
                "number_of_collections" => 0,
                "number_of_drops" => 0,
                "total_waiting_time" => 0
            );
           $this->data["insurance"] = array(
                "value" => 0.00,
                "currency" => $param->collection->address->currency_code
            );
         $this->data["status"] = "success";
         }else{
            $this->data = $carrierLists;
        }
    }
    
    
    
    
     private
    function _setPostRequest($param)
    {
        $this->data = array();
        $carrierLists = $this->_getCustomerCarrierAccount($param);
        if ($carrierLists["status"] == "success")
        {
            $key = 0;
            $isDocument = '';
            $currencyCode = (isset($param->collection->address->currency_code) && !empty($param->collection->address->currency_code)) ? $param->collection->address->currency_code : 'GBP';
            $this->data = array(
                "carriers" => $carrierLists["data"],
                 "from" => $this->_getAddress($param->collection),
                "to" => $this->_getAddress($param->delivery[0]),
                "ship_date" => date("Y-m-d", strtotime($param->service_date)),
                "extra" => array(
                    'is_document' => false
                ) ,
                "currency" => $currencyCode
            );
            
            $this->data["package"] = array();
            foreach($param->parcel as $item)
            {
                for ($i = 0; $i < $item->quantity; $i++)
                {
                    array_push($this->data["package"], array(
                        "packaging_type" => $item->package_code,
                        "width" => $item->width,
                        "length" => $item->length,
                        "height" => $item->height,
                        "dimension_unit" => "CM",
                        "weight" => $item->weight,
                        "weight_unit" => "KG"
                    ));
                    $isDocument = (isset($item->is_document)) ? (($item->is_document && !is_bool($isDocument)) ? "true" : "false") : "false";
                }
            }

            $this->data['extra']['is_document'] = $isDocument;
            ($isDocument === "false") ? ($this->data['extra']['customs_form_declared_value'] = "0") : '';
            $this->data["transit"][] = array(
                "transit_distance" => 0,
                "transit_time" => 0,
                "number_of_collections" => 0,
                "number_of_drops" => 0,
                "total_waiting_time" => 0
            );
            if (isset($param->is_insured))
            {
                if ($param->is_insured == true) 
                {
                    $this->data["insurance"] = array(
                    "value" => $param->insurance_amount,
                    "currency" => $param->collection->address->currency_code
                );
                }
            }

            $this->data["status"] = "success";
         }
        
        else
        {
            $this->data = $carrierLists;
        }
    }
    
    
    
    
    
    
    
    
    private function _getCustomerCarrierAccount($param){
        $result = array();
        $collectionCountry = $param->collection->address;
        $deliveryCountry = $param->delivery[0]->address;
        $customerInfo = $this->modelObj->getCompanyInfo($param->company_id);
        $homeCountry = strtolower($customerInfo['country']);
        $flowType = 'Domestic';
       
        if ($collectionCountry->id == $deliveryCountry->id)
        {
            $flowType = 'Domestic';
        }
        else
            if ($homeCountry == strtolower($collectionCountry->short_name) && $homeCountry != strtolower($deliveryCountry->short_name))
            {
                $flowType = 'Export';
            }
            else
                if ($homeCountry == strtolower($deliveryCountry->short_name) && $homeCountry != strtolower($collectionCountry->short_name))
                {
                    $flowType = 'Import';
                }

        $carrier = $this->getCustomerCarrierAccount($param->company_id, $param->customer_id, $this->collection_postcode, $param->service_date);
        
        if (count($carrier) > 0)
        {
            foreach($carrier as $key => $item)
            {
                $accountId = isset($item["account_id"]) ? $item["account_id"] : $item["carrier_id"];
                $carrier[$key]["account_id"] = $accountId;
                foreach($param->parcel as $parceldata)
                {
                    $checkPackageSpecificService = $this->modelObj->checkPackageSpecificService($param->company_id, $parceldata->package_code, $item['carrier_code'], $flowType);
                    if (count($checkPackageSpecificService) > 0)
                    {
                        foreach($checkPackageSpecificService as $serviceData)
                        {
                            $carrier[$key]["services"][$serviceData["service_code"]] = $serviceData;
                        }
                    }
                    else
                    {
                        $services = $this->modelObj->getCustomerCarrierServices($param->customer_id, $accountId, $item["account_number"], $flowType);
                        if (count($services) > 0)
                        {
                            foreach($services as $service)
                            {
                                $carrier[$key]["services"][$service["service_code"]] = $service;
                            }
                        }
                        else
                        {
                            unset($carrier[$key]);
                        }
                    }
                }
            }
           
            $collectionIndex = 0;
           
            $collectionList = $this->_getJobCollectionList($carrier, $this->_getAddress($param->collection),$param);
           
            if (count($collectionList) > 0)
            {
                foreach($collectionList as $item)
                {
                    if ((strtotime($param->service_date) > strtotime($item['collection_date_time'])) || (strtotime($param->service_date) == strtotime($item['collection_date_time'])))
                    {
                        $item['highlight_class'] = '';
                    }
                    else
                    {
                        $item['highlight_class'] = 'highlighted-datetime';
                    }

                    if (count($item["services"]) > 0)
                    {
                        $serviceItems = array();
                        $isRegularPickup = ($item["is_regular_pickup"] == "no") ? "1" : "0";
                        foreach($item["services"] as $service)
                        {
                            array_push($serviceItems, $service["service_code"]);
                        }

                        $result[$item["carrier_code"]]["name"] = $item["carrier_code"];
                        if (strtolower($item["carrier_code"]) == 'dhl')
                        {
                            $result[$item["carrier_code"]]["account"][] = array(
                                "credentials" => array(
                                    "username" => $item["username"],
                                    "password" => $item["password"],
                                    "account_number" => $item["account_number"],
                                    "inxpress" => false,
                                    "other_reseller_account" => false
                                ) ,
                                "services" => implode(",", $serviceItems) ,
                                "pickup_scheduled" => $isRegularPickup
                            );
                        }
                        else
                        {
                            $result[$item["carrier_code"]]["account"][] = array(
                                "credentials" => array(
                                    "username" => $item["username"],
                                    "password" => $item["password"],
                                    "account_number" => $item["account_number"]
                                ) ,
                                "services" => implode(",", $serviceItems) ,
                                "pickup_scheduled" => $isRegularPickup
                            );
                        }

                        $this->carrierList[$item["account_number"]] = $item;
                    }
                }

                if (count($result) > 0)
                {
                    return array(
                        "status" => "success",
                        "data" => array_values($result)
                    );
                }

                return array(
                    "status" => "error",
                    "message" => "Service not configured"
                );
            }

            return array(
                "status" => "error",
                "message" => "Collection list not configured"
            );
        }

        return array(
            "status" => "error",
            "message" => "Carrier not configured"
        );
    }
    public function   getNextDayQuotation($param,$quotation_ref){ 
        
       if(key_exists('parcel',(array)$param) and (count($param->delivery)==1)){
        try{ 
        $endpoint      = $this->endpoint;
        $param->customer_id = $param->customer_id;
        if(!isset($param->service_date) || ($param->service_date=='') || !($this->isValidServiceDate($param->service_date))){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'service date and time missing';
                 $response["error_code"] = "ERRORN0018";
                 return $response;
        }elseif(!isset($param->warehouse_id) || ($param->warehouse_id=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'warehouse id missing.';
                 $response["error_code"] = "ERRORN0064";
                 return $response;
        }elseif(!isset($param->warehouse_latitude) || ($param->warehouse_latitude=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'warehouse latitude missing.';
                 $response["error_code"] = "ERRORN0065";
                 return $response;
        }elseif(!isset($param->warehouse_longitude) || ($param->warehouse_longitude=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'warehouse longitude missing.';
                 $response["error_code"] = "ERRORN0066";
                 return $response;
        }elseif(!isset($param->collection) || ($param->collection=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Collection key missing';
                 $response["error_code"] = "ERRONR007";
                 return $response;
        }
        elseif(!isset($param->collection->address) || ($param->collection->address=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Collection address missing';
                 $response["error_code"] = "ERRORN0041";
                 return $response;
        }
        elseif(!isset($param->collection->address->postcode) || ($param->collection->address->postcode=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Collection Postcode parameter missing';
                 $response["error_code"] = "ERRORN008";
                 return $response;
        }elseif(isset($param->collection->address->city) && ($param->collection->address->city=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Collection city parameter missing';
                 $response["error_code"] = "ERRORNN018";
                 return $response;
        }elseif(!isset($param->collection->address->country) || ($param->collection->address->country=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Collection country parameter missing';
                 $response["error_code"] = "ERRORNN008";
                 return $response;
        }else{
            if(!isset($param->collection->address->latitude) && !isset($param->collection->address->longitude)){
              $collectionGeo = $this->getGeoLocationByPostCode($param->collection->address->postcode,0);
              if($collectionGeo['status'] != 'success'){
                return $collectionGeo;
              }
              else{
               $param->collection->address->latitude = $collectionGeo['latitude'];
               $param->collection->address->longitude = $collectionGeo['longitude'];
               $param->collection->address->geo_position = array('latitude'=>$collectionGeo['latitude'],'longitude'=>$collectionGeo['longitude']);
               $googleRequest['collection_postcodes'][] = (object) array('postcode'=>$param->collection->address->postcode,'latitude'=>$collectionGeo['latitude'],'longitude'=>$collectionGeo['longitude'],'geo_position'=> (object) array('latitude'=>$collectionGeo['latitude'],'longitude'=>$collectionGeo['longitude']));
            }
            }else{
                $googleRequest['collection_postcodes'][] = (object) array(
                                'postcode'=>$param->collection->address->postcode,
                                'latitude'=>$param->collection->address->latitude,
                                'longitude'=>$param->collection->address->longitude,
                                'geo_position'=> (object) array('latitude'=>$param->collection->address->latitude,'longitude'=>$param->collection->address->longitude));
            }
        }
        if(!isset($param->delivery)){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery key missing';
                 $response["error_code"] = "ERRORN0012";
                 return $response;
        }
        elseif(empty((array)$param->delivery)){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery data missing';
                 $response["error_code"] = "ERRORN0013";
                 return $response;
        }elseif(!isset($param->parcel)){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Parcel key missing';
                 $response["error_code"] = "ERRORN0020";   
                 return $response;
        }elseif(empty((array)$param->parcel)){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Parcel data missing';
                 $response["error_code"] = "ERRORN0021";
                 return $response;
        }
        else{
            foreach($param->delivery as $key=>$val){
             if(!isset($val->address)  || ($val->address=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery Postcode parameter missing';
                 $response["error_code"] = "ERRORN0042";
                 return $response;
            }
             elseif(!isset($val->address->postcode)  || ($val->address->postcode=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery Postcode parameter missing';
                 $response["error_code"] = "ERRORN0014";
                 return $response;
            }elseif(isset($val->address->city)  && ($val->address->city=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery city parameter missing';
                 $response["error_code"] = "ERRORN0024";
                 return $response;
            }elseif(!isset($val->address->country)  || ($val->address->country=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery country parameter missing.';
                 $response["error_code"] = "ERRORN0017";
                 return $response;
            }else{
                if(!isset($val->address->latitude) && !isset($val->address->longitude)){
                    $deliveryGeo = $this->getGeoLocationByPostCode($val->address->postcode,0);
                    if($deliveryGeo['status'] != 'success'){
                    return $deliveryGeo;
                }else{
                    $val->address->latitude = $deliveryGeo['latitude'];
                    $val->address->longitude = $deliveryGeo['longitude'];
                    $val->address->geo_position = array('latitude'=>$deliveryGeo['latitude'],'longitude'=>$deliveryGeo['longitude']);
                   $googleRequest['delivery_postcodes'][] = (object) array('postcode'=>$val->address->postcode,'latitude'=>$deliveryGeo['latitude'],'longitude'=>$deliveryGeo['longitude'],'geo_position'=> (object) array('latitude'=>$deliveryGeo['latitude'],'longitude'=>$deliveryGeo['longitude']));
                }
                }else{
                   $googleRequest['delivery_postcodes'][] = (object) array(
                       'postcode'=>$val->address->postcode,
                       'latitude'=>$val->address->latitude,
                       'longitude'=>$val->address->longitude,
                       'geo_position'=> (object) array('latitude'=>$val->address->latitude,'longitude'=>$val->address->longitude));
                }
             }
           }
        
            foreach($param->parcel as $key=>$val){ 
            if(!isset($val->quantity) || ($val->quantity=='')){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Parcel quantity parameter missing';
                 $response["error_code"] = "ERRORN0022";   
                 return $response;
            }
            elseif(!isset($val->weight) || ($val->weight=='')){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Parcel weight parameter missing';
                 $response["error_code"] = "ERRORN0023";   
                 return $response;
            } 
            elseif(!isset($val->length) || ($val->length=='')){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Parcel length parameter missing';
                 $response["error_code"] = "ERRORN0024";    
                 return $response;
            } 
            elseif(!isset($val->width) || ($val->width=='')){
                 $response = array(); 
                 $response["status"] = "fail";
                  $response["message"] = 'Parcel width parameter missing';
                 $response["error_code"] = "ERRORN0025";    
                 return $response;
            }
            elseif(!isset($val->height) || ($val->height=='')){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Parcel height parameter missing';
                 $response["error_code"] = "ERRORN0026";   
                 return $response;
            }
            elseif(!isset($val->package_code) || ($val->package_code=='')){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Parcel package_code parameter missing';
                 $response["error_code"] = "ERRORN0027";  
                 return $response;
            }
            else{
                // 
                }  
           }  
        
       
           $accountStatus = $this->_checkCustomerAccountStatus($param->customer_id);
           if($accountStatus["status"]=="error"){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = $accountStatus['message'];
                 $response["error_code"] = "ERRORN004";   
                 return $response;
            }
          
            $this->collection_postcode = $param->collection->address->postcode;
            $collectionCountry =  $this->resrServiceModel->getCountryData($param->collection->address->country);
            $param->collection->address->id = $collectionCountry['id'];
            $param->collection->address->alpha2_code = $collectionCountry['alpha2_code'];
            $param->collection->address->short_name = $collectionCountry['short_name'];
            $param->collection->address->currency_code = $collectionCountry['currency_code'];
            $deliveryCountry =  $this->resrServiceModel->getCountryData($param->delivery[0]->address->country);
            $param->delivery[0]->address->id = $deliveryCountry['id'];
            $param->delivery[0]->address->alpha2_code = $deliveryCountry['alpha2_code'];
            $param->delivery[0]->address->short_name = $deliveryCountry['short_name'];
            $param->delivery[0]->address->currency_code = $deliveryCountry['currency_code'];
            
             
            $this->_setPostRequest($param);
            $available_credit = $this->_getCustomerAccountBalence($param->customer_id,0.00); 
           if ($this->data["status"] == "success"){
            $requestStr = json_encode($this->data);
            $responseStr = $this->_postRequest($this->data);
            $response = json_decode($responseStr);
            $response = $this->_getCarrierInfo($response->rate,$param);
            if(isset($response->status) and $response->status="error"){   
                     $response = array(); 
                     $response["status"] = "fail";
                     $response["message"] = $response->message;
                     $response["error_code"] = "ERRORN005";   
                     return $response;
                 }
                $response  = $this->_getWbFormat($response,$param,$endpoint,$quotation_ref);
                if(!empty($response)){
                    return array(
                        "status"=>"success",
                        "rate"=>$response, 
                        "service_date"=>$param->service_date,
                        "availiable_balence" => $available_credit['available_credit']);
                }else{
                     $response = array(); 
                     $response["status"] = "fail";
                     $response["error_code"] = "ERRORN028";   
                     return $response;
                }
        }
        else
        {
            return array(
                "status" => "error",
                "message" => $this->data["message"]
            );
            }
          }
        }
        catch(Exception $e){
            print_r($e->getMessage());die;
        }
    }else{
        return array();
    }
    }
    private function _getJobCollectionList($carriers, $address,$param){
        $jobCollectionList = $this->collectionModel->getJobCollectionList($carriers, $address, $param->customer_id, $param->company_id, $param->service_date);
        
        $this->regular_pickup = $jobCollectionList["regular_pickup"];
        return $jobCollectionList["carrier_list"];
    }
    private function _getWbFormat($response,$param,$endpoint,$quotation_ref){
       $endpoint          = isset($endpoint)?$endpoint:'getSameDayServices';
       $reqSessionId      = ($quotation_ref!='')?$quotation_ref:date('dmY').microtime(true);
       $savedTempService  = array('session_id'=>$reqSessionId,'request'=>json_encode((array)$param),'status'=>1,'create_date'=>date('Y-m-d'),'create_time'=>date('H:m:s'),'end_point'=>'getNextDayServices','request_status'=>'NC'); 
       $responsedata      = array('quotation_ref'=>$reqSessionId);
       foreach($response as $key=>$val){ 
            foreach($val as $key1=>$val2){ 
              foreach($val2 as $key3=>$val3){
                    foreach($val3 as $key4=>$val4){ 
                      foreach($val4 as $key5=>$val5){ //print_r($val5[0]);
                          $surcharges = array();
                          $collectionKey = ($param->collected_by!='self')?0:1;
                          if(key_exists('surcharges',$val5[0]->collected_by[$collectionKey]) and count($val5[0]->collected_by[$collectionKey]['surcharges'])>0){
                              foreach($val5[0]->collected_by[$collectionKey]['surcharges'] as $keydata=>$data){
                               $surcharges[$keydata] = $data['price_with_ccf'];
                            }
                          }
                          if($param->collected_by=='self'){ 
                            $surcharges['pickup_surcharge'] = $val5[0]->collected_by[$collectionKey]['pickup_surcharge'];
                          }
                          $temp                          =   array();
                          $temp['job_type']              =   'NEXTDAY';  
                          $temp['service_code']          =   ($val5[0]->rate->info['company_service_code']!='')?$val5[0]->rate->info['company_service_code']:$val5[0]->rate->info['courier_service_code'];
                          $temp['service_name']          =   ($val5[0]->rate->info['company_service_name']!='')?$val5[0]->rate->info['company_service_name']:$val5[0]->rate->info['courier_service_name'];
                          $temp['service_id']            =   $val5[0]->rate->info['service_id'];
                          $temp['act_number']            =   $val5[0]->rate->act_number;
                          $temp['max_delivery_time']     =  '00:00:00';
                          $temp['max_waiting_time']      =  '00:00:00';
                          $val5[0]->collected_by[$collectionKey]['customer_price_info']['surcharges'] = array_sum($surcharges);
                          $temp['surcharges_info']       =  $surcharges;
                          $temp['surcharges']            =  $val5[0]->collected_by[$collectionKey]['customer_price_info']['surcharges'];
                          $temp['taxes']                 =  $val5[0]->collected_by[$collectionKey]['customer_price_info']['taxes'];
                          $temp['total']                 =  $val5[0]->collected_by[$collectionKey]['customer_price_info']['grand_total'];
                          $temp['carrier']               =  $val5[0]->collected_by[$collectionKey]['name'];
                          $temp['price']                 =  $val5[0]->collected_by[$collectionKey]['customer_price_info']['price'];
                          $temp['transit_distance']      =  '';
                          $temp['transit_time']          =  '';
                          $temp['transit_distance_text'] =  '';
                          $temp['transit_time_text']     =  '';
                          $temp['service_options']       =  $val5[0]->service_options;
                          $temp['flow_type']             =  $val5[0]->rate->flow_type;
                          $temp['rate_type']             =  $val5[0]->rate->rate_type;
                          $responsedata['services'][]    =  $temp;
                          $savedTempService['response'][$val5[0]->rate->info['service_id']] = $val5;
                       }    
                    }
                  }
            } 
         }
       $savedTempService['response'] = json_encode($savedTempService['response']);
       $this->resrServiceModel->addContent('webapi_request_response',$savedTempService);
       return $responsedata; 
   }
    private function _getAddress($item){ 
     return array(
            "zip" => $item->address->postcode,
            "country" =>$item->address->alpha2_code
        );
    }
}
?>