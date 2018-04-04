<?php
class testLoadShipment extends Library{
	public $data = array();
	public $warehouse_id = 0;
	public $company_id = 0;
	public $user_id = 0;
	public $access_token = null;
	public $shipment_type = null;
	public function __construct($param){
		$this->db = new DbHandler();
		
		if(isset($param['company_id'])){
			$this->company_id = $param['company_id'];
		}
		
		if(isset($param['warehouse_id'])){
			$this->warehouse_id = $param['warehouse_id'];
		}
		if(isset($param['shipment_ticket'])){
			$this->shipment_ticket = $param['shipment_ticket'];
		}
		
		if(isset($param['access_token'])){
			$this->access_token = $param['access_token'];
		}
		
		if(isset($param['shipment_type'])){
			$this->shipment_type = $param['shipment_type'];
		}
		
		if(isset($param['user_id'])){
			$this->user_id = $param['user_id'];
		}
	}
	
	public function testCompanyConfiguration(){
		$record = $this->db->getRowRecord("SELECT configuration_json AS configuration_json FROM " . DB_PREFIX . "configuration AS s WHERE company_id = " . $this->company_id);
		if($record){
			$configuration                      = json_decode($record['configuration_json']);
			$this->drop_count                   = $configuration->maximum_allow_drop;
			$this->buffer_time                  = $configuration->buffer_time;
			$this->buffer_time_of_drop          = $configuration->maxbuffertimeperdrop;
			$this->buffer_time_of_each_shipment = $configuration->maxbuffertimepershipment;
			$this->shipment_attempt_conf      = array(
				'regularattemptconf' => $configuration->regularattempt,
				'phoneattemptconf' => $configuration->phonetypeattempt
			);
			$data = array(
				'status' => true,
				'message' => 'Configuration found'
			);
		} else {
			$data = array(
				'status' => false,
				'message' => 'Configuration not found. Please set the configuration first.'
			);
		}
		return $data;
	}
	
	private function _get_shipment_data_by_docket_no($docket){
		$records = $this->db->getAllRecords("SELECT shipment_executionOrder AS shipment_order, instaDispatch_docketNumber  AS docket_number, shipment_postcode, shipment_service_type, distancemiles, estimatedtime, waitingtime, loadingtime, shipment_instruction  FROM " . DB_PREFIX . "shipment AS s WHERE  s.instaDispatch_docketNumber = '$docket' ORDER BY shipment_executionOrder");
		return $records;
	}
	
	private function _get_parcel_data_by_shipment_id($shipment_id){
		$records = $this->db->getAllRecords("SELECT instaDispatch_pieceIdentity, parcel_weight, parcel_width, parcel_height, parcel_length FROM " . DB_PREFIX . "shipments_parcel AS s WHERE  s.shipment_id = '$shipment_id'");
		return $records;
	}
	
	private function _get_next_day_data(){
		$records = $this->db->getAllRecords("SELECT * FROM " . DB_PREFIX . "shipment AS s WHERE s.current_status = 'C' AND s.instaDispatch_loadGroupTypeCode != 'SAME' AND s.warehouse_id = " . $this->warehouse_id);
		$counter = 0;
		foreach($records as $key => $record){
			$load_group_type_code = strtolower($record["instaDispatch_loadGroupTypeCode"]);
			
			if($record["shipment_service_type"] == "D"){
				$service_type = "Delivery";
			} else if($record["shipment_service_type"] == "P"){
				$service_type = "Collection";
			}
			
			if($load_group_type_code == "vendor"){
				$type = "Retail";
			} 
			else if($load_group_type_code == "next"){
				$type = "Next Day";
			}
			else if($load_group_type_code == "phone"){
				$type = "Phone";
			}
			$data[$counter]["shipment_ticket"] = $record["shipment_ticket"];
			$data[$counter]["docket_no"]       = $record["instaDispatch_docketNumber"];
			$data[$counter]["reference_no"]    = $record["instaDispatch_loadIdentity"];
			$data[$counter]["service_type"]    = $service_type;
			$data[$counter]["service_date"]    = $record["shipment_required_service_date"];
			$data[$counter]["service_time"]    = $record["shipment_required_service_starttime"]; 
			$data[$counter]["weight"]          = $record["shipment_total_weight"]; 
			$data[$counter]["postcode"]        = $record["shipment_postcode"]; 
			$data[$counter]["attempt"]         = $record["shipment_total_attempt"]; 
			$data[$counter]["in_warehouse"]    = $record["is_receivedinwarehouse"]; 
			$data[$counter]["type"]            = $type; 
			$data[$counter]["parcels"]         = $this->_get_parcel_data_by_shipment_id($record["shipment_id"]); 
			$data[$counter]["action"]          = "<a>Detail</a>";
			$counter++;
			
		}
		$aoColumn = array("Docket No", "Reference No", "Service Type", "Service Date", "Service Time", "Weight", "Postcode", "Attempt", "In Warehouse", "Type", "Action");
		return array("aoColumn"=>$aoColumn, "aaData"=>$data);
	}
	
