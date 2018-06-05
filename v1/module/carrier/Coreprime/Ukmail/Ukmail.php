<?php
//require_once "/../../CarrierInterface.php";
final class Coreprime_Ukmail extends Carrier /* implements CarrierInterface */{
	
	public $modelObj = null;
    public function __construct(){
        $this->modelObj = new Booking_Model_Booking();
    }

	//get to and from for address(common so will be in common class)
	//get shipdate
	//get credential
	//get package
	//get currency
	//carrier
	//service code
	public function getLabel($shipmentInfo,$loadIdentity){
		//address data
		
		//collectiondate
		
		
	}
	
	public function getShipmentDataFromCarrier($loadIdentity){
		$response = array();
		/* $shipmentInfo = $this->modelObj->getShipmentDataByLoadIdentity($loadIdentity);
		foreach($shipmentInfo as $key=>$data){
			if($data['shipment_service_type']=='P'){
				$response['from'] = array("name"=>$data["shipment_customer_name"],"company"=>$data["shipment_companyName"],"phone"=>$data["shipment_customer_phone"],"street1"=>$data["shipment_address1"],"street2"=>$data["shipment_address2"],"city"=>$data["shipment_customer_city"],"state"=>$data["shipment_county"],"zip"=>$data["shipment_postcode"],"country"=>$data["shipment_country_code"],"country_name"=>$data["shipment_customer_country"],"is_apo_fpo"=>"");
				$response['ship_date'] = $data['shipment_required_service_date'];
				
			}elseif($data['shipment_service_type']=='D'){
				$response['carrier'] = $data['carrier_code'];
				
				$response['to'] = array("name"=>$data["shipment_customer_name"],"company"=>$data["shipment_companyName"],"phone"=>$data["shipment_customer_phone"],"street1"=>$data["shipment_address1"],"street2"=>$data["shipment_address2"],"city"=>$data["shipment_customer_city"],"state"=>$data["shipment_county"],"zip"=>$data["shipment_postcode"],"zip_plus4"=>"","country"=>$data["shipment_country_code"],"country_name"=>$data["shipment_customer_country"],"email"=>$data["shipment_customer_email"],"is_apo_fpo"=>"","is_residential"=>"");
				
				//$response['ship_date'] = $data['shipment_required_service_date'];
			}
		} */
		$response['package'] = $this->getPackageInfo($loadIdentity);
		$serviceInfo = $this->getServiceInfo($loadIdentity);
		$response['currency'] = $serviceInfo['currency'];
		$response['service'] = $serviceInfo['service_code'];
		$response['credentials'] = $this->getCredentialInfo($loadIdentity);
		
		/**********start of static data from requet json ***************/
		$response['extra'] = array("service_key"=>"1","long_length"=>"","bookin"=>"","exchange_on_delivery"=>"","reference_id"=>"","region_code"=>"","confirmation"=>"","is_document"=>"","auto_return"=>"","return_service_id"=>"","special_instruction"=>"","custom_desciption"=>"","custom_desciption2"=>"","custom_desciption3"=>"","customs_form_declared_value"=>"","document_only"=>"","no_dangerous_goods"=>"","in_free_circulation_eu"=>"","extended_cover_required"=>"","invoice_type"=>"");
		$response['insurance'] = array("value"=>"","currency"=>"","insurer"=>"");
		$response['constants'] = array("shipping_charge"=>"","weight_charge"=>"","fuel_surcharge"=>"","remote_area_delivery"=>"","insurance_charge"=>"","over_sized_charge"=>"","over_weight_charge"=>"","discounted_rate"=>"");
		$response['label_options'] = "";
		$response['customs'] = "";
		$response['billing_account'] = array("payor_type"=>"","billing_account"=>"","billing_country_code"=>"","billing_person_name"=>"","billing_email"=>"");
		$response['label'] = array();
		$response['method_type'] = "post";
		/**********end of static data from requet json ***************/
		
		return $response;
		
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
	
	private function validate($data){
		$error = array();
		//call validation function from validation class
		if(!Ukmail_Validation::_getInstance()->firstName('first_name')){
			$error['first_name'] = Ukmail_Validation::_getInstance()->errorMsg;
		}
		if(!Ukmail_Validation::_getInstance()->lastName('last_name')){
			$error['last_name'] = Ukmail_Validation::_getInstance()->errorMsg;
		}
	}
	
	}
?>