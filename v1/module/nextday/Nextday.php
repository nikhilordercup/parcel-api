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
        $this->collectionModel->getJobCollectionList($carriers, $address, $this->_param->customer_id, $this->_param->company_id, $this->_param->collection_date);
    }

    private

    function _getCustomerCarrierAccount(){
        $result = array();
        $carrier = $this->modelObj->getCustomerCarrierAccount($this->_param->company_id, $this->_param->customer_id);
        $this->carrierList = array();

        foreach($carrier as $key => $item) {
            $services = $this->modelObj->getCustomerCarrierServices($this->_param->customer_id, $item["carrier_id"]);

            if(count($services)>0){
                $serviceItems = array();
                $this->services = array();
                foreach($services as $service){
                    array_push($serviceItems, $service["service_code"]);
                    $this->services[$service["service_code"]] = $service;
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
                            "services"    => implode(",", $serviceItems)
                        )
                    )
                ));
                /*$result[$key] = array(
                    "name" => $item["carrier_code"]
                );

                $result[$key]["account"][] = array(
                    "credentials" => array(
                        "username" => $item["username"],
                        "password" => $item["password"],
                        "account_number" => $item["account_number"]
                    ),
                    "services" => implode(",", $serviceItems)
                );*/

                $this->carrierList[$item["carrier_id"]] = $item;
            }
        }
        return $result;
    }

    private

    function _getCarrierInfo($data){
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
        /*$test1 = '{"carriers":[{"name":"UKMAIL","account":[{"credentials":{"username":"developers@ordercup.com","password":"B069807","account_number":"D919022"},"services":"1,2,3,4,5,9"}]}],"from":{"name":"","company":"","phone":"","street1":"Flat 1","street2":"Penfold Court","city":"Oxford","state":"Oxfordshire","zip":"OX3 9RL","country":"GBR","country_name":"United Kingdom"},"to":{"name":"","company":"","phone":"","street1":null,"street2":null,"city":null,"state":null,"zip":"OX39 4PU","country":"GBR","country_name":"United Kingdom"},"ship_date":"2018-05-21","extra":[],"currency":"GBP","package":[{"packaging_type":"Parcels","width":5,"length":5,"height":5,"dimension_unit":"CM","weight":5,"weight_unit":"KG"}],"transit":[{"transit_distance":27984,"transit_time":1670,"number_of_collections":0,"number_of_drops":0,"total_waiting_time":0}],"insurance":{"value":0,"currency":"GBP"}}';

        $this->_getCustomerCarrierAccount();

        $test1 = json_decode($test1);

        //print_r($this->_param->customer_id);die;

        $response = json_decode($this->_postRequest($test1));

        $response = $this->_getCarrierInfo($response->rate);


        return json_decode(json_encode($response),true);*/



        //$response = '{"rate":{"UKMAIL":[{"D919022":[{"1":[{"rate":{"price":"5.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":"CM"},"weight":{"weight":9999,"unit":"KG"},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0.0},"taxes":{"total_tax":0.525,"tax_percentage":10.0}}]},{"5":[{"rate":{"price":"25.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":"CM"},"weight":{"weight":9999,"unit":"KG"},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0.0},"taxes":{"total_tax":2.525,"tax_percentage":10.0}}]},{"2":[{"rate":{"price":"9.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":"CM"},"weight":{"weight":9999,"unit":"KG"},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0.0},"taxes":{"total_tax":0.925,"tax_percentage":10.0}}]},{"3":[{"rate":{"price":"15.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":"CM"},"weight":{"weight":9999,"unit":"KG"},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0.0},"taxes":{"total_tax":1.525,"tax_percentage":10.0}}]},{"9":[{"rate":{"price":"13.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":"CM"},"weight":{"weight":9999,"unit":"KG"},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0.0},"taxes":{"total_tax":1.325,"tax_percentage":10.0}}]},{"4":[{"rate":{"price":"10.25","rate_type":"Weight","act_number":"D919022","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":9999,"width":9999,"height":9999,"unit":"CM"},"weight":{"weight":9999,"unit":"KG"},"time":{"max_waiting_time":null,"unit":null},"category":"","charge_from_base":null,"icon":"/icons/original/missing.png","max_delivery_time":null},"surcharges":{"long_length_surcharge":0,"manual_handling_surcharge":0.0},"taxes":{"total_tax":1.025,"tax_percentage":10.0}}]}]}]}}';

        //$response = json_decode($response);

        //$response = $this->customerccf->calculate($response, 2, $this->_param->customer_id, $this->_param->company_id);

        //print_r($response);die;



        //$this->_getJobCollectionList("OX3 9RL");







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

            $this->distanceMatrixInfo = $distanceMatrix->data->rows[0]->elements[0]->distance;
            $this->durationMatrixInfo = $distanceMatrix->data->rows[0]->elements[0]->duration;

            $this->_setPostRequest();

            if(count($this->data)>0){
//print_r($this->param);

                $this->_getJobCollectionList($this->carrierList, $this->data["from"]);


                $response = json_decode($this->_postRequest($this->data));
//print_r($response);die;
                $response = $this->_getCarrierInfo($response->rate);

                return array("status"=>"success",  "message"=>"Rate found", "data"=>$response, "service_time"=>date("H:i", strtotime($this->_param->collection_date)),"service_date"=>date("d/M/Y", strtotime($this->_param->collection_date)));
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