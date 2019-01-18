<?php
namespace v1\module\RateEngine\ukmail\src; 

use v1\module\RateEngine\ukmail\src\Singleton\Singleton;
use v1\module\RateEngine\ukmail\src\Model\UkMailModel;
            
class UkmailGenerateLabel
{
	private $_bookingType;
	private $_wsdlUrl;
    
    /**
     * FormConfiguration constructor.
     */
    private function __construct() {
		$this->_bookingType = "DOMESTIC";//"INTERNATIONAL","PACKET";
		if(ENV == 'dev')
			$this->_wsdlUrl = 'https://qa-api.ukmail.com/Services/UKMConsignmentServices/UKMConsignmentService.svc?wsdl';
		else
			$this->_wsdlUrl = 'https://api.ukmail.com/Services/UKMConsignmentServices/UKMConsignmentService.svc?wsdl';
    }
	
	
	public static function generateLabel($data){
		//$commonRequestParams = self::commonParams($data);
		//$data = json_decode($data->request->getBody());
		$bookingType = "DOMESTIC";
		switch ($bookingType)
        {
		case 'DOMESTIC':
			//verifyRequiredParams(array('contact_name','business_name','email','parcel_quantity','service_code','insurance_amount'),$app);
			return self::addDomesticConsignment($data);
			break;

		case 'INTERNATIONAL':
			//verifyRequiredParams(array('contact_name','business_name','email','parcel_quantity','service_code','insurance_amount'),$app);
			self::addInternationalConsignment($data);
			break;

		case 'PACKET':
			self::addPacketConsignment($data);
			break;	
		}
		
	}
	
	
	public static function staticLabelRequest($AuthenticationToken){
		$request = new \stdClass();
		$request->AuthenticationToken = $AuthenticationToken; /*type Alpha Mandatory - Y */
        $request->Username = "nikhil.kumar@ordercup.com"; /*type Alpha Mandatory - Y */
		$request->AccountNumber = "K906430"; /*type Alpha(10) Mandatory - Y */
        $request->Address = new \stdClass();
		$request->Address->Address1 = "Church gate"; /*type Alpha(40) Mandatory - Y */
		$request->Address->Address2 = "West side"; /*type Alpha(40) Mandatory - N */
		$request->Address->Address3 = ""; /*type Alpha(40) Mandatory - N */
		$request->Address->CountryCode = "GBR"; /*type Alpha(3) Mandatory - Y */
		$request->Address->County = ""; /*type Alpha(40) Mandatory - N */
		$request->Address->PostalTown = ""; /*type Alpha(40) Mandatory - Y */
		$request->Address->Postcode = "OX4 2PG"; /*type Alpha(9) Mandatory - Y */
		$request->AlternativeRef = "alt_ref"; /*type Alpha(20) Mandatory - N */
        $request->BusinessName = "PCS"; /*type Alpha(40) Mandatory - N */
		$request->CollectionJobNumber = "MK437336124"; /*type Alpha Mandatory - Y */
		$request->ConfirmationOfDelivery = "false"; /*type Alpha 'true'/'false' Mandatory - Y */
		$request->ContactName = "Kavita"; /*type Alpha(20) Mandatory - N */
        $request->CustomersRef = "CustomersRef"; /*type Alpha(20) Mandatory - N */
		$request->Email = ""; /*type Alpha(40) Mandatory - N */
		$request->Items = "2"; /*type Number Mandatory - Y */
		$request->ServiceKey = "1"; /*type Number Mandatory - Y */
        $request->SpecialInstructions1 = ""; /*type Alpha(30) Mandatory - N */
		$request->SpecialInstructions2 = ""; /*type Alpha(30) Mandatory - N */
		$request->Telephone = "34245525266"; /*type Alpha(20) Mandatory - N */
		$request->Weight = "2"; /*type Number Mandatory - Y */
        $request->BookIn = "false"; /*type Alpha 'true'/'false' Mandatory - Y */
		$request->CODAmount = "0.00"; /*type Decimal Mandatory - N */
		$request->ConfirmationEmail = "test@gmail.com"; /*type string(40) Mandatory - N */
		$request->ConfirmationTelephone = "4554565788"; /*type string(20) Mandatory - N */
        $request->ExchangeOnDelivery = "false"; /*type Boolean 'true'/'false' Mandatory - Y */
		$request->ExtendedCover = "0"; /*type Number Mandatory - Y */
		$request->LongLength = "false"; /*type Boolean 'true'/'false' Mandatory - Y */
		$request->PreDeliveryNotification = "NonRequired"; /*type String Mandatory - Y  Set to NonRequired for not required, Telephone for SMS or Email for email*/
		$request->SecureLocation1 = "";  /*type string(30) Mandatory - N Details of a secure location/neighbour-Line1. Only pass a Secure Location if using the “Leave Safe” service codes.*/
		$request->SecureLocation2 = "";  /*type string(30) Mandatory - N Details of a secure location/neighbour-Line2. Only pass a Secure Location if using the “Leave Safe” service codes.*/
		$request->SignatureOptional = "false"; /*type Boolean 'true'/'false' Mandatory - Y */
		return $request;
	}
	
