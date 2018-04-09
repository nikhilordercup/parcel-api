<?php
class shipment extends Library{
	public $data = array();
    public $returntempdata = null;
	public function __construct($param = array()){
		$this->db = new DbHandler();
		
		$this->_ftp_user_name = "5waysGTS";
		$this->_ftp_password = "pcs@pcs";
		$this->_ftp_host = "54.191.172.136";
		$this->returntempdata = array();
        
         if(isset($param["file_name"])){
            $this->file_name = $param["file_name"];
        }
        if(isset($param["root_path"])){
            $this->root_path = $param["root_path"];
        }
        
        
        $this->postcodeObj = new Postcode();
        
        if(isset($param["job_type"])){
            $this->job_type = $param["job_type"];
        }
        if(isset($param["tempdata"])){
            $this->tempdata = $param["tempdata"];
        }
        if(isset($param["company_id"])){
            $this->company_id = $param["company_id"];
        }

        if(isset($param["warehouse_id"])){
            $this->warehouse_id = $param["warehouse_id"];
        }
        if(isset($param["customer_id"])){
            $this->customer_id = $param["customer_id"];
        }
        if(isset($param["user_level"])){
            $this->user_level = $param["user_level"];
        }
	}
	
	private function _get_upload_xml_path(){
		return 	$this->root_path."/upload/request";
	}
	
	private function get_parcel_count($contents){
		if($contents['loadGroupTypeCode']=='SAME')
			if(isset($contents['jobLoadItems']['jobLoadItem'])){
				$parcelCount =  (count($contents['jobLoadItems']['jobLoadItem'])>0)?count($contents['jobLoadItems']['jobLoadItem']):1;
			} else {
				$parcelCount = 1;
			}
		else
			$parcelCount =  count($contents['jobLoadItems']['jobLoadItem']);
		return $parcelCount;
	}
	
	private function _ftp_delete(){
		ftp_delete($this->ftp_con, 'the/great/jhaji/'.$this->file_name);
	}
	
	private function _ftp_close(){
		return ftp_close($this->ftp_con); 
	}
 
	private function upload_shipment_file(){
		$this->_get_ftp_connection();
		$this->fsize = ftp_size($this->ftp_con, 'the/great/jhaji/'.$this->file_name);
		ftp_pasv($this->ftp_con, true);
		  
		$contents = "";
		$result = array();
		if ($this->fsize != -1){
			if (ftp_get($this->ftp_con, $this->_get_upload_xml_path()."/".$this->file_name, 'the/great/jhaji/'.$this->file_name, FTP_BINARY)) {
				$contents = json_decode(json_encode(simplexml_load_string($this->get_file_content(array("file"=>$this->_get_upload_xml_path()."/".$this->file_name)),'SimpleXMLElement', LIBXML_NOCDATA)), TRUE);
				$contents["company_code"] = $contents['clientName']; 
              
				/*if($escap){
					if(count($this->contents['identity'])>0)
						
					else
						return array('status'=>"error",'message'=>'Parcel Details not found');					
				}else{*/
					if($this->get_parcel_count($contents)>0){
						//ftp_delete($this->ftp_con, $this->file_name);
						$result['data'] = $contents;
						$result['status'] = "success";
					}
					else{
						$result['status'] = "error";
						$result['message'] = "Parcel Details not found";
					}
				//}
				$this->_ftp_delete();
				$this->_ftp_close(); 
			}
		}else{
			$result['status'] = "error";
			$result['message'] = "Requested File Not Exist";
		}
		return $result;
	}
	
	private function search_in_multi_array($array){
		array_multisort(array_map('strlen', $array), $array);
		$data =  array_pop($array);
		return $data;
	}
	
	private function _shipment_warehouse($param){
		$record = $this->db->getRowRecord("SELECT c.warehouse_id, c.route_id FROM " . DB_PREFIX . "route_postcode AS c WHERE company_id = " . $param['company_id'] . " AND c.postcode LIKE '%" . $param['postcode'] . "%'");
      
		if(!$record){
            $departure_time = (!isset($param["service_date"])) ? date("Y-m-d h:i:s", strtotime("now")) : $this->service_date;

			$warehouse_postcode = $this->db->getAllRecords("SELECT c.postcode, c.warehouse_id, c.route_id FROM " . DB_PREFIX . "route_postcode AS c WHERE company_id = " . $param['company_id']);
            
          
			$cols =  array_column($warehouse_postcode, 'postcode');
			$key = array_search($param['postcode'],$cols);
			if(!$key){
				$temp = array();
				foreach($cols as $key => $col){  
					preg_match('/\b(\w*'.$col.'\w*)\b/', $param['postcode'], $matches);
					//preg_match('/\b('.$col.'\w+)\b/', $param['postcode'], $matches); 
					if($matches){
						array_push($temp,$key);
					}
				} 
				//$temp = array();
				if(count($temp) > 0){
					$record = $warehouse_postcode[$this->search_in_multi_array($temp)];
					
				} else{
					// find near by warehouse by postcode
					$company_warehouse = $this->db->getAllRecords("SELECT w.postcode, c.id AS warehouse_id FROM " . DB_PREFIX . "warehouse AS w INNER JOIN " . DB_PREFIX . "company_warehouse AS c ON w.id = c.warehouse_id WHERE company_id = " . $param['company_id']);
					$warehouse_postcode = array();
					foreach($company_warehouse as $record)
						$warehouse_postcode[] = $record['postcode'];
			
					$distance_and_duration = $this->multiple_destinations_distance_and_duration(array("origin"=>$param['postcode'],"destinations"=>$warehouse_postcode, 'order'=>'asc',"mode" => "bicycling","departure_time"=>$departure_time));
					
					foreach($distance_and_duration as $key => $distance){
						break;
					}
					
					if(isset($warehouse_postcode[$key])){
						$record = $company_warehouse[$key];
					} else {
						// return default warehouse_id
						$record = array("postcode"=>"SE164RP","warehouse_id"=>1);
					}
				}
			}else{
				$record = $warehouse_postcode[$key];
			}
		}
		return $record['warehouse_id'];
	}
	
	private function _get_ftp_connection(){
		$this->ftp_con = ftp_connect($this->_ftp_host);
		$this->ftp_login = ftp_login($this->ftp_con,$this->_ftp_user_name,$this->_ftp_password);
	}
	
	private function _test_shipment_ticket($shipment_ticket){
		$record = $this->db->getOneRecord("SELECT COUNT(1) AS exist FROM " . DB_PREFIX . "shipment WHERE shipment_ticket = '". $shipment_ticket ."'");
		if($record['exist'] > 0)
			return true;
		else
			return false;
	}
	
	private function _test_parcel_ticket($parcel_ticket){
		$record = $this->db->getOneRecord("SELECT COUNT(1) AS exist FROM " . DB_PREFIX . "shipments_parcel WHERE parcel_ticket = '". $parcel_ticket ."'");
		if($record['exist'] > 0)
			return true;
		else
			return false;
	}
	
	private function _generate_ticket_no(){
		$record = $this->db->getRowRecord("SELECT (shipment_end_number + 1) AS shipment_ticket_no, shipment_ticket_prefix AS shipment_ticket_prefix FROM " . DB_PREFIX . "configuration WHERE company_id = ".$this->company_id);
		if($record){
            $ticket_number = $record['shipment_ticket_prefix'].str_pad($record['shipment_ticket_no'],6,0,STR_PAD_LEFT);

            $check_digit = $this->generateCheckDigit($ticket_number);

            $ticket_number = "$ticket_number$check_digit";

            $this->db->updateData("UPDATE " . DB_PREFIX . "configuration SET shipment_end_number = shipment_end_number + 1 WHERE company_id = ".$this->company_id);

            if($this->_test_shipment_ticket($ticket_number)){
                $this->_generate_ticket_no();
            }
            return $ticket_number;
        }else{
		    return false;
        }

	}
	
	private function _generate_parcel_ticket_number(){
		$record = $this->db->getRowRecord("SELECT (parcel_end_number + 1) AS ticket_no, shipment_ticket_prefix AS shipment_ticket_prefix FROM " . DB_PREFIX . "configuration WHERE company_id = ".$this->company_id);
		
		$ticket_number = $record['shipment_ticket_prefix'].str_pad($record['ticket_no'],6,0,STR_PAD_LEFT);
		
		$check_digit = $this->generateCheckDigit($ticket_number);
		
		$ticket_number = "$ticket_number$check_digit";
		
		// update ticket number
		$this->db->updateData("UPDATE " . DB_PREFIX . "configuration SET parcel_end_number = parcel_end_number + 1 WHERE company_id = ".$this->company_id);
		
		if($this->_test_parcel_ticket($ticket_number)){
			$this->_generate_parcel_ticket_number();
		}
		return $ticket_number;
	}
		
	private function _get_company_id($company_code){
		$record = $this->db->getRowRecord("SELECT id AS id FROM " . DB_PREFIX . "user_code WHERE code = '$company_code'");
		return $record['id'];
	}
	
