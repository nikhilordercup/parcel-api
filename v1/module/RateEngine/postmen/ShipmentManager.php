<?php
namespace v1\module\RateEngine\postmen;
use Postmen\Postmen;

class ShipmentManager extends PostMenMaster
{    
    private static $shipmentManagerObj = NULL;
    private $db = NULL;
    
    public static function shipmentRoutes($app)
    {
        $app->post('/postmen/calculateRate', function () use ($app) {              
            $request = json_decode($app->request->getBody());             
            $shipmentManagerObj = self::getShipmentManagerObj();                        
            $result = $shipmentManagerObj->calculateRateAction($request); 
            echo $result;die;
        });
        
        $app->post('/postmen/createLabel', function () use ($app) {              
            $request = json_decode($app->request->getBody());             
            $shipmentManagerObj = self::getShipmentManagerObj();                        
            $result = $shipmentManagerObj->createLabelAction($request);
            echo $result;die;
        });
        
        $app->post('/postmen/cancelLabel', function () use ($app) {              
            $request = json_decode($app->request->getBody());             
            $shipmentManagerObj = self::getShipmentManagerObj();                        
            $result = $shipmentManagerObj->cancelLabelAction($request); 
            echo $result;die;
        });
        $app->post('/postmen/createManifest', function () use ($app) {              
            $request = json_decode($app->request->getBody());             
            $shipmentManagerObj = self::getShipmentManagerObj();                        
            $result = $shipmentManagerObj->createManifestAction($request); 
            echo $result;die;
        });
        $app->post('/postmen/create-shipper-account', function () use ($app) {              
            $request = json_decode($app->request->getBody());             
            $shipmentManagerObj = self::getShipmentManagerObj();                        
            $result = $shipmentManagerObj->createShipperAcAction($request); 
            echo $result;die;
        });
        $app->post('/postmen/create-bulk-download', function () use ($app) {              
            $request = json_decode($app->request->getBody());             
            $shipmentManagerObj = self::getShipmentManagerObj();                        
            $result = $shipmentManagerObj->createBulkDownloadAction($request); 
            echo $result;die;
        });
    }
          
    public static function getShipmentManagerObj()
    {
        if(!self::$shipmentManagerObj instanceof ShipmentManager)
        {           
            self::$shipmentManagerObj = new ShipmentManager(); 
            self::$shipmentManagerObj->db = new \dbConnect();
        }
        return self::$shipmentManagerObj;
    }
    
    public function calculateRateAction($request)
    {                                                   
        $fromAddress = $this->convertAddress($request->from);                
        $toAddress = $this->convertAddress($request->to);         
        $package = $this->packagesToOrder($request->package,$request->currency);                
        $shipper_accounts = $this->carrierAccounts($request->carriers);                 
        $isDocument = (strtolower($request->extra->is_document) == "true") ? TRUE : FALSE;        
        $payload = $this->buildRequestToCalculateRate($fromAddress, $toAddress, $package, $shipper_accounts, $isDocument,FALSE);                                                
        try 
        {            
            $rawRates = $this->calculateRates($payload);             
            $formatedRates = $this->formatRate($rawRates);
            header('Content-Type: application/json'); 
            return json_encode($formatedRates,TRUE);            
        }
        catch(exception $e) 
        {            
            echo $e->getCode() .$e->getMessage(). "\n";                   
        }                                        
    }
            
