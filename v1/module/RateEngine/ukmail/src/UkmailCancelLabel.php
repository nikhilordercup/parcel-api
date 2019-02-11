<?php
namespace v1\module\RateEngine\ukmail\src; 

use v1\module\RateEngine\ukmail\src\Model\UkMailModel;
            
class UkmailCancelLabel
{

    public static function voidCall($request,$wsdlBaseUrl){
		//AuthenticationToken,ConsignmentNumber,Username
		$wsdlUrl = $wsdlBaseUrl.'UKMConsignmentServices/UKMConsignmentService.svc?wsdl';
        $CancelConsignment = new \stdClass();
        $CancelConsignment->request = $request;
        $soapClient = new \SoapClient($wsdlUrl);
        $CancelConsignmentResponse = $soapClient->CancelConsignment($CancelConsignment); 
		if(isset($CancelConsignmentResponse->CancelConsignmentResult->Errors->UKMWebError)){
			return array("status"=>"error","cancel_error"=>$CancelConsignmentResponse->CancelConsignmentResult->Errors->UKMWebError->Code,"cancel_error"=>$CancelConsignmentResponse->CancelConsignmentResult->Errors->UKMWebError->Description,"message"=>$CancelConsignmentResponse->CancelConsignmentResult->Errors->UKMWebError->Description);
		}else{
			return array("status"=>"success","void_consignment"=>array("sucess"=>"Successful"));
		}
    }
}