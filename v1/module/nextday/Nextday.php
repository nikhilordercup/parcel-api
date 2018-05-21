<?php
final class Nextday extends Booking
{
    private $_param = array();

    protected static $_ccf = NULL;

    public

    function __construct($data){
        $this->_parentObj = parent::__construct(array("email" => $data->email, "access_token" => $data->access_token));
        $this->_param = $data;
    }

    private

    function _getCustomerCarrierAccount(){
        $result = array();
        $carrier = $this->modelObj->getCustomerCarrierAccount($this->_param->customer_id);

        foreach($carrier as $key => $item) {
            $services = $this->modelObj->getCustomerCarrierServices($this->_param->customer_id, $item["carrier_id"]);

            if(count($services)>0){
                $serviceItems = array();
                $this->services = array();
                foreach($services as $service){
                    array_push($serviceItems, $service["service_code"]);
                    $this->services[$service["service_code"]] = $service;
                }

                $result[$key] = array(
                    "name" => $item["carrier_code"]
                );

                $result[$key]["account"][] = array(
                    "credentials" => array(
                        "username" => $item["username"],
                        "password" => $item["password"],
                        "account_number" => $item["account_number"]
                    ),
                    "services" => implode(",", $serviceItems)
                );
            }
        }
        return $result;
    }

    private

    function _getCarrierInfo($data){print_r($data);die;
        $carrierInfo = $this->modelObj->getCarrierInfo($this->_param->customer_id);
        $customerCarrier = array();
        $customerCarrierService = array();

        foreach($carrierInfo as $info)
            $customerCarrier[$info["code"]] = $info;

        foreach($data as $carrier_code => $lists){
            foreach($lists as $key1 => $list){
                foreach($list as $key2 => $items){
                    foreach($items as $key3 => $item){
                        foreach($item as $service_code => $services){
                            foreach($services as $key5 => $service){
                                $service->carrier_info = $customerCarrier[$carrier_code];
                                $service->service_info = array(
                                    "code" => $this->services[$service_code]["service_code"],
                                    "name" => $this->services[$service_code]["service_name"]
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

    function _setPostRequest(){
        $this->data = array();
        $carrierLists = $this->_getCustomerCarrierAccount();

        if(count($carrierLists)>0){
            $key = 0;
            $this->data = array(
                "carriers" => $carrierLists,
                "from" => array(
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
                ),
                "to" => array(
                    "name" => "",
                    "company" => "",
                    "phone" => "",
                    "street1" => $this->_param->delivery->$key->address_line1,
                    "street2" => $this->_param->delivery->$key->address_line2,
                    "city" => $this->_param->delivery->$key->city,
                    "state" => $this->_param->delivery->$key->state,
                    "zip" => $this->_param->delivery->$key->postcode,
                    "country" => $this->_param->delivery_country->alpha3_code,
                    "country_name" => $this->_param->delivery_country->short_name
                ),
                "ship_date" => date("Y-m-d", strtotime($this->_param->collection_date)),
                "extra" => array(),
                "currency" => $this->_param->currency,

            );

            $this->data["package"] = array();

            foreach($this->_param->parcel as $item){
                array_push($this->data["package"], array(
                    "packaging_type" => $item->package,
                    "width" => $item->width,
                    "length" => $item->length,
                    "height" => $item->height,
                    "dimension_unit" => "CM",
                    "weight" => $item->weight,
                    "weight_unit" => "KG"
                ));
            }

            $this->data["transit"][] = array(
                "transit_distance" => $this->distanceMatrixInfo->value,
                "transit_time" => $this->durationMatrixInfo->value,
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

        $distanceMatrix = $this->_getDistanceMatrix($origin, $destinations, strtotime($this->_param->collection_date));

        if($distanceMatrix->status=="success"){

            $this->distanceMatrixInfo = $distanceMatrix->data->rows[0]->elements[0]->distance;
            $this->durationMatrixInfo = $distanceMatrix->data->rows[0]->elements[0]->duration;

            $this->_setPostRequest();
            if(count($this->data)>0){
                $response = json_decode($this->_postRequest($this->data));
return json_decode(json_encode($response),true);
                $response = $this->_getCarrierInfo($response->rate);
                return array("status"=>"success",  "message"=>"Rate found", "data"=>$response, "service_time"=>date("H:i", strtotime($this->_param->collection_date)),"service_date"=>date("d/M/Y", strtotime($this->_param->collection_date)));
            }else {
                return array("status"=>"error", "message"=>"Coreprime api error. Insufficient data.");
            }
        }else{
            return array("status"=>"error", "message"=>"Distance matrix api error");
        }
    }

    public

    function saveBooking(){
        $accountStatus = $this->_checkCustomerAccountStatus($this->_param->customer_id);
        if($accountStatus["status"]=="error"){
            return $accountStatus;
        }
//print_r($this->_param->parcel);die;

        $company_code = $this->_getCompanyCode($this->_param->company_id);

        $this->startTransaction();
        //save collection address and collection job
        $execution_order = 0;
        foreach($this->_param->collection as $key=> $item){
            $execution_order++;
            $addressInfo = $this->_saveAddressData($item, $this->_param->customer_id);
            if($addressInfo["status"]=="error"){
                $this->rollBackTransaction();
                return $addressInfo;
            }
            $shipmentStatus = $this->_saveShipment($this->_param, $this->_param->collection->$key, $this->_param->parcel, $addressInfo["address_data"], $this->_param->company_id, $company_code, $this->_param->collection_date, "next","COLL","NEXT","P",$execution_order);
            if($shipmentStatus["status"]=="error"){
                $this->rollBackTransaction();
                return $shipmentStatus;
            }
            if($key==0)
                $loadIdentity = $shipmentStatus["shipment_ticket"];

            foreach($this->_param->parcel as $item){
                $parcelStatus = $this->_saveParcel($shipmentStatus["shipment_id"],$shipmentStatus["shipment_ticket"],$this->_param->company_id,$company_code,$item,"P");
                if($parcelStatus["status"]=="error"){
                    $this->rollBackTransaction();
                    return $parcelStatus;
                }
            }
        }


        //save delivery address and delivery job
        foreach($this->_param->delivery as $key=> $item){
            print_r($this->_param->delivery->$key);die;
            $execution_order++;
            $addressInfo = $this->_saveAddressData($item, $this->_param->customer_id);
            if($addressInfo["status"]=="error"){
                $this->rollBackTransaction();
                return $addressInfo;
            }
            $shipmentStatus = $this->_saveShipment($this->_param, $this->_param->delivery->$key, $this->_param->parcel, $addressInfo["address_data"], $this->_param->company_id, $company_code, $this->_param->collection_date, "next","DELV","NEXT","D",$execution_order);
            if($shipmentStatus["status"]=="error"){
                $this->rollBackTransaction();
                return $shipmentStatus;
            }
        }

        $this->commitTransaction();
    }
}
?>