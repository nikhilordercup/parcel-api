<?php
class Route_Optimize extends Library
	{
	private $_locations = array();	
	private $_eta = false;
	private $_warehouse_id = 0;
	
	public

	function __construct($param)
		{
		$this->db = new DbHandler();
		$this->geo_data = json_decode($param['geo_data']);
		if(isset($param['eta']))
			{
			$this->_eta = $param['eta'];
			}
		if(isset($param['warehouse_id']))
			{
			$this->_warehouse_id = $param['warehouse_id'];
			}
		
		}
	
	private
	
	function _optimize_using_routexl()
		{
		try
			{
			// Use libcurl to connect and communicate
			$ch = curl_init(); // Initialize a cURL session
			curl_setopt($ch, CURLOPT_URL, 'https://api.routexl.nl/tour'); // Set the URL
			curl_setopt($ch, CURLOPT_HEADER, 0); // No header in the output
			curl_setopt($ch, CURLOPT_POST, 1); // Do a regular HTTP POST
			
			if (!$this->_eta)
				{
				curl_setopt($ch, CURLOPT_POSTFIELDS, 'locations=' . json_encode($this->_locations)); // Add the locations
				}
			else
				{
				curl_setopt($ch, CURLOPT_POSTFIELDS, 'locations=' . json_encode($this->_locations) . '&skipOptimisation=' . $this->_eta); // Add the locations
				}

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Return the output as a string
			curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate'); // Compress
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); // Basic authorization
			curl_setopt($ch, CURLOPT_USERPWD, 'nikhil122:pcs@Roopesh'); // Your credentials

			// curl_setopt($ch, CURLOPT_USERPWD, 'roopesh:923674'); // Your credentials
			// Do not use this!
			if (false) curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Unsafe!

			// Execute the given cURL session
			$output = curl_exec($ch); // Get the output

			$this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Last received HTTP code
			$this->error = curl_error($ch); // Get the last error
			curl_close($ch); // Close the connection
			if($output)
				{
				$result = json_decode($output);
				if (is_object($result))
					{
					$message = (!$this->_eta) ? "Route has been optimize successfully" : "eta successful";
					return array("status"=>true, "message"=>$message,"data"=>$result);
					}
				  else
					{
					return array("status"=>false, "message"=>$output);
					}
				}
			  else
			  	{
				return array("status"=>false, "message"=>"Api return empty result");	
				}
			}
		catch(exception $e)
			{
			return array("status"=>false, "message"=>"Api return error result","error"=>$e->getMessage());	
			}

		}
	
	private 
	
	function prepareRoutexlPostData()
		{
		//$counter = 0;
        
        foreach($this->geo_data as $data)
			{
            //if($counter<9){
                $drop = (isset($data->drop)) ? $data->drop : "";
				array_push($this->_locations,array("address"=>$data->temp_route_id."__SEPARATOR__".$data->data_index."__SEPARATOR__".$data->shipment_id."__SEPARATOR__".$data->route_index."__SEPARATOR__".$drop,"lat"=>$data->latitude,"lng"=>$data->longitude));
			//}
			//$counter++;
			}
		}
		
	private
	
	function _update_optimization_order($shipment_id, $order,$duration=0)
		{
        $status = $this->db->update('temp_routes_shipment', array('execution_order'=>$order,'drop_execution_order'=>$order,'estimatedtime'=>$duration),'shipment_id IN('.$shipment_id.')');
		return $status;
		}
	
	private 
        
    function _update_eta($shipment_id, $distance_miles, $estimated_time)
		{
		$status = $this->db->update('temp_routes_shipment', array('distancemiles'=>$distance_miles, 'estimatedtime'=>$estimated_time),'temp_ship_id IN('.$shipment_id.')');
		return $status;
		}
	
	private
	
	function _get_warehouse_geo_data()
		{
		$record = $this->db->getRowRecord("SELECT id, name, address_1, address_2, postcode, city, latitude, longitude FROM " . DB_PREFIX . "warehouse WHERE id='$this->_warehouse_id'");
		return $record;
		}
		
	public

	function tour()
		{ 
		$this->prepareRoutexlPostData();
		$results = $this->_optimize_using_routexl();               
        $data = array();
		if($results['status'])
			{
			$items = $results['data'];
			$execution_order = 0;
			foreach($items->route as $key => $item)
				{
                                $time=$item->duration;
				$temp = explode("__SEPARATOR__",$item->name);
                $this->_update_optimization_order($temp[2],++$key,$time);
				array_push($data, array('execution_order'=>++$execution_order,'temp_route_id'=>$temp[0],'data_index'=>$temp[1],'shipment_id'=>$temp[2],'route_index'=>$temp[3],'address_string'=>$item->name));
				}
			return array("status"=>true,"message"=>$results['message'],"data"=>$data);
			}
		  else
		  	{
			return $results;
			}
		}
		
	public

	function eta()
		{
		$geo_data = $this->_get_warehouse_geo_data();
		
		array_push($this->_locations,array("address"=>$geo_data['name']."__SEPARATOR__".$geo_data['address_1']."__SEPARATOR__".$geo_data['address_2']."__SEPARATOR__".$geo_data['city']."__SEPARATOR__".$geo_data['postcode']."__SEPARATOR__".$geo_data['id'],"lat"=>$geo_data['latitude'],"lng"=>$geo_data['longitude']));
		$this->prepareRoutexlPostData();
		
		$results = $this->_optimize_using_routexl();
		
		$data = array();
		if($results['status'])
			{
			$items = $results['data'];
			$execution_order = 0;
			foreach($items->route as $key => $item)
				{
				$temp = explode("__SEPARATOR__",$item->name);
				if($key>0)
					{
					$this->_update_eta($temp[2], $item->distance, $item->arrival);

					$this->db->updateData("UPDATE `" . DB_PREFIX . "temp_routes` SET `last_optimized_time` = NOW() WHERE temp_route_id = " . $temp[0]);  
					array_push($data, array('temp_route_id'=>$temp[0],'data_index'=>$temp[1],'shipment_id'=>$temp[2],'route_index'=>$temp[3],'estimated_arrival_time'=>$item->arrival,'distance'=>$item->distance));
					}
				}
			
			$record = $this->db->getRowRecord("SELECT last_optimized_time FROM " . DB_PREFIX . "temp_routes WHERE temp_route_id = " . $temp[0]);
			return array("status"=>true,"message"=>$results['message'],"data"=>$data,'last_optimized_time'=>$record['last_optimized_time']);
			}
		  else
		  	{
			return $results;
			}
		}
	}
?>