	public function shipments(){
		if(strtolower($this->shipment_type) == 'next'){
			return $this->_get_next_day_data();
		}
		else if(strtolower($this->shipment_type) == 'same'){
			return $this->_get_same_day_data();
		}
	}
	
	private function _get_same_day_data(){
		$data = array();
		$records = $this->db->getAllRecords("SELECT shipment_executionOrder AS shipment_order, instaDispatch_loadIdentity, instaDispatch_docketNumber AS docket_number, instaDispatch_jobIdentity, shipment_required_service_date, shipment_required_service_starttime, shipment_required_service_endtime, SUM(distancemiles) AS distance_miles, SUM(estimatedtime) AS estimated_time  FROM " . DB_PREFIX . "shipment AS s WHERE s.current_status = 'C' AND s.instaDispatch_loadGroupTypeCode = 'SAME' AND s.warehouse_id = '$this->warehouse_id' GROUP BY instaDispatch_loadIdentity");
		
		foreach($records as $key => $record){
			$data[$key]["reference_no"]   = $record["instaDispatch_loadIdentity"];
			$data[$key]['miles']          = $record["distance_miles"];
			$data[$key]['time']           = $record["estimated_time"];
			$data[$key]["service_date"]   = $record["shipment_required_service_date"];
			$data[$key]['collection_time']= $record["shipment_required_service_starttime"].' - '.$record["shipment_required_service_endtime"];
			$shipments                    = $this->_get_shipment_data_by_docket_no($record["docket_number"]);
			$end_shipment                 = end($shipments);
			$data[$key]['start_postcode'] = $shipments[0]['shipment_postcode'];
			$data[$key]['end_postcode']   = $end_shipment['shipment_postcode'];
			$data[$key]['drops']          = count($shipments);
			$data[$key]['shipments']      = $shipments;
		}
		
		$aoColumn = array("Reference No","Starting Point","Ending Point", "Miles", "Time", "Drops", "Service Type", "Service Date", "Collection Time");
		return array("aoColumn"=>$aoColumn, "aaData"=>$data);
	}
	
