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
        $app->post('/postmen/updateShipper', function () use ($app) {              
            $request = json_decode($app->request->getBody());             
            $shipmentManagerObj = self::getShipmentManagerObj();                        
            $result = $shipmentManagerObj->updateShipperAcAction($request); 
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
                        
        $payload = $this->buildCreateLabelRequest($fromAddress, $toAddress, $package, $shipperAccountId,$others, $isDocument, $returnShipment,FALSE);                                                
        try 
        {             
            $labelResponse = $this->createLabel($payload); 
            if($labelResponse)
            {
                $formatedLabelResponse = $this->formatCreateLabelResponse($labelResponse);
            }
            else
            {                
                $formatedLabelResponse = $this->getErrorDetails();
            }            
            header('Content-Type: application/json'); 
            return json_encode($formatedLabelResponse,TRUE); 
        }
        catch(exception $e) 
        {            
            echo $e->getCode() .$e->getMessage(). "\n";                   
        }                                        
    }
    
    public function formatCreateLabelResponse($labelResponse)
    {
        $finalResponse = array();                
        $tracking_numbers = implode(',', $labelResponse->tracking_numbers);
        $finalResponse['id'] = $labelResponse->id;
        $finalResponse['tracking_number'] = $tracking_numbers;                        
        $finalResponse['file_url'] = $labelResponse->files->label->url;
        $finalResponse['total_cost'] = $labelResponse->rate->total_charge->amount;
        $finalResponse['weight_charge'] = $labelResponse->rate->charge_weight->value;
        $finalResponse['fuel_surcharge'] = 0;
        $finalResponse['remote_area_delivery'] = 0;
        $finalResponse['insurance_charge'] = 0;
        $finalResponse['over_sized_charge'] = 0;
        $finalResponse['over_weight_charge'] = 0;
        $finalResponse['discounted_rate'] = 0;
        $finalResponse['product_content_code '] = 0;
        $finalResponse['license_plate_number '] = [];
        $finalResponse['chargeable_weight '] = '';
        $finalResponse['service_area_code '] = '';                                              
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
        $result = $this->cancelLabel($payload);        
        $finalResponse = []; 
        if($result)
        {                      
            $finalResponse['id'] = $result->id;
            $finalResponse['status'] = $result->status;
            $finalResponse['label']['id'] = $result->label->id;
            $finalResponse['created_at'] = $result->created_at;
            $finalResponse['updated_at'] = $result->updated_at;
        }
        else
        { 
            $finalResponse = $this->getErrorDetails();                     
        }           
        header('Content-Type: application/json'); 
        return json_encode($finalResponse);
    }
    
    public function getErrorDetails()
    {
        $tempErrors = [];
        $finalResponse = [];
        $errors = $this->api->getError()->getDetails();             
        if(count($errors) > 0)
        {
            foreach ($errors as $error) {
                $tempErrors[] = $error->info;
            }                                
        }            
        $finalResponse['errorCode'] = 4104;
        $finalResponse['errorMessage'] = implode(',',$tempErrors); 
        return $finalResponse;
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
        $result = $this->createShipperAc($payload); 
                        
        $finalResponse = array();        
        $finalResponse['id'] = $result->id;
        $finalResponse['description'] = $result->description;
        $finalResponse['carrier'] = $result->slug;
        $finalResponse['status'] = $result->status;
        $finalResponse['timezone'] = $result->timezone;
        $finalResponse['type'] = $result->type;
        $finalResponse['created_at'] = $result->created_at;
        $finalResponse['updated_at'] = $result->updated_at;
        $finalResponse['address'] = $result->address;
                
        header('Content-Type: application/json'); 
        return json_encode($finalResponse);        
    }
    
    public function deleteShipperAcAction()
    {
        
    }
    
    public function updateShipperAcAction($request)
    {
        $payload = array();
        $payload['account_number'] = $request->account_number;
        $payload['password'] = $request->password;
        $payload['site_id'] = $request->site_id;
        
        $result = $this->updateShipperAc($payload);
        
        
        print_r($result);die;
    }

        public function createBulkDownloadAction($request)
    {                             
        $payload = array();
        $payload['async'] = ( isset($request->async) && $request->async == TRUE ) ? TRUE : FALSE;
        $payload['labels'] = $request->labels;      
        $result = $this->createBulkDownload($payload); 
                                      
        $finalResponse = array();        
        $finalResponse['id'] = $result->id;
        $finalResponse['created_at'] = $result->created_at;
        $finalResponse['updated_at'] = $result->updated_at;
        $finalResponse['status'] = $result->status;
        $finalResponse['files'] = $result->files;
        $finalResponse['labels'] = $result->labels;
        $finalResponse['invalid_labels'] = $result->invalid_labels;
                               
        header('Content-Type: application/json'); 
        return json_encode($finalResponse,JSON_PRETTY_PRINT);        
    }
}