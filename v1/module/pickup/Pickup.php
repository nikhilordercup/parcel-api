<?php
require_once "CarrierPickupRequest.php";
class Pickup extends Icargo
{
    public function __construct($param = array())
    {
        parent::__construct(array("email"=>$param->email, "access_token"=>$param->access_token));
        $this->db = new DbHandler();
        $this->postcodeObj = new Postcode();
        if (isset($param->company_id))
        {
            $this->company_id = $param->company_id;
        }

        if (isset($param->customer_id))
        {
            $this->customer_id = $param->customer_id;
        }

        if (isset($param->user_id))
        {
            $this->user_id = $param->user_id;
        }

        if (isset($param->carrier_id))
        {
            $this->carrier_id = $param->carrier_id;
        }
    }
    
    public function getAllPickups ($reqData) 
    {        
        $sql = "SELECT IP.*, IC.name carrier_name FROM ".DB_PREFIX."pickups IP";        
        $sql .= " LEFT JOIN ".DB_PREFIX."courier IC ON IP.carrier_id = IC.id ";
        $sql .= " WHERE IP.company_id=".$reqData->company_id;
                     
        $record = $this->db->getAllRecords($sql);        
        return $record;
    }

    public function savePickupForCustomer($data)
    {        
        $quoteSave = $this->_savePickup($data);
        return $quoteSave;
    }   
    

    private function _savePickup($data)
    {
        $this->company_id = $data->company_id;              
        $date = date('Y-m-d H:i:s');                
        //print_r($data); die;
        
        /********Search string used for shipments (DHL, FEDEX etc) ***********/
        $sStr["postcode"]      = $data->pickup_postcode;
        $sStr["address_line1"] = $data->pickup_address_line1;
        $sStr["iso_code"]      = $data->pickup_country->alpha3_code;           
        $searchString          = str_replace(' ','',implode('',$sStr));             
        
        /********Search string used for shipments (DHL, FEDEX etc) ***********/        
        $pickupData = array(
            'carrier_id'            => $data->carrier_id,
            'company_id'            => $data->company_id,
            'customer_id'           => $data->customer_id,
            'user_id'               => $data->customer_user_id,
            'company_name'          => isset($data->company_name) ? $data->company_name : '',
            'name'                  => isset($data->contact_name) ? $data->contact_name : '',
            'phone'                 => isset($data->pickup_phone) ? $data->pickup_phone : '',
            'email'                 => isset($data->pickup_email) ? $data->pickup_email : '',
            'address_line1'         => $data->pickup_address_line1,
            'address_line2'         => $data->pickup_address_line2,
            'city'                  => $data->pickup_city,
            'state'                 => $data->pickup_state,
            'country'               => $data->pickup_country->alpha3_code,
            'postal_code'           => $data->pickup_postcode,
            'address_type'          => ($data->address_type == 'Business') ? 'B' : 'R',
            'package_quantity'      => $data->package_quantity,
            'package_type'          => isset($data->package_type) ? $data->package_type : 'Package' ,
            'is_overweight'         => isset($data->is_overweight) ? $data->is_overweight : 'Y' ,
            'package_location'      => $data->package_location,
            'pickup_date'           => $data->pickup_date->value,
            'earliest_pickup_time'  => $data->readyTime,
            'latest_pickup_time'    => $data->closeTime,
            'pickup_reference'      => isset($data->pickup_reference) ? $data->pickup_reference : '',
            'instruction_todriver'  => $data->special_instruction,
            'search_string'         => $searchString,
            'loginemail'         => $data->email,
            'access_token'         => $data->access_token
        );
                          
        $pickupConfDetail = $this->_getConfirmationNumber($pickupData, $data->pickup_country->alpha2_code); 
        unset($pickupData['loginemail']);
        unset($pickupData['access_token']);
        
        if ( isset( $pickupConfDetail->pickup->confirmation_number) )
        {
            $respDetail = $pickupConfDetail->pickup;
            $pickupData['confirmation_number'] =  isset( $respDetail->confirmation_number ) ? $respDetail->confirmation_number : '';
            $pickupData['currency_code'] =  isset( $respDetail->currency_code ) ? $respDetail->currency_code : '';
            $pickupData['charge'] =  isset( $respDetail->charge ) ? $respDetail->charge : '';
            $pickupData['origin_service_area'] =  isset( $respDetail->origin_service_area ) ? $respDetail->origin_service_area : '';
            $pickupData['ready_time'] =  isset( $respDetail->ready_time ) ? $respDetail->ready_time : '';
            $pickupData['second_time'] =  isset( $respDetail->second_time ) ? $respDetail->second_time : '';
            $pickupData['status'] =  isset($data->status) ? $data->status : 1;  //As confirmed
            $pickupData['created'] =  $date;
            $pickupData['updated'] =  $date;
            
            $this->db->startTransaction();            
            
            $pickupId = $this->db->save("pickups", $pickupData);   
            
            $this->db->commitTransaction();
            
            $response = array(
                "status" => "success",
                "confirmation_number" => $pickupData["confirmation_number"],
                "message" => "Pickup saved successfully. Confirmation Number -" . $pickupData["confirmation_number"]
            );
        }
        else
        { 
            $response = array(
                "status" => "error",
                "confirmation_number" => "",
                "message" => $pickupConfDetail->error
            ); 
        }    

        return $response;
    }
    