	public function shipmentStatus(){
		$data = array();
		$shipment_type = array();
		$shipment_day_services = array();
		$records = $this->db->getAllRecords("SELECT shipment_postcode, shipment_customer_country, instaDispatch_loadGroupTypeCode, shipment_service_type, is_receivedinwarehouse, instaDispatch_objectIdentity, shipment_ticket FROM " . DB_PREFIX . "shipment AS s WHERE s.shipment_ticket IN('$this->shipment_ticket') AND s.current_status = 'C'");
		
		foreach ($records as $key => $value) {
            $shipment_type[] = $value['instaDispatch_loadGroupTypeCode'];
            if (($value['instaDispatch_loadGroupTypeCode'] == 'NEXT') && ($value['shipment_service_type'] == 'D') && ($value['is_receivedinwarehouse'] == 'NO')) {
                $shipment_day_services[] = $value['shipment_ticket'];
            }
        }
		$shipment_type = array_values(array_unique($shipment_type));
		if (count($shipment_type) > 1) {
            if (in_array('SAME', $shipment_type)) {
                $data = array(
                    'status' => false,
                    'message' => 'You can not process Same day with Other',
                );
            } else {
                $count = count($shipment_day_services);
                if ($count > 0) {
                    $data = array(
                        'status' => false,
                        'message' => 'Total ' . $count . ' Next Day Shipment that has not been collected yet. Do you want to exclude ?',
                        'data' => $shipment_day_services
                    );
                } else {
				    $this->requestRoutesData();
                    $data = array(
                        'status' => true,
                        'message' => 'All selected shipments in warehouse except Next day collection.',
                    );
                }
            }
        }elseif (count($shipment_type) == 1) {
            if ($shipment_type[0] == 'NEXT') {
                $count = count($shipment_day_services);
                if ($count > 0) {
                    $data = array(
                        'status' => false,
                        'message' => 'Total ' . $count . ' next Day Shipment select who not collect yet.',
                        'data' => $shipment_day_services
                    );
                } else {
					$this->requestRoutesData();
                    $data = array(
                        'status' => true,
                        'message' => 'All selected shipments in warehouse except Next day collection.',
                    );
                }
            }
            if ($shipment_type[0] == 'Vendor') {
				$this->requestRoutesData();
                $data = array(
                    'status' => true,
                    'message' => 'All selected shipments in warehouse except Next day collection.',
                );
            }
            if ($shipment_type[0] == 'SAME') {
				$this->requestRoutesData();
                $data = array(
                    'status' => true,
                    'message' => 'All selected shipments in warehouse except Next day collection.',
                );
            }
        }
		return $data;
	}
	
	private function _search_in_array($array){
		array_multisort(array_map('strlen', $array), $array);
		$data =  array_pop($array);
		return $data;
	}
	
	private function _get_shipment_route_by_key($needle,$column){
		$data = array();
		$this->shipment_route = array();
        $cols =  array_column($this->route_postcode, $column);
		$key = array_search($needle,$cols);
		if(!$key){
			$temp = array();
			foreach($cols as $key => $col){
				if(stristr($needle,$col))
				    array_push($temp,$key);
			}
			if(count($temp)>0)
				$data = $this->route_postcode[$this->_search_in_array($temp)];
		} 
		else
			$data = $this->route_postcode[$key];
		
		$this->shipment_route = $data;
		if(isset($data['route_id'])){
			return $data['route_id'];
		} else {
		    return 0;	
		}
    }
	
	private function _clean_temporary_data_by_session_id(){
		$this->db->delete("DELETE FROM " . DB_PREFIX . "temp_routes_shipment WHERE session_id = '" . $this->access_token . "' AND company_id = '" . $this->company_id . "'");
		$this->db->delete("DELETE FROM " . DB_PREFIX . "temp_routes WHERE session_id = '" . $this->access_token . "' AND company_id = '" . $this->company_id . "'");
    }
	
