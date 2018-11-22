<?php
final class Nextday extends Booking
{
   private $_param = array();

    protected static $_ccf = NULL;
    public function __construct($data){
        $this->_parentObj = parent::__construct(array("email" => $data->email, "access_token" => $data->access_token));
        $this->_param = $data;
        $this->_param->collection_date = isset($this->_param->service_date)?$this->_param->service_date:'';
        unset($this->_param->service_date);
        $this->customerccf = new CustomerCostFactor();
        $this->resrServiceModel = new restservices_Model();
        $this->collectionModel = Collection::_getInstance();//new Collection();
    }

    private function _getCarrierInfo($data){
        foreach($data as $carrier_code => $lists){
            foreach($lists as $key1 => $list){
                foreach($list as $accountNumber => $items){
                    foreach($items as $key3 => $item){
                        foreach($item as $service_code => $services) {
                            //calculate service ccf
                            if (!isset($services[0]->rate->error)) {
                                $serviceCcf = $this->customerccf->calculateServiceCcf($service_code, $services[0]->rate->price, $this->carrierList[$accountNumber]["carrier_id"], $this->_param->customer_id, $this->_param->company_id);//$services[0]->rate
                                $services[0]->rate->price = $serviceCcf["price_with_ccf"];
                                $services[0]->rate->info = $serviceCcf;

                                //$service_code
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
                                                $surchargeCcf = $this->customerccf->calculateSurchargeCcf($surcharge_code, $this->_param->customer_id, $this->_param->company_id, $this->carrierList[$accountNumber]["carrier_id"], $surcharge_price);
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
    private function _setPostRequest(){ 
        $this->data = array();
        $carrierLists = $this->_getCustomerCarrierAccount();
        if($carrierLists["status"]=="success"){
            $key = 0;
            $this->data = array(
                "carriers" => $carrierLists["data"],
                "from" => $this->_getAddress($this->_param->collection),
                "to" => $this->_getAddress($this->_param->delivery),
                "ship_date" => date("Y-m-d", strtotime($this->_param->collection_date)),
                "extra" => array(

                ),
                "currency" => $this->_param->collection->currency_code,
            );
            $this->data["package"] = array();
            foreach($this->_param->parcel as $item){
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
                "currency" => $this->_param->collection->currency_code
            );
         $this->data["status"] = "success";
         }else{
            $this->data = $carrierLists;
        }
    }
    private function _getCustomerCarrierAccount(){
        $result = array();
        $carrier = $this->getCustomerCarrierAccount($this->_param->company_id, $this->_param->customer_id, $this->collection_postcode, $this->_param->collection_date);
        if(count($carrier)>0){
            foreach($carrier as $key => $item) {
                $services = $this->modelObj->getCustomerCarrierServices($this->_param->customer_id, $item["carrier_id"], $item["account_number"]);
                if(count($services)>0){
                    foreach($services as $service)
                        $carrier[$key]["services"][$service["service_code"]] = $service;
                }else {
                    unset($carrier[$key]);
                }
            }
            $collectionIndex = 0;
            $collectionList = $this->_getJobCollectionList($carrier, $this->_getAddress($this->_param->collection));

            if(count($collectionList)>0){
                foreach($collectionList as $item){
                    if(count($item["services"])>0){
                        $serviceItems = array();
                        $isRegularPickup = ($item["is_regular_pickup"]=="no") ? "1" : "0";

                        foreach($item["services"] as $service){
                            array_push($serviceItems, $service["service_code"]);
                        }

                        array_push($result, array(
                            "name"    => $item["carrier_code"],
                            "account" => array(
                                array(
                                    "credentials" => array(
                                        "username" => $item["username"],
                                        "password" => $item["password"],
                                        "account_number" => $item["account_number"]
                                    ),
                                    "services"         => implode(",", $serviceItems),
                                    "pickup_scheduled" => $isRegularPickup
                                )
                            )
                        ));
                        $this->carrierList[$item["account_number"]] = $item;
                    }
                }
                if(count($result)>0){
                    return array("status"=>"success", "data"=>$result);
                }
                return array("status"=>"error", "message"=>"Service not configured");
            }
            return array("status"=>"error", "message"=>"Collection list not configured");
        }
        return array("status"=>"error", "message"=>"Carrier not configured");
    }
    public function   getNextDayServices($endpoint = null){
       try{ 
        if(!isset($this->_param->collection_date)  || ($this->_param->collection_date=='')){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'service date missing';
                 $response["error_code"] = "ERROR0018";   
                 return $response;
        }
        elseif(!isset($this->_param->collection)){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Collection key missing';
                 $response["error_code"] = "ERROR007";   
                 return $response;
        }
        elseif(empty($this->_param->collection)){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Collection data missing';
                 $response["error_code"] = "ERROR0019";   
                 return $response;
        }
        elseif(!isset($this->_param->collection->postcode) || ($this->_param->collection->postcode=='')){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Collection Postcode parameter missing';
                 $response["error_code"] = "ERROR008";   
                 return $response;
        }
        /*elseif(!isset($this->_param->collection->country_code) || ($this->_param->collection->country_code=='')){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Collection country code parameter missing';
                 $response["error_code"] = "ERROR009";   
                 return $response;
        } 
        elseif(!isset($this->_param->collection->currency_code) || ($this->_param->collection->currency_code=='')){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Collection currency code parameter missing';
                 $response["error_code"] = "ERROR010";   
                 return $response;
        } */
        elseif(!isset($this->_param->collection->country) || ($this->_param->collection->country=='')){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Collection country parameter missing';
                 $response["error_code"] = "ERROR0011";   
                 return $response;
        }
        elseif(!isset($this->_param->delivery)){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery key missing';
                 $response["error_code"] = "ERROR0012";   
                 return $response;
        }
        elseif(empty($this->_param->delivery)){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery data missing';
                 $response["error_code"] = "ERROR0013"; 
                 return $response;
        }
        elseif(!isset($this->_param->delivery->postcode)|| ($this->_param->delivery->postcode=='')){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery Postcode parameter missing';
                 $response["error_code"] = "ERROR0014";   
                 return $response;
            }
        /*elseif(!isset($this->_param->delivery->country_code)|| ($this->_param->delivery->country_code=='')){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery country code parameter missing';
                 $response["error_code"] = "ERROR0015";   
                 return $response;
            } 
        elseif(!isset($this->_param->delivery->currency_code)|| ($this->_param->delivery->currency_code=='')){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery currency code parameter missing';
                 $response["error_code"] = "ERROR016";   
                 return $response;
            } */
        elseif(!isset($this->_param->delivery->country)|| ($this->_param->delivery->country=='')){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery country parameter missing';
                 $response["error_code"] = "ERROR0017";   
                 return $response;
            }
        elseif(!isset($this->_param->parcel)){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Parcel key missing';
                 $response["error_code"] = "ERROR0020";   
                 return $response;
        }
        elseif(empty((array)$this->_param->parcel)){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Parcel data missing';
                 $response["error_code"] = "ERROR0021";
                 return $response;
        }
        else{
          foreach($this->_param->parcel as $key=>$val){ 
            if(!isset($val->quantity) || ($val->quantity=='')){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Parcel quantity parameter missing';
                 $response["error_code"] = "ERROR0022";   
                 return $response;
            }
            elseif(!isset($val->weight) || ($val->weight=='')){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Parcel weight parameter missing';
                 $response["error_code"] = "ERROR0023";   
                 return $response;
            } 
            elseif(!isset($val->length) || ($val->length=='')){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Parcel length parameter missing';
                 $response["error_code"] = "ERROR0024";    
                 return $response;
            } 
            elseif(!isset($val->width) || ($val->width=='')){
                 $response = array(); 
                 $response["status"] = "fail";
                  $response["message"] = 'Parcel width parameter missing';
                 $response["error_code"] = "ERROR0025";    
                 return $response;
            }
            elseif(!isset($val->height) || ($val->height=='')){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Parcel height parameter missing';
                 $response["error_code"] = "ERROR0026";   
                 return $response;
            }
            elseif(!isset($val->package_code) || ($val->package_code=='')){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Parcel package_code parameter missing';
                 $response["error_code"] = "ERROR0027";  
                 return $response;
            }
            else{
                // 
                }  
           }  
            $accountStatus = $this->_checkCustomerAccountStatus($this->_param->customer_id);
            if($accountStatus["status"]=="error"){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = $accountStatus['message'];
                 $response["error_code"] = "ERROR004";   
                 return $response;
            }
            $this->collection_postcode = $this->_param->collection->postcode;
            $this->destination_postcode = $this->_param->delivery->postcode;
            $this->_setPostRequest();
            if($this->data["status"]=="success"){
                $requestStr = json_encode($this->data);
                $responseStr = $this->_postRequest($requestStr);
                $response = json_decode($responseStr);
                $response = $this->_getCarrierInfo($response->rate);
                if(isset($response->status) and $response->status="error"){   
                     $response = array(); 
                     $response["status"] = "fail";
                     $response["message"] = $response->message;
                     $response["error_code"] = "ERROR005";   
                     return $response;
                 }
                $available_credit = $this->_getCustomerAccountBalence($this->_param->customer_id,0.00); 
                $response  = $this->_getWbFormat($response,$this->_param,$endpoint);
                if(!empty($response)){
                    return array("status"=>"success",  "message"=>"Rate found", "services"=>$response, "service_time"=>date("H:i", strtotime($this->_param->collection_date)),"service_date"=>date("d/M/Y", strtotime($this->_param->collection_date)),"availiable_balence" => $available_credit['available_credit']);
                }else{
                     $response = array(); 
                     $response["status"] = "fail";
                     $response["message"] = 'No Service found';
                     $response["error_code"] = "ERROR028";   
                     return $response;
                }
                
            }
            else {
                     $response = array(); 
                     $response["status"] = "fail";
                     $response["message"] = $this->data["message"];
                     $response["error_code"] = "ERROR005";   
                     return $response;
             } 
         }
        }
        catch(Exception $e){
            print_r($e->getMessage());die;
        }
    }
    private function _getJobCollectionList($carriers, $address){
        $jobCollectionList = $this->collectionModel->getJobCollectionList($carriers, $address, $this->_param->customer_id, $this->_param->company_id, $this->_param->collection_date);
        $this->regular_pickup = $jobCollectionList["regular_pickup"];
        return $jobCollectionList["carrier_list"];
    }
    private function _getWbFormat($response,$param,$endpoint){ 
       $endpoint            = isset($endpoint)?$endpoint:'getSameDayServices';
       $reqSessionId        = date('dmY').microtime(true);
       $savedTempService    = array('session_id'=>$reqSessionId,'request'=>json_encode((array)$param),'status'=>1,'create_date'=>date('Y-m-d'),'create_time'=>date('H:m:s'),'end_point'=>'getNextDayServices','request_status'=>'NC'); 
       $responsedata        = array('req_session'=>$reqSessionId);
       foreach($response as $key=>$val){ 
             foreach($val as $key1=>$val2){ 
                  foreach($val2 as $key3=>$val3){
                    foreach($val3 as $key4=>$val4){ 
                      foreach($val4 as $key5=>$val5){ 
                          $temp = array();
                          $temp['service_code'] = $val5[0]->rate->info['company_service_code'];
                          $temp['service_name'] = $val5[0]->rate->info['company_service_name'];
                          $temp['service_id']   = $val5[0]->rate->info['service_id'];
                          $temp['act_number']   = $val5[0]->rate->act_number;
                          $temp['price']        = $val5[0]->collected_by[0]['customer_price_info']['price'];
                          $temp['surcharges']   = $val5[0]->collected_by[0]['customer_price_info']['surcharges'];
                          $temp['taxes']        = $val5[0]->collected_by[0]['customer_price_info']['taxes'];
                          $temp['total']        = $val5[0]->collected_by[0]['customer_price_info']['grand_total'];
                          $temp['carrier']      = $val5[0]->collected_by[0]['name'];
                          $responsedata['services'][]   = $temp;
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
            "zip" => $item->postcode,
            "country" =>$item->country_code
        );
    }

}
?>