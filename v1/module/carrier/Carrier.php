<?php
class Carrier{
    protected static $_environment = NULL;
    private $_postParam = array();
	public $modelObj = null;
	
	
	public function __construct(){
        $this->modelObj = new Booking_Model_Booking();
    }
	public function getShipmentInfo($loadIdentity){
		$carrierObj = null;
		$response = array();
		$shipmentInfo = $this->modelObj->getShipmentDataByLoadIdentity($loadIdentity);
		foreach($shipmentInfo as $key=>$data){
			if($data['shipment_service_type']=='P'){
				$response['from'] = array("name"=>$data["shipment_customer_name"],"company"=>$data["shipment_companyName"],"phone"=>$data["shipment_customer_phone"],"street1"=>$data["shipment_address1"],"street2"=>$data["shipment_address2"],"city"=>$data["shipment_customer_city"],"state"=>$data["shipment_county"],"zip"=>$data["shipment_postcode"],"country"=>$data["shipment_country_code"],"country_name"=>$data["shipment_customer_country"],"is_apo_fpo"=>"");
				$response['ship_date'] = $data['shipment_required_service_date'];
				
			}elseif($data['shipment_service_type']=='D'){
				$response['to'] = array("name"=>$data["shipment_customer_name"],"company"=>$data["shipment_companyName"],"phone"=>$data["shipment_customer_phone"],"street1"=>$data["shipment_address1"],"street2"=>$data["shipment_address2"],"city"=>$data["shipment_customer_city"],"state"=>$data["shipment_county"],"zip"=>$data["shipment_postcode"],"zip_plus4"=>"","country"=>$data["shipment_country_code"],"country_name"=>$data["shipment_customer_country"],"email"=>$data["shipment_customer_email"],"is_apo_fpo"=>"","is_residential"=>"");
				
				$response['carrier'] = $data['carrier_code'];
				//$response['ship_date'] = $data['shipment_required_service_date'];
			}
		}
		$coreprimeCarrierClass = 'Coreprime_'.ucfirst(strtolower($response['carrier']));
		$carrierObj = new $coreprimeCarrierClass();
		$shipmentInfo = $carrierObj->getShipmentDataFromCarrier($loadIdentity);
		$finalRequestArr = json_encode(array_merge($response,$shipmentInfo));
		print_r($finalRequestArr);die;
		/* $response['package'] = $this->getPackageInfo($loadIdentity);
		$serviceInfo = $this->getServiceInfo($loadIdentity);
		$response['currency'] = $serviceInfo['currency'];
		$response['service'] = $serviceInfo['service_code'];
		$response['credentials'] = $this->getCredentialInfo($loadIdentity); */
		
		/**********start of static data from requet json ***************/
		/* $response['extra'] = array("service_key"=>"1","long_length"=>"","bookin"=>"","exchange_on_delivery"=>"","reference_id"=>"","region_code"=>"","confirmation"=>"","is_document"=>"","auto_return"=>"","return_service_id"=>"","special_instruction"=>"","custom_desciption"=>"","custom_desciption2"=>"","custom_desciption3"=>"","customs_form_declared_value"=>"","document_only"=>"","no_dangerous_goods"=>"","in_free_circulation_eu"=>"","extended_cover_required"=>"","invoice_type"=>"");
		$response['insurance'] = array("value"=>"","currency"=>"","insurer"=>"");
		$response['constants'] = array("shipping_charge"=>"","weight_charge"=>"","fuel_surcharge"=>"","remote_area_delivery"=>"","insurance_charge"=>"","over_sized_charge"=>"","over_weight_charge"=>"","discounted_rate"=>"");
		$response['label_options'] = "";
		$response['customs'] = "";
		$response['billing_account'] = array("payor_type"=>"","billing_account"=>"","billing_country_code"=>"","billing_person_name"=>"","billing_email"=>"");
		$response['label'] = array();
		$response['method_type'] = "post"; */
		/**********end of static data from requet json ***************/
		
		//print_r(json_encode($response));die;
		
	}
	
	public function getPackageInfo($loadIdentity){
		$packageData = array();
		$packageInfo = $this->modelObj->getPackageDataByLoadIdentity($loadIdentity);
		foreach($packageInfo as $data){
			$packageData = array("packaging_type"=>$data["package"],"width"=>$data["parcel_width"],"length"=>$data["parcel_length"],"height"=>$data["parcel_height"],"dimension_unit"=>"CM","weight"=>$data["parcel_weight"],"weight_unit"=>"KG");
		}
		return $packageData;
	}
	
	public function getServiceInfo($loadIdentity){
		$serviceInfo = $this->modelObj->getServiceDataByLoadIdentity($loadIdentity);
		return $serviceInfo;
	}
	
	public function getCredentialInfo($loadIdentity){
		$credentialData = array();
		$credentialInfo = $this->modelObj->getCredentialDataByLoadIdentity($loadIdentity);
		$credentialInfo["master_carrier_account_number"] = "";
		$credentialInfo["latest_time"] = "";
		$credentialInfo["earliest_time"] = "";
		$credentialInfo["carrier_account_type"] = array();
		return $credentialInfo;
	}


    private function _getEnvironment(){
        if(self::$_environment===NULL){
            self::$_environment = new Environment();
        }
        return self::$_environment;
    }

    private function _getApiUrl(){
        return $this->_getEnvironment()->getApiUrl();
    }

    private function _getCoreprimeCredentials(){
        $credentials = array_filter($this->_getEnvironment()->getCoreprimeCredentials(), function($val){
            $val = trim($val);
            return $val != '';
        });

        if(count($credentials)>0){
            $this->_setCoreprimeCredentials($credentials);
            return true;
        }else{
            return false;
        }
    }

    private function _setCoreprimeCredentials($credential){
        $credential = (object)$credential;
        $this->_postParam->credentials = new stdClass();

        foreach($credential as $key=>$item){
            $this->_postParam->credentials->$key = $item;
        }
    }

    private function _setCurrency(){
        $this->_postParam->currency = "GBP";
    }

    private function _setMethodType(){
        $this->_postParam->method_type = "post";
    }

    protected function _send($data){
        $this->_postParam = $data;
        if($this->_getCoreprimeCredentials()){
            $this->_setCurrency();
            $this->_setMethodType();

            $data_string = json_encode($this->_postParam);
//echo $data_string;die;
            $ch = curl_init($this->_getEnvironment()->getApiUrl());
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data_string))
            );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $server_output = json_decode(curl_exec ($ch));
            curl_close ($ch);
            //return $this->_calculateCcf($server_output->rate, $customer_id, $company_id);
            return $server_output->rate;
        }else{
            return array("status"=>"error","message"=>"Credential not found");
        }

    }
}
?>