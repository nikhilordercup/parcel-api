<?php

class Module_Coreprime_Api extends Icargo
{
    public static $coreprimeApiObj = NULL;
    private $_isLabelCall = false;
    private $_endpoints = [];

    private $_environment = array(
        "live" => array(
            "authorization_token" => "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoxLCJlbWFpbCI6ImRldmVsb3BlcnNAb3JkZXJjdXAuY29tIiwiaXNzIjoiT3JkZXJDdXAgb3IgaHR0cHM6Ly93d3cub3JkZXJjdXAuY29tLyIsImlhdCI6MTQ5Njk5MzU0N30.cpm3XYPcLlwb0njGDIf8LGVYPJ2xJnS32y_DiBjSCGI",
            "access_url" => "http://api.icargo.in/v1/RateEngine/getRate"
        ),
        "stagging" => array(
            "authorization_token" => "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoxLCJlbWFpbCI6Im1hcmdlc2guc29uYXdhbmVAb3JkZXJjdXAuY29tIiwiaXNzIjoiT3JkZXJDdXAgb3IgaHR0cHM6Ly93d3cub3JkZXJjdXAuY29tLyIsImlhdCI6MTQ5Mzk2ODgxMX0.EJc4SVQXIwZibVuXFxkTo8UjKvH8S9gWyuFn9bsi63g",
            "access_url" => "http://api.icargo.in/v1/RateEngine/getRate"
        )
    );
    
    private $_doLabelCancel = false;

    public

    function __construct($data)
    {
        $this->_parentObj = parent::__construct(array("email" => $data->email, "access_token" => $data->access_token));
        $this->modelObj = new Coreprime_Model_Api();
        $this->customerccf = new CustomerCostFactor();

        $this->apiConn = "stagging";
        if (ENV == 'live')
            $this->apiConn = "live";

        $this->authorization_token = $this->_environment[$this->apiConn]["authorization_token"];
        $this->access_url = $this->_environment[$this->apiConn]["access_url"];
    }

    public

    static function _getInstance()
    {
        if (self::$coreprimeApiObj === NULL) {
            self::$coreprimeApiObj = new Module_Coreprime_Api();
        }
        return self::$coreprimeApiObj;
    }

    public

    function _postRequest($data)
    { 
        global $_GLOBAL_CONTAINER;
        if (isset($_GLOBAL_CONTAINER['loadIdentity'])) {
            $data->loadIdentity = $_GLOBAL_CONTAINER['loadIdentity'];
        }

        if(isset($data->doLabelCancel))
        {
            $this->_doLabelCancel = true; 
            $label=$this->doLabelCall($data);
            return $label;
        }
        
        if (isset($data->label)) {
            $this->_isLabelCall = true;
            $label=$this->doLabelCall($data);
            return $label;
        }
        
        if (isset($data->callType) && $data->callType == 'createpickup' ) 
        {       
            return $this->postToRateEngineUrl($data->pickupEndPoint, $data);                        
        }
        
        $pd = $this->filterServiceProvider($data); 
        $finalPrice = [];
        if (isset($pd['Coreprime']) && count($pd['Coreprime'])) {
            $cpData = $data;
            $cpData['carriers'] = $pd['Coreprime'];
            $cpRate = $this->postToCorePrime($cpData);
            $this->mergePrice($finalPrice, $cpRate);
        }
        if (isset($pd['Local']) && count($pd['Local'])) {
            $lcData = $data;
            $lcData['carriers'] = $pd['Local'];
            $localRate = $this->postToRateEngine('Local', $lcData);
           // echo($localRate);exit;
            $this->mergePrice($finalPrice, $localRate);
        } 
//        exit(json_encode($finalPrice));
        return json_encode($finalPrice);
    }