	public function requestRoutesData(){
		$ticketids      = $this->shipment_ticket;
        $data           = array();
        $data['routes'] = array();
        
		$this->route_postcode = $this->db->getAllRecords("SELECT t1.*, t2.name AS route_name FROM " . DB_PREFIX . "route_postcode AS t1 INNER JOIN " . DB_PREFIX . "routes AS t2 ON t1.route_id = t2.id WHERE t1.company_id = " . $this->company_id . " AND 1 AND t1.warehouse_id = " . $this->warehouse_id);
		
		$records = $this->db->getAllRecords("SELECT shipment_ticket, shipment_postcode, instaDispatch_loadGroupTypeCode, shipment_service_type, instaDispatch_docketNumber, shipment_executionOrder, shipment_customer_country FROM " . DB_PREFIX . "shipment WHERE shipment_ticket IN('$this->shipment_ticket') AND current_status = 'C' ORDER BY shipment_service_type");
		
		foreach ($records as $key => $values) {
            $routeId     = $this->_get_shipment_route_by_key($values['shipment_postcode'],'postcode');
            $values['routeId']  = $routeId;
            $data[$values['instaDispatch_loadGroupTypeCode']][$values['shipment_service_type']][$values['instaDispatch_docketNumber']][] = $values;
        }
		
        foreach ($records as $key => $value) {
            if ((strtolower($value['instaDispatch_loadGroupTypeCode']) == 'vendor') || (strtolower($value['instaDispatch_loadGroupTypeCode']) == 'phone')) {
                $routeId                    = $this->_get_shipment_route_by_key($value['shipment_postcode'],'postcode');
                $data['routes'][$routeId][] = $value;
            }
            if (((strtolower($value['instaDispatch_loadGroupTypeCode']) == 'same') && (strtolower($value['shipment_service_type']) == 'p')) || ((strtolower($value['instaDispatch_loadGroupTypeCode']) == 'next') && (strtolower($value['shipment_service_type']) == 'p'))) {
                $routeId                    = $this->_get_shipment_route_by_key($value['shipment_postcode'],'postcode');
                $data['routes'][$routeId][] = $value;
            }
            if ((strtolower($value['instaDispatch_loadGroupTypeCode']) == 'same') && (strtolower($value['shipment_service_type']) == 'd')) {
                if (array_key_exists($value['instaDispatch_docketNumber'], $data['SAME']['P'])) {
                    $existedRouteId                    = $data['SAME']['P'][$value['instaDispatch_docketNumber']]['0']['routeId'];
                    $data['routes'][$existedRouteId][] = $value;
                } else {
                    $routeId                    = $this->_get_shipment_route_by_key($value['shipment_postcode'],'postcode');
                    $data['routes'][$routeId][] = $value;
                }
            }
			
            if ((strtolower($value['instaDispatch_loadGroupTypeCode']) == 'next') && (strtolower($value['shipment_service_type']) == 'd')) {
                if (isset($data['NEXT']['P']) and array_key_exists($value['instaDispatch_docketNumber'],$data['NEXT']['P'])) {
                    $existedRouteId                    = $data['NEXT']['P'][$value['instaDispatch_docketNumber']]['0']['routeId'];
                    $data['routes'][$existedRouteId][] = $value;
                } else {
                    $routeId                    = $this->_get_shipment_route_by_key($value['shipment_postcode'],'postcode');
                    $data['routes'][$routeId][] = $value;
                }
            }
        }
        $this->_clean_temporary_data_by_session_id();
        foreach ($data['routes'] as $key => $valueinner)
            $this->_add_temp_routes($key, $valueinner);
    
        
	}
	
	private function _get_all_drops_by_session(){
		$sql = "SELECT `SH`.`temp_route_id`, `SH`.`drag_temp_route_id`, `SH`.`execution_order`, `SH`.`distancemiles`, `SH`.`estimatedtime`, `R2`.`custom_route`, `R2`.`route_id`, `R2`.`session_id`, ";
		$sql .= "`R2`.`route_name`, CONCAT(route_name,SH.temp_route_id) AS `route_name_display`, `R2`.`is_optimized`, `R2`.`optimized_type`, `R2`.`last_optimized_time`, ";
		$sql .= "CONCAT(shipment_postcode,' ',shipment_address1) AS `drops`, SUM(shipment_total_weight) AS `totweight`, SUM(shipment_total_volume) AS `totvolume`, GROUP_CONCAT(shipment_ticket) AS `tickets`, ";
		$sql .= "GROUP_CONCAT(instaDispatch_docketNumber) AS `dockets`, SUM(shipment_total_item) AS `totparcel`, COUNT(1) AS `totshipment`, GROUP_CONCAT(is_receivedinwarehouse) AS `isrecives`, ";
		$sql .= "`CA`.`shipment_postcode` AS `postcode`, `CA`.`shipment_latlong`, `CA`.`shipment_latitude`, `CA`.`shipment_longitude`, `CA`.`shipment_customer_country`, `CA`.`instaDispatch_loadGroupTypeCode`, `CA`.`shipment_service_type`, `CA`.`icargo_execution_order`";
		$sql .= "FROM `" . DB_PREFIX . "temp_routes_shipment` AS `SH` LEFT JOIN `" . DB_PREFIX . "temp_routes` AS `R2` ON SH.drag_temp_route_id = R2.temp_route_id ";
		$sql .= "LEFT JOIN `" . DB_PREFIX . "shipment` AS `CA` ON SH.temp_shipment_ticket = CA.shipment_ticket ";
		$sql .= "WHERE (SH.session_id = '" . $this->access_token . "') AND (CA.current_status = 'C') GROUP BY `SH`.`drag_temp_route_id`, `drops` ORDER BY `SH`.`execution_order` ASC, `drops` ASC";
		  
		$records = $this->db->getAllRecords($sql);
		
		return $records;
	}
	
