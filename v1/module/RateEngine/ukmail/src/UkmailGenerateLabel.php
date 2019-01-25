<?php
namespace v1\module\RateEngine\ukmail\src; 

use v1\module\RateEngine\ukmail\src\Model\UkMailModel;
            
class UkmailGenerateLabel
{

	public static function generateLabel($data,$wsdlBaseUrl){
		$labelArr = array();
		$loadIdentity = $data->loadIdentity;
		$wsdlUrl = $wsdlBaseUrl.'UKMConsignmentServices/UKMConsignmentService.svc?wsdl';
		$soapClient = new \SoapClient($wsdlUrl); 
		
		$request = new \stdClass();
		$request->AuthenticationToken = $data->credentials->authenticationToken;
        $request->Username = $data->credentials->username;
		$request->AccountNumber = $data->credentials->account_number;
		$request->Address = new \stdClass();
		$request->Address->Address1 = $data->from->street1;
		$request->Address->Address2 = $data->from->street2;
		$request->Address->Address3 = "";
		$request->Address->CountryCode = $data->from->country;
		$request->Address->County = $data->from->state;
		$request->Address->PostalTown = $data->from->city;
		$request->Address->Postcode = $data->from->zip;
		$request->CustomersRef = $data->extra->custom_desciption;
		$request->AlternativeRef = $data->extra->custom_desciption2;
        $request->BusinessName = $data->from->company;
		$request->ContactName = $data->from->name;
		$request->CollectionJobNumber = $data->collectionjobnumber;
		
		
		$bookingType = "DOMESTIC";
		switch ($bookingType)
        {
			case 'DOMESTIC':
			    $AddDomesticConsignment = new \stdClass();
				$AddDomesticConsignment->request = self::formatDomesticConsignmentRequest($request,$data);
				$AddDomesticConsignmentResponse = $soapClient->AddDomesticConsignment($AddDomesticConsignment);
				if(isset($AddDomesticConsignmentResponse->AddDomesticConsignmentResult->Errors->UKMWebError)){
					return array("status"=>"error","label_generation_error_code"=>$AddDomesticConsignmentResponse->AddDomesticConsignmentResult->Errors->UKMWebError->Code,"label_generation_error_message"=>$AddDomesticConsignmentResponse->AddDomesticConsignmentResult->Errors->UKMWebError->Description,"message"=>$AddDomesticConsignmentResponse->AddDomesticConsignmentResult->Errors->UKMWebError->Description);
				}else{
					$ConsignmentNumber = $AddDomesticConsignmentResponse->AddDomesticConsignmentResult->ConsignmentNumber;
					$labelArr["label"] = array("tracking_number"=>$ConsignmentNumber,"base_encode"=>$AddDomesticConsignmentResponse->AddDomesticConsignmentResult->Labels->base64Binary,"account_number"=>$data->credentials->account_number,"collectionjobnumber"=>$data->collectionjobnumber,"authentication_token"=>$data->credentials->authenticationToken);
					return self::saveLabelInDirectory($labelArr,$loadIdentity);			
				}
				break;

			case 'INTERNATIONAL':
				$AddInternationalConsignment = self::formatInternatinaolConsignmentRequest($request,$data);
				$AddInternationalConsignment = $soapClient->AddInternationalConsignment($AddInternationalConsignment);
				if(isset($AddInternationalConsignment->AddInternationalConsignment->Errors->UKMWebError)){
					return array("status"=>"error","label_generation_error_code"=>$AddInternationalConsignment->AddInternationalConsignment->Errors->UKMWebError->Code,"label_generation_error_message"=>$AddInternationalConsignment->AddInternationalConsignment->Errors->UKMWebError->Description,"message"=>$AddInternationalConsignment->AddInternationalConsignment->Errors->UKMWebError->Description);
				}else{
					$ConsignmentNumber = $AddInternationalConsignment->AddInternationalConsignment->ConsignmentNumber;
					return self::saveLabelInDirectory($AddInternationalConsignment->AddInternationalConsignment);			
				}
				break;

			case 'PACKET':
				$AddPacketConsignment = self::formatPacketConsignmentRequest($request,$data);
				$AddPacketConsignment = $soapClient->AddPacketConsignment($AddPacketConsignment);
				if(isset($AddPacketConsignment->AddPacketConsignment->Errors->UKMWebError)){
					return array("status"=>"error","label_generation_error_code"=>$AddPacketConsignment->AddPacketConsignment->Errors->UKMWebError->Code,"label_generation_error_message"=>$AddPacketConsignment->AddPacketConsignment->Errors->UKMWebError->Description,"message"=>$AddPacketConsignment->AddPacketConsignment->Errors->UKMWebError->Description);
				}else{
					$ConsignmentNumber = $AddPacketConsignment->AddPacketConsignment->ConsignmentNumber;
					return self::saveLabelInDirectory($AddPacketConsignment->AddPacketConsignment);			
				}
				break;	
		}
		
	}
	
	public static function formatInternatinaolConsignmentRequest($data){     
        
	}
	
	public static function formatPacketConsignmentRequest($data){     
        
	}
	
