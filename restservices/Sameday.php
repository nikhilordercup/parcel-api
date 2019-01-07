<?php
class Sameday extends  Booking
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
    }

    /*  get a Quote start here*/
    private function _filterApiResponse($input,$customer_id,$company_id,$charge_from_warehouse,$is_tax_exempt){
        if (is_array($input)) {
            $temparray = array();
            $returnarray = array();
            foreach ($input["rate"] as $carriercode => $service_list) {
                foreach ($service_list as $service_key => $list) {
                    foreach ($list as $accountkey => $accountdata) {
                        foreach ($accountdata as $key => $serviceitem) {
                            foreach ($serviceitem as $servicecode => $servicedata) {
                                foreach ($servicedata as $serkey => $item) {
                                    if (isset($item["rate"]["error"])) {
                                        unset($input["rate"][$carriercode][$service_key][$accountkey][$key]);
                                        if (count($input["rate"][$carriercode][$service_key][$accountkey]) == 0) {
                                            unset($input["rate"][$carriercode][$service_key][$accountkey]);
                                        }
                                    }
                                    else{
                                        $total_surcharge = 0;
                                        $base_price = 0;
                                        $total_price = 0;
                                        $total_tax = 0;
                                        $carrier = $this->modelObjCorePrime->getCarrierIdByCode($company_id,$customer_id,$accountkey);
                                        $res = $this->_calculateSamedayServiceccf($servicecode,$item['rate'],$carrier['id'],$customer_id,$company_id);
                                        $res['service_options'] = $item['service_options'];
                                        //$res['taxes'] = $item['taxes'];
                                        $res['taxes'] = ($is_tax_exempt)?array():$item['taxes'];
                                        $res['info']['accountkey'] = $accountkey;
                                        $res['ccf_surcharges'] = new StdClass();
                                        $res['ccf_surcharges']->alldata = array();
                                        foreach ($item['surcharges'] as $surcharge_code => $price) {
                                            $sur =     $this->_calculateSamedaySurchargeccf($surcharge_code, $customer_id, $company_id, $carrier['id'],$price);
                                            $res['surcharges'][$surcharge_code] = $sur['surcharges'][$surcharge_code];
                                            $res['ccf_surcharges']->alldata[$surcharge_code] = $sur['ccf_surcharges'];
                                        }
                                        $base_price = $res['price'];
                                        if(isset($res['ccf_surcharges'])){
                                            foreach($res['ccf_surcharges']->alldata as $surcharge_name=>$surcharge_val){
                                                $total_surcharge += $surcharge_val['price'];
                                            }
                                        }
                                        $price_without_tax = number_format($total_surcharge + $base_price,2,'.','');
                                        $customer_tax_amt = 0;
                                        if(isset($res['taxes'])){
                                            $temparray["rate"][$carriercode][$key]["taxes"] = $res['taxes'];
                                            $total_tax_val  = isset($res['taxes']['tax_percentage'])?$res['taxes']['tax_percentage']:0;
                                            $customer_tax_amt = number_format((($price_without_tax *$total_tax_val)/100),2,'.','');
                                        }
                                        $total_price = number_format($total_surcharge + $base_price + $customer_tax_amt,2,'.','');
                                        $temparray["rate"][$carriercode][$key]["pricewithouttax"] = $price_without_tax;
                                        $temparray["rate"][$carriercode][$key]["chargable_tax"] = $customer_tax_amt;
                                        $temparray["rate"][$carriercode][$key]["service_name"] = $res['service_name'];
                                        $temparray["rate"][$carriercode][$key]["charge_from_base"] =  ($charge_from_warehouse)?'1':'0';
                                        $temparray["rate"][$carriercode][$key]["rate_type"] = $res['rate_type'];
                                        $temparray["rate"][$carriercode][$key]["message"] = $res['message'];
                                        $temparray["rate"][$carriercode][$key]["currency"] = $res['currency'];
                                        $temparray["rate"][$carriercode][$key]["total_price"] = $total_price;
                                        $temparray["rate"][$carriercode][$key]["otherinfo"] = $res['info'];
                                        $temparray["rate"][$carriercode][$key]["base_price"] = $base_price;
                                        $temparray["rate"][$carriercode][$key]["icon"] = $res['service_options']['icon'];
                                        $temparray["rate"][$carriercode][$key]["max_delivery_time"] = $res['service_options']['max_delivery_time'];
                                        $temparray["rate"][$carriercode][$key]["dimensions"] = $res['service_options']['dimensions'];
                                        $temparray["rate"][$carriercode][$key]["weight"] = $res['service_options']['weight'];
                                        $temparray["rate"][$carriercode][$key]["time"] = $res['service_options']['time'];
                                        $temparray["rate"][$carriercode][$key]["surcharges"] = array();
                                        $temparray["rate"][$carriercode][$key]["surchargesinfo"] = array();
                                        if(isset($res['surcharges'])){
                                            $temparray["rate"][$carriercode][$key]["surcharges"] = $res['surcharges'];
                                            $temparray["rate"][$carriercode][$key]["surchargesinfo"] = $res['ccf_surcharges']->alldata;
                                        }
                                        $returnarray["rate"][$carriercode][] = $temparray["rate"][$carriercode][$key];
                                    }
                                }
                            }
                        }
                    }
                }
            }
            return json_decode(json_encode($returnarray));
        } else {
            return array();
        }
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
    private function _calculateSamedayServiceccf($serviceCode,$service, $courier_id, $customer_id, $company_id){
        $service_ccf_price = $this->customerccf->calculateServiceCcf($serviceCode,$service['price'], $courier_id, $customer_id, $company_id);
        $service_ccf_price['courier_id'] = $courier_id;
        $service['price'] = $service_ccf_price["price"] + $service['price'];
        $service['service_name'] =
            isset($service_ccf_price["company_service_name"]) ?
                $service_ccf_price["company_service_name"] : $service_ccf_price["courier_service_name"];
        $service['service_code'] =
            isset($service_ccf_price["company_service_code"]) ?
                $service_ccf_price["company_service_code"] : $service_ccf_price["courier_service_code"];
        $service['info'] = $service_ccf_price;
        $service['price_with_ccf'] = $service['price'];
        return $service;
    }
    private function _calculateSamedaySurchargeccf($surcharge_code, $customer_id, $company_id, $courier_id,$price){
        $tempdatasurcharge = array();
        $surcharge_ccf_price = $this->customerccf->calculateSurchargeCcf($surcharge_code, $customer_id, $company_id, $courier_id,$price);
        $surcharge_ccf_price["original_price"] = $price;
        $tempdatasurcharge['surcharges'][$surcharge_code] = $surcharge_ccf_price['price'] +$price;
        $datatemp = array('price' => $surcharge_ccf_price['price'] + $price, 'info' => $surcharge_ccf_price,'price_with_ccf'=>$surcharge_ccf_price['price'] + $price);
        $tempdatasurcharge['ccf_surcharges'] = $datatemp;
        return $tempdatasurcharge;

    }
    private function _getWbFormat($response,$transit,$param,$endpoint,$googleRequest){
       $reqSessionId        = date('dmY').microtime(true);
       $savedTempService    = array('session_id'=>$reqSessionId,'request'=>json_encode((array)$param),'status'=>1,'create_date'=>date('Y-m-d'),'create_time'=>date('H:m:s'),'end_point'=>$endpoint,'request_status'=>'NC');
       $responsedata        = array('quotation_ref'=>$reqSessionId);
       foreach($response->rate as $key=>$val){
             foreach($val as $key1=>$val2){
                  $temp = array();
                  $temp['service_code']         = $val2->otherinfo->courier_service_code;
                  $temp['service_name']         = $val2->otherinfo->courier_service_name;
                  $temp['service_id']           = $val2->otherinfo->service_id;
                  //$temp['act_number']           = $val2->otherinfo->accountkey;
                  $temp['price']                = $val2->base_price;
                  $temp['max_delivery_time']    = $val2->max_delivery_time;
                  $temp['max_waiting_time']     = $val2->time->max_waiting_time .' '. $val2->time->unit;
                  $temp['surcharges']           = array_sum((array)$val2->surcharges);
                  $temp['taxes']                = $val2->chargable_tax;
                  $temp['total']                = $val2->total_price;
                  $temp['carrier']              = $key;
                  $temp['transit_distance']     = $transit['transit_distance'];
                  $temp['transit_time']         = $transit['transit_time'];
                  $temp['transit_distance_text'] = $transit['transit_distance_text'];
                  $temp['transit_time_text']    = $transit['transit_time_text'];
                  $responsedata['services'][]   = $temp;
                  $savedTempService['response'][$val2->otherinfo->service_id] = $val2;
             }
          }
        $savedTempService['response'] = json_encode($savedTempService['response']);
        $this->resrServiceModel->addContent('webapi_request_response',$savedTempService);
        return $responsedata;
   }
    public  function getSameDayQuotation($param){
        $endpoint      = $this->endpoint;
        $param->customer_id = $param->customer_id;
        $googleRequest = array();
        if(!isset($param->service_date) || ($param->service_date=='') || !($this->isValidServiceDate($param->service_date))){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'service date and time missing';
                 $response["error_code"] = "ERROR0018";
                 return $response;
        }elseif(!isset($param->warehouse_id) || ($param->warehouse_id=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'warehouse id missing.';
                 $response["error_code"] = "ERROR0064";
                 return $response;
        }elseif(!isset($param->warehouse_latitude) || ($param->warehouse_latitude=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'warehouse latitude missing.';
                 $response["error_code"] = "ERROR0065";
                 return $response;
        }elseif(!isset($param->warehouse_longitude) || ($param->warehouse_longitude=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'warehouse longitude missing.';
                 $response["error_code"] = "ERROR0066";
                 return $response;
        }elseif(!isset($param->collection) || ($param->collection=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Collection key missing';
                 $response["error_code"] = "ERROR007";
                 return $response;
        }
        elseif(!isset($param->collection->address) || ($param->collection->address=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Collection address missing';
                 $response["error_code"] = "ERROR0041";
                 return $response;
        }
        elseif(!isset($param->collection->address->postcode) || ($param->collection->address->postcode=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Collection Postcode parameter missing';
                 $response["error_code"] = "ERROR008";
                 return $response;
        }
        /*elseif(!isset($param->collection->address->country_code)  || ($param->collection->address->country_code=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Collection country code parameter missing';
                 $response["error_code"] = "ERROR009";
                 return $response;
        } */
        /*elseif(!isset($param->collection->address->currency_code) || ($param->collection->address->currency_code=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Collection currency code parameter missing';
                 $response["error_code"] = "ERROR010";
                 return $response;
        } */
        /*elseif(!isset($param->collection->country) || ($param->collection->country=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Collection country parameter missing';
                 $response["error_code"] = "ERROR0011";
                 return $response;
        }*/
        else{

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
                 $response["error_code"] = "ERROR0012";
                 return $response;
        }
        elseif(empty((array)$param->delivery)){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery data missing';
                 $response["error_code"] = "ERROR0013";
                 return $response;
        }
        else{
          foreach($param->delivery as $key=>$val){
             if(!isset($val->address)  || ($val->address=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery Postcode parameter missing';
                 $response["error_code"] = "ERROR0042";
                 return $response;
            }
             elseif(!isset($val->address->postcode)  || ($val->address->postcode=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery Postcode parameter missing';
                 $response["error_code"] = "ERROR0014";
                 return $response;
            }
            /*elseif(!isset($val->address->country_code) || ($val->address->country_code=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery country code parameter missing';
                 $response["error_code"] = "ERROR0015";
                 return $response;
            }
              elseif(!isset($val->address->currency_code)  || ($val->address->currency_code=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery currency code parameter missing';
                 $response["error_code"] = "ERROR016";
                 return $response;
            } */
            elseif(!isset($val->address->country)  || ($val->address->country=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery country parameter missing.';
                 $response["error_code"] = "ERROR0017";
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
        }
        $googleRequest['service_date'] = $param->service_date;
        $googleRequest['warehouse_id'] = $param->warehouse_id;
        $googleRequest['warehouse_latitude'] = $param->warehouse_latitude;
        $googleRequest['warehouse_longitude'] = $param->warehouse_longitude;
        $googleRequest['company_id'] = $param->company_id;


        try{
              $distanceMatrixData = json_decode(json_encode($this->googleApi->getGeolocationAndDistanceMatrix( (object) $googleRequest)), FALSE);
            }catch(Exception $e){
                    $response = array();
                    $response["status"] = "fail";
                    $response["message"] = 'Distance Matrix API down, try again';
                    $response["error_code"] = "ERROR0058";
                    $response["api_message"] = $e->getMessage();
                    return $response;
            }
        $transit_distance = $distanceMatrixData->transit_distance;
        $transit_time = $distanceMatrixData->transit_time;
        $transit_distance_text = $distanceMatrixData->transit_distance_text;
        $transit_time_text = $distanceMatrixData->transit_time_text;
        $chargefromBase = $this->modelObjCorePrime->getCustomerChargeFromBase($param->customer_id);
        $isTaxExempt    = $this->modelObjCorePrime->getTaxExemptStatus($param->customer_id);
        $waypointCount = 0;
        if (isset($distanceMatrixData->waypoint_lists)) {
            $waypointCount = count($distanceMatrixData->waypoint_lists);
        }
        $charge_from_warehouse = ($chargefromBase['charge_from_base']=='YES')?true:false;//  true;
        $is_tax_exempt = ($isTaxExempt['tax_exempt']=='YES')?true:false;//  true;

        if ($charge_from_warehouse) {
            $transit_distance += $distanceMatrixData->warehouse_to_collection_point_distance;
            $transit_time += $distanceMatrixData->warehouse_to_collection_point_time;
        }
         $carrier = array();
         $carriers =  array();
        if(isset($param->service_code)){
            $carrierCode = (isset($param->carrier_id) && ($param->carrier_id)>0)?$param->carrier_id:0;
            $carrier = $this->modelObjCorePrime->getCustomerCarrierDataByServiceId($param->customer_id,$param->service_id, $param->company_id,$carrierCode);
        }else{
            $carrier = $this->modelObjCorePrime->getCustomerInternalCarrierData($param->customer_id, $param->company_id);
        }

        if(count($carrier)>0){
            foreach($carrier as $carrierData){
                if(isset($param->service_id)){
                 $service = $this->modelObjCorePrime->getCustomerSamedayServiceDataFromServiceId($param->customer_id, $param->company_id, $carrierData['courier_id'],$param->service_id);
                }else{
                 $service = $this->modelObjCorePrime->getCustomerSamedayServiceData($param->customer_id, $param->company_id, $carrierData['courier_id']);
                }
                if(count($service)>0){
                    $tempservice = array();
                    foreach($service as $key=>$valData){
                        $tempservice[] = $valData['service_code'];
                    }
                    $carriers[$carrierData['code']][] =  array('credentials'=>array('username'=>'','password'=>'','account_number'=>$carrierData['account_number']),'services'=>implode(',',$tempservice));
                }else{
                    $response = array();
                    $response["status"] = "fail";
                    $response["message"] = 'Service Not configured or disabled for this customer';
                    $response["error_code"] = "ERROR0078";
                    return $response;
                }
            }
        }
        else{
            $response = array();
            $response["status"] = "fail";
            $response["message"] = 'Carrier Not configured or disabled for this customer';
            $response["error_code"] = "ERROR0019";
            return $response;
        }

        $post_data = [];
        foreach($carriers as $key=>$val){
            $post_data["carriers"][] = array('name'=>$key,'account'=>$val);
        }
        $post_data["from"] = array(
            "zip" => $distanceMatrixData->origin->collection_postcode,
            "country" => "GBR"
        );
        $post_data["to"] = array(
            "zip" => $distanceMatrixData->origin->delivery_postcode,
            "country" => "GBR"
        );
        $post_data["transit"] = array(array(
            "transit_distance" => number_format($transit_distance / 1609.344, 2), // coreprime api require miles
            "transit_time" => $transit_time / 60 % 60,//$transit_time, //coreprime api require minutes
            "number_of_collections" => $distanceMatrixData->number_of_collections,
            "number_of_drops" => $waypointCount + 1,
            "total_waiting_time" => $distanceMatrixData->total_waiting_time
        ));

        $post_data["ship_date"] = date("Y-m-d h:i", strtotime($param->service_date));
        $post_data["currency"] = $carrierData['currency'];
        $post_data["package"] = [array(
            "packaging_type" => "your_packaging"
        )];
        $post_data["extra"] = [];
        $post_data["insurance"] = []; //print_r($post_data);die;
        $data = $this->_filterApiResponse(json_decode($this->_postRequest($post_data),true),$param->customer_id, $param->company_id,$charge_from_warehouse,$is_tax_exempt);
        $available_credit = $this->_getCustomerAccountBalence($param->customer_id,0);
		if(!empty($data)){
            $transitdata   =  array();
            $transitdata['transit_distance'] = $transit_distance;
            $transitdata['transit_time'] = $transit_time;
            $transitdata['transit_distance_text'] = $transit_distance_text;
            $transitdata['transit_time_text'] = $transit_time_text;
            $transitdata['number_of_collections'] = $post_data["transit"][0]['number_of_collections'];
            $transitdata['number_of_drops'] = $post_data["transit"][0]['number_of_drops'];
            $transitdata['total_waiting_time'] = $post_data["transit"][0]['total_waiting_time'];
            $param->transit_distance        = $transit_distance;
            $param->transit_time            = $transit_time;
            $param->transit_distance_text   = $transit_distance_text;
            $param->transit_time_text       = $transit_time_text;
            $data = $this->_getWbFormat($data,$transitdata,$param,$endpoint,$googleRequest);
           return array("status" => "success","rate"=>$data,"service_date"=>$param->service_date,"availiable_balence" => $available_credit['available_credit']);
        }else{
             return array("status" => "fail","rate"=>array(),"service_date"=>$param->service_date,"availiable_balence" => $available_credit['available_credit']);
        }
     }
    /*  get a Quote end here*/

     /*  Book a Quote start here*/
    public      function validateCollectionAddress(){
        $response = array();
        if(!isset($this->bookparam->collection) || ($this->bookparam->collection=='')){
                 //$response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Collection key missing';
                 $response["error_code"] = "ERROR007";
                // exit();
        }
        elseif(!isset($this->bookparam->collection->address) || ($this->bookparam->collection->address=='')){
                 //$response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Collection address missing';
                 $response["error_code"] = "ERROR0041";
                 //return $response;
                // exit();
        }
        elseif(!isset($this->bookparam->collection->address->postcode) || ($this->bookparam->collection->address->postcode=='')){
                 //$response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Collection Postcode parameter missing';
                 $response["error_code"] = "ERROR008";
                 //return $response;
                 //exit();
        }
        /*elseif(!isset($this->bookparam->collection->address->country_code)  || ($this->bookparam->collection->address->country_code=='')){
                 //$response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Collection country code parameter missing';
                 $response["error_code"] = "ERROR009";
                // return $response;
                 // exit();
        } */
        /*elseif(!isset($this->bookparam->collection->address->currency_code) || ($this->bookparam->collection->address->currency_code=='')){
                 //$response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Collection currency code parameter missing';
                 $response["error_code"] = "ERROR010";
                 //return $response;
                 // exit();
        } */
        /*elseif(!isset($this->bookparam->collection->country) || ($this->bookparam->collection->country=='')){
                 //$response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Collection country parameter missing';
                 $response["error_code"] = "ERROR0011";
                 //return $response;
                 // exit();
        }*/
        else{
             //$collectionGeo = $this->getGeoLocationByPostCode($this->bookparam->collection->address->postcode);
              //if($collectionGeo['status'] != 'success'){
                //$response =  $collectionGeo;
                //exit();
              //}else{
                 $response["status"] = "success";
                 //return $response;
            //}
        }
        return $response;
    }
    public      function validateDeliveryAddress(){
         $response = array();
     if(!isset($this->bookparam->delivery)){
                // $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery key missing';
                 $response["error_code"] = "ERROR0012";
                 //return $response;
                 exit();
        }
     elseif(empty((array)$this->bookparam->delivery)){
                 //$response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery data missing';
                 $response["error_code"] = "ERROR0013";
                // return $response;
                 exit();
        }
     else{
          foreach($this->bookparam->delivery as $key=>$val){
             if(!isset($val->address)  || ($val->address=='')){
                 //$response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery Postcode parameter missing';
                 $response["error_code"] = "ERROR0042";
                 //return $response;
                 break;
            }
             elseif(!isset($val->address->postcode)  || ($val->address->postcode=='')){
                 //$response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery Postcode parameter missing';
                 $response["error_code"] = "ERROR0014";
                 //return $response;
                 break;
            }
            /*elseif(!isset($val->address->country_code) || ($val->address->country_code=='')){
                 //$response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery country code parameter missing';
                 $response["error_code"] = "ERROR0015";
                 //return $response;
                 break;
            }
              elseif(!isset($val->address->currency_code)  || ($val->address->currency_code=='')){
                 //$response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery currency code parameter missing';
                 $response["error_code"] = "ERROR016";
                 //return $response;
                 break;
            } */
            elseif(!isset($val->address->country)  || ($val->address->country=='')){
                 //$response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery country parameter missing';
                 $response["error_code"] = "ERROR0017";
                 //return $response;
                 break;
            }else{
                //$deliveryGeo = $this->getGeoLocationByPostCode($val->address->postcode);
                //if($deliveryGeo['status'] != 'success'){
                    //$response =  $deliveryGeo;
                    //return $deliveryGeo;
                  //  break;
                //}else{
                   //$response = array();
                   $response["status"] = "success";
                   //return $response;
                //}
             }
           }
        }
    return $response;
    }
    public      function validateQuotation($current_request,$pre_request){
        $response = array();
        $temparray = array();
        if($current_request->service_date  != $pre_request->service_date){
             $response["status"] = "fail";
             $response["message"] = 'Requested Service date and time is mismatch with Quotation';
             $response["error_code"] = "ERROR0044";
        }
        elseif(count($current_request->collection)!=count($pre_request->collection)){
             $response["status"] = "fail";
             $response["message"] = 'Requested Collection mismatch with Quotation';
             $response["error_code"] = "ERROR0045";
        }
        elseif(count($current_request->delivery)!=count($pre_request->delivery)){
             $response["status"] = "fail";
             $response["message"] = 'Requested Delivery mismatch with Quotation';
             $response["error_code"] = "ERROR0045";
        }
        elseif(!isset($current_request->collection->address->country) || ($current_request->collection->address->country=='')){
             $response["status"] = "fail";
             $response["message"] = 'country is missing in collection address.';
             $response["error_code"] = "ERROR0046";
        }
        elseif(!isset($current_request->collection->address->postcode) || ($current_request->collection->address->postcode=='')){
             $response["status"] = "fail";
             $response["message"] = 'postcode is missing in collection address.';
             $response["error_code"] = "ERROR0047";
        }
        elseif(!isset($current_request->collection->address->address_line1) || ($current_request->collection->address->address_line1=='')){
             $response["status"] = "fail";
             $response["message"] = 'address Line 1 is missing in collection address.';
             $response["error_code"] = "ERROR0048";
        }elseif($current_request->collection->address->country != $pre_request->collection->address->country){
             $response["status"] = "fail";
             $response["message"] = 'collection country mismatch with Quotation';
             $response["error_code"] = "ERROR0070";
        }elseif($current_request->collection->address->postcode != $pre_request->collection->address->postcode){
             $response["status"] = "fail";
             $response["message"] = 'collection postcode mismatch with Quotation';
             $response["error_code"] = "ERROR0071";
        }else{
        foreach($current_request->delivery as $key=>$val){
            if(!isset($val->address)  || ($val->address=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery address parameter missing in Quotation';
                 $response["error_code"] = "ERROR0073";
                 return $response;
             }
             elseif(!isset($val->address->postcode)  || ($val->address->postcode=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery Postcode parameter missing.';
                 $response["error_code"] = "ERROR0074";
                 return $response;
            }elseif($val->address->postcode != $pre_request->delivery[$key]->address->postcode){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'requested delivery postcode are mismatch or order mismatch';
                 $response["error_code"] = "ERROR0075";
                 return $response;
            }elseif(!isset($val->address->country)  || ($val->address->country=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery country parameter missing';
                 $response["error_code"] = "ERROR0076";
                 return $response;
            }elseif($val->address->country != $pre_request->delivery[$key]->address->country){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'requested delivery country are mismatch or order mismatch';
                 $response["error_code"] = "ERROR0077";
                 return $response;
            }
        }
            $current_request->collection->address->latitude = $pre_request->collection->address->latitude;
            $current_request->collection->address->longitude = $pre_request->collection->address->longitude;
            $current_request->collection->address->geo_position = $pre_request->collection->address->geo_position;
            $current_request->collection->address->address_origin = 'api';
            $current_request->collection->address->name  = $current_request->collection->consignee->name;
            $current_request->collection->address->email = $current_request->collection->consignee->email;
            $current_request->collection->address->phone = $current_request->collection->consignee->phone;
            $temparray['collection_address'][] = $current_request->collection->address;
            foreach($current_request->delivery as $key=>$vals){
                if($vals->address->postcode != $pre_request->delivery[$key]->address->postcode){
                    $response["status"] = "fail";
                    $response["message"] = 'Delivery postcode are missmatch in delivery address.';
                    $response["error_code"] = "ERROR0049";
                    break;
                }else{
                    $vals->address->latitude = $pre_request->delivery[$key]->address->latitude;
                    $vals->address->longitude = $pre_request->delivery[$key]->address->longitude;
                    $vals->address->geo_position = $pre_request->delivery[$key]->address->geo_position;
                    $vals->address->address_origin = 'api';
                    $vals->address->name = $current_request->delivery[$key]->consignee->name;
                    $vals->address->email = $current_request->delivery[$key]->consignee->email;
                    $vals->address->phone = $current_request->delivery[$key]->consignee->phone;
                    $temparray['delivery_address'][] = $vals->address;
                    $response["status"] = "success";
                }
             }
          }
        if($response["status"] == 'success'){
           return array('status'=>'success','data'=>$temparray);
        }
        else{
           return $response;
        }

    }
    protected   function _manageAccountsDetails($priceServiceid,$load_identity, $customer_id,$company_id,$grandTotal=0){
          $priceData = $this->resrServiceModel->getBookedShipmentsPrice($customer_id);
          if(isset($grandTotal)){
                 $creditbalanceData = array();
                 $creditbalanceData['customer_id']          = $customer_id;
                 $creditbalanceData['customer_type']        = $priceData['customer_type'];
                 $creditbalanceData['company_id']           = $company_id;
                 $creditbalanceData['payment_type']         = 'DEBIT';
                 $creditbalanceData['pre_balance']          = $priceData["available_credit"];
                 $creditbalanceData['amount']               = $grandTotal;
                 $creditbalanceData['balance']              = $priceData["available_credit"] - $grandTotal;
                 $creditbalanceData['create_date']          = date("Y-m-d");
                 $creditbalanceData['payment_reference']    = $load_identity;
                 $creditbalanceData['payment_desc']         = 'BOOK A SHIPMENT';
                 $creditbalanceData['payment_for']          = 'BOOKSHIP';

                 $addHistory = $this->modelObj->saveAccountHistory($creditbalanceData);
                  if($addHistory>0){
                      $condition = "user_id = '".$customer_id."'";
                      $updateStatus = $this->modelObj->editAccountBalance(array('available_credit'=>$creditbalanceData['balance']),$condition);
                      if($updateStatus){
                          return array("status"=>"success", "message"=>"Price Update save");
                      }
                  }
        }
          return array("status"=>"error", "message"=>"shipment service not saved");
    }
    public      function bookSameDayShipment($data){
        $carrier_id = $data->service_detail->otherinfo->courier_id;
        $accountStatus = $this->_checkCustomerAccountStatus($data->customer_id);
        $carrierCode =  $this->_getCarrierCode($carrier_id);
        $carrierCode = $carrierCode["carrier_code"];
        $accountOfCarrier = $data->service_detail->otherinfo->accountkey;

        if($accountStatus["status"]!="error"){
            $bookingShipPrice = $data->service_detail->total_price;
            $available_credit = $this->_getCustomerAccountBalence($data->customer_id,$bookingShipPrice);
            if($available_credit["status"]=="error"){
                return array("status"=>"error", "message"=>"You don't have sufficient balance");
            }
            $shipmentData = array();
            $service_id = false;
            $shipmentId = 0;
            $timestamp = strtotime("now");
            $transit_time = $data->transit_time;
            $transit_distance = $data->transit_distance;
            $this->company_id = $data->company_id;
            $this->warehouse_id = $data->warehouse_id;
			$this->service_date = date("Y-m-d H:i:s", strtotime($data->service_date));
            $loadIdentity = "";
            $counter = 1;
            $this->db->startTransaction();
            foreach($data->collection_address as $shipment_data){
                $shipmentData = $this->_prepareShipmentData(
                        array("collection_user_id"      =>$data->customer_id,
                              "shipment_data"           =>$shipment_data,
                              "timestamp"               =>$timestamp,
                              "customer_id"             =>$data->customer_id,
                              "availabilityTypeCode"    =>"UNKN",
                              "availabilityTypeName"    =>"Unknown",
                              "file_name"               =>"",
                              "loadGroupTypeName"       =>"Same",
                              "loadGroupTypeCode"       =>"Same",
                              "isDutiable"              =>"false",
                              "jobTypeName"             =>"Collection",
                              "jobTypeCode"             =>"COLL",
                              "shipment_service_type"   =>"P",
                              "icargo_execution_order"  =>$counter,
                              "service_date"            =>$this->service_date,
                              "shipment_executionOrder" =>$counter,
                              "warehouse_id"            =>$data->warehouse_id,
                              "customer_id"             =>$data->customer_id,
                              "userid"                  =>$data->userid,
                              "notification"            =>false,
                              "shipment_instruction"    =>$shipment_data->notes,
                              "carrier_code"            =>$carrierCode,
                              "account_number"          =>$accountOfCarrier
                    ));
                $shipmentStatus = $this->_bookSameDayShipment(array("shipment_data"=>$shipmentData));
                if($shipmentStatus["status"]=="success"){
                    $loadIdentity = $shipmentStatus["load_identity"];
                    $priceVersionNo = $this->modelObj->findPriceNextVersionNo($loadIdentity);//$this->_findPriceNextVersionNo($loadIdentity);
                    $grandTotal     = $data->service_detail->total_price;
                    $shipmentService = $data->service_detail;
                    $shipmentService->price_version = $priceVersionNo;
                    $shipmentService->customer_id = $data->customer_id;
                    $shipmentService->transit_distance = $data->transit_distance;
                    $shipmentService->transit_time = $data->transit_time;
                    $shipmentService->transit_time_text = $data->transit_time_text;
                    $shipmentService->transit_distance_text = $data->transit_distance_text;
                    $shipmentService->load_identity = $loadIdentity;
					$shipmentService->service_request_string = ''; //json_encode($data->service_request_string);
					$shipmentService->service_response_string = '';//json_encode($data->service_request_string);
                    $shipmentService->booked_quotation_ref = $data->booked_quotation_ref;
                    $shipmentService->tracking_callbackurl = $data->tracking_callbackurl;
                    unset($shipmentService->message);
                    $priceBreakdownStatus = $this->_saveShipmentPriceBreakdown(array("shipment_type"=>"Same","service_opted"=>$data->service_detail,"version"=>$priceVersionNo));
                    $service_id = $this->_saveSamedayShipmentService($shipmentService);
                    $paymentStatus = $this->_manageAccountsDetails($service_id, $loadIdentity, $data->customer_id,$this->company_id,$grandTotal);
                    if($paymentStatus["status"]=="error"){
                        $this->rollBackTransaction();
                    }
                    ++$counter;
                }
                elseif($shipmentStatus["status"]=="error"){
                    $this->rollBackTransaction();
                    return $shipmentStatus;
                }

            }
            foreach($data->delivery_address as $shipment_data){
                $shipment_data->parent_id = $shipmentId;
                $shipment_data->special_instruction = (isset($shipment_data->special_instruction)) ? $shipment_data->special_instruction : "";
                if($loadIdentity!=""){
                    $shipment_data->loadIdentity = $loadIdentity;
                }
                $shipmentData = $this->_prepareShipmentData(
                    array(
                        "collection_user_id"=>$data->collection_user_id,
                        "shipment_data"=>$shipment_data,
                        "timestamp"=>$timestamp,
                        "customer_id"=>$data->customer_id,
                        "availabilityTypeCode"=>"UNKN",
                        "availabilityTypeName"=>"Unknown",
                        "file_name"=>"",
                        "loadGroupTypeName"=>"Same",
                        "loadGroupTypeCode"=>"Same",
                        "isDutiable"=>"false",
                        "jobTypeName"=>"Delivery",
                        "jobTypeCode"=>"DELV",
                        "shipment_service_type"=>"D",
                        "load_identity"=>$loadIdentity,
                        "icargo_execution_order"=>$counter,
                        "service_date"=>$this->service_date,
                        "shipment_executionOrder"=>$counter,
                        "warehouse_id"=>$data->warehouse_id,
                        "customer_id"=>$data->customer_id,
                        "userid"=>$data->userid,
                        "notification"=>true,
                        "shipment_instruction"=>$shipment_data->notes,
                        "carrier_code"=>$carrierCode,
                        "account_number"=>$accountOfCarrier
                    )
                );
                $shipmentStatus = $this->_bookSameDayShipment(array("shipment_data"=>$shipmentData));

                if($shipmentStatus["status"]=="success" and !$service_id){
                    $load_identity = $shipmentStatus["load_identity"];
                    $priceVersionNo = $this->_findPriceNextVersionNo($load_identity);
                    $shipmentService = $data->service_opted;
                    $shipmentService->price_version = $priceVersionNo;
                    $shipmentService->load_identity = $load_identity;
                    $shipmentService->customer_id = $data->customer_id;
                    unset($shipmentService->message);
                    $service_id = $this->_saveShipmentService($shipmentService);
                    $paymentStatus = $this->_manageAccountsDetails($service_id,$loadIdentity,$data->customer_id,$this->company_id,$shipmentService->total_price);
                    if($paymentStatus["status"]=="error"){
                        $this->rollBackTransaction();
                    }

                }elseif($shipmentStatus["status"]=="error"){
                    $this->rollBackTransaction();
                    return $shipmentStatus;
                }
                $counter++;
            }
            $this->db->commitTransaction();
            Consignee_Notification::_getInstance()->sendSamedayBookingConfirmationNotification(array("load_identity"=>$loadIdentity,"company_id"=>$this->company_id,"warehouse_id"=>$this->warehouse_id,"customer_id"=>$data->customer_id));
            $response =  array("status"=>"success", "message"=>"Shipment booked successfully. Booking reference no $loadIdentity","identity"=>$loadIdentity);
            $temparray = array();
            $temparray['session_id'] = $data->booked_quotation_ref;
            $temparray['response'] = json_encode($response);
            $temparray['request'] = $this->request;
            $temparray['status'] = 1;
            $temparray['create_date'] = date('Y-m-d');
            $temparray['create_time'] = date('H:m:s');
            $temparray['request_status'] = 'C';
            $temparray['end_point'] = $this->endpoint;
            $this->resrServiceModel->addContent('webapi_request_response',$temparray);
            return $response;
        }
        else{
            return array("status"=>"error", "message"=>"Customer account disabled.");
        }
    }
    private     function _save_shipment($param){
        $data = array();

        $ticketNumber =  $this->modelObj->generateTicketNo($param["company_id"]);//$this->_generate_ticket_no();

        if($ticketNumber){
            $timestamp = $param["timestamp"];
            $company_code = $this->_getCompanyCode($param["company_id"]);

            //customer info
            $data['shipment_customer_name']    = (isset($param["name"])) ? $param["name"] : "";
            $data['shipment_customer_email']   = (isset($param["email"])) ? $param["email"] : "";
            $data['shipment_customer_phone']   = (isset($param["phone"])) ? $param["phone"] : "";

            /*data not saved*/
            $data['shipment_total_weight']     = $param["weight"];
            $data['shipment_total_volume']     = $param["weight"];
            $data['shipment_statusName']       = "Un Attainded";
            $data['shipment_shouldBookIn']     = "false";
            $data['shipment_companyName']      = $param["company_name"];
            $data['distancemiles']             = "0.00";
            $data['estimatedtime']             = "00:00:00";
            /**/

            $data['shipment_highest_length']   = $param["length"];
            $data['shipment_highest_width']    = $param["width"];
            $data['shipment_highest_height']   = $param["height"];
            $data['shipment_highest_weight']   = $param["weight"];

            if(!isset($param["parcel_id"]))
                $param["parcel_id"] = 0;

            $data['shipment_required_service_starttime']       = $param["service_starttime"];
            $data['shipment_required_service_endtime']         = $param["service_endtime"];

            $data['shipment_total_item']       = $param["itemCount"];
            $warehouse_id = $param["warehouse_id"];

            $data['instaDispatch_loadGroupTypeCode'] = strtoupper($param["loadGroupTypeCode"]);
            $data['instaDispatch_docketNumber'] = (isset($param["docketNumber"])) ? $param["docketNumber"] : $ticketNumber;
            $data['instaDispatch_loadIdentity'] = (isset($param["loadIdentity"])) ? $param["loadIdentity"] : $ticketNumber;
            $data['instaDispatch_jobIdentity'] = (isset($param["jobIdentity"])) ? $param["jobIdentity"] : $ticketNumber;
            $data['instaDispatch_objectIdentity'] = (isset($param["objectIdentity"])) ? $param["objectIdentity"] : $ticketNumber;
            $data['instaDispatch_objectTypeName'] = (isset($param["instaDispatch_objectTypeName"])) ? $param["instaDispatch_objectTypeName"] : "JobLoad";

            $data['instaDispatch_objectTypeId'] = $param["objectTypeId"];
            $data['instaDispatch_accountNumber'] = $param["accountNumber"];
            $data['instaDispatch_businessName'] = $company_code;
            $data['instaDispatch_statusCode'] = $param["statusCode"];
            $data['instaDispatch_jobTypeCode'] = $param["jobTypeCode"];

            $data['instaDispatch_availabilityTypeCode'] = $param["availabilityTypeCode"];
            $data['instaDispatch_availabilityTypeName'] = $param["availabilityTypeName"];
            $data['instaDispatch_loadGroupTypeId'] = $param["loadGroupTypeId"];
            $data['instaDispatch_loadGroupTypeIcon'] = $param["loadGroupTypeIcon"];
            $data['instaDispatch_loadGroupTypeName'] = $param["loadGroupTypeName"];
            $data['instaDispatch_customerReference'] = $param["customerReference"];

            $data['shipment_isDutiable'] = $param['isDutiable'];
            $data['error_flag'] = "0";


            $data['shipment_xml_reference'] = $param["file_name"];

            $data['shipment_total_attempt'] = '0';
            $data['parent_id'] = (isset($param["parent_id"])) ? $param["parent_id"] : 0;

            $data['shipment_pod'] = '';
            $data['shipment_ticket'] = $ticketNumber;
            $data['shipment_required_service_date'] = date("Y-m-d", strtotime($param["service_date"]));
            $data['current_status'] = 'C';
            $data['is_shipment_routed'] = '0';
            $data['is_driver_assigned'] = '0';
            $data['dataof'] = $company_code;
            $data['waitAndReturn'] = $param['waitAndReturn'];
            $data['company_id'] = $this->company_id;
            $data['warehouse_id'] = $warehouse_id;
            $data['address_id'] = 0;
            $data['shipment_service_type'] = $param['shipment_service_type'];

            $data['shipment_latitude'] = $param["latitude"];
            $data['shipment_longitude'] = $param["longitude"];
            $data['shipment_latlong'] = $param["latitude"].','.$param["longitude"];
            $data['shipment_create_date'] = date("Y-m-d", strtotime('now'));
            $data['icargo_execution_order'] = $param['icargo_execution_order'];
            $data['shipment_executionOrder'] = $param['shipment_executionOrder'];

            $data['customer_id'] = (isset($param['customer_id'])) ? $param['customer_id'] : "0";

            $data['search_string'] = $ticketNumber . ' ' . $data['instaDispatch_docketNumber'] . ' ' . $data['instaDispatch_objectIdentity'].
                                     str_replace(' ', '', $param['postcode']) . ' ' . $data['shipment_customer_name'] . ' ' . $data['shipment_required_service_date'];

            $data['shipment_assigned_service_date'] = (isset($param["shipment_assigned_service_date"])) ? $param["shipment_assigned_service_date"] : "1970-01-01" ;
            $data['shipment_assigned_service_time'] = (isset($param["shipment_assigned_service_time"])) ? $param["shipment_assigned_service_time"] : "00:00:00" ;
            $data["booked_by"] =  (isset($param['userid'])) ? $param['userid'] : "0";
            $data["user_id"] =  (isset($param['collection_user_id'])) ? $param['collection_user_id'] : "0";

            $data["booking_ip"] = $_SERVER['REMOTE_ADDR'];

            $data["notification_status"] = (isset($param['notification'])) ? $param['notification'] : "0";

            $data['shipment_address1'] = (isset($param["address_line1"])) ? $param["address_line1"] : "";
            $data['shipment_address2'] = (isset($param["address_line2"])) ? $param["address_line2"] : ""; //$param["address_line2"];
            $data['shipment_customer_city'] = (isset($param["city"])) ? $param["city"]: "";
            $data['shipment_postcode'] = (isset($param["postcode"])) ? $this->postcodeObj->format_uk_postcode($param["postcode"]) : "";
            $data['shipment_customer_country'] = (isset($param["country"])) ? $param["country"] : "";
            $data['shipment_instruction'] = (isset($param["shipment_instruction"])) ? $param["shipment_instruction"] : "";

            $data['carrier_code'] = (isset($param["carrier_code"])) ? $param["carrier_code"] : "";
            $data['carrier_account_number'] = (isset($param["carrier_account_number"])) ? $param["carrier_account_number"] : "";


            //save address first then save shipment detail with address id
            $shipmentId = $this->db->save("shipment", $data);

            if($shipmentId){
                return array('status'=>"success",'message'=>'Shipment has been added successfully', "load_identity"=>$data['instaDispatch_loadIdentity']);
            }else{
                return array('status'=>"error",'message'=>'Shipment has not been added successfully');
            }
        }else{
            return array('status'=>"error",'message'=>'Configuration not found');
        }
    }
    private     function _bookSameDayShipment($param){
            $shipment_data = $param["shipment_data"];
            $shipmentStatus = $this->_save_shipment($shipment_data);
            if($shipmentStatus["status"]=="success"){
                $load_identity = $shipmentStatus["load_identity"];
                return array("status"=>"success", "load_identity"=>$load_identity, "address_id"=> 0);
            }else{
                return array("status"=>"error","message"=>$shipmentStatus["message"]);
            }
       }
    private     function _prepareShipmentData($param){
        $_data       = array();
        $data        = $param["shipment_data"];
        $timestamp   = $param["timestamp"];
        $customer_id = $param["customer_id"];
        foreach($data as $column => $item){
            $_data[$column] = $item;
        }
        $_data["parcel_quantity"]       = (isset($_data["parcel_quantity"])) ? $_data["parcel_quantity"] : 1;
        $_data["parcel_weight"]         = (isset($_data["parcel_weight"])) ? $_data["parcel_weight"] : 1;
        $_data["length"]                = (isset($_data["length"])) ? $_data["length"] : 1;
        $_data["width"]                 = (isset($_data["width"])) ? $_data["width"] : 1;
        $_data["height"]                = (isset($_data["height"])) ? $_data["height"] : 1;
        $_data["weight"]                = (isset($_data["weight"])) ? $_data["weight"] : 1;
        $_data["parcel_id"] = (isset($_data["parcel_id"])) ? $_data["parcel_id"] : 0;
        $_data["itemCount"] = (isset($_data["itemCount"])) ? $_data["itemCount"] : 1;
        $_data["objectTypeName"] = (isset($_data["objectTypeName"])) ? $_data["objectTypeName"] : "JobLoad";
        $_data["instruction"] = (isset($_data["instruction"])) ? $_data["instruction"] : "";
        $_data["isDutiable"] = (isset($_data["isDutiable"])) ? $_data["isDutiable"] : "false";
        $_data["shouldBookIn"] = (isset($_data["shouldBookIn"])) ? $_data["shouldBookIn"] : "false";
        $_data["statusName"] = (isset($_data["statusName"])) ? $_data["statusName"] : "Un Attainded";
        $_data["executionOrder"] = (isset($_data["executionOrder"])) ? $_data["executionOrder"] : 0;
        $_data["companyName"] = (isset($_data["companyName"])) ? $_data["companyName"] : "Need to find";
        $_data["objectTypeId"] = (isset($_data["objectTypeId"])) ? $_data["objectTypeId"] : 0;
        $_data["businessName"] = (isset($_data["businessName"])) ? $_data["businessName"] : "Need to find";
        $_data["accountNumber"] = (isset($_data["accountNumber"])) ? $_data["accountNumber"] : 0;
        $_data["statusCode"] = (isset($_data["statusCode"])) ? $_data["statusCode"] : "UNATTAINDED";
        $_data["waitAndReturn"] = (isset($_data["waitAndReturn"])) ? $_data["waitAndReturn"] : "false";
        $_data["loadGroupTypeIcon"] = (isset($_data["loadGroupTypeIcon"])) ? $_data["loadGroupTypeIcon"] : "";
        $_data["loadGroupTypeId"] = (isset($_data["loadGroupTypeId"])) ? $_data["loadGroupTypeId"] : 0;
        $_data["customerReference"] = (isset($_data["customerReference"])) ? $_data["customerReference"] : "";
        $_data["shipment_service_type"] = (isset($param["shipment_service_type"])) ? $param["shipment_service_type"] : "D";
        $_data["jobTypeName"] = $param["jobTypeName"];
        $_data["jobTypeCode"] = $param["jobTypeCode"];
        $_data["customer_id"] = $customer_id;
        $_data["company_id"] = $this->company_id;
        $_data["warehouse_id"] = $param["warehouse_id"];
        $_data["availabilityTypeCode"]  = $param["availabilityTypeCode"];
        $_data["availabilityTypeName"]  = $param["availabilityTypeName"];
        $_data["file_name"] = "";
        $_data["timestamp"] = $timestamp;
        $_data["loadGroupTypeName"] = $param["loadGroupTypeName"];
        $_data["loadGroupTypeCode"] = $param["loadGroupTypeCode"];
        $_data["isDutiable"] = $param["isDutiable"];
        $_data["icargo_execution_order"] = $param["icargo_execution_order"];
        $_data["shipment_executionOrder"] = $param["shipment_executionOrder"];
        $_data["service_date"] = (isset($param["service_date"])) ? $param["service_date"] : date("Y-m-d H:i:s", $timestamp);
        $_data["service_starttime"] = (isset($param["service_date"])) ? date("H:i:s", strtotime($param["service_date"])) : date("H:i:s", $timestamp);
        $_data["service_endtime"] = (isset($param["service_date"])) ? date("H:i:s", strtotime($param["service_date"])) : date("H:i:s", $timestamp);
        $_data["shipment_required_service_date"] = (isset($_data["shipment_required_service_date"])) ? date("Y-m-d",strtotime($_data["shipment_required_service_date"])) : "1970-01-01";
        $_data["customer_id"] = (isset($param["customer_id"])) ? $param["customer_id"] : "0";
        $_data["collection_user_id"] = (isset($param["collection_user_id"])) ? $param["collection_user_id"] : "0";
		$_data["userid"] = (isset($param["userid"])) ? $param["userid"] : "0";
        $_data["shipment_instruction"] = (isset($param["shipment_instruction"])) ? $param["shipment_instruction"] : "";
        $_data["carrier_code"] = (isset($param["carrier_code"])) ? $param["carrier_code"] : "";
        $_data["carrier_account_number"] = (isset($param["account_number"])) ? $param["account_number"] : "";
        if(isset($_data["address_list"])){
            unset($_data["address_list"]);
        }
        return $_data;
    }
    private     function _saveShipmentPriceBreakdown($param){
        $priceVersionNo = $param["version"];
        $shipmentType = $param["shipment_type"];
        $response = array();
        //save service
        if(isset($param["service_opted"]->otherinfo)){
            $servicePriceinfoInfo = array();
            $servicePriceinfoInfo =  json_decode(json_encode($param["service_opted"]->otherinfo),1);
            $service_price_breakdown = array();
            $service_price_breakdown["load_identity"] = $param["service_opted"]->load_identity;
            //$service_price_breakdown["shipment_id"] = $shipmentId;
            $service_price_breakdown["shipment_type"] = $shipmentType;
            $service_price_breakdown["version"] = $priceVersionNo;
            $service_price_breakdown["api_key"] = "service";
            $service_price_breakdown["price_code"] = $servicePriceinfoInfo['courier_service_code'];
            $service_price_breakdown["ccf_operator"] = $servicePriceinfoInfo['operator'];
            $service_price_breakdown["ccf_value"] = $servicePriceinfoInfo['ccf_value'];
            $service_price_breakdown["ccf_level"] = $servicePriceinfoInfo['level'];
            $service_price_breakdown["baseprice"] = $servicePriceinfoInfo['original_price'];
            $service_price_breakdown["ccf_price"] = $servicePriceinfoInfo['price'];
            $service_price_breakdown["price"] = ($service_price_breakdown["baseprice"] + $service_price_breakdown["ccf_price"]);
            $service_price_breakdown["service_id"] = $servicePriceinfoInfo['service_id'];
            $service_price_breakdown["carrier_id"] = $servicePriceinfoInfo['courier_id'];
            try{
                $price_breakdown_id = $this->db->save("shipment_price", $service_price_breakdown);
                array_push($response,$price_breakdown_id);
            }catch(Exception $e){
                print_r($e);
            }
        }
        //save surcharges
        $totalcarrierSurchage = 0;
        if(isset($param["service_opted"]->surchargesinfo)){
            foreach($param["service_opted"]->surchargesinfo as $key => $item){
                $surchargeInfo = array();
                $surchargeInfo = json_decode(json_encode($item->info),1);
                $price_breakdown = array();
                $totalcarrierSurchage += $surchargeInfo['original_price'];
                $price_breakdown["load_identity"] = $param["service_opted"]->load_identity;
                $price_breakdown["shipment_type"] = $shipmentType;
                $price_breakdown["version"] = $priceVersionNo;
                $price_breakdown["api_key"] = "surcharges";
                $price_breakdown["price_code"] = $key;
                $price_breakdown["ccf_operator"] = $surchargeInfo['operator'];
                $price_breakdown["ccf_value"] = $surchargeInfo['surcharge_value'];
                $price_breakdown["ccf_level"] = $surchargeInfo['level'];
                $price_breakdown["baseprice"] = $surchargeInfo['original_price'];
                $price_breakdown["ccf_price"] = $surchargeInfo['price'];
                $price_breakdown["price"] = ($price_breakdown["baseprice"] + $price_breakdown["ccf_price"]);
                $price_breakdown["surcharge_id"] = $surchargeInfo['surcharge_id'];
                $price_breakdown["carrier_id"] = $surchargeInfo['carrier_id'];
                try{
                    $price_breakdown_id = $this->db->save("shipment_price", $price_breakdown);
                    array_push($response,$price_breakdown_id);
                }catch(Exception $e){
                    print_r($e);
                }
            }
        }
        //save tax
        if(isset($param["service_opted"]->taxes) and !empty($param["service_opted"]->taxes)){
            $carriertotalpriceWithouttax = ($totalcarrierSurchage  + $param["service_opted"]->otherinfo->original_price);
            $price_breakdown = array();
            $price_without_tax = $param["service_opted"]->pricewithouttax;
            foreach($param["service_opted"]->taxes as $key => $item){
                if($key=='total_tax'){
                    $price_breakdown["price_code"] = $key;
                    $price_breakdown["load_identity"] = $param["service_opted"]->load_identity;
                    $price_breakdown["shipment_type"] = $shipmentType;
                    $price_breakdown["version"] = $priceVersionNo;
                    $price_breakdown["api_key"] = "taxes";
                    $price_breakdown["inputjson"] = json_encode(array('originnal_tax_amt'=>$item));
                    $price_breakdown["carrier_id"] = $servicePriceinfoInfo['courier_id'];
                }elseif($key=='tax_percentage'){
                    $basetaxprice = number_format((($carriertotalpriceWithouttax *$item)/100),2,'.','');
                    $price_breakdown["ccf_operator"] = "PERCENTAGE";
                    $price_breakdown["ccf_value"] = $item;
                    $price_breakdown["ccf_level"] = 0;
                    $price_breakdown["baseprice"] = $basetaxprice;
                    $price_breakdown["ccf_price"] = $param["service_opted"]->chargable_tax;//$price;
                    $price_breakdown["price"] = $price_breakdown["ccf_price"];
                }else{
                    //
                }
            }
            $price_breakdown_id = $this->db->save("shipment_price", $price_breakdown);
            array_push($response,$price_breakdown_id);

        }
        return $response;
    }
    private     function _saveSamedayShipmentService($param){
        $_data = array();
        $_attribute = array();
        $service_id = "";
        $quoteRef = $param->booked_quotation_ref;
        $callbackUrl = $param->tracking_callbackurl;
        $_data["surcharges"] = 0;
        $_data["taxes"] = 0;
        $_data["charge_from_base"] = 0;

        if(isset($param->otherinfo)){
            $param->otherinfo =  json_decode(json_encode($param->otherinfo),1);
        }
       if(isset($param->surchargesinfo)){
            foreach($param->surchargesinfo as $key => $item){
                $item->info = $item->info;
            }
        }
        $data_string = json_encode($param);
        $_data["carrier"]                   = $param->otherinfo['courier_id'];
        $_data["courier_commission_type"]   = $param->otherinfo['operator'];
        $_data["courier_commission"]        = $param->otherinfo['ccf_value'];
        $_data["courier_commission_value"]  = $param->otherinfo['price'];
        $_data["base_price"]                = $param->otherinfo['original_price'];
        $_data["booked_service_id"]         = $param->otherinfo['service_id'];
        $_data["accountkey"]                = $param->otherinfo['accountkey'];
        $_data["total_price"]               = $_data["base_price"]  + $_data["courier_commission_value"];
        unset($param->surchargesinfo);
        unset($param->otherinfo);
        unset($param->base_price);
        unset($param->total_price);
        unset($param->pricewithouttax);
        unset($param->booked_quotation_ref);
        unset($param->tracking_callbackurl);
        if(isset($param->icon)){
            $_attribute["column_name"] = "icon";
            $_attribute["value"] = $param->icon;
            $_attribute["api_key"] = "icon";
            $_attribute["load_identity"] = $param->load_identity;
            $attribute_id = $this->db->save("shipment_attributes", $_attribute);
            unset($param->icon);
        }
        if(isset($param->dimensions)){
            foreach($param->dimensions as $column=>$item){
                $_attribute["column_name"] = $column;
                $_attribute["value"] = $item;
                $_attribute["api_key"] = "dimensions";
                $_attribute["load_identity"] = $param->load_identity;
                $attribute_id = $this->db->save("shipment_attributes", $_attribute);
            }
            unset($param->dimensions);
        }
        if(isset($param->weight)){
            foreach($param->weight as $column=>$item){
                $_attribute["column_name"] = $column;
                $_attribute["value"] = $item;
                $_attribute["api_key"] = "weight";
                $_attribute["load_identity"] = $param->load_identity;
                $attribute_id = $this->db->save("shipment_attributes", $_attribute);
            }
            unset($param->weight);
        }

        if(isset($param->time)){
            foreach($param->time as $column=>$item){
                $_attribute["column_name"] = $column;
                $_attribute["value"] = ($item!="") ? $item : 0;
                $_attribute["api_key"] = "time";
                $_attribute["load_identity"] = $param->load_identity;
                $attribute_id = $this->db->save("shipment_attributes", $_attribute);
            }
            unset($param->time);
        }

        if(isset($param->surcharges)){
            $_data["surcharges"] = array_sum((array)$param->surcharges);
            unset($param->surcharges);
        }

        if(isset($param->taxes)){
           //$_data["taxes"] = array_sum((array)$param->taxes);
            $_data["taxes"] = $param->chargable_tax;
            unset($param->chargable_tax);
            unset($param->taxes);
        }

        foreach($param as $column=>$item)
        $_data[$column] = $item;
        if(isset($_data["charge_from_base"])){
              if($_data["charge_from_base"]==""){
            $_data["charge_from_base"] = 0;
        }}
        if($_data){
            $_data["grand_total"] = $_data['total_price'] + $_data['surcharges'] + $_data['taxes'];
            //$_data["chargable_value"] = 0.00;
            $_data["invoice_reference"] ='';
                // $_data["json_data"] = $data_string;
                $priceData = $this->resrServiceModel->getBookedShipmentsPrice($_data['customer_id']);
                $_data['customer_type']        = $priceData['customer_type'];
                $_data['booked_api_token_id']  = $this->webApiToken;
                $_data['booked_quotation_ref']  = $quoteRef;
                $_data['tracking_callbackurl']  = $callbackUrl;
                $service_id = $this->db->save("shipment_service", $_data);
        }
        return $service_id;
    }
    public      function bookSameDayQuotation($param){
        $bookingData = array();
        $this->request = json_encode($param);
        $endpoint = $this->endpoint;
        $this->bookparam = $param;
        if(!isset($this->bookparam->service_code) || ($this->bookparam->service_code=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'service code  missing';
                 $response["error_code"] = "ERROR0050";
                 return $response;
        }elseif(!isset($this->bookparam->service_date) || ($this->bookparam->service_date=='') || !($this->isValidServiceDate($this->bookparam->service_date))){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'service date and time missing or invalid';
                 $response["error_code"] = "ERROR0079";
                 return $response;
        }elseif(!isset($this->bookparam->quation_reference) || ($this->bookparam->quation_reference=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'quation reference is missing';
                 $response["error_code"] = "ERROR0043";
                 return $response;
        }elseif(!isset($this->bookparam->service_code) || ($this->bookparam->service_code=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'service code is missing';
                 $response["error_code"] = "ERROR0044";
        }else{
             $collectionStatus = $this->validateCollectionAddress();
             $deliveryStatus   = $this->validateDeliveryAddress();
             if($collectionStatus['status']=='success'){
                    if($deliveryStatus['status']=='success'){
                      $quotationData   = $this->resrServiceModel->getQuotationData($this->bookparam->quation_reference);
                      if($quotationData!=''){
                      $serviceId = $this->resrServiceModel->getCustomerCarrierDataByServiceCode($this->bookparam->customer_id,$this->bookparam->service_code, $this->bookparam->company_id);
                      $this->bookparam->service_id = $serviceId['service_id'];
                      $preRequest      = json_decode($quotationData['request']);
                      $preResponse     = json_decode($quotationData['response'],1);
                      $preServiceKeys  = array_keys((array)$preResponse);
                      if(in_array($this->bookparam->service_id,$preServiceKeys)){
                              $filterQuotationdata    = $this->validateQuotation($this->bookparam,$preRequest);
                              if($filterQuotationdata['status']=='fail'){
                                  return $filterQuotationdata;
                              }
                              unset($preRequest->collection,$preRequest->delivery);
                              $bookingData  = array_merge($filterQuotationdata['data'],(array)$preRequest);
                              $bookingData['collection_user_id']        = $bookingData['customer_id'];
                              $bookingData['service_detail']            = json_decode(json_encode($preResponse[$this->bookparam->service_id], FALSE));
                              $bookingData['userid']                    = $bookingData['company_id'];
                              $bookingData['service_request_string']    =  array();
                              $bookingData['service_response_string']   =  array();
                              $bookingData['booked_quotation_ref']      =  $this->bookparam->quation_reference;
                              $bookingData['tracking_callbackurl']      =  isset($this->bookparam->callback_url)?$this->bookparam->callback_url:'';
                              $status = $this->bookSameDayShipment((object)$bookingData);
                              if($status['status']=='success'){
                                  $updatestatus = $this->resrServiceModel->editContent('webapi_request_response',
                                    array('request_status'=>'C')," session_id = '".$this->bookparam->quation_reference."'");
                              }
                             return $status;
                      }
                      else{
                             $response = array();
                             $response["status"]        = "fail";
                             $response["message"]       = 'service code is missmatch with quotation';
                             $response["error_code"]    = "ERROR0051";
                             return $response;
                      }
                     }else{
                             $response = array();
                             $response["status"]        = "fail";
                             $response["message"]       = 'quotation code is missmatch or expired';
                             $response["error_code"]    = "ERROR0060";
                             return $response;
                      }



                    }else{
                     return $deliveryStatus;
                   }
             }else{
                 return $collectionStatus;
             }
        }
        if(!empty($data)){
           $data = $this->_getWbFormat($data,$post_data["transit"],$param,$endpoint,$googleRequest);
           return array("status" => "success","rate"=>$data,"availiable_balence" => $available_credit['available_credit']);
        }else{
             return array("status" => "fail","rate"=>array(),"availiable_balence" => $available_credit['available_credit']);
        }
     }
    public      function bookSameDayJobWithoutQuotion($param){
        if(!isset($param->service_code) || ($param->service_code=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'service code  missing';
                 $response["error_code"] = "ERROR0055";
                 return $response;
        }else{
            $serviceId = $this->resrServiceModel->getCustomerCarrierDataByServiceCode($param->customer_id,$param->service_code, $param->company_id);
            $param->service_id = $serviceId['service_id'];
            $quoteData  = $this->getSameDayQuotation($param);
                if($quoteData['status']!='success'){
                return $quoteData;
                }else{
                    $quotation_ref  = $quoteData['rate']['quotation_ref'];
                    if(count($quoteData['rate']['services'])==1){
                      if($quoteData['rate']['services'][0]['service_id']===$param->service_id){
                         $param->quation_reference =  $quotation_ref;
                         return $this->bookSameDayQuotation($param);
                      }else{
                             $response = array();
                             $response["status"] = "fail";
                             $response["message"] = 'Please check your service code';
                             $response["error_code"] = "ERROR0056";
                             return $response;
                      }
                     }else{
                             $response = array();
                             $response["status"] = "fail";
                             $response["message"] = 'Please check your service code';
                             $response["error_code"] = "ERROR0057";
                             return $response;
                       }
                    }
               }
           }
    public      function executeSameDayRecurringJob(){
        $reccuringBucket =  array();
        $return  =  array();
        $reccuringBucket = $this->getEligibleRecurringJob();
        if(count($reccuringBucket)>0){
            foreach($reccuringBucket as $key=>$data){
                $returnData = $this->bookSamedayByLoadIdentity($data['load_identity']);
                if($returnData['status']=='error' || $returnData['status']=='fail'){
                    Consignee_Notification::_getInstance()->sendRecurringNotification(
                    array('rowdata'=>$data['rowdata'],'returnData'=>$returnData));
                    $updatestatus = $this->resrServiceModel->editContent('recurring_jobs',
                                                         array('status'=>'fail')," job_id = '".$data['rowdata']['job_id']."'");
                    return $returnData;
                }else{
                   if($returnData['status']=='success'){
                       $id = $data['rowdata']['job_id'];
                       $tempdata   = array();
                       $tempdata['last_booking_date'] = date('Y-m-d');
                       $tempdata['last_booking_time'] = date('H:m:s');
                       $tempdata['last_booking_reference'] = $returnData['identity'];
                       $tempdata['status'] = ($data['rowdata']['recurring_type']  == 'ONCE')?false:true;
                       $updatestatus = $this->resrServiceModel->editContent('recurring_jobs',
                                                         $tempdata," job_id = '".$id."'");
                       if($updatestatus){
                         $updatestatus = $this->resrServiceModel->editContent('shipment_service',
                                                         array('booked_by_recurring'=>'YES')," load_identity = '".$returnData['identity']."'");
                       }
                   }
                 $return[]   = $returnData;
                }
            }
        }
        return $return;
    }
    public      function getEligibleRecurringJob(){
        $reccuringBucket =  array();
        $recurringData = $this->resrServiceModel->getSamedayReccuringJobs();
        //print_r($recurringData);
        $currenttime  = date("H:i:s");
        //print_r($currenttime);die;
        if(count($recurringData)>0){
            foreach($recurringData as $reccuringVal){
                switch($reccuringVal['recurring_type']){
                    case 'DAILY':
                     //echo strtotime($currenttime);echo '</br>';echo  strtotime($reccuringVal['recurring_time']);echo '</br>';
                      if(strtotime($currenttime) >= strtotime($reccuringVal['recurring_time']) && (strtotime(date('Y-m-d')) > strtotime($reccuringVal['last_booking_date']))){
                          $reccuringBucket[] = array('load_identity'=>$reccuringVal['load_identity'],'rowdata'=>$reccuringVal);
                      }
                    //break;
                    case 'WEEKLY':
                      if((strtotime($currenttime) >= strtotime($reccuringVal['recurring_time'])) && (strtoupper(date("D"))===$reccuringVal['recurring_day']) && (strtotime(date('Y-m-d')) > strtotime($reccuringVal['last_booking_date']))){
                          $reccuringBucket[] = array('load_identity'=>$reccuringVal['load_identity'],'rowdata'=>$reccuringVal);
                      }
                    //break;
                    case 'MONTHLY':
                      if((strtotime($currenttime) >= strtotime($reccuringVal['recurring_time'])) && (date("d")===$reccuringVal['recurring_month_date']) && (strtotime(date('Y-m-d')) > strtotime($reccuringVal['last_booking_date']))){
                          $reccuringBucket[] = array('load_identity'=>$reccuringVal['load_identity'],'rowdata'=>$reccuringVal);
                      }

                   // break;
                    case 'ONCE':
                      if((strtotime($currenttime) >= strtotime($reccuringVal['recurring_time'])) && (strtotime(date("Y-m-d"))===strtotime($reccuringVal['recurring_date']))){
                          $reccuringBucket[] = array('load_identity'=>$reccuringVal['load_identity'],'rowdata'=>$reccuringVal && ($reccuringVal['last_booking_date'] == '1970-01-01' ) );
                      }
                    //break;
                 }
               }
            }
        //print_r($reccuringBucket);die;
        return $reccuringBucket;
    }
    public      function bookSamedayByLoadIdentity($loadidentity){
        $jsonData                           = array('collection'=>array(),'delivery'=>array());
        $loadServicedetails                 = $this->resrServiceModel->getLoadServiceDetails($loadidentity);
        $jsonData['service_date']           =   date("Y-m-d h:i");
        $jsonData['service_code']           =   $loadServicedetails['service_code'];
        $jsonData['service_id']             =   $loadServicedetails['service_id'];
        $jsonData['customer_id']            =   $loadServicedetails['customer_id'];
        $jsonData['carrier_id']             =   $loadServicedetails['carrier'];
        $jsonData['callback_url']           =   'https://api-sandbox.noqu.delivery/callback/mydelivery';
        $loadDetails                        =   $this->resrServiceModel->getLoadDetails($loadidentity);
        foreach($loadDetails as $key=>$data){
          if($data['shipment_service_type'] =='P'){
              $temp = array();
              $temp['address'] = array(
                  'country'=>$data['shipment_customer_country'],
                  'country_code'=>$data['shipment_country_code'],
                  'currency_code'=>$loadServicedetails['currency'],
                  'postcode'=>$data['shipment_postcode'],
                  'company_name'=>$data['shipment_companyName'],
                  'address_line1'=>$data['shipment_address1'],
                  'address_line2'=>$data['shipment_address2'],
                  'city'=>$data['shipment_customer_city'],
                  'notes'=>$data['shipment_notes'],
                  'latitude'=>$data['shipment_latitude'],
                  'longitude'=>$data['shipment_longitude'],
                  'geo_position' => array('latitude'=>$data['shipment_latitude'],'longitude'=>$data['shipment_longitude'])
              );
              $temp['consignee'] = array('name'=>$data['shipment_customer_name'],'phone'=>$data['shipment_customer_phone'],'email'=>$data['shipment_customer_email']);
              $jsonData['collection'] = $temp;
              //array_push($jsonData['collection'],$temp);
            }elseif($data['shipment_service_type']=='D'){
              $temp = array();
              $temp['address'] = array(
                  'country'=>$data['shipment_customer_country'],
                  'country_code'=>$data['shipment_country_code'],
                  'currency_code'=>$loadServicedetails['currency'],
                  'postcode'=>$data['shipment_postcode'],
                  'company_name'=>$data['shipment_companyName'],
                  'address_line1'=>$data['shipment_address1'],
                  'address_line2'=>$data['shipment_address2'],
                  'city'=>$data['shipment_customer_city'],
                  'notes'=>$data['shipment_notes'],
                  'latitude'=>$data['shipment_latitude'],
                  'longitude'=>$data['shipment_longitude'],
                  'geo_position' => array('latitude'=>$data['shipment_latitude'],'longitude'=>$data['shipment_longitude'])
              );
              $temp['consignee'] = array('name'=>$data['shipment_customer_name'],'phone'=>$data['shipment_customer_phone'],'email'=>$data['shipment_customer_email']);
              array_push($jsonData['delivery'],$temp);
          }
        }

        $jsonData['user_id']         =   $loadDetails[0]['user_id'];
        $jsonData['warehouse_id']   =   $loadDetails[0]['warehouse_id'];
        $jsonData['warehouse_latitude']   =   $loadDetails[0]['warehouse_latitude'];
        $jsonData['warehouse_longitude']   =   $loadDetails[0]['warehouse_longitude'];
        $jsonData['company_id']   =   $loadDetails[0]['company_id'];
        $this->endpoint = 'bookedRecurringJob';
        $data =  $this->bookSameDayJobWithoutQuotion(json_decode(json_encode($jsonData)));
        return $data;

     }
    public      function canCancelJob($param){
        $endpoint      = $this->endpoint;
        $param->customer_id = $param->customer_id;
        if(!isset($param->identity) || ($param->identity=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'identity missing';
                 $response["error_code"] = "ERROR0052";
                 return $response;
        }
        $loadServiceDetail =  $this->resrServiceModel->getLoadServiceDetails($param->identity);
         if($loadServiceDetail['customer_id'] != $param->customer_id){
             $response = array();
             $response["status"] = "fail";
             $response["message"] = 'Unauthorized inquiry';
             $response["error_code"] = "ERROR0053";
             return $response;
         }
         elseif(!is_array($loadServiceDetail) || empty($loadServiceDetail)){
             $response = array();
             $response["status"] = "fail";
             $response["message"] = 'No shipment found';
             $response["error_code"] = "ERROR0054";
             return $response;
        }else{
             return $this->allShipmentsObj->checkEligibleForCancel((object)array('job_identity'=>array($param->identity)));

        }
       }
    public      function cancelJob($param){
        $param->job_identity = array($param->identity);
        $param->user = $param->company_id;
        $endpoint      = $this->endpoint;
        if(!isset($param->identity) || ($param->identity=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'identity missing';
                 $response["error_code"] = "ERROR0052";
                 return $response;
        }
        $loadServiceDetail =  $this->resrServiceModel->getLoadServiceDetails($param->identity);
         if($loadServiceDetail['customer_id'] != $param->customer_id){
             $response = array();
             $response["status"] = "fail";
             $response["message"] = 'Unauthorized inquiry';
             $response["error_code"] = "ERROR0062";
             return $response;
         }
         elseif(!is_array($loadServiceDetail) || empty($loadServiceDetail)){
             $response = array();
             $response["status"] = "fail";
             $response["message"] = 'No shipment found';
             $response["error_code"] = "ERROR0059";
             return $response;
        }elseif($this->canCancelJob($param)['status'] != 'success'){
             $response = array();
             $response["status"] = "fail";
             $response["message"] = $this->canCancelJob($param)['message'];
             $response["error_code"] = "ERROR0061";
             return $response;
        }else{
             unset($param->identity);
             return $this->allShipmentsObj->cancelJob($param);

        }
      }
    public      function saveWebReqResponce($req,$resp,$app){
        $token      = decodeJWtKey($app->request->headers->get("Authorization"));
        $url        = $app->request->getRootUri();
        $tokenId    = $token->identity;
        $data       = array();
        $data['token_id'] = $tokenId;
        $data['web_request'] = json_encode($req);
        $data['web_responce'] = json_encode($resp);
        $data['requested_url'] = $url;
        $data['create_date'] = date('Y-m-d H:i:s');
        $this->resrServiceModel->addContent('api_request_response',$data);
    }
    public      function isValidServiceDate($date, $format = 'Y-m-d H:i'){
                    $d = DateTime::createFromFormat($format, $date);
                    return $d && $d->format($format) == $date;
        }


    public  function getShipmentTracking($param){
        $param->job_identity = array($param->identity);
        $param->user = $param->company_id;
        $endpoint      = $this->endpoint;
        if(!isset($param->identity) || ($param->identity=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'identity missing';
                 $response["error_code"] = "ERROR0052";
                 return $response;
        }
        $loadServiceDetail =  $this->resrServiceModel->getLoadServiceDetails($param->identity);
         if($loadServiceDetail['customer_id'] != $param->customer_id){
             $response = array();
             $response["status"] = "fail";
             $response["message"] = 'Unauthorized tracking inquiry';
             $response["error_code"] = "ERROR0063";
             return $response;
         }
         elseif(!is_array($loadServiceDetail) || empty($loadServiceDetail)){
             $response = array();
             $response["status"] = "fail";
             $response["message"] = 'No shipment found';
             $response["error_code"] = "ERROR0059";
             return $response;
        }else{
             unset($param->identity);
             return $this->allShipmentsObj->loadTracking($param);

        }
      }


}
?>