	private function _add_shipment_data_nextday_sameday($data){
		
		if($data['itemCount'] > 0){
			$data['jobLoadItems']['jobLoadItem'] = array($data['jobLoadItems']['jobLoadItem']);
		} else {
			$data['jobLoadItems']['jobLoadItem'] = array();
		}
		
		$pieceDetails = $data['jobLoadItems']['jobLoadItem'];
	
		if (!array_key_exists('0', $data['jobLoadLocations']['jobLoadLocation'])){
			$tempData = $data['jobLoadLocations']['jobLoadLocation'];
			$data['jobLoadLocations']['jobLoadLocation'] = '';
			$data['jobLoadLocations']['jobLoadLocation'][] = $tempData;
			$tempData = '';
			}
	    $this->company_id = $this->_get_company_id($data['company_code']);
		$typedata = '';
		if ($data['loadGroupTypeCode'] == 'NEXT'){
			if (count($data['jobLoadLocations']['jobLoadLocation']) > 1){
				$tempArr = array();
				foreach($data['jobLoadLocations']['jobLoadLocation'] as $innervaluedata){
					array_push($tempArr, $innervaluedata['executionDate']);
					}
	
				$tempArr = array_unique($tempArr);
				if (count($tempArr) == 1){
					$typedata = 'TempSAME';
					}
				}
			}
		
		foreach($data['jobLoadLocations']['jobLoadLocation'] as $valuedata){
			$dataStatus = false;
            $returnData = array();
			$ticketNumber = $this->_generate_ticket_no();
			
			$datapostcode = $this->postcodeObj->validate($data['postcode']);
			
			$shipment_geo_location = $this->get_lat_long_by_postcode($datapostcode,$data['latitude'],$data['longitude']);
			
			$warehouse_id = $this->_shipment_warehouse(array("company_id"=>$this->company_id, "postcode"=>$datapostcode, "shipment_geo_location"=>$shipment_geo_location));
			
			$postcode = $this->postcodeObj->validate($valuedata['postcode']);
			if($postcode){
				$valuedata['postcode'] = $postcode;
				$shipmentData['error_flag'] = 0;
			} else {
				$shipmentData['error_flag'] = 1;
			}
			
            
            
             if($shipment_geo_location['latitude'] ==0.00){
			 $shipmentData['error_flag'] = 1;
		      } else {
			 $shipmentData['error_flag'] = 0;
		      }
            
			$shipmentData['warehouse_id'] = $warehouse_id;
			$shipmentData['instaDispatch_loadIdentity'] = $valuedata['loadIdentity'];
			$shipmentData['instaDispatch_jobIdentity'] = $valuedata['jobIdentity'];
			$shipmentData['instaDispatch_objectIdentity'] = isset($data['objectIdentity']) ? $data['objectIdentity'] : ''; //  added new field on 16 10 2015
			$shipmentData['instaDispatch_objectTypeName'] = isset($data['objectTypeName']) ? $data['objectTypeName'] : ''; //  added new field on 16 10 2015
			$shipmentData['shipment_service_type'] = ($valuedata['purposeTypeName'] == 'Collection') ? 'P' : 'D';
			$shipmentData['shipment_required_service_date'] = $valuedata['executionDate'];
			$shipmentData['shipment_required_service_starttime'] = $valuedata['startTime'];
			$shipmentData['shipment_required_service_endtime'] = $valuedata['endTime'];
			$shipmentData['shipment_address1'] = !empty($valuedata['address1']) ? $valuedata['address1'] : '';
			$shipmentData['shipment_address2'] = !empty($valuedata['address2']) ? $valuedata['address2'] : '';
			$shipmentData['shipment_address3'] = !empty($valuedata['address3']) ? $valuedata['address3'] : '';
			$shipmentData['shipment_postcode'] = $valuedata['postcode'];
			$shipmentData['shipment_total_item'] = $valuedata['parcelCount'];
			$shipmentData['shipment_total_weight'] = $valuedata['parcelWeight'];
			$shipmentData['shipment_total_volume'] = $valuedata['parcelVolume'];
			$shipmentData['shipment_highest_length'] = !empty($data['jobLoadItems']['jobLoadItem'][0]['pieceLength']) ? $data['jobLoadItems']['jobLoadItem'][0]['pieceLength'] : 0;
			$shipmentData['shipment_highest_width'] = !empty($data['jobLoadItems']['jobLoadItem'][0]['pieceWidth']) ? $data['jobLoadItems']['jobLoadItem'][0]['pieceWidth'] : 0;
			$shipmentData['shipment_highest_height'] = !empty($data['jobLoadItems']['jobLoadItem'][0]['pieceHeight']) ? $data['jobLoadItems']['jobLoadItem'][0]['pieceHeight'] : 0;
			$shipmentData['shipment_highest_weight'] = !empty($data['jobLoadItems']['jobLoadItem'][0]['pieceWeight']) ? $data['jobLoadItems']['jobLoadItem'][0]['pieceWeight'] : 0;
			$shipmentData['shipment_customer_name'] = !empty($valuedata['personName']) ? $valuedata['personName'] : '';
			$shipmentData['shipment_customer_email'] = !empty($data['contactEmail']) ? $data['contactEmail'] : '';
			$shipmentData['shipment_customer_phone'] = !empty($valuedata['phone']) ? $valuedata['phone'] : '';
			$shipmentData['shipment_contact_mobile'] = !empty($valuedata['mobile']) ? $valuedata['mobile'] : '';
			$shipmentData['attemptonlunchbreak'] = !empty($valuedata['attemptOnLunchBreak']) ? $valuedata['attemptOnLunchBreak'] : '';
			$shipmentData['waitingtime'] = !empty($valuedata['waitingTime']) ? $valuedata['waitingTime'] : '0';
			$shipmentData['loadingtime'] = !empty($valuedata['loadingTime']) ? $valuedata['loadingTime'] : '0';
			$shipmentData['shipment_customer_city'] = !empty($valuedata['city']) ? $valuedata['city'] : '';
			$shipmentData['shipment_customer_country'] = !empty($valuedata['county']) ? $valuedata['county'] : '';
			$shipmentData['shipment_country_code'] = $valuedata['countryCode'];
			$shipmentData['shipment_zone_code'] = $valuedata['zoneCode'];
			$shipmentData['shipment_instruction'] = json_encode($valuedata['instruction']);
			$shipmentData['shipment_isDutiable'] = $data['isDutiable'];
			$shipmentData['shipment_shouldBookIn'] = $valuedata['isBookIn'];
			$shipmentData['shipment_statusName'] = $data['statusName'];
			$shipmentData['shipment_executionOrder'] = $valuedata['executionOrder'];
			$shipmentData['shipment_companyName'] = !empty($valuedata['companyName']) ? $valuedata['companyName'] : '';
			$shipmentData['shipment_xml_reference'] = $this->file_name;
			$shipmentData['shipment_create_date'] = "NOW()";
			$shipmentData['shipment_total_attempt'] = '0';
			$shipmentData['is_shipment_routed'] = '0';
			$shipmentData['is_driver_assigned'] = '0';
			$shipmentData['shipment_pod'] = '';
			$shipmentData['current_status'] = 'C';
			$shipmentData['dataof'] = $data['company_code'];
			$shipmentData['shipment_ticket'] = $ticketNumber;
			$shipmentData['waitAndReturn'] = $data['waitAndReturn'];
			$shipmentData['waitAndReturn'] = $data['waitAndReturn'];
			$shipmentData['instaDispatch_docketNumber'] = !empty($data['docketNumber']) ? $data['docketNumber'] : '';
			/* same day work start */
			$shipmentData['instaDispatch_objectTypeId'] = $data['objectTypeId'];
			$shipmentData['instaDispatch_businessName'] = $data['businessName'];
			$shipmentData['instaDispatch_accountNumber'] = $data['accountNumber'];
			$shipmentData['instaDispatch_statusCode'] = $data['statusCode'];
			$shipmentData['instaDispatch_jobTypeCode'] = $data['jobTypeCode'];
			$shipmentData['instaDispatch_availabilityTypeCode'] = $data['availabilityTypeCode'];
			$shipmentData['instaDispatch_availabilityTypeName'] = $data['availabilityTypeName'];
			$shipmentData['instaDispatch_loadGroupTypeId'] = $data['loadGroupTypeId'];
			$shipmentData['instaDispatch_loadGroupTypeIcon'] = $data['loadGroupTypeIcon'];
			$shipmentData['instaDispatch_loadGroupTypeCode'] = ($typedata == 'TempSAME') ? 'SAME' : $data['loadGroupTypeCode'];
			$shipmentData['instaDispatch_loadGroupTypeName'] = ($typedata == 'TempSAME') ? 'Same Day' : $data['loadGroupTypeName'];
			$shipmentData['instaDispatch_customerReference'] = $data['customerReference'];
			
			$valuedata['lattitude'] = empty($valuedata['lattitude']) ? '' : $valuedata['lattitude'];
			$valuedata['longitude'] = empty($valuedata['longitude']) ? '' : $valuedata['longitude'];
			$shipmentData['company_id'] = $this->company_id;
			$shipmentData['search_string'] = $ticketNumber . ' ' . $data['docketNumber'] . ' ' . $shipmentData['instaDispatch_objectIdentity'] . ' ' . $shipmentData['shipment_address1'] . ' ' . $shipmentData['shipment_address2'] . ' ' . $shipmentData['shipment_address3'] . ' ' . str_replace(' ', '', $shipmentData['shipment_postcode']) . ' ' . $shipmentData['shipment_customer_city'] . ' ' . $shipmentData['shipment_customer_name'] . ' ' . $shipmentData['shipment_required_service_date'];
			$address = $shipmentData['shipment_address1'] . " " . $shipmentData['shipment_address2'] . " " . $shipmentData['shipment_address3'] . " " . str_replace(' ', '', $shipmentData['shipment_postcode']) . " " . $shipmentData['shipment_customer_city'] . " " . $shipmentData['shipment_customer_country'];
			
			
			
			$shipmentData['shipment_latitude'] = $shipment_geo_location["latitude"];
			$shipmentData['shipment_longitude'] = $shipment_geo_location["longitude"];
			$shipmentData['shipment_latlong'] = $shipment_geo_location["latitude"].','.$shipment_geo_location["longitude"];//implode(",",$shipment_geo_location);

			$shipmentId = $this->db->save("shipment", $shipmentData);
			
			if ($shipmentId && $data['loadGroupTypeCode'] != 'SAME'){
				$dataStatus = true;
				if (!array_key_exists('0', $data['jobLoadItems']['jobLoadItem'])){
					$tempData = $data['jobLoadItems']['jobLoadItem'];
					$data['jobLoadItems']['jobLoadItem'] = '';
					$data['jobLoadItems']['jobLoadItem'][] = $tempData;
					$tempData = '';
					}

				if (count($data['jobLoadItems']['jobLoadItem']) > 0){
					for ($i = 0; $i < $valuedata['parcelCount']; $i++){
						$parcelticketNumber = $this->_generate_parcel_ticket_number();
						$parcelData = array();
						$parcelData['shipment_id'] = $shipmentId;
						
						$parcelData['instaDispatch_Identity'] = isset($pieceDetails[$i]['identity']) ? $pieceDetails[$i]['identity'] : '' ;
						$parcelData['instaDispatch_pieceIdentity'] = $pieceDetails[$i]['pieceIdentity'];
						$parcelData['instaDispatch_jobIdentity'] = $pieceDetails[$i]['jobIdentity'];
						$parcelData['instaDispatch_loadIdentity'] = $pieceDetails[$i]['loadIdentity'];
						$parcelData['shipment_ticket'] = $ticketNumber;
						$parcelData['parcel_ticket'] = $parcelticketNumber;
						$parcelData['parcel_weight'] = $pieceDetails[$i]['pieceWeight'];
						$parcelData['parcel_height'] = $pieceDetails[$i]['pieceHeight'];
						$parcelData['parcel_length'] = $pieceDetails[$i]['pieceLength'];
						$parcelData['parcel_width'] = $pieceDetails[$i]['pieceWidth'];
						$parcelData['parcel_type'] = ($valuedata['purposeTypeName'] == 'Collection') ? 'P' : 'D';
	
						$parcelData['create_date'] = "NOW()";
						$parcelData['dataof'] = $data['company_code'];
						$parcelData['status'] = '1';
						/* add some new data for same day*/
						$parcelData['docketNumber'] = empty($data['docketNumber']) ? '' : $data['docketNumber'];
						$parcelData['customerReference'] = empty($data['customerReference']) ? '' : $data['customerReference'];
						$parcelData['objectIdentity'] = $pieceDetails[$i]['objectIdentity'];
						$parcelData['availabilityTypeId'] = $pieceDetails[$i]['availabilityTypeId'];
						$parcelData['availabilityTypeCode'] = $pieceDetails[$i]['availabilityTypeCode'];
						$shipmentId = $this->db->save("shipments_parcel", $parcelData);
						}
					if ($valuedata['purposeTypeName'] != 'Collection'){
						for ($j = 0; $j < $valuedata['parcelCount']; $j++){
							unset($pieceDetails[$j]);
						}
						$pieceDetails = array_values($pieceDetails);
						}
					}
				}
			  else
				{
				$dataStatus = true;
				}
		$returnData[] = $ticketNumber;	
        }
	
		return $returnData;
        //return $dataStatus;
		}
	  