	public static function formatDomesticConsignmentRequest($param,$data){
		$param->ConfirmationOfDelivery = "false";
		$param->Email = $data->to->email;
		$param->Items = count($data->package);
		$param->ServiceKey = $data->service;
        $param->SpecialInstructions1 = $data->extra->special_instruction;
		$param->SpecialInstructions2 = $data->extra->pickup_instruction;
		$param->Telephone = $data->to->phone;
		$param->Weight = "2"; //total weight of parcels.minimum 1KG
        $param->BookIn = "false";
		$param->CODAmount = "0.00";
		$param->ConfirmationEmail = $data->to->email;
		$param->ConfirmationTelephone = $data->to->phone;
        $param->ExchangeOnDelivery = "false";
		$param->ExtendedCover = "0";//$data->extra->extended_cover_required;
		$param->LongLength = "false"; //If any of the parcels are over 1.4m in length then set this flag to true, otherwise false
		$param->PreDeliveryNotification = "NonRequired";
		$param->SecureLocation1 = "";
		$param->SecureLocation2 = "";
		$param->SignatureOptional = "false";
		return $param;
	}
	
	public static function saveLabelInDirectory($labelArr,$loadIdentity)
	{
		$libObj = new \Library();
		$pdf_base64 = $labelArr['label']['base_encode'];
		$files = array();
		if(is_array($pdf_base64)){
			foreach($pdf_base64 as $key=>$value){
				array_push($files,$labelArr['label']['tracking_number'].$key.'.png');
			}
		}else{
			$files = array($labelArr['label']['tracking_number'].'1.png');
		}

		$label_path = ROOT_DIR.DIRECTORY_SEPARATOR.'label';
		$labelPath = "$label_path/$loadIdentity/ukmail";
		$fileUrl = $libObj->get_api_url();

		if (!file_exists("$label_path/$loadIdentity/ukmail/")) {
			$oldmask = umask(0);
			mkdir("$label_path/$loadIdentity/ukmail/", 0777, true);
			umask($oldmask);
		}

		$images = self::saveImages($pdf_base64,$files,$labelPath,$loadIdentity);
		$labelStatus = self::joinImages($images,$labelPath,$loadIdentity);
		if($labelStatus){
			unset($labelArr['label']['base_encode']);
			return array(
				"status" => "success",
				"message" => "label generated successfully",
				"file_url" => $fileUrl."/label/$loadIdentity/ukmail/$loadIdentity.pdf",
				"tracking_number" => $labelArr['label']['tracking_number'],
				"label_files_png" => implode(',', $images),
				"label_file_pdf" => $fileUrl."/label/$loadIdentity/ukmail/$loadIdentity.pdf",
				"label_json" => json_encode(array("label"=>array("tracking_number"=>$labelArr['label']['tracking_number'],"accountnumber"=>$labelArr['label']['account_number'],"authenticationtoken"=>$labelArr['label']['authentication_token'],"collectionjobnumber"=>$labelArr['label']['collectionjobnumber']))),
			);
		}else{
			return array(
				"status" => "error",
				"message" => "Label generation error, pdf not created successfully"
			);
		}

	}
	
	public static function saveImages($pdf_base64,$files,$labelPath,$loadIdentity){
		$images = array();
        foreach($files as $key=>$file){
            $fileName = uniqid().".png";
            $file = "$labelPath/$fileName";
			try{
            $success = file_put_contents($file, $pdf_base64[$key]);
			header('Content-Type: application/image');
			}catch(Exception $e){
				print_r($e);die;
			}
            array_push($images, $file);
        }
		
		return $images;
    }

    public static function joinImages($images,$labelPath,$loadIdentity){
        $config = array(
            'mode' => 'c',
			'margin_left' => 15,
            'margin_right' => 0,
            'margin_top' => 5,
			'format' => array(101,152),
			'orientation' => 'L'
        );
        $mpdf = new \Mpdf\Mpdf($config);
        foreach($images as $image) {
            $size = getimagesize($image);
            $width = $size[0];
            $height = $size[1];
            $mpdf->Image($image,250,140,$width,$height,'png','',true, true);
        }
        try{
            $mpdf->Output("$labelPath/$loadIdentity.pdf","F");
            return true;
        }catch(Exception $e){
            return false;
        }
  }
	
	public static function staticDomesticLabelRequest($AuthenticationToken,$collectionJobNumber){
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
		$request->CollectionJobNumber = "MK437344313"; /*type Alpha Mandatory - Y */
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
		$request->ConfirmationEmail = "test@gmail.com"; /*type Alpha(40) Mandatory - N */
		$request->ConfirmationTelephone = "4554565788"; /*type Alpha(20) Mandatory - N */
        $request->ExchangeOnDelivery = "false"; /*type Boolean 'true'/'false' Mandatory - Y */
		$request->ExtendedCover = "0"; /*type Number Mandatory - Y */
		$request->LongLength = "false"; /*type Boolean 'true'/'false' Mandatory - Y */
		$request->PreDeliveryNotification = "NonRequired"; /*type Alpha Mandatory - Y  Set to NonRequired for not required, Telephone for SMS or Email for email*/
		$request->SecureLocation1 = "";  /*type Alpha(30) Mandatory - N Details of a secure location/neighbour-Line1. Only pass a Secure Location if using the “Leave Safe” service codes.*/
		$request->SecureLocation2 = "";  /*type Alpha(30) Mandatory - N Details of a secure location/neighbour-Line2. Only pass a Secure Location if using the “Leave Safe” service codes.*/
		$request->SignatureOptional = "false"; /*type Boolean 'true'/'false' Mandatory - Y */
		return $request;
	}
	
	
}