    public function formatRate($rawRates)
    {             
        $rates['rate'] = array(); 
        if (count($rates) > 0) 
        {       
            $differentAcIds = array();
            foreach($rawRates as $rate)
            {                        
                $innerRate = array();
                $innerRate['rate']['id'] = '';
                $innerRate['rate']['carrier_name'] = $rate->shipper_account->slug;
                $innerRate['rate']['service_name'] = $rate->service_name;
                $innerRate['rate']['service_code'] = $rate->service_type;                 
                $innerRate['rate']['rate_type'] = (in_array($rate->charge_weight->unit, array('kg','g','oz','lb'))) ? 'Weight':'#';
                $innerRate['rate']['rate_unit'] = strtoupper($rate->charge_weight->unit);
                $innerRate['rate']['price'] = $rate->total_charge->amount;
                $innerRate['rate']['act_number'] = $rate->shipper_account->id; 
                
                $innerRate['surcharges']['remote_area_surcharge'] = 0; 
                $innerRate['surcharges']['long_length_surcharge'] = 0; 
                $innerRate['surcharges']['manual_handling_surcharge'] = 0; 
                $innerRate['surcharges']['extrabox_surcharge'] = 0; 
                $innerRate['surcharges']['overweight_surcharge'] = 0; 
                $innerRate['surcharges']['isle_weight_surcharge'] = 0; 
                $innerRate['surcharges']['fuel_surcharge'] = $rate->detailed_charges[1]->charge->amount; 
                                
                $dimensions = array('length'=>'','width'=>'','height'=>'','unit'=>$rate->charge_weight->unit);
                $weight = array("weight"=>$rate->charge_weight->value,"unit"=>$rate->charge_weight->unit);
                $time = array("max_waiting_time"=>"","unit"=>"");
                
                $innerRate['service_options']["dimensions"] =$dimensions; 
                $innerRate['service_options']["weight"] =$weight; 
                $innerRate['service_options']["time"] =$time; 
                
                $innerRate['taxes'] = array("total_tax"=>"","tax_percentage"=>"");
                
                if(!in_array($rate->shipper_account->id, $differentAcIds))
                {
                    $differentAcIds[] = $rate->shipper_account->id;
                }
                $length = array_search($rate->shipper_account->id, $differentAcIds);
                                
                if(isset($rates['rate'][$rate->shipper_account->slug]))
                {  
                    if(isset($rates['rate'][$rate->shipper_account->slug][$length][$rate->shipper_account->id]))
                    { 
                        if(isset($rates['rate'][$rate->shipper_account->slug][$length][$rate->shipper_account->id][0][$rate->service_type]))
                        {
                            //$rates['rate'][$rate->shipper_account->slug][$length][$rate->shipper_account->id][0][$rate->service_type][0][] = $innerRate;
                            $rates['rate'][$rate->shipper_account->slug][$length][$rate->shipper_account->id][0][$rate->service_type][] = $innerRate;
                        }
                        else
                        {                                                     
                            $rates['rate'][$rate->shipper_account->slug][$length][$rate->shipper_account->id][0][$rate->service_type][] = $innerRate;                                                        
                        }
                    }
                    else
                    { 
                        $serviceTemp = array();
                        $serviceTemp[$rate->service_type][] = $innerRate;
                        
                        $account = array();
                        $account[$rate->shipper_account->id][] = $serviceTemp;                                                                                            
                        
                        $rates['rate'][$rate->shipper_account->slug][] = $account;                        
                    }
                }
                else
                { 
                    $serviceTemp = array();
                    $serviceTemp[$rate->service_type][] = $innerRate;
                    
                    $account = array();
                    $account[$rate->shipper_account->id][] = $serviceTemp;
                    
                    $rates['rate'][$rate->shipper_account->slug][] = $account;
                    
                    
                }                                                
            }
        }               
        return $rates;
    }
    
    public function createLabelAction($request)
    {                       
        $fromAddress = $this->convertAddress($request->label->from);                
        $toAddress = $this->convertAddress($request->label->to);         
        $package = $this->convertPackage($request->label->package[0],$request->label->currency);                                         
        $isDocument = (isset($request->label->extra->is_document) && strtolower($request->label->extra->is_document) == "true") ? TRUE : FALSE;        
        $returnShipment = (isset($request->label->extra->return_shipment) && strtolower($request->label->extra->return_shipment) == "true") ? TRUE : FALSE;
        
        $carrierName = $request->label->carrier;
        $carrierId = $this->getCarrierIdByName($carrierName);
        $credentials = $request->label->credentials;
        $carrierAccountDetails = $this->getCarrierAccount($carrierId, $credentials->username, $credentials->password, $credentials->account_number);
        $shipper_accounts = array('id'=>$carrierAccountDetails->carrierAccount);
                                             
        $shipperAccountId = $shipper_accounts['id'];                            
        $others = array();
        $others['paid_by'] = 'shipper';
        $others['account_number'] = $shipperAccountId; 
        $others['type'] = 'account';
        $others['purpose'] = 'gift';
        $others['service_type'] = $request->label->service;
        $others['paper_size'] = 'default';
                        
        $payload = $this->buildCreateLabelRequest($fromAddress, $toAddress, $package, $shipperAccountId,$others, $isDocument, $returnShipment);                                                
        try 
        {             
            $rawRates = $this->createLabel($payload); 
            echo '<pre>'; print_r($rawRates);die;
            
            /* Test Data Start */
            /*$labelResponse = array();
            $labelResponse['data']['id'] = '3318b97b-150f-4205-840d-a6d966b9e0ea'; 
            $labelResponse['data']['status'] = 'created'; 
            $labelResponse['data']['tracking_numbers'][] = '3884930103'; 
            $labelResponse['data']['files'] = array(
                'label'=> array(
                    'paper_size' => 'default',
                    'url' => 'https://sandbox-download.postmen.com/label/2015-11-27/00000000-0000-0000-0000-000000000000-1441785264309885.pdf',
                    'file_type' => 'pdf'
                ),
                'invoice' =>'',
                'customs_declaration' =>'',
                'manifest' =>''
            );
            
            $labelResponse['data']['rate'] = array(
                'charge_weight' => array('value'=>'3.307','unit'=>'lb'),
                'total_charge' => array('amount'=>'3','currency'=>'USD'),
                'shipper_account' => array('id'=>'3','slug'=>'USD','description'=>'DHL Sandbox'),
                'service_type'=>'dhl_express_0900',
                'service_name'=>'dhl_express_0900',
                'pickup_deadline'=>NULL,
                'booking_cut_off'=>NULL,
                'delivery_date'=>NULL,
                'transit_time'=>NULL,
                'detailed_charges'=>[],
                'error_message'=>NULL,
                'info_message'=>NULL
            );
            
            $labelResponse['data']['created_at'] = '2015-09-09T07:54:13.024Z';
            $labelResponse['data']['updated_at'] = '2015-09-09T07:54:24.569Z';
            $labelResponse['data']['ship_date'] = '2015-09-09';
            
           */
            //echo json_encode($labelResponse,JSON_PRETTY_PRINT);die;
             /* Test End */
            
            $otherParam['authentication_token']  = $credentials->authentication_token;
            $otherParam['authentication_token_created_at']  = $credentials->authentication_token_created_at;
            $otherParam['account_number']  = $credentials->account_number;
            $formatedLabelResponse = $this->formatCreateLabelResponse($labelResponse,$otherParam);
            header('Content-Type: application/json'); 
            return json_encode($formatedLabelResponse,TRUE); 
        }
        catch(exception $e) 
        {            
            echo $e->getCode() .$e->getMessage(). "\n";                   
        }                                        
    }
    
