<?php
require_once  __DIR__ . '/../../../../vendor/autoload.php';    
include_once __DIR__ .'/../../../module/ukmail_api/src/Singleton/Singleton.php';
include_once __DIR__ .'/model/UkMailModel.php';
            
class UkmailLogin
{

    private $_db;
    private $_app;
    private $_requestParams;
    
    /**
     * FormConfiguration constructor.
     */
    private function __construct($app) {
        $this->_db = new DbHandler();
        $this->_app = $app;
        $this->_requestParams=json_decode($this->_app->request->getBody());
    }
	
	public static function initRoutes($app){               
        $app->post('/ukmailLogin', function() use ($app) {
			$loginResponse = self::doLogin($app);
			echoResponse(200,$loginResponse);
		});
    }

    public static function doLogin($app){
		$app = json_decode($app->request->getBody());
		verifyRequiredParams(array('username','password'),$app);
		
		$wsdl_url = 'https://qa-api.ukmail.com/Services/UKMAuthenticationServices/UKMAuthenticationService.svc?wsdl';              
        $LoginWebRequest = new stdClass();
        $LoginWebRequest->Username = $app->username;
        $LoginWebRequest->Password = $app->password;
        $Login = new stdClass();
        $Login->loginWebRequest = $LoginWebRequest;

        $soapClient = new SoapClient($wsdl_url);
        $LoginResponse = $soapClient->Login($Login); 
		if(isset($LoginResponse->LoginResult->Errors->UKMWebError)){
			return array("status"=>"error","auth_error_code"=>$LoginResponse->LoginResult->Errors->UKMWebError->Code,"auth_error_message"=>$LoginResponse->LoginResult->Errors->UKMWebError->Description,"message"=>$LoginResponse->LoginResult->Errors->UKMWebError->Description);
		}else{
			$AuthenticationToken = $LoginResponse->LoginResult->AuthenticationToken; 
			error_log("Ukmail, Authentication key generated - ".$AuthenticationToken); 
			// Update authenticationToken to database table carrier_user_token
			if($AuthenticationToken != NULL)
			{
				error_log("Ukmail, Sent request to update db --- ");
				$ukMailModel = UkMailModel::getInstance();
				$ukMailModel->updateAuthToDb($app->username, $AuthenticationToken);
			}
			return array("status"=>"success","message"=>$AuthenticationToken);
		}
    }
}