	private function _add_shipment_data_uk_mail($data){
		$dataStatus = false;
		$returnData = array();
        
		$ticketNumber = $this->_generate_ticket_no();
		$this->company_id = $this->_get_company_id($data['company_code']);
        
        $postcode = $this->postcodeObj->validate($data['postcode']);

        
		$warehouse_id = $this->warehouse_id;//$this->_shipment_warehouse(array("company_id"=>$this->company_id, "postcode"=>$postcode));

        $warehouseReceivedDate = date("Y-m-d h:i:s", strtotime("now"));
		//$postcode = $this->postcodeObj->validate($data['postcode']);
		if($postcode){
			//$shipmentData['shipment_postcode'] = $postcode;
			$shipmentData['error_flag'] = 0;
		} else {
			$shipmentData['error_flag'] = 1;
		}
		
        $shipmentData['warehouse_id'] = $warehouse_id;
		$shipmentData['instaDispatch_loadIdentity'] = ($data['identity']!=0)?$data['identity']:$ticketNumber;
		$shipmentData['instaDispatch_jobIdentity'] = ($data['jobIdentity']!=0)?$data['jobIdentity']:$ticketNumber;
		$shipmentData['instaDispatch_objectIdentity'] = ($data['objectIdentity']!=0)?$data['objectIdentity']:$ticketNumber;
            //isset($data['objectIdentity'])?$data['objectIdentity']:''; //  added new field on 16 10 2015
		$shipmentData['instaDispatch_objectTypeName'] = isset($data['objectTypeName'])?$data['objectTypeName']:'';
		$shipmentData['shipment_service_type'] = ($data['jobTypeName']=='Collection')?'P':'D';
		$shipmentData['shipment_required_service_date'] = $data['executionDate'];
		$shipmentData['shipment_required_service_starttime'] = $data['startTime'];
		$shipmentData['shipment_required_service_endtime'] = $data['endTime'];
		$shipmentData['shipment_address1'] = !empty($data['address1'])?$data['address1']:'';
		$shipmentData['shipment_address2'] = !empty($data['address2'])?$data['address2']:'';
		$shipmentData['shipment_address3'] = !empty($data['address3'])?$data['address3']:'';
		$shipmentData['shipment_postcode'] = $data['postcode'];
		$shipmentData['shipment_total_item'] = $data['itemCount'];
		$shipmentData['shipment_total_weight'] = $data['weight'];
		$shipmentData['shipment_total_volume'] = $data['volume'];
		$shipmentData['shipment_highest_length'] =$data['highestLength'];
		$shipmentData['shipment_highest_width'] = $data['highestWidth'];
		$shipmentData['shipment_highest_height'] = $data['highestHeight'];
		$shipmentData['shipment_highest_weight'] = $data['highestWeight'];
		$shipmentData['shipment_customer_name'] = !empty($data['contactName'])?$data['contactName']:'';
		$shipmentData['shipment_customer_email'] = !empty($data['contactEmail'])?$data['contactEmail']:''; 
		$shipmentData['shipment_customer_phone'] = !empty($data['contactPhone'])?$data['contactPhone']:'';  
		$shipmentData['shipment_customer_city'] = !empty($data['city'])?$data['city']:'';
		$shipmentData['shipment_customer_country'] = !empty($data['countryCode'])?$data['countryCode']:''; 
		$shipmentData['shipment_country_code'] = $data['countryCode'];
		$shipmentData['shipment_zone_code'] = $data['zoneCode'];
		$shipmentData['shipment_instruction'] = json_encode($data['instruction']);
		$shipmentData['shipment_isDutiable'] = $data['isDutiable'];
		$shipmentData['shipment_shouldBookIn'] = $data['shouldBookIn'];
		$shipmentData['shipment_statusName'] = $data['statusName'];
		$shipmentData['shipment_executionOrder'] = $data['executionOrder'];
		$shipmentData['shipment_companyName'] = !empty($data['companyName'])?$data['companyName']:'';   
		$shipmentData['shipment_xml_reference'] = isset($this->file_name)?$this->file_name:'';
		$shipmentData['shipment_create_date'] = date("Y-m-d");
		$shipmentData['shipment_total_attempt'] = '0';
		$shipmentData['is_shipment_routed'] = '0';
		$shipmentData['is_driver_assigned'] = '0';
		$shipmentData['shipment_pod'] = '';
		$shipmentData['current_status'] = 'C';
		$shipmentData['dataof'] = $data['company_code'];	
		$shipmentData['shipment_ticket'] = $ticketNumber;
		$shipmentData['instaDispatch_docketNumber'] = (($data['docketNumber']!=0) || !empty($data['docketNumber']))?$data['docketNumber']:$ticketNumber;
            //!empty($data['docketNumber'])?$data['docketNumber']:''; 
		/* same day work start */
		$shipmentData['instaDispatch_objectTypeId'] = $data['objectTypeId'];
		$shipmentData['instaDispatch_businessName'] = $data['businessName'];
		$shipmentData['instaDispatch_accountNumber'] = $data['accountNumber'];
		$shipmentData['instaDispatch_statusCode'] = $data['statusCode'];
		$shipmentData['instaDispatch_jobTypeCode'] = $data['jobTypeCode'];
		$shipmentData['instaDispatch_availabilityTypeCode'] = $data['availabilityTypeCode'];
		$shipmentData['instaDispatch_availabilityTypeName'] = $data['availabilityTypeName'];
		$shipmentData['instaDispatch_loadGroupTypeId'] = $data['loadGroupTypeId'];
		$shipmentData['instaDispatch_loadGroupTypeIcon'] = $data['loadGroupTypeIcon'];
		$shipmentData['instaDispatch_loadGroupTypeCode'] = 'Vendor';
		$shipmentData['instaDispatch_loadGroupTypeName'] = 'Vendor';
		$shipmentData['instaDispatch_customerReference'] = $data['customerReference'];
		$shipmentData['waitAndReturn'] = $data['waitAndReturn'];
		$shipmentData['company_id'] = $this->company_id;
        $shipmentData['customer_id'] = $this->customer_id;

        $shipmentData['warehousereceived_date'] = $warehouseReceivedDate;


        
		//$shipmentData['search_string'] = $ticketNumber.' '.$data['docketNumber'].' '.$shipmentData['instaDispatch_objectIdentity'].' '.$shipmentData['shipment_address1'].' '.$shipmentData['shipment_address2'].' '.$shipmentData['shipment_address3'].' '.str_replace(' ','',$shipmentData['shipment_postcode']).' '.$shipmentData['shipment_customer_city'].' '.$shipmentData['shipment_customer_name'].' '.$shipmentData['shipment_required_service_date'];
		/*$address = $shipmentData['shipment_address1']." ".$shipmentData['shipment_address2']." ".$shipmentData['shipment_address3']." ".str_replace(' ','',$shipmentData['shipment_postcode'])." ".
		$shipmentData['shipment_customer_city']." ".$shipmentData['shipment_customer_country'];*/

        $shipmentData['search_string'] = $ticketNumber.' '.$data['docketNumber'].' '.$shipmentData['instaDispatch_objectIdentity'].' '.str_replace(' ','',$data['postcode']).' '.$shipmentData['shipment_customer_name'].' '.$shipmentData['shipment_required_service_date'];
        /*$address = $shipmentData['shipment_address1']." ".$shipmentData['shipment_address2']." ".$shipmentData['shipment_address3']." ".str_replace(' ','',$shipmentData['shipment_postcode'])." ".
        $shipmentData['shipment_customer_city']." ".$shipmentData['shipment_customer_country']; */
		
        $data['latitude']  = isset($data['latitude'])?$data['latitude']:'';
        $data['longitude'] = isset($data['longitude'])?$data['longitude']:'';
		
        $shipment_geo_location = $this->get_lat_long_by_postcode($data['postcode'],$data['latitude'],$data['longitude']);
		
        $shipmentData['shipment_latitude'] = $shipment_geo_location["latitude"];
		$shipmentData['shipment_longitude'] = $shipment_geo_location["longitude"];
		$shipmentData['shipment_latlong'] = $shipment_geo_location["latitude"].','.$shipment_geo_location["longitude"];
		
       if($shipmentData['shipment_latitude'] ==0.00){
			$shipmentData['error_flag'] = 1;
		} else {
			$shipmentData['error_flag'] = 0;
		}
        
        $address_data = array();
        $address_data['address_line1'] = (isset($data["address1"])) ? $data["address1"] : "";
        $address_data['address_line2'] = (isset($data["address2"])) ? $data["address2"] : "";
        $address_data['city'] = (isset($data["city"])) ? $data["city"] : "";
        $address_data['state'] = (isset($data["county"])) ? $data["county"] : "";
        $address_data['country'] = (isset($data["country"])) ? $data["country"] : "";
        $address_data['postcode'] = (isset($data["postcode"])) ? $data["postcode"] : "";
        $address_data['latitude'] = $shipmentData["shipment_latitude"];
        $address_data['longitude'] = $shipmentData["shipment_longitude"];
        $address_data['customer_id'] = $this->company_id;
        $address_data['type'] = (isset($data["status"])) ? $data["status"] : "";

        $address_status = $this->_save_address($address_data);
       
        if($address_status["status"]=="success"){

            $shipmentData["address_id"] = $address_status["address_id"];

            $shipmentId = $this->db->save("shipment", $shipmentData);

            $shipmentData['shipment_postcode'] = $postcode;
            $shipmentData['shipment_address'] = $address_data['address_line1']; 
            
            $this->returntempdata[$ticketNumber]['shipment'] = (array)$shipmentData;
            if($shipmentId){
                $dataStatus = true;
                if(!array_key_exists('0',$data['jobLoadItems']['jobLoadItem'])){
                    $tempData = $data['jobLoadItems']['jobLoadItem'] ;
                    $data['jobLoadItems']['jobLoadItem'] = array();
                    $data['jobLoadItems']['jobLoadItem'][] = $tempData;
                    $tempData = '';
                }
                if(count($data['jobLoadItems']['jobLoadItem'])>0){
                    foreach($data['jobLoadItems']['jobLoadItem'] as $key=>$values){
                        $parcelticketNumber = $this->_generate_parcel_ticket_number();
                        $parcelData = array();
                        $parcelData['shipment_id'] = $shipmentId;
                        $parcelData['instaDispatch_pieceIdentity'] = ($values['pieceIdentity']!=0)?$values['pieceIdentity']:$ticketNumber;
                        $parcelData['instaDispatch_jobIdentity'] = ($values['jobIdentity']!=0)?$values['jobIdentity']:$ticketNumber;
                        $parcelData['instaDispatch_loadIdentity'] = ($values['loadIdentity']!=0)?$values['loadIdentity']:$ticketNumber;
                        $parcelData['shipment_ticket'] = $ticketNumber;
                        $parcelData['parcel_ticket'] = $parcelticketNumber;
                        $parcelData['parcel_weight'] = $values['pieceWeight'];
                        $parcelData['parcel_height'] = $values['pieceHeight'];
                        $parcelData['parcel_length'] = $values['pieceLength'];
                        $parcelData['parcel_width'] = $values['pieceWidth'];
                        $parcelData['parcel_type'] = ($values['loadTypeName']=='Collection')?'P':'D';
                        $parcelData['create_date'] = date("Y-m-d");
                        $parcelData['dataof'] = $data['company_code'];
                        $parcelData['status'] = '1';
                        $parcelData['docketNumber']  = ($shipmentData['instaDispatch_docketNumber']!=0)?$shipmentData['instaDispatch_docketNumber']:$ticketNumber; //$shipmentData['instaDispatch_docketNumber'];
                        $parcelData['customerReference']              = $shipmentData['instaDispatch_customerReference'];
                        $parcelData['objectIdentity'] = ($values['objectIdentity']!=0)?$values['objectIdentity']:$ticketNumber;
                        $parcelData['availabilityTypeId'] = $values['availabilityTypeId'];
                        $parcelData['availabilityTypeCode'] = $values['availabilityTypeCode'];
                        $parcelData['instaDispatch_Identity'] = $ticketNumber;
                        $parcelData['warehouse_id'] = $warehouse_id;
                        $parcelData['company_id'] = $this->company_id;
                        $parcelData['is_driver_scan'] = 0;

                        $parcelData["warehousereceived_date"] = $warehouseReceivedDate;

                        $parcelData["driver_pickuptime"] = "1970-01-01 00:00:00";



                        $shipmentId = $this->db->save("shipments_parcel", $parcelData);
                        $this->returntempdata[$ticketNumber]['parcel'][] = $parcelData;

                    }
                }
            }
            //$returnData = $ticketNumber;
            return array("status"=>"success","ticket_number"=>$ticketNumber);
            //return $returnData;
        }else{
            return array("status"=>"error","message"=>"Address not saved");
            
        }
	}
	
