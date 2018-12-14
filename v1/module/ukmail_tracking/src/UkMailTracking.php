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
        $app->post('/ukmailtracking', function() use ($app) {                                    
           
            //self::doLogin('test','test');
            //self::doLogin(USERNAME, PASSWORD);
            //self::doTracking(USERNAME, PASSWORD,TOKEN,CONSIGNMENTNUMBER,CONSIGNMENTKEY);
            self::doTracking('malowany333@gmail.com', 'c78nx90i','','41400110000044',0);
            
            
        });
        
        
        $app->get('/phpinfo', function() use ($app) {                                    
           
            
            
            
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

        //$ConsignmentTrackingGetConsignmentDetailsV3Response = $soapClient->ConsignmentTrackingGetConsignmentDetailsV3($ConsignmentTrackingGetConsignmentDetailsV3);        
        //$ConsignmentTrackingGetConsignmentDetailsV3Result = $ConsignmentTrackingGetConsignmentDetailsV3Response->ConsignmentTrackingGetConsignmentDetailsV3Result;
              
        $a = new stdClass();
        $a->ResultState = 'Successful';
        $a->Errors = new stdClass();
        $a->ConsignmentNumber = 41400110000044;
        $a->StatusCode = 3;
        $a->StatusMessage = 'At Delivery Location';
        $a->OriginalDelivery = '2018-11-30T00:00:00';
        $a->ExpectedDelivery = '2018-11-30T00:00:00';
        $a->CollectionDate = '2018-11-29T00:00:00';
        $a->Quantity = 1;
        $a->Weight = 2;
        $a->CompanyName = 'NIBBLE';
        $a->PostalTown = 'LONDON';
        $a->FoundConsignment = '41400110000044';
        $a->International = '';
        $a->Mail = '';
        $a->MailingID = '';
        $a->SwapOut = '';
        $a->ReturnConsignmentNumber = '';
        $a->EstimatedTimeOfArrivalStart = '18:31';
        $a->EstimatedTimeOfArrivalEnd = '19:31';
        
        $b = new stdClass();
        $b->SubEmail = 'ops@simplyparcel.co.uk';
        $b->SubInstructions = '';
        $b->SubName = 'Erin';
        $b->SubPhone = '00000000';
        $b->SubRef1 = 'ID1800047092/008205';
        $b->SubRef2 = '47692 Nibble';
        $b->SubSequence = 1;
        
        $c = new stdClass();
        $c->GetConsignmentDetailsSub = $b;
        
        $a->ConsignmentSubs = $c;
        
        
        $d = new stdClass();
        $d->PodDescription = 'Signed for at address';
        $d->PodQuantity = 1;
        $d->PodSequence = 1;
        $d->PodTimeStamp = '2018-11-30T17:23:00+00:00';
        $d->PodRecipientName = 'Signed for at address';
        $d->PodDescription = 'Emmaaaaa';
        $d->PodDeliveryComments = '';
        $d->PodDeliveryTypeCode = 'DT01';
                
        $e = new stdClass();
        $e->GetConsignmentDetailsPod = $d;
        
        $a->ConsignmentPods = $e;
        
        
        
        
        
        $f1= array(
                    'StatusCode'=>1,
                    'StatusDescription'=>'Awaiting Collection',
                    'StatusSequence'=>1,
                    'StatusTimeStamp'=>'2018-11-29T11:07:41+00:00'
                );
        $f2= array(
                    'StatusCode'=>2,
                    'StatusDescription'=>'Collected',
                    'StatusSequence'=>2,
                    'StatusTimeStamp'=>'2018-11-30T01:15:20+00:00'
                );
        $f3= array(
                    'StatusCode'=>3,
                    'StatusDescription'=>'At delivery location',
                    'StatusSequence'=>3,
                    'StatusTimeStamp'=>'2018-11-30T01:15:20+00:00'
                );
        $f4= array(
                    'StatusCode'=>4,
                    'StatusDescription'=>'Delivered',
                    'StatusSequence'=>4,
                    'StatusTimeStamp'=>'2018-11-30T17:23:00+00:00'
                );
        $f5= array(
                    'StatusCode'=>5,
                    'StatusDescription'=>'Successfully Deliveried',
                    'StatusSequence'=>5,
                    'StatusTimeStamp'=>'2018-11-30T17:23:00+00:00'
                );
                        
        $f = new stdClass();
        $f->GetConsignmentDetailsStatus = array(
            (object)$f1,
            (object)$f2,
            (object)$f3,
            (object)$f4,
            (object)$f5
        );
        
        $a->ConsignmentStatus = $f;  
                
        if($a->ResultState == 'Failed')
        {
            $errorInfoObj =  $a->Errors->Error;            
            error_log("Ukmail, Error when tracking --- ".$errorInfoObj->ErrorCode.'--'.$errorInfoObj->ExceptionMessage);
        }
            
        error_log("Ukmail, Sent request to update db when doTracking --- ");
        $ukMailModel = UkMailModel::getInstance();
        $ukMailModel->saveTrackingInfo($a);                               
    }
    

    
}