    public function formatCreateLabelResponse($labelResponse,$others=array())
    {
        $finalResponse = array();
        
        $tracking_numbers = implode(',', $labelResponse['data']['tracking_numbers']);
        $finalResponse['tracking_number'] = $tracking_numbers;                
        
        $finalResponse['file_url'] = $labelResponse['data']['files']['label']['url'];
        $finalResponse['accountnumber'] = $others['account_number']; 
        $finalResponse['accountstatus'] = "";
        $finalResponse['accounttype'] = "";
        $finalResponse['authenticationtoken'] = $others['authentication_token'];
        $finalResponse['authenticationtoken_created_at'] = $others['authentication_token_created_at'];
        $finalResponse['collectionjobnumber'] = ""; //Need Discussion i.e not available in response                               
        return array('label' => $finalResponse);        
    }

    public function cancelLabelAction($request)
    {              
        $labelId = $request->label->id;
        $payload = array (
            'label' => array (
                'id' => $labelId
            )            
        );
        //$result = $this->cancelLabel($payload);
        
        // Start Demo Response
        /*$result = [];
        $result['meta']['code'] = 200;
        $result['meta']['message'] = 'ok';
        $result['meta']['details'] = [];
        $result['data']['id'] = 'fffffffffffff';
        $result['data']['status'] = 'cancel';
        $result['data']['label'] = array(
            'id'=>'aaaa'
        );
        $result['created_at']='aaa';
        $result['updated_at']='bbb'; */
        //echo json_encode($result);die;
        // End Demo Response
        
        $finalResponse = [];
        $finalResponse['code'] = $result['meta']['code'];
        $finalResponse['message'] = $result['meta']['message'];        
        $finalResponse['data']['id'] = $result['data']['id'];
        $finalResponse['data']['status'] = $result['data']['status'];
        $finalResponse['data']['label']['id'] = $result['data']['label']['id'];
        $finalResponse['created_at'] = $result['created_at'];
        $finalResponse['updated_at'] = $result['updated_at'];   
        
        header('Content-Type: application/json'); 
        return json_encode($formatedRates);
    }
    
