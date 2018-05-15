<?php
require_once('model/api.php');

class Module_Coreprime_Api extends Icargo
{

    public

    function __construct($data)
    {
        $this->_parentObj = parent::__construct(array("email"=>$data->email, "access_token"=>$data->access_token));
        $this->modelObj = new Coreprime_Model_Api();
        $this->customerccf = new CustomerCostFactor();
    }

    private

    function _postRequest($data)
    {
        $data_string = json_encode($data);

        //$ch = curl_init('http://occore.ordercup1.com/api/v1/rate');
        $ch = curl_init('http://occore.ordercup.com/api/v1/rate');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec ($ch);
        curl_close ($ch);
        return $server_output;
    }

    private

    function _assembleResponseByMinimumPrice($param)
    {
        $services = array();
        $total_surcharge = 0;
        $total_price = 0;
        $sort = array();
        foreach($param as $key=> $lists)
        {
            foreach($lists as $index=> $list)
            {
                $total_surcharge = 0;
                $base_price = 0;
                $total_price = 0;

                foreach($list as $list_key=>$items)
                {
                    if(!isset($services[$list_key]))
                    {
                        $services[$list_key] = array();
                    }
                    foreach($items as $item)
                    {
                        $base_price = $item->rate->price;
                        if(isset($item->ccf_surcharges)){
                            foreach($item->ccf_surcharges->alldata as $surcharge_name=>$surcharge_val){
                                $total_surcharge += $surcharge_val['price'];
                            }
                        }
                        $services[$list_key]["taxes"] = array();
                        if(isset($item->taxes)){
                            $services[$list_key]["taxes"] = $item->taxes;
                        }
                        $total_price = number_format($total_surcharge + $base_price + $services[$list_key]["taxes"]->total_tax,2,'.','');
                        if(isset($services->$list_key->base_price))
                        {
                            if($services->$list_key->total_price > $total_price)
                            {
                                $sort[$list_key] = $total_price;
                                $services[$list_key]["service_name"] = $item->rate->service_name;
                                $services[$list_key]["rate_type"] = $item->rate->rate_type;
                                $services[$list_key]["message"] = $item->rate->message;

                                if(isset($item->rate->currency))
                                {
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
                                if(isset($item->surcharges))
                                {
                                    $services[$list_key]["surcharges"] = $item->surcharges;
                                    $services[$list_key]["surchargesinfo"] = $item->ccf_surcharges[$list_key]['info'];
                                }
                            }
                        }
                        else
                        {
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
                            if(isset($item->surcharges))
                            {
                                $services[$list_key]["surcharges"] = $item->surcharges;
                                $services[$list_key]["surchargesinfo"] = $item->ccf_surcharges->alldata;
                            }
                        }
                    }
                }
            }
        }
        array_multisort($sort, SORT_ASC, $services);
        return array("status"=>"success","service_lists"=>$services,"best_price"=>array_shift($services));
    }

    private
    function _filterApiResponse($input)
    {
        if(is_array($input)){
            foreach($input["rate"] as $key=>$service_list){
                foreach($service_list as $service_key=>$list){
                    foreach($list as $list_key=>$item){
                        if(isset($item["rate"]["error"])){
                            unset($input["rate"][$key][$service_key]);
                            if(count($input["rate"][$key])==0){
                                unset($input["rate"][$key]);
                            }
                        }
                    }
                }
            }
            return json_decode(json_encode($input));
        }else{
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
        if(isset($param->waypoint_lists)){
            $waypointCount = count($param->waypoint_lists);
        }
        $charge_from_warehouse = true;
        if(!isset($param->from_postcode)){
            $error["from_postcode"] = "Collection postcode is mandatory";
        }
        if(!isset($param->to_postcode)){
            $error["to_postcode"] = "Delivery postcode is mandatory";
        }
        if($charge_from_warehouse){
            $transit_distance += $param->warehouse_to_collection_point_distance;
            $transit_time += $param->warehouse_to_collection_point_time;
        }
        //$carrier = $this->modelObj->getCustomerCode($param->customer_id);
        $carrier = $this->modelObj->getCustomerCarrierData($param->customer_id,$param->company_id,1);
        $post_data = [];
        $post_data["credentials"] = array(
            "account_number"=> $carrier['account_number'],
            "token"=> $carrier['token']
        );
        $post_data["from"] = array(
            "zip"=>$param->origin->collection_postcode,
            "country"=>"GBR"
        );
        $post_data["to"] = array(
            "zip"=>$param->origin->delivery_postcode,
            "country"=>"GBR"
        );
        $post_data["transit"] = array(array(
            "transit_distance"=> number_format($transit_distance / 1609.344,2), $transit_distance, // coreprime api require miles
            "transit_time"=> $transit_time / 60 % 60,//$transit_time, //coreprime api require minutes
            "number_of_collections"=> $param->number_of_collections,
            "number_of_drops"=> $waypointCount+1,
            "total_waiting_time"=> $param->total_waiting_time
        ));
        $post_data["drops"] = [];

        $post_data["ship_date"] = date("Y-m-d h:i", strtotime($param->service_date));
        $post_data["currency"] = $carrier['currency'];
        $post_data["carrier"] = $carrier['code'];//$carrier["code"];
        $post_data["service"] = "";
        $post_data["package"] = [array(
            "packaging_type"=> "your_packaging"
        )];
        $post_data["extra"] = [];
        $post_data["insurance"] = [];
        $post_data["constants"] = [];
      //$input = $this->_filterApiResponsewithAllowedServicesforCustomer(json_decode($this->_postRequest($post_data), true),$param->customer_id, $param->company_id,$carrier['courier_id']);
      //$data = $this->_filterApiResponse($input);
        $data = $this->_filterApiResponse(json_decode($this->_postRequest($post_data), true));
        $this->customerccf->calculate($data, $carrier['courier_id'], $param->customer_id, $param->company_id);
        if(count($data)>0){
            switch(strtoupper($response_filter_type))
            {
                case 'MIN':
                    $data = $this->_assembleResponseByMinimumPrice($data);
                    return $data;
                    break;
            }
        }
        else{
            return array("status"=>"error","message"=>"Rating api return null response");
        }

    }
    private function _filterApiResponsewithAllowedServicesforCustomer($input,$customer_id,$company_id,$courier_id){
       if(is_array($input)){
            foreach($input["rate"] as $key=>$service_list){
               $returndata =  $this->modelObj->isServiceAvailableforCustomer(key($service_list),$customer_id,$company_id,$courier_id);
               if($returndata){
                 if(($returndata['courierstatus'] == 0) || ($returndata['status'] == 0)){
                  unset($input["rate"][$key]); 
                 }   
               }      
             }
        return json_decode(json_encode($input),1);
        }else{
            return array();
        } 
    }
}
?>