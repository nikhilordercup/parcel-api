<?php
final class Nextday extends Booking
{
    private $_param = array();

    protected static $_ccf = NULL;

    public

    function __construct($data){
        $this->_parentObj = parent::__construct(array("email" => $data->email, "access_token" => $data->access_token));
        $this->_param = $data;
        $this->customerccf = new CustomerCostFactor();

        $this->collectionModel = Collection::_getInstance();//new Collection();
    }

    private

    function _getJobCollectionList($carriers, $address){
        $jobCollectionList = $this->collectionModel->getJobCollectionList($carriers, $address, $this->_param->customer_id, $this->_param->company_id, $this->_param->collection_date);

        $this->regular_pickup = $jobCollectionList["regular_pickup"];
        return $jobCollectionList["carrier_list"];
    }

    private

    function _getCustomerCarrierAccount(){
        $result = array();

        //$carrier = $this->modelObj->getCustomerCarrierAccount($this->_param->company_id, $this->_param->customer_id);
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

            $collectionList = $this->_getJobCollectionList($carrier, $this->_getAddress($this->_param->collection->$collectionIndex));

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

    private

    function _getCarrierInfo($data){
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

    private

    function _getAddress($item){
        return array(
            "name" => "",
            "company" => "",
            "phone" => "",
            "street1" => (isset($item->address_line1)) ? $item->address_line1 : "",//$this->_param->collection->$key->address_line1,
            "street2" => (isset($item->address_line2)) ? $item->address_line2 : "",//$this->_param->collection->$key->address_line2,
            "city" => (isset($item->city)) ? $item->city : "",//$this->_param->collection->$key->city,
            "state" => (isset($item->state)) ? $item->state : "",//$this->_param->collection->$key->state,
            "zip" => $item->postcode,//$this->_param->collection->$key->postcode,
            "country" =>$item->country->alpha3_code,//$this->_param->collection->$key->country->currency_code,
            "country_name" => $item->country->short_name,//$this->_param->collection->$key->country->short_name
        );
    }

    private

    function _setPostRequest(){
        $this->data = array();
        $carrierLists = $this->_getCustomerCarrierAccount();

        if($carrierLists["status"]=="success"){
            $key = 0;
            $this->data = array(
                "carriers" => $carrierLists["data"],
                "from" => $this->_getAddress($this->_param->collection->$key),
                "to" => $this->_getAddress($this->_param->delivery->$key),
                "ship_date" => date("Y-m-d", strtotime($this->_param->collection_date)),
                "extra" => array(

                ),
                "currency" => $this->_param->collection->$key->country->currency_code,

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
                "transit_distance" => 0,//$this->distanceMatrixInfo->value,
                "transit_time" => 0,//$this->durationMatrixInfo->value,
                "number_of_collections" => 0,
                "number_of_drops" => 0,
                "total_waiting_time" => 0
            );

           $this->data["insurance"] = array(
                "value" => 0.00,
                "currency" => $this->_param->collection->$key->country->currency_code
            );

            $this->data["status"] = "success";
        }else{
            $this->data = $carrierLists;
        }
    }

    public

    function searchNextdayCarrierAndPrice(){
        $accountStatus = $this->_checkCustomerAccountStatus($this->_param->customer_id);
        if($accountStatus["status"]=="error"){
            return $accountStatus;
        }
        //find distance matrix
        $key = 0;
        $destinations = array();

        $this->collection_postcode = $this->_param->collection->$key->postcode;
        $this->_setPostRequest();

        if($this->data["status"]=="success"){
            $requestStr = json_encode($this->data);
            $responseStr = $this->_postRequest($requestStr);

            $response = json_decode($responseStr);

            $response = $this->_getCarrierInfo($response->rate);

            if(isset($response->status) and $response->status="error"){
                return array("status"=>"error", "message"=>$response->message);
            }
            return array("status"=>"success",  "message"=>"Rate found","service_request_string"=>$requestStr,"service_response_string"=>$responseStr, "data"=>$response, "service_time"=>date("H:i", strtotime($this->_param->collection_date)),"service_date"=>date("d/M/Y", strtotime($this->_param->collection_date)));
        }else {
            return array("status"=>"error", "message"=>$this->data["message"]);
        }
    }

    public

    function saveBooking(){
        $accountStatus = $this->_checkCustomerAccountStatus($this->_param->customer_id);
        if($accountStatus["status"]=="error"){
            return $accountStatus;
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

        if($is_internal==1){
            //email to customer
            Consignee_Notification::_getInstance()->sendNextdayBookingConfirmationNotification(array("load_identity"=>$loadIdentity,"company_id"=>$this->_param->company_id,"warehouse_id"=>$this->_param->warehouse_id,"customer_id"=>$this->_param->customer_id));

            //email to courier
            Consignee_Notification::_getInstance()->sendNextdayBookingConfirmationNotificationToCourier(array("load_identity"=>$loadIdentity,"company_id"=>$this->_param->company_id,"warehouse_id"=>$this->_param->warehouse_id,"customer_id"=>$this->_param->customer_id));
        }

        // call label generation method
        return array("status"=>"success","message"=>"Shipment booked successful. Shipment ticket $loadIdentity");
    }
}
?>