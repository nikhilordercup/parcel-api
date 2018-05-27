<?php
require_once('model/api.php');

class Module_Coreprime_Api extends Icargo
{

    public

    function __construct($data)
    {
        $this->_parentObj = parent::__construct(array("email" => $data->email, "access_token" => $data->access_token));
        $this->modelObj = new Coreprime_Model_Api();
        $this->customerccf = new CustomerCostFactor();
    }

    private

    function _postRequest($data)
    {
        $data_string = json_encode($data);
        $ch = curl_init('http://occore.ordercup1.com/api/v1/rate');
        //$ch = curl_init('http://occore.ordercup.com/api/v1/rate');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Authorization:eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoxLCJlbWFpbCI6Im1hcmdlc2guc29uYXdhbmVAb3JkZXJjdXAuY29tIiwiaXNzIjoiT3JkZXJDdXAgb3IgaHR0cHM6Ly93d3cub3JkZXJjdXAuY29tLyIsImlhdCI6MTQ5Mzk2ODgxMX0.EJc4SVQXIwZibVuXFxkTo8UjKvH8S9gWyuFn9bsi63g',
                'Content-Length: ' . strlen($data_string))
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
<<<<<<< HEAD
        curl_close($ch);
=======
        curl_close($ch); 
        
       //$server_output =  '{"rate":{"PNP":[{"21232123":[{"standard_same_day":[{"rate":{"flow_type":"Domestic","price":14.12,"rate_type":"Distance","act_number":"21232123","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":12,"width":12,"height":12,"unit":"IN"},"weight":{"weight":10,"unit":"KG"},"time":{"max_waiting_time":45,"unit":"MIN"},"category":"standard_same_day","charge_from_base":false,"icon":"/icons/original/missing.png","max_delivery_time":"09:32:41"},"surcharges":{"same_day_drop_surcharge":1.0,"collection_surcharge":0},"taxes":{"total_tax":0.925,"tax_percentage":10.0}}]},{"asap":[{"rate":{"flow_type":"Domestic","price":16.62,"rate_type":"Distance","act_number":"21232123","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":12,"width":12,"height":12,"unit":"IN"},"weight":{"weight":10,"unit":"KG"},"time":{"max_waiting_time":15,"unit":"MIN"},"category":"asap","charge_from_base":false,"icon":"/icons/original/missing.png","max_delivery_time":"09:26:50"},"surcharges":{"same_day_drop_surcharge":1.0,"collection_surcharge":0},"taxes":{"total_tax":0.925,"tax_percentage":10.0}}]},{"one_hour":[{"rate":{"flow_type":"Domestic","price":15.12,"rate_type":"Distance","act_number":"21232123","message":null,"currency":"GBP"},"service_options":{"dimensions":{"length":12,"width":12,"height":12,"unit":"IN"},"weight":{"weight":10,"unit":"KG"},"time":{"max_waiting_time":45,"unit":"MIN"},"category":"1_hour_delivery","charge_from_base":false,"icon":"/icons/original/missing.png","max_delivery_time":"09:31:53"},"surcharges":{"same_day_drop_surcharge":1.0,"collection_surcharge":0},"taxes":{"total_tax":0.925,"tax_percentage":10.0}}]}]}]}}';
       // echo $server_output;die;
>>>>>>> 01937db73205efdb42a04006d6227323da32e2da
        return $server_output;
    }
    private
