<?php
/*
* Optimize_Route is used to re-optimize the route and this class is used only with app
*  {"route_index":0,"latitude":"51.76664577","longitude":"-1.27228913","temp_route_id":"1902","data_index":0,"shipment_id":"141"}
*/
class Optimize_Route
{
    private $_lat_lng = array();
    
    public function __construct($params)
    {
        if(isset($params->destination_postcode_and_drop))
        {
            $this->destination_postcode_and_drop = $params->destination_postcode_and_drop;
        }
        if(isset($params->shipment_route_id))
        {
            $this->shipment_route_id = $params->shipment_route_id;
        }
        if(isset($params->subcode))
        {
            $this->subcode = $params->subcode;
        }
        if(isset($params->source_postcode))
        {
            $this->source_postcode = $params->source_postcode;
        }
        if(isset($params->warehouse_id))
        {
            $this->warehouse_id = $params->warehouse_id;
        }
        if(isset($params->post_id))
        {
            $this->post_id = $params->post_id;
        }
        if(isset($params->last_drop))
        {
            $this->last_drop = $params->last_drop;
        }
        if(isset($params->lat))
        {
            $this->latitude = $params->lat;
        }
        if(isset($params->lng))
        {
            $this->longitude = $params->lng;
        }
        $this->library_obj = new Library();
    }
    
    private function _set_lat_long_series()
    {
        foreach($this->destination_postcode_and_drop as $key=>$postcode_and_drop){
            if($key<7)
            {
                $latLng = array(
                    "route_index"=>"0",
                    "postcode"=>$postcode_and_drop->postcode,
                    "address"=>"$postcode_and_drop->drops,$postcode_and_drop->postcode,$postcode_and_drop->shipment_address1",
                    "latitude"=>$postcode_and_drop->latitude,
                    "longitude"=>$postcode_and_drop->longitude,
                    "shipment_id"=>$postcode_and_drop->shipment_id,
                    "temp_route_id"=>$this->shipment_route_id,
                    "drop"=>"$postcode_and_drop->drops",
                    "data_index"=>"0"
                    );
                array_push($this->_lat_lng,$latLng);
            }
        }
    }
    
    private function _set_first_lat_long()
    {
        if($this->subcode=='warehouse')
        {
            $data = $this->library_obj->get_lat_long_by_postcode($this->source_postcode,'','',"Warehouse");
            array_push($this->_lat_lng, array(
                "route_index"=>"0",
                "postcode"=>$this->source_postcode,
                "address"=>"Warehouse",
                "latitude"=>$data['latitude'],
                "longitude"=>$data['longitude'],
                "shipment_id"=>"0",
                "temp_route_id"=>$this->shipment_route_id,
                "drop"=>"Warehouse",
                "data_index"=>"0"
            ));    
        }
            
        else if($this->subcode=='specific_postcode')
        {
            $data = $this->library_obj->get_lat_long_by_postcode($this->source_postcode,'','',"Specific Postcode");
            array_push($this->_lat_lng, array(
                "route_index"=>"0",
                "postcode"=>$this->source_postcode,
                "address"=>"Specific Postcode",
                "latitude"=>$data['latitude'],
                "longitude"=>$data['longitude'],
                "shipment_id"=>"0",
                "temp_route_id"=>$this->shipment_route_id,
                "drop"=>"Specific Postcode",
                "data_index"=>"0"
            )); 
        }
            
        else if($this->subcode=='current_location')
        {
            array_push($this->_lat_lng, array(
                "route_index"=>"0",
                "postcode"=>"",
                "address"=>"Current Location",
                "latitude"=>$this->latitude,
                "longitude"=>$this->longitude,
                "shipment_id"=>"0",
                "temp_route_id"=>$this->shipment_route_id,
                "drop"=>"Current Location",
                "data_index"=>"0"
            ));
        }
    }
    
    private function _set_last_lat_long()
    {
        $data = $this->library_obj->get_lat_long_by_postcode($this->last_drop->postcode,'','',$this->last_drop->location);
        array_push($this->_lat_lng, array(
            "route_index"=>"0",
            "postcode"=>$this->last_drop->postcode,
            "address"=>$this->last_drop->location,
            "latitude"=>$data['latitude'],
            "longitude"=>$data['longitude'],
            "shipment_id"=>"0",
            "temp_route_id"=>$this->shipment_route_id,
            "data_index"=>"0"
        ));
    }
    
    public function optimize()
    {
        $this->_set_first_lat_long();
        
        $this->_set_lat_long_series();
        
        $this->_set_last_lat_long();
        $obj = new Route_Optimize(array("geo_data"=>json_encode($this->_lat_lng),"warehouse_id"=>$this->warehouse_id));
	    $data = $obj->tour();
        
        
        //remove the last element from $data because last location is warehouse
        array_pop($data['data']);
       
        //remove the first element from $data because first location is warehouse
        $optimized_from = array_shift($data['data']);
        
        //prepare response
        $response = array();
        $response['route'] = array();
        $counter = 1;
        
        foreach($data['data'] as $value)
        {
            $temp = explode('__SEPARATOR__',$value['address_string']);
            array_push($response['route'], array('icargo_execution_order'=>$counter, "shipment_id"=>$value['shipment_id'], "shipment_route_id"=>$value['temp_route_id'],'drop'=>$temp[4],'post_id'=>$this->post_id));
            $counter++;
        }
        $response['status']  = $data['status'];
        $response['message'] = $data['message'];
        $response['route_optimized_from'] = $optimized_from;
        return $response;
    }
}
?>