    public function addShipmentDataPhone($data,$filename){ 
	$dataStatus = false;
	$ticketNumber = $this->_generate_ticket_no();
	$shipmentData['instaDispatch_loadIdentity'] = $data['identity'];
	$shipmentData['instaDispatch_jobIdentity'] = $data['jobIdentity'];
	$shipmentData['instaDispatch_objectIdentity'] = isset($data['objectIdentity'])?$data['objectIdentity']:''; //  added new field on 16 10 2015
	$shipmentData['instaDispatch_objectTypeName'] = isset($data['objectTypeName'])?$data['objectTypeName']:''; //  added new field on 16 10 2015
	$shipmentData['shipment_service_type'] = ($data['jobTypeName']=='Collection')?'P':'D';
	$shipmentData['shipment_required_service_date'] = $data['executionDate'];
	$shipmentData['shipment_required_service_starttime'] = $data['startTime'];
	$shipmentData['shipment_required_service_endtime'] = $data['endTime'];
	$shipmentData['shipment_address1'] = !empty($data['address1'])?$data['address1']:'';
	$shipmentData['shipment_address2'] = !empty($data['address2'])?$data['address2']:'';
	$shipmentData['shipment_address3'] = !empty($data['address3'])?$data['address3']:'';
	$shipmentData['shipment_postcode'] = $data['postcode'];
	$shipmentData['shipment_total_item'] = $data['itemCount'];
	$shipmentData['shipment_total_weight'] = $data['weight'];
	$shipmentData['shipment_total_volume'] = $data['volume'];
	$shipmentData['shipment_highest_length'] =$data['highestLength'];
	$shipmentData['shipment_highest_width'] = $data['highestWidth'];
	$shipmentData['shipment_highest_height'] = $data['highestHeight'];
	$shipmentData['shipment_highest_weight'] = $data['highestWeight'];
	$shipmentData['shipment_customer_name'] = !empty($data['contactName'])?$data['contactName']:'';
	$shipmentData['shipment_customer_email'] = !empty($data['contactEmail'])?$data['contactEmail']:''; 
	$shipmentData['shipment_customer_phone'] = !empty($data['contactPhone'])?$data['contactPhone']:'';  
	$shipmentData['shipment_customer_city'] = !empty($data['city'])?$data['city']:''; 
	$shipmentData['shipment_customer_country'] = !empty($data['county'])?$data['county']:''; 
	$shipmentData['shipment_country_code'] = $data['countryCode'];
	$shipmentData['shipment_zone_code'] = $data['zoneCode'];
	$shipmentData['shipment_instruction'] = json_encode($data['instruction']);
	$shipmentData['shipment_isDutiable'] = $data['isDutiable'];
	$shipmentData['shipment_shouldBookIn'] = $data['shouldBookIn'];
	$shipmentData['shipment_statusName'] = $data['statusName'];
	$shipmentData['shipment_executionOrder'] = $data['executionOrder'];
	$shipmentData['shipment_companyName'] = !empty($data['companyName'])?$data['companyName']:'';   
	$shipmentData['shipment_xml_reference'] = $filename;
	$shipmentData['shipment_create_date'] = date("Y-m-d");
	$shipmentData['shipment_total_attempt'] = '0';
	$shipmentData['is_shipment_routed'] = '0';
	$shipmentData['is_driver_assigned'] = '0';
	$shipmentData['shipment_pod'] = '';
	$shipmentData['current_status'] = 'C';
	$shipmentData['dataof'] = 'PnP';	
	$shipmentData['shipment_ticket'] = $ticketNumber;
	$shipmentData['instaDispatch_docketNumber'] = !empty($data['docketNumber'])?$data['docketNumber']:'';
	/* same day work start */
	$shipmentData['instaDispatch_objectTypeId'] = $data['objectTypeId'];
	$shipmentData['instaDispatch_businessName'] = $data['businessName'];
	$shipmentData['instaDispatch_accountNumber'] = $data['accountNumber'];
	$shipmentData['instaDispatch_statusCode'] = $data['statusCode'];
	$shipmentData['instaDispatch_jobTypeCode'] = $data['jobTypeCode'];
	$shipmentData['instaDispatch_availabilityTypeCode'] = $data['availabilityTypeCode'];
	$shipmentData['instaDispatch_availabilityTypeName'] = $data['availabilityTypeName'];
	$shipmentData['instaDispatch_loadGroupTypeId'] = $data['loadGroupTypeId'];
	$shipmentData['instaDispatch_loadGroupTypeIcon'] = $data['loadGroupTypeIcon'];
	//$shipmentData['instaDispatch_loadGroupTypeCode'] = $data['loadGroupTypeCode'];
	//$shipmentData['instaDispatch_loadGroupTypeName'] = $data['loadGroupTypeName'];
	$shipmentData['instaDispatch_loadGroupTypeCode'] = 'PHONE';
	$shipmentData['instaDispatch_loadGroupTypeName'] = 'Phone';
	$shipmentData['waitAndReturn'] = $data['waitAndReturn'];
	$shipmentData['instaDispatch_customerReference'] = $data['customerReference'];
	//$shipmentData['search_string'] = $ticketNumber.' '.$data['docketNumber'].' '.$shipmentData['instaDispatch_objectIdentity'];
	$shipmentData['search_string'] = $ticketNumber.' '.$data['docketNumber'].' '.$shipmentData['instaDispatch_objectIdentity'].' '.$shipmentData['shipment_address1'].' '.$shipmentData['shipment_address2'].' '.$shipmentData['shipment_address3'].' '.str_replace(' ','',$shipmentData['shipment_postcode']).' '.$shipmentData['shipment_customer_city'].' '.$shipmentData['shipment_customer_name'].' '.$shipmentData['shipment_required_service_date'];
	
	
	$address = $shipmentData['shipment_address1']." ".$shipmentData['shipment_address2']." ".$shipmentData['shipment_address3']." ".str_replace(' ','',$shipmentData['shipment_postcode'])." ".
	           $shipmentData['shipment_customer_city']." ".$shipmentData['shipment_customer_country']; 
	
	//$shipmentData['shipment_latlong'] = $this->get_lat_long($address,$shipmentData['shipment_customer_country']);
	$shipmentData['shipment_latlong'] = parent::get_lat_longbyPostcode($data['postcode'],$data['lattitude'],$data['longitude']);
	$shipmentId = $this->modelObjs->addContent('iCargo_shipment',$shipmentData);
	if($shipmentId){
	   $dataStatus = true;
	   if(!array_key_exists('0',$data['jobLoadItems']['jobLoadItem'])){
		$tempData = $data['jobLoadItems']['jobLoadItem'] ;
		$data['jobLoadItems']['jobLoadItem'] = '';
		$data['jobLoadItems']['jobLoadItem'][] = $tempData;
		$tempData = '';
	  }
		if(count($data['jobLoadItems']['jobLoadItem'])>0){
		   foreach($data['jobLoadItems']['jobLoadItem'] as $key=>$values){
			    $parcelticketNumber							 = $this->generateParcelTicketNumber();
			    $parcelData 								 = array();
			    $parcelData['shipment_id'] 					 = $shipmentId;
			    $parcelData['instaDispatch_Identity'] 		 = $values['identity'];
			    $parcelData['instaDispatch_pieceIdentity']   = $values['pieceIdentity'];
			    $parcelData['instaDispatch_jobIdentity']     = $values['jobIdentity'];
			    $parcelData['instaDispatch_loadIdentity']    = $values['loadIdentity'];
			    $parcelData['shipment_ticket'] 				 = $ticketNumber;
			    $parcelData['parcel_ticket'] 				 = $parcelticketNumber;
			    $parcelData['parcel_weight'] 				 = $values['pieceWeight'];
			    $parcelData['parcel_height'] 				 = $values['pieceHeight'];
			    $parcelData['parcel_length'] 				 = $values['pieceLength'];
			    $parcelData['parcel_width'] 				 = $values['pieceWidth'];
			    $parcelData['parcel_type'] 					 = ($values['loadTypeName']=='Collection')?'P':'D';    
			    $parcelData['create_date'] 					 = date("Y-m-d");
			    $parcelData['dataof'] 						 = 'PnP';
			    $parcelData['status'] 						 = '1';
			    
			    // add some new data for same day
			    //$parcelData['docketNumber'] 				 = empty($values['docketNumber'])?'':$values['docketNumber'];
			    //$parcelData['customerReference'] 			 = empty($values['customerReference'])?'':$values['customerReference'];
			    $parcelData['docketNumber'] 				 = $shipmentData['instaDispatch_docketNumber'];
			    $parcelData['customerReference'] 			 = $shipmentData['instaDispatch_customerReference'];
				
			    $parcelData['objectIdentity'] 				 = $values['objectIdentity'];
			    $parcelData['availabilityTypeId'] 			 = $values['availabilityTypeId'];
			    $parcelData['availabilityTypeCode'] 		 = $values['availabilityTypeCode'];
			    $this->modelObjs->addContent('iCargo_shipments_parcel',$parcelData);			  
			  }
		    }
		}	  
     // return $dataStatus;
	  return array('dataStatus'=>$dataStatus,'ticketNumber'=>$ticketNumber);
      }

