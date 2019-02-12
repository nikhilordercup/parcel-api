<?php
namespace v1\module\RateEngine\ukmail\src; 

use v1\module\RateEngine\ukmail\src\Model\UkMailModel;
            
class UkmailLogin
{

    public static function doLogin($data,$wsdlBaseUrl){
		$wsdlUrl = $wsdlBaseUrl.'UKMAuthenticationServices/UKMAuthenticationService.svc?wsdl';	
        $LoginWebRequest = new \stdClass();
        $LoginWebRequest->Username = $data->credentials->username;
        $LoginWebRequest->Password = $data->credentials->password;
        $Login = new \stdClass();
        $Login->loginWebRequest = $LoginWebRequest;
        $soapClient = new \SoapClient($wsdlUrl);
        $LoginResponse = $soapClient->Login($Login); 
		if(isset($LoginResponse->LoginResult->Errors->UKMWebError)){
			return array("status"=>"error","auth_error_code"=>$LoginResponse->LoginResult->Errors->UKMWebError->Code,"auth_error_message"=>$LoginResponse->LoginResult->Errors->UKMWebError->Description,"message"=>$LoginResponse->LoginResult->Errors->UKMWebError->Description);
		}else{
			$AuthenticationToken = $LoginResponse->LoginResult->AuthenticationToken; 
			// Update authenticationToken to database table carrier_user_token
			if($AuthenticationToken != NULL)
			{
				error_log("Ukmail, Sent request to update db --- ");
				$ukMailModel = new UkMailModel();
				$ukMailModel->updateAuthToDb($data->credentials->username, $AuthenticationToken);
			}
			return array("status"=>"success","message"=>"auth token created successfully","authentication_token"=>$AuthenticationToken);
		}
    }
}