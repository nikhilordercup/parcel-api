<?php
namespace v1\module\RateEngine\ukmail\src; 

use v1\module\RateEngine\ukmail\src\Singleton\Singleton;
use v1\module\RateEngine\ukmail\src\Model\UkMailModel;
            
class UkmailLogin
{

    private $_db;
    private $_app;
    private $_requestParams;
	private $_wsdlUrl;
    
    /**
     * FormConfiguration constructor.
     */
    private function __construct($app) {
        $this->_db = new DbHandler();
        $this->_app = $app;
        $this->_requestParams=json_decode($this->_app->request->getBody());
		if(ENV == 'dev')
			$this->_wsdlUrl = 'https://qa-api.ukmail.com/Services/UKMAuthenticationServices/UKMAuthenticationService.svc?wsdl';
		else
			$this->_wsdlUrl = 'https://api.ukmail.com/Services/UKMAuthenticationServices/UKMAuthenticationService.svc?wsdl';	
    }
	
	public static function initRoutes($app){               
        /* $app->post('/ukmailLogin', function() use ($app) {
			$loginResponse = self::doLogin($app);
			echoResponse(200,$loginResponse);
		}); */
    }

    public static function doLogin($app){
		//$app = json_decode($app->request->getBody());
		$app = new \stdClass();
		$app->username = 'nikhil.kumar@ordercup.com';
		$app->password = 'b85op06w';
        $LoginWebRequest = new \stdClass();
        $LoginWebRequest->Username = $app->username;
        $LoginWebRequest->Password = $app->password;
        $Login = new \stdClass();
        $Login->loginWebRequest = $LoginWebRequest;
        
		$wsdlUrl = 'https://qa-api.ukmail.com/Services/UKMAuthenticationServices/UKMAuthenticationService.svc?wsdl';
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
				$ukMailModel->updateAuthToDb($app->username, $AuthenticationToken);
			}
			return array("status"=>"success","message"=>"auth token created successfully","authentication_token"=>$AuthenticationToken);
		}
    }
}