	private function _get_active_driver(){
		$sql = "SELECT t1.id AS driver_id, t1.name AS driver_name FROM " . DB_PREFIX . "users AS t1 INNER JOIN " . DB_PREFIX ."company_users AS t2 ON t1.id = t2.user_id WHERE t1.user_level = 4 AND t2.company_id = " . $this->company_id ." ORDER BY driver_name";
		$records = $this->db->getAllRecords($sql);
		return $records;
	}
	
	public function loadPreparedRoute(){
		$data = array('prepared_route' => $this->_load_prepared_route(),'active_drivers'=>$this->_get_active_driver());
		return $data;
	}

	private function _load_prepared_route(){

		$getAllDropsBySession                    = $this->_get_all_drops_by_session();

		$containerarray      = array();
		$containerRoutearray = array();
		$dropcountNum        = 0;
		foreach ($getAllDropsBySession as $keysdrop => $valuedrop) {
			
			$tdrop                                                     = ($valuedrop['estimatedtime'] != '') ? $valuedrop['estimatedtime'] + ceil((($valuedrop['totshipment'] * $this->buffer_time_of_each_shipment) + $this->buffer_time_of_drop) / 60) : '';
			$valuedrop['ETA']                                          = $tdrop;
			$containerRoutearray[$valuedrop['route_name']]             = $valuedrop['drag_temp_route_id'];
			$containerarray[$valuedrop['route_name']]['routes'][]      = $valuedrop;
			
			$total_volume = (isset($containerarray[$valuedrop['route_name']]['totalvolume'])) ?  $containerarray[$valuedrop['route_name']]['totalvolume'] + $valuedrop['totvolume'] : $valuedrop['totvolume'];
			$total_weight = (isset($containerarray[$valuedrop['route_name']]['totalWeight'])) ?  $containerarray[$valuedrop['route_name']]['totalWeight'] + $valuedrop['totweight'] : $valuedrop['totweight'];
			$total_shipment = (isset($containerarray[$valuedrop['route_name']]['totalshipment'])) ?  $containerarray[$valuedrop['route_name']]['totalshipment'] + $valuedrop['totshipment'] : $valuedrop['totshipment'];
			$total_parcel = (isset($containerarray[$valuedrop['route_name']]['totalparcel'])) ?  $containerarray[$valuedrop['route_name']]['totalparcel'] + $valuedrop['totparcel'] : $valuedrop['totparcel'];
			$total_drop = ($containerarray[$valuedrop['route_name']]['totaldrop'] = '') ? 0 : $containerarray[$valuedrop['route_name']]['totaldrop'] + 1;
			
			$containerarray[$valuedrop['route_name']]['totalvolume']   = $total_volume;
			$containerarray[$valuedrop['route_name']]['totalWeight']   = $total_weight;
			$containerarray[$valuedrop['route_name']]['totalshipment'] = $total_shipment;
			$containerarray[$valuedrop['route_name']]['totalparcel']   = $total_parcel;
			$containerarray[$valuedrop['route_name']]['totaldrop']     = $total_drop;
			
			$containerarray[$valuedrop['route_name']]['postcodes'][]   = $valuedrop['postcode'];
			$str                                                       = '';
			if ($valuedrop['is_optimized'] == 'YES') {
				$optimstr = ($valuedrop['optimized_type'] == 'E') ? 'ETA Optimise' : 'Route Optimise';
				$str      = 'Last "' . $optimstr . '" On ' . date("d-m-Y H:m:s", strtotime($valuedrop['last_optimized_time']));
			}
			$containerarray[$valuedrop['route_name']]['lastupdate'] = $str;
		}
		$arrayKeys = array_keys($containerarray);
		
		foreach ($getAllDropsBySession as $vals) {
			if (in_array($vals['route_name'], $arrayKeys)) {} 
			else {
				$containerarray[$vals['route_name']]      = array();
				$containerRoutearray[$vals['route_name']] = $vals['temp_route_id'];
			}
		}
		asort($containerRoutearray);
		$arrangearray = array();
		foreach ($containerarray as $key => $vals) {
			$arrangearray[$containerRoutearray[$key]][$key] = $vals;
		}
		ksort($arrangearray);
		return $arrangearray;
	}
	