    public function createManifestAction($request)
    {
        $carrierName = $request->carrier;
        $carrierId = $this->getCarrierIdByName($carrierName);
        $credentials = $request->credentials;
        $carrierAccountDetails = $this->getCarrierAccount($carrierId, $credentials->username, $credentials->password, $credentials->account_number);
        $shipper_accounts = array('id'=>$carrierAccountDetails->carrierAccount);                
        $payload['shipper_account'] = $shipper_accounts;
        
        //$result = $this->createManifest($payload);
        
        // Start Demo Response
        $labels = array(
            array(
                'id' => '0000',
                'manifested' => 'true',
                'ship_date' => '2016-08-18'
            )
        );
        $result['labels'] = $labels;
        $result['files']['manifest'] = array(
			'paper_size'=>'a4',
			'url'=>'https://sandbox-download.postmen.com/manifest/2016-08-18/00000000-0000-0000-0000-000000000000-1448506399446029.pdf',
			'file_type'=>'pdf'
        );
        $result['shipper_account'] = array(
			'id'=>'0000',
			'slug'=>'dhl',
			'description'=>'dhl'
			
        );
        $result['status'] = 'manifested';
        $result['id'] = '000';
        $result['created_at'] = '2016-08-18T09:15:33+00:00';
        $result['updated_at'] = '2016-08-18T09:15:33+00:00';                                
        //print_r($result);die;
        // End Demo Response
        
        $finalResponse = [];
        $finalResponse['id'] = $result['id'];
        $finalResponse['labels'] = $result['labels'];
        $finalResponse['files']['manifest'] = $result['files']['manifest'];
        $finalResponse['shipper_account'] = $result['shipper_account'];
        $finalResponse['shipper_account']['id'] = $credentials->account_number;        
        $finalResponse['status'] = $result['status'];        
        $finalResponse['created_at'] = $result['created_at'];
        $finalResponse['updated_at'] = $result['updated_at'];
        
        header('Content-Type: application/json'); 
        return json_encode($finalResponse);        
    }
    
    public function createShipperAcAction($request)
    {               
        $payload = array();
        $payload['slug'] = $request->carrier;
        $payload['description'] = $request->description;
        $payload['timezone'] = $request->timezone;                
        $credentials['account_number'] = $request->credentials->account_number;
        $credentials['password'] = $request->credentials->password;
        $credentials['site_id'] = $request->credentials->site_id;        
        $payload['credentials'] = $credentials;
        $payload['address'] = $request->address;        
        //$result = $this->createShipperAc($payload); 
        
        //Demo Response
        /* $result = [];
        $result['meta']['code'] = '200';
        $result['meta']['message'] = 'ok';
        $result['meta']['details'] = [];
        
        $result['data']['id'] = '9161239a-9aff-40ab-aa5b-5b1a5dd2184b';
        $result['data']['description'] = 'Ordercup dhl test export';
        $result['data']['slug'] = 'dhl';
        $result['data']['status'] = 'enabled';
        $result['data']['timezone'] = 'Europe/London';
        $result['data']['type'] = 'user';
        $result['data']['created_at'] = '2019-01-03T05:47:43+00:00';
        $result['data']['updated_at'] = '2019-01-03T05:47:43+00:00';
        
        $result['data']['address']['country'] = 'GBR';
        $result['data']['address']['contact_name'] = 'Nikhil Kumar';
        $result['data']['address']['phone'] = '07595590074';
        $result['data']['address']['fax'] = '+1 206-654-3100';
        $result['data']['address']['email'] = 'info@perceptive-solutions.com';
        $result['data']['address']['company_name'] = 'Perceptive Consulting Solutions';
        $result['data']['address']['street1'] = '6 York Way';
        $result['data']['address']['street2'] = 'Harlestone';
        $result['data']['address']['street3'] = null;
        $result['data']['address']['city'] = 'Northampton';
        $result['data']['address']['state'] = 'KZ';
        $result['data']['address']['postal_code'] = 'NN5 6UX';
        $result['data']['address']['type'] = 'business';
        $result['data']['address']['tax_id'] = 'null'; */
                
        $finalResponse = array();
        $finalResponse['code'] = $result['meta']['code'];
        $finalResponse['message'] = $result['meta']['message'];
        $finalResponse['details'] = $result['meta']['details'];
        $finalResponse['data']['id'] = $result['data']['id']; 
        header('Content-Type: application/json'); 
        return json_encode($result);        
    }
    
    public function deleteShipperAcAction()
    {
        
    }
    
    public function createBulkDownloadAction($request)
    {                                
        $payload = array();
        $payload['async'] = ( isset($request->async) && $request->async == TRUE ) ? TRUE : FALSE;
        $payload['labels'] = $request->labels;        
        $result = $this->createBulkDownload($payload);
                                        
        $finalResponse = array();
        $finalResponse['code'] = $result['meta']['code'];
        $finalResponse['message'] = $result['meta']['message'];
        $finalResponse['details'] = $result['meta']['details'];
        $finalResponse['id'] = $result['data']['id'];
        $finalResponse['created_at'] = $result['data']['created_at'];
        $finalResponse['updated_at'] = $result['data']['updated_at'];
        $finalResponse['status'] = $result['data']['status'];
        $finalResponse['files'] = $result['data']['files'];
        $finalResponse['labels'] = $result['data']['labels'];
        $finalResponse['invalid_labels'] = $result['data']['invalid_labels'];
                              
        header('Content-Type: application/json'); 
        return json_encode($finalResponse);        
    }
}