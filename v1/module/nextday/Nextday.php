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

        $this->collectionModel = new Collection();
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
        $carrier = $this->modelObj->getCustomerCarrierAccount($this->_param->company_id, $this->_param->customer_id);
       
        foreach($carrier as $key => $item) {
            $services = $this->modelObj->getCustomerCarrierServices($this->_param->customer_id, $item["carrier_id"]);

            if(count($services)>0){
                foreach($services as $service)
                    $carrier[$key]["services"][$service["service_code"]] = $service;
            }
        }

        $collectionList = $this->_getJobCollectionList($carrier, $this->_fromAddress(0));
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
                    $this->carrierList[$item["carrier_code"]] = $item;
                }
            }
        }
        return $result;
    }

    private

    function _getCarrierInfo($data){
        foreach($data as $carrier_code => $lists){
            foreach($lists as $key1 => $list){
                foreach($list as $key2 => $items){
                    foreach($items as $key3 => $item){
                        foreach($item as $service_code => $services){

                            //calculate service ccf
                            $serviceCcf = $this->customerccf->calculateServiceCcf($service_code, $services[0]->rate->price, $this->carrierList[$carrier_code]["carrier_id"],$this->_param->customer_id, $this->_param->company_id);//$services[0]->rate

                            $services[0]->rate->price = $serviceCcf["price_with_ccf"];
                            $services[0]->rate->info = $serviceCcf;

                            //$service_code
                            foreach($services as $key5 => $service){
                                if(isset($service->rate->error)){
                                    return (object)array("status"=>"error", "message"=>$service->rate->error);
                                }

                                //set tax number format
                                if(isset($service->taxes)){
                                    if(isset($service->taxes->total_tax)){
                                        $service->taxes->total_tax = number_format($service->taxes->total_tax, 2);
                                    }
                                    if(isset($service->taxes->tax_percentage)){
                                        $service->taxes->tax_percentage = number_format($service->taxes->tax_percentage, 2);
                                    }
                                }

                                //calculate surcharge ccf
                                $surchargeWithCcfPrice = 0;
                                $surchargePrice = 0;
                                $service->collected_by = $this->carrierList[$carrier_code]["collected_by"];

                                foreach($service->collected_by as $collected_key => $collected_item){
                                    $surchargeWithCcfPrice = 0;
                                    $surchargePrice = 0;
                                    foreach($service->surcharges as $surcharge_code => $surcharge_price){
                                        if($collected_item["carrier_code"]!=$carrier_code and $surcharge_code=="collection_surcharge"){
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
                                        }else{
                                            $surchargeCcf = $this->customerccf->calculateSurchargeCcf($surcharge_code, $this->_param->customer_id, $this->_param->company_id, $this->carrierList[$carrier_code]["carrier_id"], $surcharge_price);
                                        }
                                        //print_r($surchargeCcf);
                                        $collected_item["surcharges"][$surcharge_code] = $surchargeCcf;

                                        $surchargeWithCcfPrice += $surchargeCcf["price_with_ccf"];

                                        if($surchargeCcf["operator"]!="FLAT"){
                                            $surchargePrice += $surchargeCcf["original_price"];
                                        }

                                    }

                                    $collected_item["carrier_price_info"]["price"] = $serviceCcf["original_price"];
                                    $collected_item["customer_price_info"]["price"] = $serviceCcf["price_with_ccf"];

                                    $collected_item["carrier_price_info"]["surcharges"] = number_format($surchargePrice, 2);
                                    $collected_item["customer_price_info"]["surcharges"] = number_format($surchargeWithCcfPrice, 2);

                                    $collected_item["carrier_price_info"]["taxes"] = number_format($service->taxes->total_tax, 2);
                                    $collected_item["customer_price_info"]["taxes"] = number_format((($serviceCcf["price_with_ccf"] + $surchargeWithCcfPrice) * $service->taxes->tax_percentage / 100), 2);

                                    $collected_item["carrier_price_info"]["grand_total"] = number_format($serviceCcf["original_price"] + $surchargePrice + $service->taxes->total_tax, 2);
                                    $collected_item["customer_price_info"]["grand_total"] = number_format($serviceCcf["price_with_ccf"] + $surchargeWithCcfPrice + $collected_item["customer_price_info"]["taxes"], 2);


                                    $service->collected_by[$collected_key] = $collected_item;
                                }


                                /*foreach($service->surcharges as $surcharge_code => $surcharge_price){
                                    $surchargeCcf = $this->customerccf->calculateSurchargeCcf($surcharge_code, $this->_param->customer_id, $this->_param->company_id, $this->carrierList[$carrier_code]["carrier_id"], $surcharge_price);

                                    //print_r($surchargeCcf);die;
                                    if(!isset($services[0]->rate->ccf_surcharges))
                                        $services[0]->rate->ccf_surcharges = new stdClass();

                                    $services[0]->rate->ccf_surcharges->$surcharge_code = $surchargeCcf;

                                    $surchargeWithCcfPrice += $surchargeCcf["price_with_ccf"];
                                    $surchargePrice += $surchargeCcf["original_price"];
                                }*/

                                /*$services[0]->rate->info["carrier_price_info"]["surcharges"] = number_format($surchargePrice, 2);
                                $services[0]->rate->info["customer_price_info"]["surcharges"] = number_format($surchargeWithCcfPrice, 2);

                                $services[0]->rate->info["carrier_price_info"]["taxes"] = $service->taxes->total_tax;
                                $services[0]->rate->info["customer_price_info"]["taxes"] = number_format((($serviceCcf["price_with_ccf"] + $surchargeWithCcfPrice) * $service->taxes->tax_percentage / 100), 2);

                                $services[0]->rate->info["carrier_price_info"]["grand_total"] = number_format($serviceCcf["original_price"] + $surchargePrice + $service->taxes->total_tax, 2);
                                $services[0]->rate->info["customer_price_info"]["grand_total"] = number_format($serviceCcf["price_with_ccf"] + $surchargeWithCcfPrice + $services[0]->rate->info["customer_price_info"]["taxes"], 2);*/

                                $service->carrier_info = array(
                                    "carrier_id" => $this->carrierList[$carrier_code]["carrier_id"],
                                    "name" => $this->carrierList[$carrier_code]["name"],
                                    "icon" => $this->carrierList[$carrier_code]["icon"],
                                    "code" => $this->carrierList[$carrier_code]["carrier_code"],
                                    "description" => $this->carrierList[$carrier_code]["description"]
                                );
                                $service->service_info = array(
                                    "code" => $this->carrierList[$carrier_code]["services"][$service_code]["service_code"],
                                    "name" => $this->carrierList[$carrier_code]["services"][$service_code]["service_name"]
                                );
                            }
                        }
                    }
                }
            }
        }
        return $data;
    }

    private

    function _fromAddress($key){
        return array(
            "name" => "",
            "company" => "",
            "phone" => "",
            "street1" => $this->_param->collection->$key->address_line1,
            "street2" => $this->_param->collection->$key->address_line2,
            "city" => $this->_param->collection->$key->city,
            "state" => $this->_param->collection->$key->state,
            "zip" => $this->_param->collection->$key->postcode,
            "country" => "GBR",
            "country_name" => "United Kingdom"
        );
    }

    private

    function _setPostRequest(){
        $this->data = array();
        $carrierLists = $this->_getCustomerCarrierAccount();

        if(count($carrierLists)>0){
            $key = 0;
            $this->data = array(
                "carriers" => $carrierLists,
                "from" => $this->_fromAddress($key),
                "to" => array(
                    "name" => "",
                    "company" => "",
                    "phone" => "",
                    "street1" => (isset($this->_param->delivery->$key->address_line1)) ? $this->_param->delivery->$key->address_line1 : "",
                    "street2" => (isset($this->_param->delivery->$key->address_line2)) ? $this->_param->delivery->$key->address_line2 : "",
                    "city" => (isset($this->_param->delivery->$key->city)) ? $this->_param->delivery->$key->city : "",
                    "state" => (isset($this->_param->delivery->$key->city)) ? $this->_param->delivery->$key->state : "",
                    "zip" => $this->_param->delivery->$key->postcode,
                    "country" => $this->_param->delivery_country->alpha3_code,
                    "country_name" => $this->_param->delivery_country->short_name
                ),
                "ship_date" => date("Y-m-d", strtotime($this->_param->collection_date)),
                "extra" => array(

                ),
                "currency" => $this->_param->currency,

            );

            $this->data["package"] = array();

            foreach($this->_param->parcel as $item){
                array_push($this->data["package"], array(
                    "packaging_type" => $item->package->name,
                    "width" => $item->width,
                    "length" => $item->length,
                    "height" => $item->height,
                    "dimension_unit" => "CM",
                    "weight" => $item->weight,
                    "weight_unit" => "KG"
                ));
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
                "currency" => $this->_param->currency
            );
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
        $origin = implode(",", (array)$this->_param->collection->$key->geo_position);

        foreach($this->_param->delivery as $item)
            array_push($destinations, implode(",", (array) $item->geo_position));

        //$distanceMatrix = $this->_getDistanceMatrix($origin, $destinations, strtotime($this->_param->collection_date));

        //if($distanceMatrix->status=="success"){

            //$this->distanceMatrixInfo = $distanceMatrix->data->rows[0]->elements[0]->distance;
            //$this->durationMatrixInfo = $distanceMatrix->data->rows[0]->elements[0]->duration;

            $this->_setPostRequest();

            if(count($this->data)>0){
                $requestStr = json_encode($this->data);
                $responseStr = $this->_postRequest($requestStr);
                $response = json_decode($responseStr);

                $response = $this->_getCarrierInfo($response->rate);

                if(isset($response->status) and $response->status="error"){
                    return array("status"=>"error", "message"=>$response->message);
                }
                return array("status"=>"success",  "message"=>"Rate found","service_request_string"=>base64_encode($requestStr),"service_response_string"=>base64_encode($responseStr), "data"=>$response, "service_time"=>date("H:i", strtotime($this->_param->collection_date)),"service_date"=>date("d/M/Y", strtotime($this->_param->collection_date)));
            }else {
                return array("status"=>"error", "message"=>"Coreprime api error. Insufficient data.");
            }
        //}else{
        //    return array("status"=>"error", "message"=>"Distance matrix api error");
        //}
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
        $collection_date_time = $this->_param->service_opted->collected_by[0]->collection_date_time;

        $collection_end_at = $this->_param->service_opted->collected_by[0]->collection_end_at;

        foreach($this->_param->collection as $key=> $item){

            $execution_order++;
            $addressInfo = $this->_saveAddressData($item, $this->_param->customer_id);

            if($addressInfo["status"]=="error"){
                $this->rollBackTransaction();
                return $addressInfo;
            }
            $shipmentStatus = $this->_saveShipment($this->_param, $this->_param->collection->$key, $this->_param->parcel, $addressInfo["address_data"], $customerWarehouseId, $this->_param->company_id, $company_code, $collection_date_time, $collection_end_at, "next","COLL","NEXT","P",$execution_order);
            if($shipmentStatus["status"]=="error"){
                $this->rollBackTransaction();
                return $shipmentStatus;
            }
            if($key==0)
                $loadIdentity = $shipmentStatus["shipment_ticket"];

            foreach($this->_param->parcel as $item){
                $parcelStatus = $this->_saveParcel($shipmentStatus["shipment_id"],$loadIdentity,$customerWarehouseId,$this->_param->company_id,$company_code,$item,"P");
                if($parcelStatus["status"]=="error"){
                    $this->rollBackTransaction();
                    return $parcelStatus;
                }
            }

            $serviceStatus = $this->_saveShipmentService($this->_param->service_opted, $this->_param->service_opted->carrier->surcharges, $loadIdentity, $this->_param->customer_id);

            if($serviceStatus["status"]=="error"){
                $this->rollBackTransaction();
                return $serviceStatus;
            }

            $collectedBy = $this->_param->service_opted->carrier;
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
        //save delivery address and delivery job
        foreach($this->_param->delivery as $key=> $item){
            $execution_order++;
            $addressInfo = $this->_saveAddressData($item, $this->_param->customer_id);

            if($addressInfo["status"]=="error"){
                $this->rollBackTransaction();
                return $addressInfo;
            }

            $this->_param->delivery->$key->load_identity = $loadIdentity;
            $shipmentStatus = $this->_saveShipment($this->_param, $this->_param->delivery->$key, $this->_param->parcel, $addressInfo["address_data"], $customerWarehouseId, $this->_param->company_id, $company_code, $collection_date_time, $collection_end_at, "next","DELV","NEXT","D",$execution_order);
            if($shipmentStatus["status"]=="error"){
                $this->rollBackTransaction();
                return $shipmentStatus;
            }

            foreach($this->_param->parcel as $item){
                $parcelStatus = $this->_saveParcel($shipmentStatus["shipment_id"],$loadIdentity,$customerWarehouseId,$this->_param->company_id,$company_code,$item,"D");
                if($parcelStatus["status"]=="error"){
                    $this->rollBackTransaction();
                    return $parcelStatus;
                }
            }
        }
        $this->commitTransaction();
        return array("status"=>"success","message"=>"Shipment booked successful. Shipment ticket $loadIdentity");
    }
}
?>