    private
    function _filterApiResponse($input, $customer_id, $company_id, $charge_from_warehouse, $is_tax_exempt)
    {
        if (is_array($input)) {
            $temparray = array();
            $returnarray = array();
            unset($input["rate"]["timestamp"]);
            foreach ($input["rate"] as $carriercode => $service_list) {
                if ($carriercode != "error") {
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
                                        } else {
                                            $total_surcharge = 0;
                                            $base_price = 0;
                                            $total_price = 0;
                                            $total_tax = 0;
                                            $carrier = $this->modelObj->getCarrierIdByCode($company_id, $customer_id, $accountkey);
                                            $res = $this->_calculateSamedayServiceccf($servicecode, $item['rate'], $carrier['id'], $customer_id, $company_id);
                                            $res['service_options'] = $item['service_options'] ?? [];
                                            //$res['taxes'] = $item['taxes'];
                                            $res['taxes'] = ($is_tax_exempt) ? array() : $item['taxes'] ?? [];
                                            $res['info']['accountkey'] = $accountkey;
                                            $res['ccf_surcharges'] = new StdClass();
                                            $res['ccf_surcharges']->alldata = array();
                                            foreach ($item['surcharges'] as $surcharge_code => $price) {
                                                $sur = $this->_calculateSamedaySurchargeccf($surcharge_code, $customer_id, $company_id, $carrier['id'], $price);
                                                $res['surcharges'][$surcharge_code] = $sur['surcharges'][$surcharge_code];
                                                $res['ccf_surcharges']->alldata[$surcharge_code] = $sur['ccf_surcharges'];
                                            }
                                            $base_price = $res['price'];
                                            if (isset($res['ccf_surcharges'])) {
                                                foreach ($res['ccf_surcharges']->alldata as $surcharge_name => $surcharge_val) {
                                                    $total_surcharge += $surcharge_val['price'];
                                                }
                                            }

                                            $price_without_tax = number_format($total_surcharge + $base_price, 2, '.', '');
                                            $customer_tax_amt = 0;
                                            if (isset($res['taxes'])) {
                                                $temparray["rate"][$carriercode][$key]["taxes"] = $res['taxes'];
                                                $total_tax_val = isset($res['taxes']['tax_percentage']) ? $res['taxes']['tax_percentage'] : 0;
                                                $customer_tax_amt = number_format((($price_without_tax * $total_tax_val) / 100), 2, '.', '');
                                            }
                                            $total_price = number_format($total_surcharge + $base_price + $customer_tax_amt, 2, '.', '');
                                            $temparray["rate"][$carriercode][$key]["pricewithouttax"] = $price_without_tax;
                                            $temparray["rate"][$carriercode][$key]["chargable_tax"] = $customer_tax_amt;
                                            $temparray["rate"][$carriercode][$key]["service_name"] = $res['service_name'];
                                            $temparray["rate"][$carriercode][$key]["charge_from_base"] = ($charge_from_warehouse) ? '1' : '0';
                                            $temparray["rate"][$carriercode][$key]["rate_type"] = $res['rate_type'];
                                            $temparray["rate"][$carriercode][$key]["message"] = $res['message'] ?? '';
                                            $temparray["rate"][$carriercode][$key]["currency"] = $res['currency'] ?? 'GBP';
                                            $temparray["rate"][$carriercode][$key]["total_price"] = $total_price;
                                            $temparray["rate"][$carriercode][$key]["otherinfo"] = $res['info'];
                                            $temparray["rate"][$carriercode][$key]["base_price"] = $base_price;
                                            $temparray["rate"][$carriercode][$key]["icon"] = $res['service_options']['icon'] ?? '';
                                            $temparray["rate"][$carriercode][$key]["max_delivery_time"] = $res['service_options']['max_delivery_time'] ?? '';
                                            $temparray["rate"][$carriercode][$key]["dimensions"] = $res['service_options']['dimensions'] ?? '';
                                            $temparray["rate"][$carriercode][$key]["weight"] = $res['service_options']['weight'] ?? '';
                                            $temparray["rate"][$carriercode][$key]["time"] = $res['service_options']['time'] ?? '';
                                            $temparray["rate"][$carriercode][$key]["surcharges"] = array();
                                            $temparray["rate"][$carriercode][$key]["surchargesinfo"] = array();
                                            if (isset($res['surcharges'])) {
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
                } else {
                    return json_decode(json_encode(array("status" => "error", "message" => str_replace('"', "", $input["rate"]["error"]))));
                }
            }
            return json_decode(json_encode($returnarray));
        } else {
            return array("status" => "error", "message" => "Empty responses");
        }
    }

    public
    function getAllServices($param)
    {
        $available_credit = $this->_getCustomerAccountBalence($param->customer_id);
        $response_filter_type = "min";
        $param->customer_id = $param->customer_id;
        $transit_distance = $param->transit_distance;
        $transit_time = $param->transit_time;
        $chargefromBase = $this->modelObj->getCustomerChargeFromBase($param->customer_id);
        $isTaxExempt = $this->modelObj->getTaxExemptStatus($param->customer_id);
        $waypointCount = 0;
        if (isset($param->waypoint_lists)) {
            $waypointCount = count($param->waypoint_lists); // per drop
            //$waypointCount = $param->delivery_postcodes_count; // per shipment
        }
        $charge_from_warehouse = ($chargefromBase['charge_from_base'] == 'YES') ? true : false;//  true;
        $is_tax_exempt = ($isTaxExempt['tax_exempt'] == 'YES') ? true : false;//  true;
        if (!isset($param->from_postcode)) {
            $error["from_postcode"] = "Collection postcode is mandatory";
        }
        if (!isset($param->to_postcode)) {
            $error["to_postcode"] = "Delivery postcode is mandatory";
        }
        if ($charge_from_warehouse) {
            $transit_distance += $param->warehouse_to_collection_point_distance;
            $transit_time += $param->warehouse_to_collection_point_time;
        }
        //$carrier = $this->modelObj->getCustomerCode($param->customer_id);
        $carrier = $this->modelObj->getCustomerCarrierData($param->customer_id, $param->company_id);
        $carriers = array();
        if (count($carrier) > 0) {
            foreach ($carrier as $carrierData) {
                if ($carrierData['is_self'] == 'YES') {
                    $service = $this->modelObj->getCustomerSamedayServiceData($param->customer_id, $param->company_id, $carrierData['courier_account_id']);
                    if (count($service) > 0) {
                        $tempservice = array();
                        foreach ($service as $key => $valData) {
                            $tempservice[] = $valData['service_code'];
                        }
                        $carriers[$carrierData['code']][] = array('credentials' => array('username' => '', 'password' => '', 'account_number' => $carrierData['account_number']), 'services' => implode(',', $tempservice));
                    } else {
                        return array("status" => "error", "message" => "Service Not configured or disabled for this customer");
                    }
                }
            }
        } else {
            return array("status" => "error", "message" => "Carrier Not configured or disabled for this customer");
        }
        $post_data = [];
        /*$post_data["credentials"] = array(
            "account_number" => $carrier['account_number'],
            "token" => $carrier['token']
        );*/
        foreach ($carriers as $key => $val) {
            $post_data["carriers"][] = array('name' => $key, 'account' => $val);
        }
        $post_data["from"] = array(
            "zip" => $param->origin->collection_postcode,
            "country" => "GBR"
        );
        $post_data["to"] = array(
            "zip" => $param->origin->delivery_postcode,
            "country" => "GBR"
        );
        $post_data["transit"] = array(array(
            "transit_distance" => number_format($transit_distance / 1609.344, 2), // coreprime api require miles
            "transit_time" => $transit_time / 60 % 60,//$transit_time, //coreprime api require minutes
            "number_of_collections" => $param->number_of_collections,
            "number_of_drops" => $waypointCount + 1,
            "total_waiting_time" => $param->total_waiting_time
        ));
        //$post_data["drops"] = [];

        $post_data["ship_date"] = date("Y-m-d h:i", strtotime($param->service_date));
        $post_data["currency"] = $carrierData['currency'];
        //$post_data["carrier"] = $carrier['code'];//$carrier["code"];
        //$post_data["service"] = "";
        $post_data["package"] = [array(
            "packaging_type" => "your_packaging"
        )];
        $post_data["extra"] = [];
        $post_data["insurance"] = [];

        $data = $this->_filterApiResponse(json_decode($this->_postRequest($post_data), true), $param->customer_id, $param->company_id, $charge_from_warehouse, $is_tax_exempt);

        if (isset($data->status) && ($data->status == "error")) {
            return $data;
        } else {
            return array("status" => "success", "rate" => $data, "availiable_balence" => $available_credit['available_credit']);
        }
    }

    private function _filterApiResponsewithAllowedServicesforCustomer($input, $customer_id, $company_id, $courier_id)
    {
        if (is_array($input)) {
            foreach ($input["rate"] as $key => $service_list) {
                $returndata = $this->modelObj->isServiceAvailableforCustomer(key($service_list), $customer_id, $company_id, $courier_id);
                if ($returndata) {
                    if (($returndata['courierstatus'] == 0) || ($returndata['status'] == 0)) {
                        unset($input["rate"][$key]);
                    }
                }
            }
            return json_decode(json_encode($input), 1);
        } else {
            return array();
        }
    }

    private function _calculateSamedayServiceccf($serviceCode, $service, $courier_id, $customer_id, $company_id)
    {
        $service_ccf_price = $this->customerccf->calculateServiceCcf($serviceCode, $service['price'], $courier_id, $customer_id, $company_id);
        $service_ccf_price['courier_id'] = $courier_id;
        $service['price'] = $service_ccf_price["price"] + $service['price'];

        $service['service_name'] = ($service_ccf_price["company_service_name"]) ? $service_ccf_price["company_service_name"] : $service_ccf_price["courier_service_name"];
        $service['service_code'] = ($service_ccf_price["company_service_code"]) ? $service_ccf_price["company_service_code"] : $service_ccf_price["courier_service_code"];
        $service['info'] = $service_ccf_price;
        $service['price_with_ccf'] = $service['price'];
        return $service;
    }

    private function _calculateSamedaySurchargeccf($surcharge_code, $customer_id, $company_id, $courier_id, $price)
    {
        $tempdatasurcharge = array();
        $surcharge_ccf_price = $this->customerccf->calculateSurchargeCcf($surcharge_code, $customer_id, $company_id, $courier_id, $price);
        $surcharge_ccf_price["original_price"] = $price;
        $tempdatasurcharge['surcharges'][$surcharge_code] = $surcharge_ccf_price['price'] + $price;
        $datatemp = array('price' => $surcharge_ccf_price['price'] + $price, 'info' => $surcharge_ccf_price, 'price_with_ccf' => $surcharge_ccf_price['price'] + $price);
        $tempdatasurcharge['ccf_surcharges'] = $datatemp;
        return $tempdatasurcharge;

    }

    private

    function _getCustomerAccountBalence($customer_id)
    {
        $available_credit = $this->modelObj->getCustomerAccountBalence($customer_id);
        if ($available_credit["available_credit"] <= 0) {
            return array("status" => "error", "message" => "you don't have sufficient balance,your current balance is " . $available_credit["available_credit"] . " .", "available_credit" => $available_credit['available_credit']);
        }
        return array("status" => "success", "message" => "sufficient balance.", "available_credit" => $available_credit['available_credit']);
    }

    public
    function getAllServices2($param)
    {
        $available_credit = $this->_getCustomerAccountBalence($param->customer_id);
        $response_filter_type = "min";
        $param->customer_id = $param->customer_id;
        $transit_distance = $param->transit_distance;
        $transit_time = $param->transit_time;
        $chargefromBase = $this->modelObj->getCustomerChargeFromBase($param->customer_id);
        $isTaxExempt = $this->modelObj->getTaxExemptStatus($param->customer_id);
        $waypointCount = 0;
        if (isset($param->waypoint_lists)) {
            $waypointCount = count($param->waypoint_lists);
        }
        $charge_from_warehouse = ($chargefromBase['charge_from_base'] == 'YES') ? true : false;//  true;
        $is_tax_exempt = ($isTaxExempt['tax_exempt'] == 'YES') ? true : false;//  true;
        if (!isset($param->from_postcode)) {
            $error["from_postcode"] = "Collection postcode is mandatory";
        }
        if (!isset($param->to_postcode)) {
            $error["to_postcode"] = "Delivery postcode is mandatory";
        }
        if ($charge_from_warehouse) {
            $transit_distance += $param->warehouse_to_collection_point_distance;
            $transit_time += $param->warehouse_to_collection_point_time;
        }
        $data = $this->_filterApiResponseManulal($param);
        if (isset($data->status) && ($data->status == "error")) {
            return $data;
        } else {
            return array("status" => "success", "rate" => $data, "availiable_balence" => $available_credit['available_credit']);
        }
    }

    private
    function _filterApiResponseManulal($input)
    { //print_r($input);die;
        //print_r($input);die;
        $selectedCarrier = json_decode($input->manualprice->selectedcarrier, 1);
        $selectedService = json_decode($input->manualprice->selected_service, 1);
        $servicePrice = $input->manualprice->service_price;
        $taxval = $input->manualprice->taxval;
        $surcharges = array();
        $surchargesPrice = array();

        if (isset($input->manualprice->newsurcharges) and count($input->manualprice->newsurcharges) > 0) {
            foreach ($input->manualprice->newsurcharges as $key => $val) {
                $surcharges[$key] = json_decode($val, 1);
            }
        }
        if (isset($input->manualprice->newsurchargesprice) and count($input->manualprice->newsurchargesprice) > 0) {
            foreach ($input->manualprice->newsurchargesprice as $key => $val) {
                $surchargesPrice[$key] = json_decode($val, 1);
            }
        }
        $returnarray = array();
        $data = array();
        $returnarray['rate'][$selectedCarrier['code']] = array();
        $data['taxes'] = array('total_tax' => 1.11, 'tax_percentage' => $taxval);
        $data['dimensions'] = array("length" => 0, "width" => 0, "height" => 0, "unit" => "IN");
        $data['weight'] = array("weight" => 0, "unit" => "KG");
        $data['time'] = array();
        $data['charge_from_base'] = 1;
        $data['max_delivery_time'] = '00:00:00';
        $data['rate_type'] = '';
        $data['message'] = '';
        $data['currency'] = '';


        $data['service_name'] = ($selectedService['company_service_name'] == '') ? $selectedService['service_name'] : $selectedService['company_service_name'];
        $data['base_price'] = $servicePrice;
        $data['icon'] = $selectedCarrier['icon'];

        $data['otherinfo']['original_price'] = $servicePrice;
        $data['otherinfo']['ccf_value'] = 0;
        $data['otherinfo']['operator'] = 'FLAT';
        $data['otherinfo']['price'] = 0;
        $data['otherinfo']['company_service_code'] = ($selectedService['company_service_code'] == '') ? $selectedService['service_code'] : $selectedService['company_service_code'];
        $data['otherinfo']['company_service_name'] = ($selectedService['company_service_name'] == '') ? $selectedService['service_name'] : $selectedService['company_service_name'];
        $data['otherinfo']['courier_service_code'] = $selectedService['service_code'];
        $data['otherinfo']['courier_service_name'] = $selectedService['service_name'];
        $data['otherinfo']['level'] = 0;
        $data['otherinfo']['service_id'] = $selectedService['service_id'];
        $data['otherinfo']['price_with_ccf'] = $servicePrice;
        $data['otherinfo']['courier_id'] = $selectedCarrier['courier_id'];
        $data['otherinfo']['autocarrier_id'] = $selectedCarrier['autocarrier_id'];
        $data['otherinfo']['accountkey'] = $selectedCarrier['account_number'];
        $totalSurcharges = 0;
        if (count($surcharges) > 0) {
            foreach ($surcharges as $keysur => $valsur) {
                $data['surcharges'][$valsur['surcharge_code']] = $surchargesPrice[$keysur];
                $data['surchargesinfo'][$valsur['surcharge_code']]['price'] = $surchargesPrice[$keysur];
                $data['surchargesinfo'][$valsur['surcharge_code']]['price_with_ccf'] = $surchargesPrice[$keysur];
                $data['surchargesinfo'][$valsur['surcharge_code']]['info']['original_price'] = $surchargesPrice[$keysur];
                $data['surchargesinfo'][$valsur['surcharge_code']]['info']['surcharge_value'] = 0;
                $data['surchargesinfo'][$valsur['surcharge_code']]['info']['operator'] = 'FLAT';
                $data['surchargesinfo'][$valsur['surcharge_code']]['info']['price'] = 0;
                $data['surchargesinfo'][$valsur['surcharge_code']]['info']['price_with_ccf'] = $surchargesPrice[$keysur];
                $data['surchargesinfo'][$valsur['surcharge_code']]['info']['company_surcharge_code'] = $valsur['company_surcharge_code'];
                $data['surchargesinfo'][$valsur['surcharge_code']]['info']['company_surcharge_name'] = $valsur['company_surcharge_name'];
                $data['surchargesinfo'][$valsur['surcharge_code']]['info']['courier_surcharge_code'] = $valsur['surcharge_code'];
                $data['surchargesinfo'][$valsur['surcharge_code']]['info']['courier_surcharge_name'] = $valsur['surcharge_name'];
                $data['surchargesinfo'][$valsur['surcharge_code']]['info']['level'] = 0;
                $data['surchargesinfo'][$valsur['surcharge_code']]['info']['surcharge_id'] = $valsur['surcharge_id'];
                $data['surchargesinfo'][$valsur['surcharge_code']]['info']['carrier_id'] = $selectedCarrier['courier_id'];
            }
            $totalSurcharges = array_sum($data['surcharges']);
        }
        $data['pricewithouttax'] = $totalSurcharges + $servicePrice;
        $total_tax_val = isset($data['taxes']['tax_percentage']) ? $data['taxes']['tax_percentage'] : 0;
        $data['chargable_tax'] = number_format((($data['pricewithouttax'] * $total_tax_val) / 100), 2, '.', '');
        $data['total_price'] = $data['pricewithouttax'] + $data['chargable_tax'];
        $returnarray['rate'][$selectedCarrier['code']][] = $data;
        if (is_array($returnarray)) {
            return json_decode(json_encode($returnarray));
        } else {
            return array("status" => "error", "message" => "Empty responses");
        }
    }

    public function filterServiceProvider($data)
    {
        $env = 'DEV';
        if (ENV == 'live') {
            $env = 'PROD';
        }
        $callType = 'RATE';
        if ($this->_isLabelCall) {
            $callType = 'LABEL';
        }
        $rateEngModel = new \v1\module\RateEngine\RateEngineModel();
        $providerList = $rateEngModel->getProviderInfo($callType, $env);
        $this->_endpoints = $providerList; 
        $filteredData = [];
        foreach ($data['carriers'] as $c) {
            foreach ($providerList as $p) {
                if ($p['code'] == $c['name'])
                    $filteredData[$p['provider']][] = $c;
            }
        }
        //print_r($filteredData);die;
        return $filteredData;
    }

    public function postToRateEngine($provider, $data)
    { 
        $url = "";
        foreach ($this->_endpoints as $ep) {
            if ($ep['provider'] == $provider) {
                $url = $ep['rate_endpoint'];
            }
        }

        $data_string = json_encode($data);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch); //print_r($server_output);die;
        curl_close($ch);
        return $server_output;
    }
    