<<<<<<< HEAD

    function _assembleResponseByMinimumPrice($param)
    {
        $services = array();
        $total_surcharge = 0;
        $total_price = 0;
        $sort = array();
        foreach ($param as $key => $lists) {
            foreach ($lists as $index => $list) {
                $total_surcharge = 0;
                $base_price = 0;
                $total_price = 0;

                foreach ($list as $list_key => $items) {
                    if (!isset($services[$list_key])) {
                        $services[$list_key] = array();
                    }
                    foreach ($items as $item) {
                        $base_price = $item->rate->price;
                        if (isset($item->ccf_surcharges)) {
                            foreach ($item->ccf_surcharges->alldata as $surcharge_name => $surcharge_val) {
                                $total_surcharge += $surcharge_val['price'];
                            }
                        }
                        $services[$list_key]["taxes"] = array();
                        if (isset($item->taxes)) {
                            $services[$list_key]["taxes"] = $item->taxes;
                        }
                        $total_price = number_format($total_surcharge + $base_price + $services[$list_key]["taxes"]->total_tax, 2, '.', '');
                        if (isset($services->$list_key->base_price)) {
                            if ($services->$list_key->total_price > $total_price) {
                                $sort[$list_key] = $total_price;
                                $services[$list_key]["service_name"] = $item->rate->service_name;
                                $services[$list_key]["rate_type"] = $item->rate->rate_type;
                                $services[$list_key]["message"] = $item->rate->message;

                                if (isset($item->rate->currency)) {
                                    $services[$list_key]["currency"] = $item->rate->currency;
                                }
                                $services[$list_key]["total_price"] = $total_price;
                                $services[$list_key]["otherinfo"] = $item->rate->info;
                                //$services[$list_key]["courier_commission"] = $courier_commission;
                                //$services[$list_key]["courier_commission_value"] = $courier_commission_value;
                                $services[$list_key]["base_price"] = $base_price;
                                $services[$list_key]["charge_from_base"] = $item->service_options->charge_from_base;
                                $services[$list_key]["icon"] = $item->service_options->icon;
                                $services[$list_key]["max_delivery_time"] = $item->service_options->max_delivery_time;
                                $services[$list_key]["dimensions"] = $item->service_options->dimensions;
                                $services[$list_key]["weight"] = $item->service_options->weight;
                                $services[$list_key]["time"] = $item->service_options->time;
                                $services[$list_key]["surcharges"] = array();
                                if (isset($item->surcharges)) {
                                    $services[$list_key]["surcharges"] = $item->surcharges;
                                    $services[$list_key]["surchargesinfo"] = $item->ccf_surcharges[$list_key]['info'];
                                }
                            }
                        } else {
                            $sort[$list_key] = $total_price;
                            $services[$list_key]["service_name"] = $item->rate->service_name;//$item->service_options->category;//$list_key;
                            $services[$list_key]["rate_type"] = $item->rate->rate_type;
                            $services[$list_key]["message"] = $item->rate->message;
                            $services[$list_key]["currency"] = $item->rate->currency;
                            $services[$list_key]["total_price"] = $total_price;
                            $services[$list_key]["otherinfo"] = $item->rate->info;
                            //$services[$list_key]["courier_commission"] = $courier_commission;
                            //$services[$list_key]["courier_commission_value"] = $courier_commission_value;
                            $services[$list_key]["base_price"] = $base_price;
                            //$services[$list_key]["charge_from_base"] = $item->service_options->charge_from_base;
                            $services[$list_key]["icon"] = $item->service_options->icon;
                            $services[$list_key]["max_delivery_time"] = $item->service_options->max_delivery_time;
                            $services[$list_key]["dimensions"] = $item->service_options->dimensions;
                            $services[$list_key]["weight"] = $item->service_options->weight;
                            $services[$list_key]["time"] = $item->service_options->time;
                            $services[$list_key]["surcharges"] = array();
                            $services[$list_key]["surchargesinfo"] = array();
                            if (isset($item->surcharges)) {
                                $services[$list_key]["surcharges"] = $item->surcharges;
                                $services[$list_key]["surchargesinfo"] = $item->ccf_surcharges->alldata;
                            }
                        }
                    }
                }
            }
        }
        array_multisort($sort, SORT_ASC, $services);
        return array("status" => "success", "service_lists" => $services, "best_price" => array_shift($services));
    }

    private
    function _filterApiResponse($input)
    {
        if (is_array($input)) {
            foreach ($input["rate"] as $key => $service_list) {
                foreach ($service_list as $service_key => $list) {
                    foreach ($list as $list_key => $item) {
                        if (isset($item["rate"]["error"])) {
                            unset($input["rate"][$key][$service_key]);
                            if (count($input["rate"][$key]) == 0) {
                                unset($input["rate"][$key]);
=======
    function _filterApiResponse($input,$customer_id,$company_id)
    {   
        if (is_array($input)) {
        $temparray = array();
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
                            }else{
                              $total_surcharge = 0;
                              $base_price = 0;
                              $total_price = 0;
                              $total_tax = 0;
                              $carrier = $this->modelObj->getCarrierIdByCode($carriercode);
                              $res = $this->customerccf->calculate($servicecode,$item,$carrier['id'],$customer_id,$company_id);
                              $base_price = $res['rate']['price'];
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
                            $temparray["rate"][$carriercode][$key]["service_name"] = $res['rate']['service_name'];
                            $temparray["rate"][$carriercode][$key]["rate_type"] = $res['rate']['rate_type'];
                            $temparray["rate"][$carriercode][$key]["message"] = $res['rate']['message'];
                            $temparray["rate"][$carriercode][$key]["currency"] = $res['rate']['currency'];
                            $temparray["rate"][$carriercode][$key]["total_price"] = $total_price;
                            $temparray["rate"][$carriercode][$key]["otherinfo"] = $res['rate']['info'];
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
>>>>>>> 01937db73205efdb42a04006d6227323da32e2da
                            }
                           }
                         }
                        }
                      }
                    }
                }
<<<<<<< HEAD
            }
            return json_decode(json_encode($input));
=======
            } 
            return json_decode(json_encode($temparray));