    private function _getConfirmationNumber($pickupData, $countryCode) 
    {        
        $pickupRequest['credentials'] = $this->getCredentialInfo( $pickupData['carrier_id'], $pickupData['company_id'] );
        
        $pickupRequest['carrier'] = 'dhl';
        $pickupRequest['services'] = "";
        $pickupRequest['address'] = array(
            "location_type" => ($pickupData['address_type'] == 'Business') ? 'B' : 'R',
            "package_location" => $pickupData['package_location'],
            "company" => $pickupData['company_name'],
            "street1" => $pickupData['address_line1'],
            "street2" => $pickupData['address_line2'],
            "city" => $pickupData['city'],
            "state" => $pickupData['state'],
            "country" => $countryCode,
            "zip" => $pickupData['postal_code'],
        );        
        
        $type_codes = ( strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', strtotime($pickupData['pickup_date']))) == 0 ) ? 'S' : 'A';
        
        $pickupRequest['pickup_details'] = array (
            "pickup_date" => date('Y-m-d', strtotime($pickupData['pickup_date'])),
            "ready_time" => $pickupData['earliest_pickup_time'],
            "close_time" => $pickupData['latest_pickup_time'],
            "number_of_pieces" => $pickupData['package_quantity'],
            "instructions" => $pickupData['instruction_todriver'],
			"type_codes" => $type_codes
        );
        $pickupRequest['pickup_contact'] = array (
            "name" => $pickupData['name'],
            "phone" => $pickupData['phone'],
            "email" => $pickupData['email']
        );
        $pickupRequest['confirmation_number'] = '';
        $pickupRequest['method_type'] = 'post';
        $pickupRequest['pickup'] = '';  
                               
        $carrierCode = 'DHL';                
        $bkgModel = new \Booking_Model_Booking();
        $providerInfo = $bkgModel->getProviderInfo('PICKUP',ENV,'PROVIDER',$carrierCode);             
        if($providerInfo['provider'] == 'Core')
        {  
            $formatedReq = new \stdClass();
            $dataObj = new \stdClass();
            $dataObj->email = $pickupData['loginemail'];
            $dataObj->access_token = $pickupData['access_token']; 
            
            if($carrierCode == 'DHL')
            {
                $dhlApiObj = new \v1\module\RateEngine\core\dhl\DhlApi();
                $formatedReq = $dhlApiObj->formatPickupData($pickupRequest);
                $formatedReq->callType = 'createpickup';
                $formatedReq->pickupEndPoint = $providerInfo['rate_endpoint'];
                $formatedReq->carrier = $carrierCode;
            }
            
            $obj = new \Module_Coreprime_Api($dataObj);
            $rawResponse = $obj->_postRequest($formatedReq);            
            $confirmationDetail = $dhlApiObj->formatPickupResponseData($rawResponse);            
        }
        else
        {         
            $obj = new CarrierPickupRequest();        
            $confirmationDetail = $obj->_postRequest( 'pickup', json_encode($pickupRequest) );
        }           
        return $confirmationDetail;
       
    }
    
    private function getCredentialInfo($carrierId, $companyId) {
       
        $credentialData = array();
        //$credentialInfo = $this->modelObj->getCredentialDataByLoadIdentity($carrierId);

        $credentialInfo["username"] = "kuberusinfos";
        $credentialInfo["password"] = "GgfrBytVDz";
        $credentialInfo["third_party_account_number"] = "";
        $credentialInfo["account_number"] = "420714888";
        $credentialInfo["token"] = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoyLCJlbWFpbCI6InNtYXJnZXNoQGdtYWlsLmNvbSIsImlzcyI6Ik9yZGVyQ3VwIG9yIGh0dHBzOi8vd3d3Lm9yZGVyY3VwLmNvbS8iLCJpYXQiOjE1MDI4MjQ3NTJ9.qGTEGgThFE4GTWC_jR3DIj9NpgY9JdBBL07Hd-6Cy-0";

        /* $credentialInfo["account_number"] = $carrierAccountNumber;
          $credentialInfo["master_carrier_account_number"] = "";
          $credentialInfo["latest_time"] = "";
          $credentialInfo["earliest_time"] = "";
          $credentialInfo["carrier_account_type"] = array("1"); */
        return $credentialInfo;
    }
    
    public function saveUpdateAddress($data) {
        $this->modelObj = new Booking_Model_Booking();
        $postcode = ($data->country->alpha3_code == 'GBR') ? ( $this->postcodeObj->validate($data->postcode) ) : true;
        if($postcode) {
            $customer_id            = $data->customer_id;
            $address_id             = $data->address_id;
            $param["postcode"]      = $data->postcode;
            $param["address_line1"] = (isset($data->address_line1)) ? $data->address_line1 : "";
            $param["address_line2"] = (isset($data->address_line2)) ? $data->address_line2 : "";
            $param["city"]          = (isset($data->city)) ? $data->city : "";
            $param["state"]         = (isset($data->state)) ? $data->state : "";
            $param["country"]       = $data->country->short_name;
            $param["iso_code"]      = $data->country->alpha3_code;               

            $param["search_string"] = str_replace(' ','',implode('',$param));

            $param["company_name"]  = $data->company_name;        
            $param["first_name"]        = $data->name;
            $param["contact_email"]      = $data->pickup_email;
            $param["contact_no"]         = $data->phone;
            $param["country_id"]    = $data->country->id;
            $param["customer_id"]   = $customer_id;                        
            $param["billing_address"] = "N";            
            
            $addressVersion = $this->modelObj->getAddressBySearchStringAndCustomerId($customer_id, $param["search_string"]);
            if(!$addressVersion) {
                $param["version_id"] = "version_1";
            }
            else{
                $version = explode("_", $addressVersion['version_id']);
                $param["version_id"] = "version_".($version[1]+1);
            }
			$param["address_type"] = $data->address_type;
            if($address_id) {                
                $address_id = $this->db->update("address_book", $param, "id='$address_id'");    
            } else {                
                $address_id = $this->db->save("address_book", $param);
                $userAdd = array('user_id' => $data->customer_user_id, 'address_id' => $address_id, 'default_address' => 'N', 'pickup_address' => 1, 'warehouse_address' => 'Y', 'billing_address' => 0);
                $useraddress_id = $this->db->save("user_address", $userAdd);
            }
            return array("status"=>"success", "address_id"=>$address_id,"address_data"=>$param);
        }else{
            return array("status"=>"error", "message"=>"Invalid postcode");
        }
    }
    
    public function getPickupDetail($searchData) {
        $sql = "SELECT IP.*, IC.name carrier_name, GROUP_CONCAT(ISH.shipment_ticket) AS shipments FROM ".DB_PREFIX."pickups IP";        
        $sql .= " LEFT JOIN ".DB_PREFIX."courier IC ON IP.carrier_id = IC.id ";
        $sql .= " LEFT JOIN ".DB_PREFIX."shipment ISH ON IP.id = ISH.pickup_id ";
        $sql .= " WHERE IP.id=".$searchData->id;
        $sql .= " GROUP BY IP.id";
                     
        $record = $this->db->getRowRecord($sql);        
        return $record;
    }
	
	public function getPickupData($param) {
		$carrier_code = $param->carrier->code;
        $sql = "SELECT confirmation_number as collectionjobnumber,pickup_date,package_location,earliest_pickup_time,latest_pickup_time,account_number FROM ".DB_PREFIX."pickups IP WHERE IP.carrier_code='$carrier_code' AND IP.account_number='$param->account_number' AND IP.pickup_date ='$param->pickup_date' AND IP.company_id = $param->company_id order by id desc limit 0,1"; //echo $sql;die;
        $record = $this->db->getRowRecord($sql); 
    	return array("status"=>"success", "data"=>$record);
    }
}
?>
