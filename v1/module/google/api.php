<?php
    class Module_Google_Api extends Icargo
        {
        public function __construct($data)
            {
            $this->_parentObj = parent::__construct(array("email"=>$data->email, "access_token"=>$data->access_token));
            $this->libObj = new Library();
            }
        
        public function getGeoPositionFromPostcode($param)
            {
            $geoLocation = $this->libObj->get_lat_long_by_postcode($param->postcode);
            return $geoLocation;
            }

        private

        function _multipleDestinationsDistanceAndDuration($param)
            {  
            $matrix = $this->libObj->multiple_destinations_distance_and_duration(array("origin"=>$param["origin_geo_location"],"destinations"=>$param["destination_geo_location"],"departure_time"=>$param["departure_time"],"mode"=>$param["mode"]));
            if($matrix["status"]=="success")
                {
                if($param["mode"] == "bicycling")// save distance as duration_in_traffic
                    {
                    $matrix['data']->rows[0]->elements[0]->duration_in_traffic = $matrix['data']->rows[0]->elements[0]->duration;
                    }
                
                else if($param["mode"] == "transit")// save distance as duration_in_traffic
                    {
                    $matrix['data']->rows[0]->elements[0]->duration_in_traffic = $matrix['data']->rows[0]->elements[0]->duration;
                    }
                
                else if($param["mode"] == "walking")// save distance as duration_in_traffic
                    {
                    $matrix['data']->rows[0]->elements[0]->duration_in_traffic = $matrix['data']->rows[0]->elements[0]->duration;
                    }
                
                return $matrix;
                } else{
                    return $matrix;
                }
            }

        private

        function _convertMetersToMiles($meter)
            {
            return number_format($meter / 1609.344,2);
            }

        private

        function _getTransitDistanceText($param)
            {
            $data = "";
            switch($param["convert_to"]){
                case "mi":
                    $data = $this->_convertMetersToMiles($param["transit_distance"])." mi";//number_format($param["transit_distance"] / 1609.344,2)." mi";
                break;
            }
            return $data;
            }

        private

        function _getTransitTimeText($transit_time)
            {
            $duration_in_traffic = array();
            $hrs = (int)sprintf('%02d',$transit_time / 3600);
            $mins = (int)sprintf('%02d',$transit_time / 60 % 60);
            $secs = (int)sprintf('%02d',$transit_time % 60);
            
            if($hrs>0){
                if($hrs>10)
                    array_push($duration_in_traffic, "$hrs hours");
                else
                    array_push($duration_in_traffic, "$hrs hour");
            }
             
            if($mins>0){
                if($mins>10)
                    array_push($duration_in_traffic, "$mins mins");
                else
                    array_push($duration_in_traffic, "$mins min");
            }
                
            if($secs>0){
                if($secs>10)
                    array_push($duration_in_traffic, "$secs seconds");
                else
                    array_push($duration_in_traffic, "$secs second");
            }
            return implode(" ", $duration_in_traffic);
            }

        public function getGeolocationAndDistanceMatrix($param)
            {
            $destination = null;
            $destinations = array();
            $waypoints = array();
            $data = array();
            $counter = 0;
            $waiting_time = 0;//20*60; // minute to second
            
            $total_waiting_time = array();// push waiting time in seconds
            $transit_time = 0;
            $transit_distance = 0;

            $warehouse_to_collection_point_distance = 0;
            $warehouse_to_collection_point_time = 0;
            $warehouse_to_collection_point_distance_text = "";
            $warehouse_to_collection_point_time_text = "";

            $warehouse_id = $param->warehouse_id;
            $warehouse_latitude = $param->warehouse_latitude;
            $warehouse_longitude = $param->warehouse_longitude;

            $service_date = strtotime($param->service_date);
            $mode = "bicycling";//"walking";//"transit";//"driving";//"bicycling";

            //Request to coreprime api including warehouse to collection postcode distance
            $charge_from_warehouse = true;
            
            unset($param->email);
            unset($param->access_token);
            unset($param->company_id);
            unset($param->service_date);

            unset($param->warehouse_id);
            unset($param->warehouse_latitude);
            unset($param->warehouse_longitude);
            unset($param->endPointUrl);
                
            if(!isset($param->collection_postcodes))
                {
                return array("status"=>"error", "message"=>"Collection postcode is empty or invalid");
                }
            //check destination postcode is supplied or not
            if(!isset($param->delivery_postcodes))
                {
                //$param->delivery_postcodes = $param->collection_postcodes;
                return array("status"=>"error", "message"=>"Deliver postcode is empty or invalid");
                }
        
            foreach($param as $key => $items)
                {
                foreach($items as $key2 => $item)
                    {
                    if(count($item)>0)
                        {
                        if($key=="collection_postcodes")
                            {
                            $origin = array("geo_location"=>$item->geo_position->latitude.','.$item->geo_position->longitude,"postcodes"=>$item->postcode,"position"=>array("lat"=>$item->geo_position->latitude,"lng"=>$item->geo_position->longitude));
                            }
                        elseif($key=="delivery_postcodes")
                            {
                            array_push($destinations,array("geo_location"=>$item->geo_position->latitude.','.$item->geo_position->longitude,"postcodes"=>$item->postcode,"position"=>array("lat"=>$item->geo_position->latitude,"lng"=>$item->geo_position->longitude)));
                            }
                        }
                    }
                };
            
            array_unshift($destinations,$origin);
            
            $counter = count($destinations);
            
            for($i=0; $i<=$counter; $i++)
                {
                if(isset($destinations[$i+1]))
                    {
                    $origin_geo_location = $destinations[$i]["geo_location"];
                    $destination_geo_location = $destinations[$i+1]["geo_location"];
                    $position = $destinations[$i]["position"];
                    
                    $_waiting_time = $waiting_time;
                    if($i==0)
                        {
                        $_waiting_time = 0;
                        }
                    
                    $departure_time = $service_date + $_waiting_time;
                    
                    array_push($total_waiting_time, $_waiting_time);

                    $matrix = $this->_multipleDestinationsDistanceAndDuration(array("origin_geo_location"=>$origin_geo_location,"destination_geo_location"=>array($destination_geo_location),"departure_time"=>$departure_time,"mode"=>$mode));

                    if($matrix["status"]=="success")
                        {
                        $transit_time += $matrix['data']->rows[0]->elements[0]->duration_in_traffic->value;
                        $transit_distance += $matrix['data']->rows[0]->elements[0]->distance->value;
                        //save origin
                        if($i==0)
                            $origin = array("geo_location"=>$origin_geo_location, "collection_postcode"=>$destinations[$i]["postcodes"], "delivery_postcode"=>$destinations[$i+1]["postcodes"],"origin_addresses"=>$matrix['data']->origin_addresses,"destination_addresses"=>$matrix['data']->destination_addresses,"matrix"=>$matrix['data']->rows[0]->elements[0]);

                        $destination = array("geo_location"=>$destination_geo_location, "collection_postcode"=>$destinations[$i]["postcodes"], "delivery_postcode"=>$destinations[$i+1]["postcodes"], "origin_addresses"=>$matrix['data']->origin_addresses,"destination_addresses"=>$matrix['data']->destination_addresses,"matrix"=>$matrix['data']->rows[0]->elements[0]);

                        if($i>0)
                            $waypoints[$destinations[$i]["postcodes"]] = array("location"=>$position, "collection_postcode"=>$destinations[$i]["postcodes"], "delivery_postcode"=>$destinations[$i+1]["postcodes"], "origin_addresses"=>$matrix['data']->origin_addresses,"destination_addresses"=>$matrix['data']->destination_addresses,"matrix"=>$matrix['data']->rows[0]->elements[0]);

                        $service_date = $departure_time; 
                        }
                    else
                        {
                        break;
                        return $matrix;
                        }
                    }
                }

            if(!$destination)
                {
                $destination = array("geo_location"=>$origin['geo_location'],"delivery_postcodes"=>$origin['postcodes'],"position"=>$origin["position"]);
                }

            if($charge_from_warehouse){

                $collection_postcodes = array();
                foreach($param->collection_postcodes as $collection_postcode)
                    array_push($collection_postcodes, $collection_postcode->geo_position->latitude.",".$collection_postcode->geo_position->longitude);
                
                $matrix = $this->_multipleDestinationsDistanceAndDuration(array("origin_geo_location"=>$warehouse_latitude.",".$warehouse_longitude,"destination_geo_location"=>$collection_postcodes,"departure_time"=>$service_date,"mode"=>$mode));

                if($matrix["status"]=="success"){
                    $warehouse_to_collection_point_distance = $matrix["data"]->rows[0]->elements[0]->distance->value;
                    $warehouse_to_collection_point_time = $matrix["data"]->rows[0]->elements[0]->duration_in_traffic->value;
                    $warehouse_to_collection_point_distance_text = $this->_getTransitDistanceText(array("transit_distance"=>$warehouse_to_collection_point_distance,"convert_to"=>"mi"));
                    $warehouse_to_collection_point_time_text = $this->_getTransitTimeText($warehouse_to_collection_point_time);
                }else{
                    return $matrix;
                }
            }

            return array(
                "origin"=>$origin,
                "destination"=>$destination,
                "waypoints"=>$waypoints,
                "total_waiting_time"=>array_sum($total_waiting_time),
                "number_of_collections"=>count($param->collection_postcodes),
                "number_of_drops"=>count($param->delivery_postcodes),
                "transit_distance"=>$transit_distance, 
                "transit_time"=>$transit_time, 
                         
                "transit_distance_text"=>$this->_getTransitDistanceText(array("transit_distance"=>$transit_distance,"convert_to"=>"mi")),//"$distance_text mi",
                "transit_time_text"=>$this->_getTransitTimeText($transit_time),//implode(" ", $duration_in_traffic),
                         
                "collection_postcode"=>$origin["collection_postcode"],
                "waypoint_lists"=>array_keys($waypoints),
                "delivery_postcode"=>$destination["delivery_postcode"],

                "warehouse_to_collection_point_distance"=>$warehouse_to_collection_point_distance,
                "warehouse_to_collection_point_time"=>$warehouse_to_collection_point_time,
                "warehouse_to_collection_point_distance_text"=>$warehouse_to_collection_point_distance_text,
                "warehouse_to_collection_point_time_text"=>$warehouse_to_collection_point_time_text,

                "status"=>"success");
            }
        }
?>