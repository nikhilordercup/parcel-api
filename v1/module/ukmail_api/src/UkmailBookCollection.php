<?php
require_once  __DIR__ . '/../../../../vendor/autoload.php';    
include_once __DIR__ .'/../../../module/ukmail_api/src/Singleton/Singleton.php';
include_once __DIR__ .'/model/UkMailModel.php';
            
class UkmailBookCollection
{

    private $_db;
    private $_app;
    private $_requestParams;
	private $_wsdl_url;
    
    /**
     * FormConfiguration constructor.
     */
    private function __construct($app) {
        $this->_db = new DbHandler();
        $this->_app = $app;
        $this->_requestParams=json_decode($this->_app->request->getBody());
		$this->wsdl_url = 'https://qa-api.ukmail.com/Services/UKMCollectionServices/UKMCollectionService.svc';
    }
	
    public static function bookCollection($app){
		$app = json_decode($app->request->getBody());
		verifyRequiredParams(array('authentication_token','username','account_number','earliest_time','latest_time','requested_collection_date'),$app);
		
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