	public function save(){
		$status = array();
		$temp = $this->upload_shipment_file();
		if(!empty($temp) and $temp["status"]!="error"){
			//save the shipment
			$data = $temp['data'];
			$data['latitude'] = empty($data['lattitude'])?'':$data['lattitude'];
			$data['longitude'] = empty($data['longitude'])?'':$data['longitude'];
			$data['companyName'] = empty($data['companyName'])?'':$data['companyName'];
			$data['customerReference'] = empty($data['customerReference'])?'':$data['customerReference'];
			if((strtoupper($data['loadGroupTypeCode']) =='NEXT') || (strtoupper($data['loadGroupTypeCode'] =='SAME'))){
				$status = $this->_add_shipment_data_nextday_sameday($data); 
			}else{
				if(!empty($data['attributes'])){
					if($data['attributes']['loadSubGroupTypeCode']=='Phone'){
						$returnstatus = $this->addShipmentDataPhone($data);   
						$status = $returnstatus['dataStatus'];
						$ticketNumber =$returnstatus['ticketNumber']; 
						$informationarray = $data['attributes'];
						unset($informationarray['loadSubGroupTypeCode']);
						if(!empty($informationarray)){
							foreach($informationarray as $key=>$val){
								$infoData['ticket'] = $ticketNumber;
								$infoData['keydata'] = $key;
								$infoData['valuedata'] = $val;
								$status = $this->modelObjs->addContent('iCargo_shipments_additionalinfo',$infoData);	
							}  
						}
                    }else{
						$status = $this->_add_shipment_data_uk_mail($data); 
					}
				}else{
					$status = $this->_add_shipment_data_uk_mail($data);
				} 
			}
		}
		return $status;
	}
    
