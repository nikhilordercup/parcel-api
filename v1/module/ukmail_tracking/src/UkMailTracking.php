<?php
require_once  __DIR__ . '/../../../../vendor/autoload.php';    
include_once __DIR__ .'/../../../module/ukmail_tracking/src/Singleton/Singleton.php';
include_once __DIR__ .'/../../../module/ukmail_tracking/src/Auth.php';
include_once __DIR__ .'/../../../module/ukmail_tracking/src/Send.php';
include_once __DIR__ .'/UkMailModel.php';
            
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
            if(count($shipmentsToTrack) > 0)
            {                
                foreach($shipmentsToTrack as $shipment)
                { 
                    $credentials = $ukMailModel->getAccountCredential($shipment['company_id'],$shipment['parent_account_key']);  
                    if(count($credentials) > 0)
                    {                       
                        self::doTracking($credentials['username'], $credentials['password'],'',$shipment['label_tracking_number'],0);
                    }                                                            
                }
            }                                    
        });
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