    public function postToRateEngineUrl($url, $data)
    {                
        $data_string = json_encode($data);
        $ch = curl_init($url);        
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);//print_r($server_output);die('postToRateEngineUrl');
        curl_close($ch);
        return $server_output;
    }

    public function postToCorePrime($cpData)
    {//exit('core');
        $url = "";
        foreach ($this->_endpoints as $ep) {
            if ($ep['provider'] == 'Coreprime') {
                $url = $ep['rate_endpoint'];
            }
        }
        $data_string = json_encode($cpData);//echo $url;echo $data_string;die;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Authorization:' . $this->authorization_token,
                'Content-Length: ' . strlen($data_string))
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch); 
        curl_close($ch);
        return $server_output;
    }

    public function mergePrice(&$finalArray, $price)
    {
        if (trim($price) === "") return false;
        $price = json_decode($price);
        foreach ($price->rate as $k => $p) {
            $finalArray['rate'][$k] = $p;
        }
    }

    /* public function doLabelCall($data)
    { 
        $env = 'DEV';
        if (ENV == 'live')
            $env = 'PROD';
		
        $rateEngModel = new \v1\module\RateEngine\RateEngineModel();
        $providerList = $rateEngModel->getProviderInfo('LABEL', $env,'ENDPOINT');
        $obj = is_object ($data)?$data:json_decode($data);

        foreach ($providerList as $p) {
            if (strtolower($p['code']) == strtolower($obj->carrier)) {
                if ($p['provider'] == 'Coreprime') {
                    return $this->postToCorePrime($data);
                }
                if ($p['provider'] == 'Local') {
                    $this->_endpoints []=$p;
                    return $this->postToRateEngine($p['provider'],$data);
                }
            }

        }
        return [];
    } */
    
    public function doLabelCall($data)
    {   
		$res = $this->postToRateEngineUrl($data->providerInfo->endPointUrl,$data);         
		return ($res) ? $res : [];
    }
}