    public function addshipmentDetail(){
       $data = array();  
	   $postcode = $this->postcodeObj->validate($this->tempdata->delivery->postcode);//$this->validatePostcode($this->tempdata->delivery->postcode);
       if($postcode){
		   $dateData = isset($this->tempdata->delivery->servicedate)?$this->tempdata->delivery->servicedate:date("Y/m/d H:m:s");
		   
		   $data['parcelid'] = isset($this->tempdata->delivery->docketnumber)?$this->tempdata->delivery->docketnumber:0;
		   $data['scandate'] = date("Y/m/d H:m:s",strtotime($dateData));
		   
		   $data['quantity'] = isset($this->tempdata->delivery->parcel->quantity)?$this->tempdata->delivery->parcel->quantity:1;
		   $data['weight']  = isset($this->tempdata->delivery->parcel->weight)?$this->tempdata->delivery->parcel->weight:1;
		   $data['length'] = isset($this->tempdata->delivery->parcel->length)?$this->tempdata->delivery->parcel->lengt:1;
		   $data['width'] = isset($this->tempdata->delivery->parcel->width)?$this->tempdata->delivery->parcel->width:1;
		   $data['height'] = isset($this->tempdata->delivery->parcel->height)?$this->tempdata->delivery->parcel->height:1;
		   $data['customername'] = isset($this->tempdata->delivery->customername)?$this->tempdata->delivery->customername:'';
           
           $data['address1'] = isset($this->tempdata->delivery->address1)?$this->tempdata->delivery->address1:'';
           $data['address2'] = isset($this->tempdata->delivery->address2)?$this->tempdata->delivery->address2:'';
           $data['city'] = isset($this->tempdata->delivery->city)?$this->tempdata->delivery->city:'';
           $data['county'] = isset($this->tempdata->delivery->county)?$this->tempdata->delivery->county:'';
           $data['country'] = $this->tempdata->delivery->country->country;
           $data['postcode'] = strtoupper($postcode);
           
           $data['email'] = isset($this->tempdata->delivery->email)?$this->tempdata->delivery->email:'';
		   $data['phone'] = isset($this->tempdata->delivery->phone)?$this->tempdata->delivery->phone:'';
		   
           
		   $data['client'] = isset($this->tempdata->delivery->company)?$this->tempdata->delivery->company:'';
		   $shipdata = $this->_getSingleShipmentData((object)$data,$this->company_id);
          
		   $shipid = $this->_add_shipment_data_uk_mail($shipdata);
		   
		   if($shipid["status"]=="success"){
               $gridData[] =  $this->_getgridData($shipid["ticket_number"]);
               return array('success'=>true,'message'=>'Shipment has been added successfully','griddata'=>$gridData);
           }else{
               return array('success'=>false,'message'=>'Shipment has not been added successfully','griddata'=>array());
           }
     	}else{
        	throw new Exception('Invalid Post Code');   
       }
   }

    public function importshipment(){
        $status = array();
        switch(strtolower($this->job_type)){
            case 'retail':
            $data = $this->_preparedataFromCsv($this->job_type,$this->company_id,$this->file_name,$this->tempdata);
            $status = $this->_addShipment($this->job_type,$data);
            break;
        }
    return $status;  
    }
   
    private function _get_company_code($company_id){
        $record = $this->db->getRowRecord("SELECT code AS code FROM " . DB_PREFIX . "user_code WHERE id = '$company_id'");
		return $record['code'];
	}
    
    private function _preparedataFromCsv($job_type,$company_id,$file_name,$tempdata){
        $temperarydata = array();
        $tempdata =  (array) $tempdata;
       if(count($tempdata['values']) > 0){  
           foreach($tempdata['values'] as $key=>$val){  
              $data = $this->_getSingleShipmentData($val,$company_id);
              //$data['address1'] = $val->address;
              $data['address1'] = isset($val->address)?$val->address:'';
              $temperarydata[] = $data;
           }
       }
     return $temperarydata;    
   }    
    
    private function _addShipment($job_type,$shipmentdata){
        $count = 0;
        $temparray = array();
        $tempreturnarray = array();
		if(count($shipmentdata)>0){
			foreach($shipmentdata as $key=>$val){
				$count++;
				$status = $this->_add_shipment_data_uk_mail($val);
                if($status["status"]=="success"){
                    $gridData =  $this->_getgridData($status["ticket_number"]);
                    $temparray[] = $status["ticket_number"];
                    $tempreturnarray[] = $gridData;
                }
			}
		}
        return array('status'=>'sussess','message'=>'Total '.$count.' has been added successfully','griddata'=>$tempreturnarray,'return'=>json_encode($temparray));
    }
   
       
    private function _getSingleShipmentData($val,$company_id){
		$data = array();
		$data['company_code']    = $this->_get_company_code($company_id);
		$data['postcode']        = $val->postcode;	
		$data['docketNumber']    = $val->parcelid;	
		$data['identity']        = $val->parcelid;	
		$data['jobIdentity']     = $val->parcelid;	
		$data['objectIdentity']  = $val->parcelid;	
		$data['objectTypeName']	 = 'JobLoad';
		$data['jobTypeName']     = 'Delivery';
		$data['executionDate']   = date('Y-m-d',strtotime(str_replace(array("/","-"), array("-","-"), isset($val->scandate)?$val->scandate:'')));
		$data['startTime']       = date('h:m:s',strtotime(str_replace("/", "-", isset($val->scandate)?$val->scandate:'')));
		$data['endTime']         = date('h:m:s',strtotime(str_replace("/", "-", isset($val->scandate)?$val->scandate:'')));
        
		$data['address1']        =  isset($val->address1)?$val->address1:'';  
		$data['address2']        =  isset($val->address2)?$val->address2:'';               
		$data['address3']        =  '';
        $data['contactName']     =  isset($val->customername)?$val->customername:'';
        $data['contactEmail']    =  isset($val->email)?$val->email:'';
        $data['contactPhone']    =  isset($val->phone)?$val->phone:'';
        $data['city']            =  isset($val->city)?$val->city:'';
        $data['county']          =  isset($val->county)?$val->county:'';
        $data['countryCode']     =  isset($val->country)?$val->country:'';
        $data['zoneCode']        = '';
        
		$data['itemCount']       =  isset($val->quantity)?$val->quantity:'1';
		$data['weight']          =  isset($val->weight)?$val->weight:'1';   
		$data['volume']          =  1;   
		$data['highestLength']   =  isset($val->length)?$val->length:'1';   
		$data['highestWidth']    =  isset($val->width)?$val->width:'1';   
		$data['highestHeight']   =  isset($val->height)?$val->height:'1';   
		$data['highestWeight']   =  isset($val->weight)?$val->weight:'1';  
		
		$data['instruction']     = '';
		$data['isDutiable']      = 'false';
		$data['shouldBookIn']    = 'false';
		$data['statusName']      = 'Un Attainded';
		$data['executionOrder']  = 0;   
		$data['companyName']     = isset($val->client)?$val->client:'';
		$data['objectTypeId']    = 0;  
		$data['businessName']    = $data['company_code'];
		$data['accountNumber']   = 0;
		$data['statusCode']      = 'UNATTAINDED';
		$data['jobTypeCode']     = 'DELV';
		$data['availabilityTypeCode']  ='UNKN';  
		$data['availabilityTypeName']  = 'Unknown';
		$data['loadGroupTypeId']       = '0';
		$data['loadGroupTypeIcon']     = '';
		$data['customerReference']     = '';
		$data['waitAndReturn']         = 'false';
		$data['jobLoadItems']['jobLoadItem'] = array(
				'identity'			  =>	$val->parcelid,
				'pieceIdentity'		  =>	$val->parcelid,
				'jobIdentity'		  =>	$val->parcelid,
				'loadIdentity'  	  =>  $val->parcelid,
				'pieceHeight'   	  =>  isset($val->height)?$val->height:'1',
				'pieceLength'   	  =>  isset($val->length)?$val->length:'1',
				'pieceWidth'    	  =>  isset($val->width)?$val->width:'1',
				'pieceWeight'   	  =>  isset($val->weight)?$val->weight:'1',  
				'loadTypeName'  	  =>  'Delivery',
				'objectIdentity'      =>  $val->parcelid,
				'availabilityTypeId'  =>0,
				'availabilityTypeCode'=>'UNKN'
			);    
		return $data;
     }
 
   
    private function _getgridData($shipid){
        $record = $this->returntempdata[$shipid]['shipment'];

        $parcel = array();
        $parcelRecord = $this->returntempdata[$shipid]['parcel'];
        if(count($parcelRecord)>0){foreach($parcelRecord as $key=>$val){
          $temprarce = array();
          $temprarce['instaDispatch_pieceIdentity'] = $val['instaDispatch_pieceIdentity'];
          $temprarce['parcel_weight'] = $val['parcel_weight'];
          $temprarce['parcel_width']  = $val['parcel_width'];
          $temprarce['parcel_height'] = $val['parcel_height'];
          $temprarce['parcel_length'] = $val['parcel_length'];
          $parcel[] = $temprarce;
    }}
        
        $data  = array();        
        $data["shipment_ticket"] = $record["shipment_ticket"];
        $data["docket_no"]       = $record["instaDispatch_docketNumber"];
        $data["reference_no"]    = $record["instaDispatch_loadIdentity"];
        $data["service_date"]    = $record["shipment_required_service_date"];
        $data["service_time"]    = $record["shipment_required_service_starttime"];
        $data["weight"]          = $record["shipment_total_weight"];
        $data["postcode"]        = $record["shipment_postcode"];
        $data["attempt"]         = $record["shipment_total_attempt"];
        $data["shipment_address"]= $record["shipment_address"];
        $data["in_warehouse"]    = 'NO';
        $data["type"]            = "retail";
        $data["parcels"]         = $parcel;
        $data["action"]          = "<a>Detail</a>";
        $data["count"]           =  1;
        $data["service_type"]    = "Delivery"; 
      return $data;
    }

    private
    