	private function _add_temp_routes($roughtkey, $drops){
        foreach ($drops as $key => $row) {
            $mid[$key] = $row['instaDispatch_docketNumber'];
        }
        array_multisort($mid, SORT_DESC, $drops);
        $count = 0;
        if (count($drops) > 0) {
            $temprouteId = ($count == 0) ? '' : $this->_create_temp_route($roughtkey);
            $routeDrop   = ($roughtkey == 0) ? $this->drop_count : $this->_get_allowed_drop_of_route($roughtkey);
			
            $loopcounter = 0;
            foreach ($drops as $keys => $valData) {
                if ($valData['instaDispatch_loadGroupTypeCode'] != 'SAME') {
                    if (($count % $routeDrop) == 0) {
                        $temprouteId = $this->_create_temp_route($roughtkey);
                        $count       = 0;
                    }
                    $executionorder = $count + 1;
                } else {
                    if ($valData['shipment_service_type'] == 'P') {
                        $executionorder = 1;
                    } else {
                        $executionorder = $valData['shipment_executionOrder'] + 1;
                    }
                    if ($count == 0) {
                        $temprouteId = $this->_create_temp_route($roughtkey);
                        $count       = 0;
                    }
                    if ($loopcounter > 0) {
                        if ($valData['shipment_service_type'] == 'P') {
                            $temprouteId = $this->_create_temp_route($roughtkey);
                            $count       = 0;
                        }
                    }
                }
                $this->_add_temp_shipment($valData, $temprouteId, $executionorder);
                $count++;
                $loopcounter++;
            }
        }
    }
	
	private function _add_temp_shipment($valData, $tempRid, $ordernum){
        $shiparr = explode(',', $valData['shipment_ticket']);
        foreach ($shiparr as $key => $valDa) {
            $dataArr                         = array();
            $dataArr['temp_route_id']        = $tempRid;
            $dataArr['temp_shipment_ticket'] = $valDa;
            $dataArr['session_id']           = $this->access_token;
            $dataArr['drag_temp_route_id']   = $tempRid;
            $dataArr['execution_order']      = $ordernum;
            $dataArr['status']               = '1';
			$dataArr['company_id']           = $this->company_id;
			$this->db->save("temp_routes_shipment", $dataArr);
        }
    }
	
	private function _create_temp_route($roughtkey){
        $routeArray                 = array();
        $routeArray['custom_route'] = ($roughtkey == 0) ? 'Y' : 'N';
        $routeArray['route_id']     = $roughtkey;
        $routeArray['route_name']   = ($roughtkey == 0) ? 'Custom ' : $this->shipment_route['route_name'];
        $routeArray['route_name']   = $this->_check_route_name_exist_in_temp($routeArray['route_name'], '1');
        $routeArray['session_id']   = $this->access_token;
        $routeArray['status']       = '1';
		$routeArray['company_id']   = $this->company_id;
		return $this->db->save("temp_routes", $routeArray);
    }
	
	private function _get_allowed_drop_of_route($rid) {
		$record = $this->db->getOneRecord("SELECT allowed_drops AS allowed_drops FROM " . DB_PREFIX . "routes WHERE id = " . $rid);
	    return $record['allowed_drops'];
	}
	
	private function _check_route_name_exist_in_temp($routeName, $s){
		for ($i = 0; $i < 45; $i++) {
			$sql = "SELECT COUNT(1) AS exist FROM " . DB_PREFIX . "temp_routes WHERE route_name = '" . $routeName . '_' . $i . "' AND session_id = '" . $this->access_token . "'";
			$record = $this->db->getOneRecord($sql);			
            if ($record['exist']) {}
			else {
                $routeName = $routeName . '_' . $i;
                break;
            }
        }
        return $routeName;
    }
}
?>