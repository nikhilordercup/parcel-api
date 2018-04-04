<?php
    require_once('model/api.php');
    
    class Module_Coreprime_Api extends Icargo
        {
            
        public
            
        function __construct($data)
            {
            $this->_parentObj = parent::__construct(array("email"=>$data->email, "access_token"=>$data->access_token));
            $this->modelObj = new Coreprime_Model_Api();
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
            
        function _assembleResponseByMinimumPrice($param, $courier_commission=0)
            {
            $services = array();
            $total_surcharge = 0;
            $total_price = 0;
            $courier_commission_value = 0;
            $sort = array();
             
            foreach($param as $key=> $lists)
                {
                foreach($lists as $index=> $list)
                    {
                    $total_surcharge = 0;
                    $base_price = 0;
                    $total_price = 0;
                    $courier_commission_value = 0;
                    foreach($list as $list_key=>$items)
                        {
                        if(!isset($services[$list_key]))
                            {
                            $services[$list_key] = array();
                            }
                        foreach($items as $item)
                            {
                            $base_price = $item->rate->price;
                                
                            if(isset($item->surcharges))
                                $total_surcharge = array_sum((array)$item->surcharges);
                                
                            $courier_commission_value = (($total_surcharge + $base_price)*$courier_commission)/100;
                            
                            $total_price = number_format($total_surcharge + $base_price + $courier_commission_value, 2, '.', '');
                                
                            if(isset($services->$list_key->base_price))
                                {
                                if($services->$list_key->total_price > $total_price)
                                    {
                                    $sort[$list_key] = $total_price;
                                        $services[$list_key]["service_name"] = '79878989';$list_key;
                                    $services[$list_key]["rate_type"] = $item->rate->rate_type;
                                    $services[$list_key]["message"] = $item->rate->message;
                                        
                                    if(isset($item->rate->currency))
                                        {
                                        $services[$list_key]["currency"] = $item->rate->currency;
                                        }
                                        
                                    
                                        
                                    $services[$list_key]["total_price"] = $total_price;
                                    $services[$list_key]["courier_commission"] = $courier_commission;
                                    $services[$list_key]["courier_commission_value"] = $courier_commission_value;
                                    $services[$list_key]["base_price"] = $base_price;
                                    
                                    $services[$list_key]["charge_from_base"] = $item->service_options->charge_from_base;
                                    $services[$list_key]["icon"] = $item->service_options->icon;
                                    $services[$list_key]["max_delivery_time"] = $item->service_options->max_delivery_time;
                                    $services[$list_key]["dimensions"] = $item->service_options->dimensions;
                                    $services[$list_key]["weight"] = $item->service_options->weight;
                                    $services[$list_key]["time"] = $item->service_options->time;
                                    
                                    $services[$list_key]["taxes"] = array();
                                    if(isset($item->taxes))
                                        {
                                        $services[$list_key]["taxes"] = $item->taxes;
                                        }
                                        
                                    $services[$list_key]["surcharges"] = array();
                                    if(isset($item->surcharges))
                                        {
                                        $services[$list_key]["surcharges"] = $item->surcharges;
                                        }
                                    }
                                }
                            else
                                {
                                $sort[$list_key] = $total_price;
                                    $services[$list_key]["service_name"] = $list_key;//$item->service_options->category;//$list_key;
                                $services[$list_key]["rate_type"] = $item->rate->rate_type;
                                $services[$list_key]["message"] = $item->rate->message;
                                $services[$list_key]["currency"] = $item->rate->currency;
                                $services[$list_key]["total_price"] = $total_price;
                                $services[$list_key]["courier_commission"] = $courier_commission;
                                $services[$list_key]["courier_commission_value"] = $courier_commission_value;
                                $services[$list_key]["base_price"] = $base_price;
                                    
                                $services[$list_key]["charge_from_base"] = $item->service_options->charge_from_base;
                                $services[$list_key]["icon"] = $item->service_options->icon;
                                $services[$list_key]["max_delivery_time"] = $item->service_options->max_delivery_time;
                                $services[$list_key]["dimensions"] = $item->service_options->dimensions;
                                $services[$list_key]["weight"] = $item->service_options->weight;
                                $services[$list_key]["time"] = $item->service_options->time;
                                
                                $services[$list_key]["taxes"] = array();
                                if(isset($item->taxes))
                                    {
                                    $services[$list_key]["taxes"] = $item->taxes;
                                    }
                                $services[$list_key]["surcharges"] = array();
                                if(isset($item->surcharges))
                                    {
                                    $services[$list_key]["surcharges"] = $item->surcharges;
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
            $courier_commission = 0;
            $customerData = $this->modelObj->getCustomerCcfByCustomerId($param->customer_id);
            $transit_distance = $param->transit_distance;
            $transit_time = $param->transit_time;
            
            $waypointCount = 0;

            if(isset($param->waypoint_lists))
                $waypointCount = count($param->waypoint_lists);

            //Request to coreprime api including warehouse to collection postcode distance
            $charge_from_warehouse = true;

            if($customerData){
                // in percentage
                $courier_commission = $customerData["ccf"];
            }

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

            $carrier = $this->modelObj->getCustomerCode($param->customer_id);
            
            $post_data = [];
            $post_data["credentials"] = array(
                "account_number"=> "21232123",
                //"token"=> "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoyLCJlbWFpbCI6InNtYXJnZXNoQGdtYWlsLmNvbSIsImlzcyI6Ik9yZGVyQ3VwIG9yIGh0dHBzOi8vd3d3Lm9yZGVyY3VwLmNvbS8iLCJpYXQiOjE1MDI4MjQ3NTJ9.qGTEGgThFE4GTWC_jR3DIj9NpgY9JdBBL07Hd-6Cy-0"
                "token"=> "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoxLCJlbWFpbCI6ImRldmVsb3BlcnNAb3JkZXJjdXAuY29tIiwiaXNzIjoiT3JkZXJDdXAgb3IgaHR0cHM6Ly93d3cub3JkZXJjdXAuY29tLyIsImlhdCI6MTQ5Njk5MzU0N30.cpm3XYPcLlwb0njGDIf8LGVYPJ2xJnS32y_DiBjSCGI"
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
                
            $post_data["ship_date"] = $param->service_date;
            $post_data["currency"] = "GBP";
            $post_data["carrier"] = "pnp";//$carrier["code"];
            $post_data["service"] = "";
            $post_data["package"] = [array(
                "packaging_type"=> "your_packaging"
                )];
            $post_data["extra"] = [];
            $post_data["insurance"] = [];
            $post_data["constants"] = [];
            
            $data = $this->_filterApiResponse(json_decode($this->_postRequest($post_data), true));
            
            if(count($data)>0){
                switch(strtoupper($response_filter_type))
                {
                    case 'MIN':
                        $data = $this->_assembleResponseByMinimumPrice($data, $courier_commission);
                        return $data;
                        break;
                }
            }
            else{
                return array("status"=>"error","message"=>"Rating api return null response");
            }
            
            }
        }
?>