    function _save_shipment($param){
        $data = array();
        
        $ticketNumber = $this->_generate_ticket_no();

        if($ticketNumber){
            $timestamp = $param["timestamp"];
            $company_code = $this->_get_company_code($param["company_id"]);

            //customer info
            $data['shipment_customer_name']    = (isset($param["name"])) ? $param["name"] : "";
            $data['shipment_customer_email']   = (isset($param["email"])) ? $param["email"] : "";
            $data['shipment_customer_phone']   = (isset($param["phone"])) ? $param["phone"] : "";

            /*data not saved*/
            $data['shipment_total_weight']     = $param["weight"];
            $data['shipment_total_volume']     = $param["weight"];
            $data['shipment_statusName']       = "Un Attainded";
            $data['shipment_shouldBookIn']     = "false";
            $data['shipment_companyName']      = "";
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

            //$shipment_geo_location = $this->get_lat_long_by_postcode($param["postcode"],$param['latitude'],$param['longitude']);

            //$warehouse_id = $this->_shipment_warehouse(array("company_id"=>$param["company_id"], "postcode"=>$param["postcode"], "shipment_geo_location"=>$shipment_geo_location));
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
            $data['shipment_required_service_date'] = $param["service_date"];
            $data['current_status'] = 'C';
            $data['is_shipment_routed'] = '0';
            $data['is_driver_assigned'] = '0';
            $data['dataof'] = $company_code;
            $data['waitAndReturn'] = $param['waitAndReturn'];
            $data['company_id'] = $this->company_id;
            $data['warehouse_id'] = $warehouse_id;
            $data['address_id'] = $param['address_id'];
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
            $data['shipment_postcode'] = (isset($param["postcode"])) ? $param["postcode"] : "";
            $data['shipment_customer_country'] = (isset($param["country"])) ? $param["country"] : "";
            $data['shipment_instruction'] = (isset($param["shipment_instruction"])) ? $param["shipment_instruction"] : "";

//print_r($data);die;
            //save address first then save shipment detail with address id
            $shipmentId = $this->db->save("shipment", $data);

            if($shipmentId){
                return array('status'=>"success",'message'=>'Shipment has been added successfully', "shipment_id"=>$shipmentId);
            }else{
                return array('status'=>"error",'message'=>'Shipment has not been added successfully');
            }
        }else{
            return array('status'=>"error",'message'=>'Configuration not found');
        }
    }

    private
    
    function _saveShipmentPriceBreakdown($param)
    {
        $price_breakdown = array();
        $shipmentId = $param["shipment_id"];
        $priceVersionNo = $param["version"];
        $shipmentType = $param["shipment_type"];
        //$serviceOpted = $param["service_opted"];
        $price_breakdown["shipment_type"] = $shipmentType;
        $price_breakdown["shipment_id"] = $shipmentId;
        $price_breakdown["version"] = $priceVersionNo;
        $price_breakdown["load_identity"] = $param["service_opted"]->load_identity;
	    
        $response = array();
        
        if(isset($param->total_price)){
            $price_breakdown["price_code"] = "total_price";
            $price_breakdown["price"] = $param->total_price;
            $price_breakdown["api_key"] = "total_price";
            //save record
            $price_breakdown_id = $this->db->save("shipment_price", $price_breakdown);
            
            array_push($response,$price_breakdown_id);
        }
        if(isset($param->service_opted->courier_commission_value)){
            $price_breakdown["price_code"] = "courier_commission";
            $price_breakdown["price"] = $param->service_opted->courier_commission_value;
            $price_breakdown["api_key"] = "courier_commission";
            //save record
            $price_breakdown_id = $this->db->save("shipment_price", $price_breakdown);
            array_push($response,$price_breakdown_id);
        }
        if(isset($param->service_opted->base_price)){
            $price_breakdown["price_code"] = "base_price";
            $price_breakdown["price"] = $param->service_opted->base_price;
            $price_breakdown["api_key"] = "base_price";
            //save record
            $price_breakdown_id = $this->db->save("shipment_price", $price_breakdown);
            array_push($response,$price_breakdown_id);
        }
        //save surcharges
        if(isset($param["service_opted"]->surcharges)){
            foreach($param["service_opted"]->surcharges as $key => $item){
                $price_breakdown["price_code"] = $key;
                $price_breakdown["price"] = ($item!="") ? $item : "0.00";
                $price_breakdown["api_key"] = "surcharges";
                $price_breakdown_id = $this->db->save("shipment_price", $price_breakdown);
                array_push($response,$price_breakdown_id);
            }
        }
        
        //save surcharges
        if(isset($param["service_opted"]->taxes)){
            foreach($param["service_opted"]->taxes as $key => $item){
                $price_breakdown["price_code"] = $key;
                $price_breakdown["price"] = ($item!="") ? $item : "0.00";
                $price_breakdown["api_key"] = "taxes";
                $price_breakdown_id = $this->db->save("shipment_price", $price_breakdown);
                array_push($response,$price_breakdown_id);
            }
        }
        return $response;
    }

    private
    
    function _saveShipmentService($param){
        $_data = array();
        $_attribute = array();
        $service_id = "";
        $_data["surcharges"] = 0;
        $_data["taxes"] = 0; 
        $_data["carrier"] = '1';
        
            
        $data_string = json_encode($param);
        
        $_attribute["shipment_id"] = $param->shipment_id;
        
        if(isset($param->icon)){
            $_attribute["column_name"] = "icon";
            $_attribute["value"] = $param->icon;
            $_attribute["api_key"] = "icon";
            $attribute_id = $this->db->save("shipment_attributes", $_attribute);
            
            unset($param->icon);
        }
        
        if(isset($param->dimensions)){
            foreach($param->dimensions as $column=>$item){
                $_attribute["column_name"] = $column;
                $_attribute["value"] = $item;
                $_attribute["api_key"] = "dimensions";
                $attribute_id = $this->db->save("shipment_attributes", $_attribute);
            }
            unset($param->dimensions);
        }
        
        if(isset($param->weight)){
            foreach($param->weight as $column=>$item){
                $_attribute["column_name"] = $column;
                $_attribute["value"] = $item;
                $_attribute["api_key"] = "weight";
                $attribute_id = $this->db->save("shipment_attributes", $_attribute);
            }
            unset($param->weight);
        }
        
        if(isset($param->time)){
            foreach($param->time as $column=>$item){
                $_attribute["column_name"] = $column;
                $_attribute["value"] = ($item!="") ? $item : 0;
                $_attribute["api_key"] = "time";
                $attribute_id = $this->db->save("shipment_attributes", $_attribute);
            }
            unset($param->time);
        }
        
        if(isset($param->surcharges)){
            $_data["surcharges"] = array_sum((array)$param->surcharges);
            unset($param->surcharges);
        }
        
        if(isset($param->taxes)){
            $_data["taxes"] = array_sum((array)$param->taxes);
            unset($param->taxes);
        }
        
        foreach($param as $column=>$item)
            $_data[$column] = $item;
        
        if($_data["charge_from_base"]==""){
            $_data["charge_from_base"] = 0;
        }
       
        if($_data){
            $_data["json_data"] = $data_string;
            $service_id = $this->db->save("shipment_service", $_data);
        }
        return $service_id;
    }

    private
    
    function _findPriceNextVersionNo($shipment_id){
        $record = $this->db->getRowRecord("SELECT `price_version` + 1 AS version_no FROM " . DB_PREFIX . "shipment_service WHERE `shipment_id` = '$shipment_id'");
        if(!$record)
            return 1;
        else
            return $record['version_no'];
    }

    private

    function _getCountryAlpha3Code($country){
        $record = $this->db->getRowRecord("SELECT alpha3_code FROM " . DB_PREFIX . "countries WHERE `short_name` = '$country'");
        return $record['alpha3_code'];
    }

    private
    
    function _getAddressBySearchStringAndCustomerId($customer_id, $search_string){
        $record = $this->db->getRowRecord("SELECT id AS address_id FROM " . DB_PREFIX . "address_book WHERE `customer_id` = '$customer_id' AND `search_string` LIKE '$search_string'");
        return $record['address_id'];
    }
    
    private
    
    function _getShipmentLoadIdentity($shipment_id){
        $record = $this->db->getRowRecord("SELECT instaDispatch_loadIdentity AS load_identity FROM " . DB_PREFIX . "shipment WHERE `shipment_id` = '$shipment_id'");
        return $record['load_identity'];
    }

    private
    
    function _save_address($address){
        $postcode = $this->postcodeObj->validate($address["postcode"]);
       
        if($postcode){
            $data = array();
            $data["address_line1"] = (isset($address["address_line1"])) ? addslashes($address["address_line1"]) : "";
            $data["address_line2"] = (isset($address["address_line2"])) ? addslashes($address["address_line2"]) : "";
            
            $data["postcode"] = addslashes($address["postcode"]);
            $data["city"] = (isset($address["city"])) ? addslashes($address["city"]) : "";
            $data["state"] = (isset($address["state"])) ? addslashes($address["state"]) : "" ;
            $data["country"] = (isset($address["country"])) ? addslashes($address["country"]) : "";
            $data["iso_code"] = (isset($address["country"])) ? addslashes($this->_getCountryAlpha3Code($address["country"])) : "";
            
            $data["company_name"] = (isset($address["company_name"])) ? addslashes($address["company_name"]) : "";
            
            $data["search_string"] = str_replace(' ','',implode('',$data));
            
            $data["latitude"] = $address["latitude"];
            $data["longitude"] = $address["longitude"];
            $data["is_default_address"] = (isset($address["is_default_address"])) ? $address["is_default_address"] : "N";
            $data["customer_id"] = $address["customer_id"];
            
            $data["is_warehouse"] = (isset($address["is_warehouse"])) ? $address["is_warehouse"] : "N";
            $data["address_type"] = (isset($address["type"])) ? $address["type"] : "";//address_type
            
            $data["billing_address"] = (isset($address["billing_address"])) ? addslashes($address["billing_address"]) : "N";
            
            $address_id = $this->_getAddressBySearchStringAndCustomerId($address["customer_id"], $data["search_string"]);
            
            if(!$address_id){
                $data["version_id"] = "version_1";
            
            $address_id = $this->db->save("address_book", $data);
            }
            return array("status"=>"success", "address_id"=>$address_id);
        }else{
            return array("status"=>"error", "message"=>"Invalid postcode");
        }
    }

