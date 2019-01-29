<?php
require_once  __DIR__ . '/../../../../vendor/autoload.php';    
include_once __DIR__ .'/../../../module/ukmail_tracking/src/Singleton/Singleton.php';
include_once __DIR__ .'/../../../module/ukmail_tracking/src/Auth.php';
include_once __DIR__ .'/../../../module/ukmail_tracking/src/Send.php';
include_once __DIR__ .'/UkMailModel.php';
         
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);
   
class UkMailTracking 
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
        $app->get('/ukmailtracking', function() use ($app) {                               
            $ukMailModel = UkMailModel::getInstance();
            $shipmentsToTrack = $ukMailModel->getShipmentToTrack();                                       
            //print_r($shipmentsToTrack);die;
//            $shipmentsToTrack = array();
//            $shipmentsToTrack[] = array(
//                                'id' =>'3116',
//                                'load_identity' =>'ICARGOS194000903',
//                                'carrier' =>10,
//                                'status' =>'success',
//                                'accountkey' =>'M901043',
//                                'parent_account_key' =>'B020626',
//                                'label_tracking_number' =>'41679540000036',
//                                'tracking_code' =>'DELAYED',
//                                'company_id' =>194
//                            );
            
            if(count($shipmentsToTrack) > 0)
            {                
                foreach($shipmentsToTrack as $shipment)
                { 
                    $credentials = $ukMailModel->getAccountCredential($shipment['company_id'],$shipment['parent_account_key']);  
                    if(count($credentials) > 0)
                    {              
//                        try
//                        {
//                            self::doTracking($credentials['username'], $credentials['password'],'',$shipment['label_tracking_number'],0);   
//                        }
//                        catch(Exception $e)
//                        {
//                            //break;
//                            echo $e->getMessage();
//                            //echo "<br/>";
//                            
//                            echo '<pre>'; print_r($shipment);
//                            die;
//                        }     
                        
                        self::doTracking($credentials['username'], $credentials['password'],'',$shipment['label_tracking_number'],0);
                    }                                                            
                }
            }                                    
        });
    }
            
    public static function doLogin($username, $password)
    {                                                  
        $wsdl_url = 'https://api.ukmail.com/Services/UKMAuthenticationServices/UKMAuthenticationService.svc?wsdl';              
        $LoginWebRequest = new stdClass();
        $LoginWebRequest->Username = $username;
        $LoginWebRequest->Password = $password;
        $Login = new stdClass();
        $Login->loginWebRequest = $LoginWebRequest;

        $soapClient = new SoapClient($wsdl_url);
        $LoginResponse = $soapClient->Login($Login);                                         
        $AuthenticationToken = $LoginResponse->LoginResult->AuthenticationToken; 
        error_log("Ukmail, Authentication key generated - ".$AuthenticationToken);
                  
        // Update authenticationToken to database table carrier_user_token
        if($AuthenticationToken != NULL)
        {
            error_log("Ukmail, Sent request to update db --- ");
            $ukMailModel = UkMailModel::getInstance();
            $ukMailModel->updateAuthToDb($username, $AuthenticationToken);
        }
        
        return $AuthenticationToken;
    }
    
    public static function doTracking($username, $password, $token, $consignmentNumber, $consignmentKey)
    {
        $wsdl_url = 'http://webapp-cl.internet-delivery.com/ThirdPartyIntegration/ThirdPartyIntegrationService.asmx?wsdl';
        $soapClient = new SoapClient($wsdl_url);
        $ConsignmentTrackingGetConsignmentDetailsV3 = new stdClass();
        $ConsignmentTrackingGetConsignmentDetailsV3->UserName = $username;
        $ConsignmentTrackingGetConsignmentDetailsV3->Password = $password;
        if($token != "")
        {
            $ConsignmentTrackingGetConsignmentDetailsV3->Token = $token;
        }        
        $ConsignmentTrackingGetConsignmentDetailsV3->ConsignmentNumber = $consignmentNumber;
        $ConsignmentTrackingGetConsignmentDetailsV3->ConsignmentKey = $consignmentKey;

        $ConsignmentTrackingGetConsignmentDetailsV3Response = $soapClient->ConsignmentTrackingGetConsignmentDetailsV3($ConsignmentTrackingGetConsignmentDetailsV3);        
        $ConsignmentTrackingGetConsignmentDetailsV3Result = $ConsignmentTrackingGetConsignmentDetailsV3Response->ConsignmentTrackingGetConsignmentDetailsV3Result;
                                  
        error_log("Ukmail, Sent request to update db when doTracking --- ");
        $ukMailModel = UkMailModel::getInstance();
        $ukMailModel->saveTrackingInfo($ConsignmentTrackingGetConsignmentDetailsV3Result);                               
    }
    

    
}
