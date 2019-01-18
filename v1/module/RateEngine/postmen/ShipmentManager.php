<?php
namespace v1\module\RateEngine\postmen;

class ShipmentManager extends PostMenMaster
{    
    private static $shipmentManagerObj = NULL;
    protected $db = NULL;
    protected $responseData = [];
    
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
        $app->post('/postmen/updateShipperInfo', function () use ($app) {              
            $request = json_decode($app->request->getBody());             
            $shipmentManagerObj = self::getShipmentManagerObj();                        
            $result = $shipmentManagerObj->updateShipperInfoAction($request); 
            echo $result;die;
        });
        $app->post('/postmen/deleteShipperAc', function () use ($app) {              
            $request = json_decode($app->request->getBody());             
            $shipmentManagerObj = self::getShipmentManagerObj();                        
            $result = $shipmentManagerObj->deleteShipperAcAction($request); 
            echo $result;die;
        });
    }
          
    public static function getShipmentManagerObj()
    {
        if(!self::$shipmentManagerObj instanceof ShipmentManager)
        {           
            self::$shipmentManagerObj = new ShipmentManager(); 
            self::$shipmentManagerObj->db = new \DbHandler();
        }
        return self::$shipmentManagerObj;
    }
    
    public function getServiceCodeMapped($providerCode,$mappedByRemote=TRUE)
    { 
        $query1 = "SELECT scm.id, scm.`service_id`, scm.`provider_id`, scm.`service_name` as provServiceName, scm.service_code as provServiceCode, scm.service_type
                    , cvs.service_code, cvs.service_name
                    FROM `".DB_PREFIX."service_code_mapping` as scm 
                    inner join ".DB_PREFIX."courier_vs_services as cvs
                    on scm.service_id = cvs.id 
                    WHERE scm.service_type = 'service'                    
                    ";                                                                                   
                    $query1 .= ( $mappedByRemote ) ? " and scm.service_code = '$providerCode'" : " and cvs.service_code = '$providerCode'"; 
                    //echo $query1;die;
                $results = $this->db->getAllRecords($query1);
                if(count($results) > 0)
                {
                    return $results[0];
                }
                return array();
    }

    public function calculateRateAction($request, $pd)
    {       
        if( !(isset($pd['Postmen'])) || !(count($pd['Postmen'])) )
        {
            return json_encode(array());
        }
        $fromAddress = $this->convertAddress($request->from);                
        $toAddress = $this->convertAddress($request->to);         
        $package = $this->packagesToOrder($request->package,$request->currency);                
        $shipper_accounts_temp = $this->carrierAccounts($request->carriers); 
        
        $servicesToShow = $this->getServicesToShow($request->carriers);
        
        
        $newShiperAc = [];
        $shipper_accounts = [];
        if(count($shipper_accounts_temp) > 0)
        {
            foreach($shipper_accounts_temp as $shipper_account)
            {
                $newShiperAc[$shipper_account['id']] = $shipper_account['RequestedAcId'];
                unset($shipper_account['RequestedAcId']);
                $shipper_accounts[] = $shipper_account;
            }            
        }
        
        $isDocument = (strtolower($request->extra->is_document) == "true") ? TRUE : FALSE;        
        $payload = $this->buildRequestToCalculateRate($fromAddress, $toAddress, $package, $shipper_accounts, $isDocument,FALSE);  
        try 
        {            
            $rawRates = $this->calculateRates($payload);                         
            header('Content-Type: application/json'); 
            if($rawRates)
            {
                $formatedRates = $this->formatRate($rawRates,$newShiperAc);                
                return json_encode($formatedRates);            
            }
            else
            {
                $this->getErrorDetails();                
                return json_encode($this->responseData); 
            }
        }
        catch(exception $e) 
        {            
            echo $e->getCode() .$e->getMessage(). "\n";                   
        }                                        
    }
            
    public function formatRate($rawRates, $newShiperAc)
    {                           
        $notSupportdRates = array('dhl_express_easy');        
        $rates['rate'] = array(); 
        if (count($rates) > 0) 
        {       
            $differentAcIds = array();
            foreach($rawRates as $rate)
            {              
                if(in_array($rate->service_type, $notSupportdRates)){ continue;}                 
                $innerRate = array();
                $innerRate['rate']['id'] = '';
                $innerRate['rate']['carrier_name'] = $rate->shipper_account->slug;
                
                $serviceDetail = $this->getServiceCodeMapped($rate->service_type);                   
                $innerRate['rate']['service_name'] = $serviceDetail['service_name'];
                $innerRate['rate']['service_code'] = $serviceDetail['service_code'];
                $rate->service_type = $serviceDetail['service_code'];
                                                                                
                $innerRate['rate']['rate_type'] = (in_array($rate->charge_weight->unit, array('kg','g','oz','lb'))) ? 'Weight':'#';
                $innerRate['rate']['rate_unit'] = strtoupper($rate->charge_weight->unit);                
                
                $rate->shipper_account->id = $newShiperAc[$rate->shipper_account->id];
                $innerRate['rate']['act_number'] = $rate->shipper_account->id; 
                
                $innerRate['surcharges']['remote_area_surcharge'] = 0; 
                $innerRate['surcharges']['long_length_surcharge'] = 0; 
                $innerRate['surcharges']['manual_handling_surcharge'] = 0; 
                $innerRate['surcharges']['extrabox_surcharge'] = 0; 
                $innerRate['surcharges']['overweight_surcharge'] = 0; 
                $innerRate['surcharges']['isle_weight_surcharge'] = 0; 
                $innerRate['surcharges']['insurance_charge'] = 0; 
                                
                $fuel_surchargeAmt = 0;
                $taxAmt = 0;
                $baseAmt = 0; 
                $long_length_surcharge = 0;
                if(count($rate->detailed_charges) > 0)
                {                    
                    foreach($rate->detailed_charges as $detailed_charge)
                    {                        
                        if($detailed_charge->type == 'tax')
                        {
                            $taxAmt = $detailed_charge->charge->amount;                            
                            break;
                        }                        
                    }
                        
                    $tax_percentage = 0;
                    $tax_percentage = round(number_format(((100 * $taxAmt)/($rate->total_charge->amount - $taxAmt)), 2)) ;
                        
                    foreach($rate->detailed_charges as $detailed_charge)
                    {                                                                                                                                                
                        if($detailed_charge->type == 'base')
                        {
                            $baseAmt = $detailed_charge->charge->amount;                            
                            $baseAmt = $baseAmt * (100/(100+$tax_percentage));
                        }
                        
                        if($detailed_charge->type == 'fuel_surcharge')
                        {
                            $fuel_surchargeAmt = $detailed_charge->charge->amount;
                            $fuel_surchargeAmt = $fuel_surchargeAmt * (100/(100+$tax_percentage)); 
                        }
                        
                        if($detailed_charge->type == 'oversize_piece_(dimension)')
                        {
                            $long_length_surcharge = $detailed_charge->charge->amount;
                            $long_length_surcharge = $long_length_surcharge * (100/(100+$tax_percentage));                                                         
                        }
                    }
                } 
                $innerRate['rate']['price'] = (number_format($baseAmt, 2));                
                $innerRate['surcharges']['fuel_surcharge'] =  (number_format($fuel_surchargeAmt, 2));
                $innerRate['surcharges']['long_length_surcharge'] = (number_format($long_length_surcharge, 2)); 
                                
                $dimensions = array('length'=>'','width'=>'','height'=>'','unit'=>$rate->charge_weight->unit);
                $weight = array("weight"=>$rate->charge_weight->value,"unit"=>$rate->charge_weight->unit);
                $time = array("max_waiting_time"=>"","unit"=>"");
                
                $innerRate['service_options']["dimensions"] =$dimensions; 
                $innerRate['service_options']["weight"] =$weight; 
                $innerRate['service_options']["time"] =$time; 
                                                   
                $innerRate['taxes'] = array("total_tax"=>$taxAmt,"tax_percentage"=>$tax_percentage);
                                                    
                $tempServiceType[$rate->shipper_account->id][] = $rate->service_type;                                                                                                                                            
                $differentAcIds[$rate->shipper_account->id] = array_unique($tempServiceType[$rate->shipper_account->id]);                                     
                $length = array_search($rate->shipper_account->id, array_keys($differentAcIds));
                $length1 = array_search($rate->service_type, $differentAcIds[$rate->shipper_account->id]);                
                
                if(isset($rates['rate'][$rate->shipper_account->slug]))
                {  
                    if(isset($rates['rate'][$rate->shipper_account->slug][$length][$rate->shipper_account->id]))
                    {                      
                        if(isset($rates['rate'][$rate->shipper_account->slug][$length][$rate->shipper_account->id][$length1][$rate->service_type]))
                        {                            
                            $rates['rate'][$rate->shipper_account->slug][$length][$rate->shipper_account->id][$length1][$rate->service_type][] = $innerRate;
                        }
                        else
                        {                                                     
                            $rates['rate'][$rate->shipper_account->slug][$length][$rate->shipper_account->id][$length1][$rate->service_type][] = $innerRate;                                                        
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
    
    public function getServicesToShow($carriers)
    {
        $servicesToShow = [];
        if(count($carriers) > 0)
        {
            foreach($carriers as $carrier)
            {
                $carrierName = $carrier->name;                                                 
                $carrierAccounts = $carrier->account;
                if(count($carrierAccounts) > 0)
                {
                    foreach($carrierAccounts as $carrierAccount)
                    {                         
                        $services = explode(',',$carrierAccount->services);  
                        $servicesToShow[$carrierName] = $services;
                    }
                }                                                
            }
        }          
        return $servicesToShow; 
    }


    public function createLabelAction($request)
    {                               
        $fromAddress = $this->convertAddress($request->from);                
        $toAddress = $this->convertAddress($request->to);    
        
        //For Address test
        $fromAddress['contact_name'] = 'Nikhil Kumar';
        $fromAddress['phone']= '7595590074';
        $fromAddress['company_name']= 'Perceptive Consulting Solutions';
        $fromAddress['street1'] = '6 York Way';
        $fromAddress['city']= 'Northampton';
        $fromAddress['state']= 'KZ';
        $fromAddress['country'] = 'GBR';
        $fromAddress['email']= 'test@test.test'; 
        $fromAddress['type']= 'business'; 
        $fromAddress['postal_code']= 'NN5 6UX';
        
        
        $toAddress['contact_name'] = 'Rick McLeod (RM Consulting)';
        $toAddress['phone']= '1-403-504-5496';
        $toAddress['company_name']= 'IHS Markit';
        $toAddress['street1'] = '71 Terrace Crescent NE';
        $toAddress['city']= 'Medicine Hat';
        $toAddress['state']= 'Alberta';
        $toAddress['country'] = 'CAN';
        $toAddress['email']= 'test@test.test'; 
        $toAddress['type']= 'business'; 
        $toAddress['postal_code']= 'T1C1Z9';
        
        
        $package = $this->convertPackage($request->package[0],$request->currency);                                         
        $isDocument = (isset($request->extra->is_document) && strtolower($request->extra->is_document) == "true") ? TRUE : FALSE;        
        $returnShipment = (isset($request->extra->return_shipment) && strtolower($request->extra->return_shipment) == "true") ? TRUE : FALSE;
        $reference_id1 = (isset($request->extra->reference_id) && $request->extra->custom_desciption != '') ? $request->extra->custom_desciption : '';
        $reference_id2 = (isset($request->extra->reference_id2) && $request->extra->custom_desciption2 != '') ? $request->extra->custom_desciption2 : '';
        
        
        $carrierName = $request->carrier;
        $carrierId = $this->getCarrierIdByName($carrierName);
        $credentials = $request->credentials;
        $carrierAccountDetails = $this->getCarrierAccount($carrierId, $credentials->username, $credentials->password, $credentials->account_number);        
        $shipper_accounts = array('id'=>$carrierAccountDetails->carrierAccount);
                                             
        $shipperAccountId = $shipper_accounts['id'];                                    
        $others = array();
        $others['paid_by'] = 'shipper';
        $others['custom_paid_by'] = 'recipient';
        $others['account_number'] = (isset($request->billing_account->billing_account) && $request->billing_account->billing_account != '') ? $request->billing_account->billing_account : ''; 
        $others['type'] = 'account';
        $others['purpose'] = 'merchandise';                
        $serviceDetail = $this->getServiceCodeMapped($request->service,FALSE); // ui se false api se true  
                
        $others['service_type'] = $serviceDetail['provServiceCode'];        
        $others['paper_size'] = (isset($request_options->size) && $request_options->size != '') ? $request_options->size : 'default';
        
        $tempRef = [];
        if($reference_id1 != ''){$tempRef[] = $reference_id1;}
        if($reference_id2 != ''){$tempRef[] = $reference_id2;}        
        $others['references'] = $tempRef;
                        
        $payload = $this->buildCreateLabelRequest($fromAddress, $toAddress, $package, $shipperAccountId,$others, $isDocument, $returnShipment,FALSE);                                                
        try 
        {             
            $labelResponse = $this->createLabel($payload); 
            if($labelResponse)
            {
                $this->formatCreateLabelResponse($labelResponse);
            }
            else
            {                
                $this->getErrorDetails();
            }            
            header('Content-Type: application/json'); 
            return json_encode($this->responseData); 
        }
        catch(exception $e) 
        {            
            echo $e->getCode() .$e->getMessage(). "\n";                   
        }                                        
    }
    
    public function formatCreateLabelResponse($labelResponse)
    { 
        $this->responseData['label'] = [];                
        $tracking_numbers = implode(',', $labelResponse->tracking_numbers);
        $this->responseData['label']['id'] = $labelResponse->id;
        $this->responseData['label']['tracking_number'] = $tracking_numbers;                        
        $this->responseData['label']['file_url'] = $labelResponse->files->label->url;
        $this->responseData['label']['total_cost'] = $labelResponse->rate->total_charge->amount;
        $this->responseData['label']['weight_charge'] = $labelResponse->rate->charge_weight->value;
        $this->responseData['label']['fuel_surcharge'] = 0;
        $this->responseData['label']['remote_area_delivery'] = 0;
        $this->responseData['label']['insurance_charge'] = 0;
        $this->responseData['label']['over_sized_charge'] = 0;
        $this->responseData['label']['over_weight_charge'] = 0;
        $this->responseData['label']['discounted_rate'] = 0;
        $this->responseData['label']['product_content_code '] = 0;
        $this->responseData['label']['license_plate_number '] = [];
        $this->responseData['label']['chargeable_weight '] = '';
        $this->responseData['label']['service_area_code '] = '';                                                    
        $this->responseData['label']['base_encode'] = chunk_split(base64_encode(file_get_contents($labelResponse->files->label->url)));
        return;
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
        if($result)
        {                      
            $this->responseData['id'] = $result->id;
            $this->responseData['status'] = $result->status;
            $this->responseData['label']['id'] = $result->label->id;
            $this->responseData['created_at'] = $result->created_at;
            $this->responseData['updated_at'] = $result->updated_at;
        }
        else
        { 
            $this->getErrorDetails();                     
        }           
        header('Content-Type: application/json'); 
        return json_encode($this->responseData);
    }
    
    public function getErrorDetails()
    {
        $tempErrors = [];        
        $errors = $this->api->getError()->getDetails();             
        if(count($errors) > 0)
        {
            foreach ($errors as $error) {
                $tempErrors[] = $error->info;
            }                                
        }            
        $this->responseData['errorCode'] = PostMenMaster::UNKNOWN_ERROR;
        $this->responseData['errorMessage'] = (implode(',',$tempErrors)) ? implode(',',$tempErrors):'Unknown Error'; 
        return $this->responseData;
    }

    public function createManifestAction($request)
    {
        $carrierName = $request->carrier;
        $carrierId = $this->getCarrierIdByName($carrierName);
        $credentials = $request->credentials;
        $carrierAccountDetails = $this->getCarrierAccount($carrierId, $credentials->username, $credentials->password, $credentials->account_number);
        $shipper_accounts = array('id'=>$carrierAccountDetails->carrierAccount);                
        $payload = array();
        $payload['async'] = (isset($request->async) && $request->async == "true") ? TRUE: FALSE;
        $payload['shipper_account'] = $shipper_accounts;
               
        $result = $this->createManifest($payload); 
        if($result && $result->status == 'manifested')
        {            
            $this->responseData = [];
            $this->responseData['id'] = $result->id;
            $this->responseData['labels'] = $result->labels;
            $this->responseData['files']['manifest'] = $result->files->manifest;
            $this->responseData['shipper_account'] = $result->shipper_account;
            $this->responseData['shipper_account']->id = $credentials->account_number;        
            $this->responseData['status'] = $result->status;        
            $this->responseData['created_at'] = $result->created_at;
            $this->responseData['updated_at'] = $result->updated_at;
        }
        else
        {
            $this->getErrorDetails();
        }
        
        header('Content-Type: application/json'); 
        return json_encode($this->responseData);        
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
        if($result)
        {            
            $this->responseData['id'] = $result->id;
            $this->responseData['description'] = $result->description;
            $this->responseData['carrier'] = $result->slug;
            $this->responseData['status'] = $result->status;
            $this->responseData['timezone'] = $result->timezone;
            $this->responseData['type'] = $result->type;
            $this->responseData['created_at'] = $result->created_at;
            $this->responseData['updated_at'] = $result->updated_at;
            $this->responseData['address'] = $result->address;
        }
        else
        {
            $this->getErrorDetails();
        }
        
        header('Content-Type: application/json'); 
        return json_encode($this->responseData);        
    }
    
    public function deleteShipperAcAction($request)
    {        
        $carrierName = $request->carrier;
        $carrierId = $this->getCarrierIdByName($carrierName);        
        $shipperAccountRes = $this->getCarrierAccount($carrierId,$request->credentials->username,$request->credentials->password,$request->credentials->account_number);                
        $shipper_account = array('id'=> $shipperAccountRes->carrierAccount); 
        $payload = array();
        
        $result = $this->deleteShipperAc($shipper_account['id']);
        $responseArr = json_decode($result['response']);
        if($responseArr->meta->code == 200)
        { 
            $this->responseData = [];
            $this->responseData['code'] = $responseArr->meta->code;
            $this->responseData['message'] = $responseArr->meta->message;
            $this->responseData['details'] = $responseArr->meta->details;
            $this->responseData['data']['id'] = $responseArr->data->id;
        }
        else
        {
            $this->responseData = [];
            $this->responseData['code'] = $responseArr->meta->code;
            $this->responseData['message'] = $responseArr->meta->message;
            $this->responseData['details'] = $responseArr->meta->details;
        }
        header('Content-Type: application/json'); 
        return json_encode($this->responseData);
    }
    
    public function updateShipperAcAction($request)
    {
        $carrierName = $request->carrier;
        $carrierId = $this->getCarrierIdByName($carrierName);        
        $shipperAccountRes = $this->getCarrierAccount($carrierId,$request->credentials->username,$request->credentials->password,$request->credentials->account_number);                
        $shipper_account_id =  (isset($shipperAccountRes->carrierAccount)) ? $shipperAccountRes->carrierAccount:'';         
                        
        $payload = array();
        $payload['account_number'] = $request->credentials->account_number;
        $payload['password'] = $request->credentials->password;
        $payload['site_id'] = $request->credentials->site_id;
        
        $result = $this->updateShipperAc($payload, $shipper_account_id);
        $responseArr = json_decode($result['response']);
        if($responseArr->meta->code == 200)
        {
            $this->responseData = [];
            $this->responseData['code'] = $responseArr->meta->code;
            $this->responseData['message'] = $responseArr->meta->message;
            $this->responseData['details'] = $responseArr->meta->details;
            $this->responseData['data']['id'] = $responseArr->data->id;
        }
        else
        {
            $this->responseData = [];
            $this->responseData['code'] = $responseArr->meta->code;
            $this->responseData['message'] = $responseArr->meta->message;
            $this->responseData['details'] = $responseArr->meta->details;
        }
        header('Content-Type: application/json'); 
        return json_encode($this->responseData);
    }

    public function updateShipperInfoAction($request)
    {        
        $carrierName = $request->carrier;
        $carrierId = $this->getCarrierIdByName($carrierName);        
        $shipperAccountRes = $this->getCarrierAccount($carrierId,$request->credentials->username,$request->credentials->password,$request->credentials->account_number);                
        $shipper_account = array('id'=> $shipperAccountRes->carrierAccount); 

        $payload = array();
        $payload['description'] = $request->description;    
        $payload['timezone'] = $request->timezone;    
        
        $tempAddress = [];
        if(isset($request->address->country) && $request->address->country != '')
        {
            $tempAddress['country'] = $request->address->country;
        }
        if(isset($request->address->contact_name) && $request->address->contact_name != '')
        {
            $tempAddress['contact_name'] = $request->address->contact_name;
        }
        if(isset($request->address->street1) && $request->address->street1 != '')
        {
            $tempAddress['street1'] = $request->address->street1;
        }
        if(isset($request->address->street3) && $request->address->street3 != '')
        {
            $tempAddress['street3'] = $request->address->street3;
        }
        if(isset($request->address->company_name) && $request->address->company_name != '')
        {
            $tempAddress['company_name'] = $request->address->company_name;
        }
        if(isset($request->address->state) && $request->address->state != '')
        {
            $tempAddress['state'] = $request->address->state;
        }
        if(isset($request->address->postal_code) && $request->address->postal_code != '')
        {
            $tempAddress['postal_code'] = $request->address->postal_code;
        }
        if(isset($request->address->tax_id) && $request->address->tax_id != '')
        {
            $tempAddress['tax_id'] = $request->address->tax_id;
        }
        if(isset($request->address->fax) && $request->address->fax != '')
        {
            $tempAddress['fax'] = $request->address->fax;
        }
        if(isset($request->address->email) && $request->address->email != '')
        {
            $tempAddress['email'] = $request->address->email;
        }
        if(isset($request->address->street2) && $request->address->street2 != '')
        {
            $tempAddress['street2'] = $request->address->street2;
        }
        if(isset($request->address->city) && $request->address->city != '')
        {
            $tempAddress['city'] = $request->address->city;
        }
        if(isset($request->address->type) && $request->address->type != '')
        {
            $tempAddress['type'] = $request->address->type;
        }                
        $payload['address'] = $tempAddress;
                
        $result = $this->updateShipperInfo($payload, $shipper_account);
        $responseArr = json_decode($result['response']);
        if($responseArr->meta->code == 200)
        {
            $this->responseData = [];
            $this->responseData['code'] = $responseArr->meta->code;
            $this->responseData['message'] = $responseArr->meta->message;
            $this->responseData['details'] = $responseArr->meta->details;

            $this->responseData['id'] = $responseArr->data->id;
            $this->responseData['description'] = $responseArr->data->description;
            $this->responseData['slug'] = $responseArr->data->slug;
            $this->responseData['status'] = $responseArr->data->status;
            $this->responseData['timezone'] = $responseArr->data->timezone;
            $this->responseData['type'] = $responseArr->data->type;
            $this->responseData['created_at'] = $responseArr->data->created_at;
            $this->responseData['updated_at'] = $responseArr->data->updated_at;
            $this->responseData['address'] = array(
                'country'=>$responseArr->data->address->country,
                'contact_name'=>$responseArr->data->address->contact_name,
                'phone'=>$responseArr->data->address->phone,
                'fax'=>$responseArr->data->address->fax,
                'email'=>$responseArr->data->address->email,
                'company_name'=>$responseArr->data->address->company_name,
                'street1'=>$responseArr->data->address->street1,
                'street2'=>$responseArr->data->address->street2,
                'street3'=>$responseArr->data->address->street3,
                'city'=>$responseArr->data->address->city,
                'state'=>$responseArr->data->address->state,
                'postal_code'=>$responseArr->data->address->postal_code,
                'type'=>$responseArr->data->address->type,
                'tax_id'=>$responseArr->data->address->tax_id
            );

            $this->responseData['extra_info'] = $responseArr->data->extra_info;                                
        }
        else
        { 
            $this->responseData = [];
            $this->responseData['code'] = $responseArr->meta->code;
            $this->responseData['message'] = $responseArr->meta->message;
            $this->responseData['details'] = $responseArr->meta->details;
        }

        header('Content-Type: application/json'); 
        return json_encode($this->responseData);        
    }
            
    public function createBulkDownloadAction($request)
    {                             
        $payload = array();
        $payload['async'] = ( isset($request->async) && $request->async == TRUE ) ? TRUE : FALSE;
        $payload['labels'] = $request->labels;      
        $result = $this->createBulkDownload($payload); 
        if($result)
        {                                                     
            $this->responseData['id'] = $result->id;
            $this->responseData['created_at'] = $result->created_at;
            $this->responseData['updated_at'] = $result->updated_at;
            $this->responseData['status'] = $result->status;
            $this->responseData['files'] = $result->files;
            $this->responseData['labels'] = $result->labels;
            $this->responseData['invalid_labels'] = $result->invalid_labels;
        }
        else
        {
            $this->getErrorDetails();
        }
        header('Content-Type: application/json'); 
        return json_encode($this->responseData);        
    }
    
    public static function getAlpha3CodeFromAlpha2($alpha2)
    {        
        $res =  \v1\module\Database\Model\Countries::all()
            ->where('alpha2_code','=',$alpha2)                                             
            ->first();         
        return $res->alpha3_code;
    }
}