    private
    
    function _bookSameDayShipment($param){ 
        $shipment_data = $param["shipment_data"];
        //address
        $address = $this->_save_address($shipment_data);
        //shipment
        if($address["status"]=="success"){
            $shipment_data["address_id"] = $address["address_id"];

            $shipmentStatus = $this->_save_shipment($shipment_data);

            if($shipmentStatus["status"]=="success"){
                $shipmentId = $shipmentStatus["shipment_id"];
                return array("status"=>"success", "shipment_id"=>$shipmentId, "address_id"=> $address["address_id"]);
            }else{
                return array("status"=>"error","message"=>$shipmentStatus["message"]);
            }
        }
        else{
            return array("status"=>"error","message"=>"Address book error");
        }
    }

    private
    
    function _prepareShipmentData($param){
        $_data = array();
        $data = $param["shipment_data"];
        $timestamp = $param["timestamp"];
        $customer_id = $param["customer_id"];
        
        foreach($data as $column => $item){
            $_data[$column] = $item;
        }
        
        
        $_data["parcel_quantity"] = (isset($_data["parcel_quantity"])) ? $_data["parcel_quantity"] : 1;
        $_data["parcel_weight"] = (isset($_data["parcel_weight"])) ? $_data["parcel_weight"] : 1;
        $_data["length"] = (isset($_data["length"])) ? $_data["length"] : 1;
        $_data["width"] = (isset($_data["width"])) ? $_data["width"] : 1;
        $_data["height"] = (isset($_data["height"])) ? $_data["height"] : 1;
        
        $_data["weight"] = (isset($_data["weight"])) ? $_data["weight"] : 1;
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
        $_data["shipment_required_service_date"] = (isset($_data["shipment_required_service_date"])) ? $_data["shipment_required_service_date"] : "Not Set";
        $_data["customer_id"] = (isset($param["customer_id"])) ? $param["customer_id"] : "0";
        $_data["collection_user_id"] = (isset($param["collection_user_id"])) ? $param["collection_user_id"] : "0";
		$_data["userid"] = (isset($param["userid"])) ? $param["userid"] : "0";
        $_data["shipment_instruction"] = (isset($param["shipment_instruction"])) ? $param["shipment_instruction"] : "";
        
        

        if(isset($_data["address_list"])){
            unset($_data["address_list"]);
        }
        return $_data;
    }
    
    private
    
    function _convertMeterToMiles($param){
        return number_format($param/1609.344, 2);
    }
    
    private
    
    function _convertSecondToMinute($param){
        return ($param/60);
    }

    public
        
    function bookSameDayShipment($data)
    {   
        //check customer is enable or not  shipment_instruction
        $customerStatus = $this->db->getRowRecord("SELECT status FROM ".DB_PREFIX."users WHERE id = '$data->customer_id' AND status=1");
        if($customerStatus){
            if(!isset($data->collection_address))
                {
                return array("status"=>"error", "message"=>"Shipment collection address is mandatory", "data"=>$data);
                }
            if(!isset($data->delivery_address))
                {
                return array("status"=>"error", "message"=>"Shipment delivery address is mandatory", "data"=>$data);
                }
            if(!isset($data->service_detail))
                {
                return array("status"=>"error", "message"=>"Shipment service is mandatory", "data"=>$data);
                }
            
            $shipmentData = array();
            $courier_commission_type = "percentage";
            $service_id = false;
            $shipmentId = 0;
            $timestamp = strtotime("now");
            $transit_time = $data->transit_time;
            $transit_distance = $data->transit_distance;
            $this->company_id = $data->company_id;
            $this->warehouse_id = $data->warehouse_id;
            $this->service_date = $data->service_date;
            $loadIdentity = "";
            $counter = 1;
            $this->db->startTransaction();

            //save collection shipment detail
            foreach($data->collection_address as $shipment_data){

                $shipmentData = $this->_prepareShipmentData(array("collection_user_id"=>$data->collection_user_id,"shipment_data"=>$shipment_data,"timestamp"=>$timestamp,"customer_id"=>$data->customer_id,"availabilityTypeCode"=>"UNKN", "availabilityTypeName"=>"Unknown","file_name"=>"","loadGroupTypeName"=>"Same","loadGroupTypeCode"=>"Same","isDutiable"=>"false","jobTypeName"=>"Collection","jobTypeCode"=>"COLL","shipment_service_type"=>"P","icargo_execution_order"=>$counter,"service_date"=>$this->service_date,"shipment_executionOrder"=>$counter,"warehouse_id"=>$data->warehouse_id,"customer_id"=>$data->customer_id,"userid"=>$data->userid,"notification"=>$shipment_data->notification,"shipment_instruction"=>$shipment_data->special_instruction));

                $shipmentStatus = $this->_bookSameDayShipment(array("shipment_data"=>$shipmentData));

                if($shipmentStatus["status"]=="success"){
                    $shipmentId = $shipmentStatus["shipment_id"];
                    //find load identity
                    $loadIdentity = $this->_getShipmentLoadIdentity($shipmentId);
                    
                    //find version number of shipment price
                    $priceVersionNo = $this->_findPriceNextVersionNo($shipmentId);
                    
                    $shipmentService = $data->service_detail;
                    
                    $shipmentService->price_version = $priceVersionNo;
                    $shipmentService->shipment_id = $shipmentId;
                    $shipmentService->customer_id = $data->customer_id;
                    $shipmentService->courier_commission_type = $courier_commission_type;
                    $shipmentService->transit_distance = $data->transit_distance;
                    $shipmentService->transit_time = $data->transit_time;
                    $shipmentService->transit_time_text = $data->transit_time_text;
                    $shipmentService->transit_distance_text = $data->transit_distance_text;
                    $shipmentService->load_identity = $loadIdentity;

                    unset($shipmentService->message);
                    //save shipment price breakdown
                    $priceBreakdownStatus = $this->_saveShipmentPriceBreakdown(array("shipment_type"=>"Same","service_opted"=>$data->service_detail,"shipment_id"=>$shipmentId,"version"=>$priceVersionNo));
                    //save shipment price detail
                    
                    $service_id = $this->_saveShipmentService($shipmentService);
                    ++$counter;
                }elseif($shipmentStatus["status"]=="error"){
                    return $shipmentStatus;
                }
            }
            
            foreach($data->delivery_address as $shipment_data){
                $shipment_data->parent_id = $shipmentId;
                if($loadIdentity!=""){
                    $shipment_data->loadIdentity = $loadIdentity;
                }

                $shipmentData = $this->_prepareShipmentData(array("collection_user_id"=>$data->collection_user_id,"shipment_data"=>$shipment_data,"timestamp"=>$timestamp,"customer_id"=>$data->customer_id,"availabilityTypeCode"=>"UNKN", "availabilityTypeName"=>"Unknown","file_name"=>"","loadGroupTypeName"=>"Same","loadGroupTypeCode"=>"Same","isDutiable"=>"false","jobTypeName"=>"Delivery","jobTypeCode"=>"DELV","shipment_service_type"=>"D","load_identity"=>$loadIdentity,"icargo_execution_order"=>$counter,"service_date"=>$this->service_date,"shipment_executionOrder"=>$counter,"warehouse_id"=>$data->warehouse_id,"customer_id"=>$data->customer_id,"userid"=>$data->userid,"notification"=>$shipment_data->notification,"shipment_instruction"=>$shipment_data->special_instruction));

                $shipmentStatus = $this->_bookSameDayShipment(array("shipment_data"=>$shipmentData));
              
                if($shipmentStatus["status"]=="success" and !$service_id){
                    //save only service id is false
                    $shipmentId = $shipmentStatus["shipment_id"];
                    //find version number of shipment price
                    $priceVersionNo = $this->_findPriceNextVersionNo($shipmentId);
                    
                    $shipmentService = $data->service_opted;
                    
                    $shipmentService->price_version = $priceVersionNo;
                    $shipmentService->shipment_id = $shipmentId;
                    $shipmentService->customer_id = $data->customer_id;
                    $shipmentService->courier_commission_type = $courier_commission_type;
                    
                    unset($shipmentService->message);
                    //save shipment price detail
                    $service_id = $this->_saveShipmentService($shipmentService);
                }elseif($shipmentStatus["status"]=="error"){
                    return $shipmentStatus;
                }
                $counter++;
            }
            $this->db->commitTransaction();

            //email to customer
            Consignee_Notification::_getInstance()->sendSamedayBookingConfirmationNotification(array("load_identity"=>$loadIdentity,"company_id"=>$this->company_id,"warehouse_id"=>$this->warehouse_id,"customer_id"=>$data->customer_id));

            return array("status"=>"success", "message"=>"Shipment booked successfully. Booking reference no $loadIdentity");
        }else{
            return array("status"=>"error", "message"=>"Customer account disabled.");
        }
    }
} 
?>