>>>>>>> 01937db73205efdb42a04006d6227323da32e2da
        } else {
            return array();
        }
    }

    public
    function getAllServices($param)
    {
        $response_filter_type = "min";
        $param->customer_id = $param->customer_id;
        $transit_distance = $param->transit_distance;
        $transit_time = $param->transit_time;
        $waypointCount = 0;
        if (isset($param->waypoint_lists)) {
            $waypointCount = count($param->waypoint_lists);
        }
        $charge_from_warehouse = true;
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
<<<<<<< HEAD
        $carrier = $this->modelObj->getCustomerCarrierData($param->customer_id, $param->company_id, 1);
        $post_data = [];
        $post_data["credentials"] = array(
            "account_number" => $carrier['account_number'],
            "token" => $carrier['token']
        );
=======
        $carrier = $this->modelObj->getCustomerCarrierData($param->customer_id, $param->company_id);
        $carriers =  array();
        if(count($carrier)>0){ 
          foreach($carrier as $carrierData){
            $service = $this->modelObj->getCustomerSamedayServiceData($param->customer_id, $param->company_id, $carrierData['courier_id']);
            if(count($service)>0){
               $tempservice = array();
               foreach($service as $key=>$valData){
                   $tempservice[] = $valData['service_code'];
               }  
               $carriers[] = array('name'=>$carrierData['code'],'account'=>array(array('credentials'=>array('username'=>'','password'=>'','account_number'=>$carrierData['account_number']),'services'=>implode(',',$tempservice))));
              }else{
                return array("status" => "error", "message" => "Service Not configured or disabled for this customer");
              }   
           }
        }else{
          return array("status" => "error", "message" => "Carrier Not configured or disabled for this customer");  
        }
        $post_data = [];
        /*$post_data["credentials"] = array(
            "account_number" => $carrier['account_number'],
            "token" => $carrier['token']
        );*/
        $post_data["carriers"] = $carriers;
>>>>>>> 01937db73205efdb42a04006d6227323da32e2da
        $post_data["from"] = array(
            "zip" => $param->origin->collection_postcode,
            "country" => "GBR"
        );
        $post_data["to"] = array(
            "zip" => $param->origin->delivery_postcode,
            "country" => "GBR"
        );
        $post_data["transit"] = array(array(
            "transit_distance" => number_format($transit_distance / 1609.344, 2), $transit_distance, // coreprime api require miles
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
<<<<<<< HEAD
        $post_data["constants"] = [];
        //$input = $this->_filterApiResponsewithAllowedServicesforCustomer(json_decode($this->_postRequest($post_data), true),$param->customer_id, $param->company_id,$carrier['courier_id']);
        //$data = $this->_filterApiResponse($input);
        $data = $this->_filterApiResponse(json_decode($this->_postRequest($post_data), true));
        $this->customerccf->calculate($data, $carrier['courier_id'], $param->customer_id, $param->company_id);
        if (count($data) > 0) {
            switch (strtoupper($response_filter_type)) {
                case 'MIN':
                    $data = $this->_assembleResponseByMinimumPrice($data);
                    return $data;
                    break;
            }
        } else {
            return array("status" => "error", "message" => "Rating api return null response");
        }

    }

    private function _filterApiResponsewithAllowedServicesforCustomer($input, $customer_id, $company_id, $courier_id)
    {
=======
        $data = $this->_filterApiResponse(json_decode($this->_postRequest($post_data), true),$param->customer_id, $param->company_id);
       return array("status" => "success","rate"=>$data);
    }

    private function _filterApiResponsewithAllowedServicesforCustomer($input, $customer_id, $company_id, $courier_id)
    {  
>>>>>>> 01937db73205efdb42a04006d6227323da32e2da
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
<<<<<<< HEAD
}

=======
}   
>>>>>>> 01937db73205efdb42a04006d6227323da32e2da
?>