	public static function addDomesticConsignment($data){     
        /* $request = new stdClass();
        $request->AuthenticationToken = $data->AuthenticationToken;
        $request->Username = $data->username;
		$request->AccountNumber = $data->account_number;
        $request->Address = new stdClass();
		$request->Address->Address1 = $data->address1;
		$request->Address->Address2 = $data->address2;
		$request->Address->Address3 = $data->address3;
		$request->Address->CountryCode = $data->country->alpha3_code;
		$request->Address->County = $data->state;
		$request->Address->PostalTown = $data->city;
		$request->Address->Postcode = $data->postcode;
		$request->AlternativeRef = $data->alt_ref;
        $request->BusinessName = $data->business_name;
		$request->CollectionJobNumber = $data->CollectionJobNumber;
		$request->ConfirmationOfDelivery = $data->special_instructions;
		$request->ContactName = $data->earliest_time;
        $request->CustomersRef = $data->latest_time;
		$request->Email = $data->requested_collection_date;
		$request->Items = $data->special_instructions;
		$request->ServiceKey = $data->earliest_time;
        $request->SpecialInstructions1 = $data->latest_time;
		$request->SpecialInstructions2 = $data->requested_collection_date;
		$request->Telephone = $data->special_instructions;
		$request->Weight = $data->earliest_time;
        $request->BookIn = $data->latest_time;
		$request->CODAmount = $data->requested_collection_date;
		$request->ConfirmationEmail = $data->special_instructions;
		$request->ConfirmationTelephone = $data->earliest_time;
        $request->ExchangeOnDelivery = $data->latest_time;
		$request->ExtendedCover = $data->requested_collection_date;
		$request->LongLength = $data->special_instructions;
		$request->PreDeliveryNotification = $data->special_instructions;
		$request->SecureLocation1 = $data->special_instructions;
		$request->SecureLocation2 = $data->special_instructions;
		$request->SignatureOptional = $data->special_instructions; */
		$request = self::staticLabelRequest($data->AuthenticationToken);
		
        $AddDomesticConsignment = new \stdClass();
        $AddDomesticConsignment->request = $request;
        $wsdlUrl = 'https://qa-api.ukmail.com/Services/UKMConsignmentServices/UKMConsignmentService.svc?wsdl';
        $soapClient = new \SoapClient($wsdlUrl);
        $AddDomesticConsignmentResponse = $soapClient->AddDomesticConsignment($AddDomesticConsignment); 
		if(isset($AddDomesticConsignmentResponse->AddDomesticConsignmentResult->Errors->UKMWebError)){
			return array("status"=>"error","label_generation_error_code"=>$AddDomesticConsignmentResponse->AddDomesticConsignmentResult->Errors->UKMWebError->Code,"label_generation_error_message"=>$AddDomesticConsignmentResponse->AddDomesticConsignmentResult->Errors->UKMWebError->Description,"message"=>$AddDomesticConsignmentResponse->AddDomesticConsignmentResult->Errors->UKMWebError->Description);
		}else{
			$ConsignmentNumber = $AddDomesticConsignmentResponse->AddDomesticConsignmentResult->ConsignmentNumber; 
			return array("status"=>"success","message"=>"Domestic consignment booked successfully,consignment number is - $ConsignmentNumber","label_tracking_number"=>$ConsignmentNumber,"label_base_64"=>$AddDomesticConsignmentResponse->AddDomesticConsignmentResult->Labels->base64Binary[0]);
		}
	}
	
	public static function addInternationalConsignment($data){     
        
	}
	
	public static function addPacketConsignment($data){     
        
	}
	
}