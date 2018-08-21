<?php
//require_once ("../Carrier_Coreprime_Request.php");
require_once dirname(dirname(__FILE__))."/Carrier_Coreprime_Request.php";

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
	/* public function getLabel($shipmentInfo,$loadIdentity){
		//address data
		
		//collectiondate
		
		
	} */

	private function _getLabel($loadIdentity,$json_data){//print_r($json_data);die;
		$images = array();
		$label_images = array();
        $obj = new Carrier_Coreprime_Request();
        $label = $obj->_postRequest("label",$json_data);
		$labelArr = json_decode($label);
		if(isset($labelArr->label)){
			$pdf_base64 = $labelArr->label->base_encode;
			$pdf_base64 = explode(',',$pdf_base64);
			//print_r($pdf_base64);die;
			$labels = explode(",",$labelArr->label->file_url);

			$label_path = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/label/';

			if(!file_exists($label_path.$loadIdentity.'/ukmail/')){
				$oldmask = umask(0);
				mkdir($label_path.$loadIdentity.'/ukmail/', 0777, true);
				umask($oldmask); 
			}  

				
			foreach($pdf_base64 as $base64str){
				$dataFile = uniqid().'.png';
				$file_name = $label_path.$loadIdentity.'/ukmail/'.$dataFile;
				$labelImages = array_push($label_images,$dataFile);
				$data = base64_decode($base64str);
				file_put_contents($file_name,$data);
				header('Content-Type: application/image');
				array_push($images,$file_name);
			}

			$img = new \Imagick($images);
			$img->setImageFormat('pdf');
			$pdf = $img->writeImages($label_path.$loadIdentity.'/ukmail/'.$loadIdentity.'.pdf',true);
			unset($labelArr->label->base_encode);
			return array("status"=>"success","message"=>"label generated successfully","file_path"=>"http://api.instadispatch.com/live/label/".$loadIdentity.'/ukmail/'.$loadIdentity.'.pdf',"label_tracking_number"=>$labelArr->label->tracking_number,"label_files_png"=>implode(',',$label_images),"label_file_pdf"=>"http://api.instadispatch.com/live/label/".$loadIdentity.'/ukmail/'.$loadIdentity.'.pdf',"label_json"=>json_encode($labelArr));
			
		}else{
			return array("status"=>"error","message"=>$labelArr->error);
		}
		
    }
	
	public function getShipmentDataFromCarrier($loadIdentity){
		$response = array();
		$shipmentInfo = $this->modelObj->getShipmentDataByLoadIdentity($loadIdentity);

		foreach($shipmentInfo as $key=>$data){
			if($data['shipment_service_type']=='P'){
				$response['from'] = array("name"=>$data["shipment_customer_name"],"company"=>$data["shipment_companyName"],"phone"=>$data["shipment_customer_phone"],"street1"=>$data["shipment_address1"],"street2"=>$data["shipment_address2"],"city"=>$data["shipment_customer_city"],"state"=>$data["shipment_county"],"zip"=>$data["shipment_postcode"],"country"=>$data["shipment_country_code"],"country_name"=>$data["shipment_customer_country"],"is_apo_fpo"=>"");
				$response['ship_date'] = $data['shipment_required_service_date'];
				
			}elseif($data['shipment_service_type']=='D'){
				$response['carrier'] = $data['carrier_code'];
				
				$response['to'] = array("name"=>$data["shipment_customer_name"],"company"=>$data["shipment_companyName"],"phone"=>$data["shipment_customer_phone"],"street1"=>$data["shipment_address1"],"street2"=>$data["shipment_address2"],"city"=>$data["shipment_customer_city"],"state"=>$data["shipment_county"],"zip"=>$data["shipment_postcode"],"zip_plus4"=>"","country"=>$data["shipment_country_code"],"country_name"=>$data["shipment_customer_country"],"email"=>$data["shipment_customer_email"],"is_apo_fpo"=>"","is_residential"=>"");

				$carrierAccountNumber = $data["carrier_account_number"];
				//$response['ship_date'] = $data['shipment_required_service_date'];
			}
		}
		$response['package'] = $this->getPackageInfo($loadIdentity);
		$serviceInfo = $this->getServiceInfo($loadIdentity);
		$response['currency'] = $serviceInfo['currency'];
		$response['service'] = $serviceInfo['service_code'];
		$response['credentials'] = $this->getCredentialInfo($carrierAccountNumber, $loadIdentity);
		
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
		return $this->_getLabel($loadIdentity,json_encode($response));
		//return $response;
		
	}
	
	public function getPackageInfo($loadIdentity){
		$packageData = array();
		$packageInfo = $this->modelObj->getPackageDataByLoadIdentity($loadIdentity);
		foreach($packageInfo as $data){
			array_push($packageData, array("packaging_type"=>$data["package"],"width"=>$data["parcel_width"],"length"=>$data["parcel_length"],"height"=>$data["parcel_height"],"dimension_unit"=>"CM","weight"=>$data["parcel_weight"],"weight_unit"=>"KG"));
		}
		return $packageData;
	}
	
	public function getServiceInfo($loadIdentity){
		$serviceInfo = $this->modelObj->getServiceDataByLoadIdentity($loadIdentity);
		return $serviceInfo;
	}
	
	public function getCredentialInfo($carrierAccountNumber, $loadIdentity){
		$credentialData = array();
		$credentialData = $this->modelObj->getCredentialDataByLoadIdentity($carrierAccountNumber, $loadIdentity);
		
		$credentialInfo["username"] = $credentialData["username"];
		$credentialInfo["password"] = $credentialData["password"];
		$credentialInfo["authentication_token"] = $credentialData["authentication_token"];
		$credentialInfo["authentication_token_created_at"] = $credentialData["authentication_token_created_at"];
		$credentialInfo["token"] = $credentialData["token"];
		$credentialInfo["account_number"] = $carrierAccountNumber; 
		$credentialInfo["master_carrier_account_number"] = "";
        $credentialInfo["latest_time"] = "17:00:00";
		$credentialInfo["earliest_time"]="14:00:00";
		$credentialInfo["carrier_account_type"] = array("1");
		
		/* $credentialInfo["username"] = "info@pedalandpost.co.uk";
		$credentialInfo["password"] = "x65we30pg";//"casi0advent";
		$credentialInfo["authentication_token"] = "";
		$credentialInfo["authentication_token_created_at"] = "";
		$credentialInfo["token"] ="";
		$credentialInfo["account_number"] = "D052411";//"K906430"; 
		$credentialInfo["master_carrier_account_number"] = "";
        $credentialInfo["latest_time"] = "17:00:00";
		$credentialInfo["earliest_time"]="14:00:00";
		$credentialInfo["carrier_account_type"] = array("1"); */

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