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
            $pd = array('Postmen'=>'');
            $result = $shipmentManagerObj->calculateRateAction($request,$pd); 
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
      /**
       * 
       * @return ShipmentManager
       */    
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

    public function calculateRateAction($request, $pd=array())
    {      
        $this->request = $request; 
        if(!isset($pd['Postmen']))
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
                /*
                 *  There were two rate with same date and amount 
                    but for one booking cut off time was NULL and it's transit time was minimum                 
                 */
                $rate->shipper_account->slug = strtoupper($rate->shipper_account->slug);
                if(!$rate->booking_cut_off){continue;}
                               
                /**
                 * If postmen do not return rate for picked collection date then remove it from list
                 */
                if(strtotime($rate->pickup_deadline) < strtotime($this->request->ship_date))
                {continue;}
                
                if(in_array($rate->service_type, $notSupportdRates)){ continue;}                 
                $innerRate = array();
                $innerRate['rate']['id'] = '';
                $innerRate['rate']['carrier_name'] = $rate->shipper_account->slug;
                
                $serviceDetail = $this->getServiceCodeMapped($rate->service_type);   
                if(count($serviceDetail) == 0){continue;}
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
                
                //Calculating insurance showing on ui for hint                     
                $insurance_charge = 0;
                if(isset($this->request->insurance) && $this->request->insurance->value != '')
                {                
                    $insurance_charge = $this->calculateInsuranceAmount($this->request->insurance->value, $this->request->insurance->currency);
                }
                $innerRate['surcharges']['insurance_charge'] = $insurance_charge; 
                                
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
                    $tax_percentage = round(number_format(((100 * $taxAmt)/($rate->total_charge->amount - $taxAmt)), 2,'.', '')) ;
                        
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
                $innerRate['rate']['price'] = (number_format($baseAmt, 2,'.', ''));                
                $innerRate['surcharges']['fuel_surcharge'] =  (number_format($fuel_surchargeAmt, 2,'.', ''));
                $innerRate['surcharges']['long_length_surcharge'] = (number_format($long_length_surcharge, 2,'.', '')); 
                                
                $dimensions = array('length'=>'','width'=>'','height'=>'','unit'=>$rate->charge_weight->unit);
                $weight = array("weight"=>$rate->charge_weight->value,"unit"=>$rate->charge_weight->unit);
                $time = array("max_waiting_time"=>"","unit"=>"");                
                $innerRate['rate']['chargeable_weight'] = $rate->charge_weight->value;                
                $innerRate['rate']['rate_unit'] = $rate->charge_weight->unit;                
                $innerRate['service_options']["dimensions"] =$dimensions; 
                $innerRate['service_options']["weight"] =$weight; 
                $innerRate['service_options']["time"] =$time; 
                
                $innerRate['service_options']['others'] = array(
                    'pickup_deadline' => ($rate->pickup_deadline) ? date('Y-m-d H:i',strtotime($rate->pickup_deadline)):'NA',
                    'booking_cut_off' => ($rate->booking_cut_off) ? date('Y-m-d H:i',strtotime($rate->booking_cut_off)):'NA',
                    'delivery_date' => ($rate->delivery_date) ? date('Y-m-d H:i',strtotime($rate->delivery_date)) : 'NA',
                    'transit_time' => $rate->transit_time
                );
                                                   
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
                            $res = $this->changeRateWithMaxTransitTime($rates['rate'][$rate->shipper_account->slug][$length][$rate->shipper_account->id][$length1][$rate->service_type], $innerRate);                            
                            if($res !== "false")
                            {                                                                                                                               
                                unset($rates['rate'][$rate->shipper_account->slug][$length][$rate->shipper_account->id][$length1][$rate->service_type][$res]);                                                                                                                                
                                $rates['rate'][$rate->shipper_account->slug][$length][$rate->shipper_account->id][$length1][$rate->service_type][$res] = $innerRate;
                            }
                            else
                            {
                                $rates['rate'][$rate->shipper_account->slug][$length][$rate->shipper_account->id][$length1][$rate->service_type][] = $innerRate;
                            }                                                                                    
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
        $this->request = $request;  
        $fromAddress = $this->convertAddress($request->from);                
        $toAddress = $this->convertAddress($request->to);                            
        $package = $this->packagesToOrder($request->package,$request->currency);                                        
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
        if(isset($this->request->customs))
        { 
            if($this->request->customs->terms_of_trade == 'DAP')
            {
                $others['custom_paid_by'] = 'recipient';
            }
            if($this->request->customs->terms_of_trade == 'DAD')
            {
                $others['custom_paid_by'] = 'shipper';
            }
        }
        
        $others['account_number'] = (isset($request->billing_account->billing_account) && $request->billing_account->billing_account != '') ? $request->billing_account->billing_account : $shipperAccountId; 
        $others['type'] = 'account';        
        $others['currency'] = $request->currency;        
        $directlyCallForPostmen = ($request->directlyCallForPostmen == "false") ? FALSE : TRUE;        
        $serviceDetail = $this->getServiceCodeMapped($request->service,$directlyCallForPostmen); // ui se false api se true  
               
        $others['service_type'] = $serviceDetail['provServiceCode'];        
        $others['paper_size'] = (isset($request_options->size) && $request_options->size != '') ? $request_options->size : 'default';
        
        $tempRef = [];
        if($reference_id1 != ''){$tempRef[] = $reference_id1;}
        if($reference_id2 != ''){$tempRef[] = $reference_id2;}        
        $others['references'] = $tempRef;
                                        
        if(isset($request->insurance) && $request->insurance != '')
        {
            $others['insurance_detail'] = array(
                'amount'=>$request->insurance->value,
                'currency'=>$request->insurance->currency                
            );
        }
        
        $payload = $this->buildCreateLabelRequest($fromAddress, $toAddress, $package, $shipperAccountId,$others, $isDocument, $returnShipment,FALSE);                                                
        try 
        {   
            $labelResponse = $this->createLabel($payload);                        
            if($labelResponse)
            { 
                $this->formatCreateLabelResponse($labelResponse);
                if(!$directlyCallForPostmen)
                {
                    $res = $this->createAndSavePdf($request);
                    $res1 = $this->createPickup($request, $res);
                    exit(json_encode(array('label'=>$res1)));
                }
            }
            else
            {         
                $this->getErrorDetails();
            }            
            exit(json_encode($this->responseData)); 
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
        
        if(isset($labelResponse->files->invoice))
        {
            $this->responseData['label']['invoice_file_url'] = $labelResponse->files->invoice->url;
            $this->responseData['label']['invoice_base_encode'] = chunk_split(base64_encode(file_get_contents($labelResponse->files->invoice->url)));
        }
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
        exit(json_encode($this->responseData));        
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
        $this->responseData['status'] = 'error';        
        $this->responseData['message'] = (implode(',',$tempErrors)) ? implode(',',$tempErrors):'Unknown Error';
        $this->responseData['errorCode'] = PostMenMaster::UNKNOWN_ERROR;
        $this->responseData['errorMessage'] = (implode(',',$tempErrors)) ? implode(',',$tempErrors):'Unknown Error'; 
		$this->responseData['label'] = array(
				'status'=>'error',
				'message'=>(implode(',',$tempErrors)) ? implode(',',$tempErrors):'Unknown Error'
			);         
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
    
    public static function getAlpha2CodeFromAlpha3($alpha3)
    {        
        $res =  \v1\module\Database\Model\Countries::all()
            ->where('alpha3_code','=',$alpha3)                                             
            ->first();         
        return $res->alpha2_code;
    }
    
    public function createAndSavePdf($request)
    {        
        $labelArr = $this->responseData;                  
        if( isset($labelArr['label']) ) 
        {
            $libObj = new \Library();
            $pdf_base64 = $labelArr['label']['base_encode'];            
            $labels = explode(",", $labelArr['label']['file_url']);                                        
            $label_path = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/label/';                                                                        
            $loadIdentity = $request->loadIdentity;
            $carrier = strtolower($request->carrier);          
            $file_url = mkdir($label_path . $loadIdentity .'/'.$carrier.'/', 0777, true);
            
            foreach ($labels as $dataFile) {                
                $dataFile = $loadIdentity . '.pdf';                
                $file_name = $label_path . $loadIdentity .'/'.$carrier.'/'. $dataFile;
                $data = base64_decode($pdf_base64);
                file_put_contents($file_name, $data);
                header('Content-Type: application/pdf');
            }    
            
            $invoice_created = 0;
            if(isset($labelArr['label']['invoice_file_url']))
            {                
                $invoice_pdf_base64 = $labelArr['label']['invoice_base_encode'];
                $invoices = explode(",", $labelArr['label']['invoice_file_url']); 
                foreach ($invoices as $dataFile) {                
                    $dataFile = $loadIdentity.'-custom'.'.pdf';                
                    $inv_file_name = $label_path . $loadIdentity .'/'.$carrier.'/'. $dataFile;
                    $inv_data = base64_decode($invoice_pdf_base64);
                    file_put_contents($inv_file_name, $inv_data);
                    header('Content-Type: application/pdf');
                }
                
                $invoice_created = 1;
            }
                        
            $fileUrl = $libObj->get_api_url();
            unset($labelArr['label']['base_encode']);
            $res =  array(
                    "status" => "success",
                    "message" => "label generated successfully",                    
                    "file_loc"=>$file_name,                    
                    "file_url" => $fileUrl . "label/" . $loadIdentity . '/'.$carrier.'/' . $loadIdentity . '.pdf',                    
                    "tracking_number"=>$labelArr['label']['tracking_number'],
                    "label_files_png" => '',
                    "label_json" =>json_encode($labelArr),
                    "callFromPostmen" =>"true",
                    "invoice_created" => $invoice_created
            );  
           return $res;
        }        
    }
    
    public function getFormatedCancelLabelRequest($rawRequest)
    {        
        $carrierObj = new \Carrier();
        $labelInfo = $carrierObj->getLabelByLoadIdentity($rawRequest->load_identity);
        if( isset($labelInfo[0]['label_json']) && $labelInfo[0]['label_json'] != '' )
        {
            $labelArr = json_decode($labelInfo[0]['label_json']);
            $labelId = (isset($labelArr->label->id)) ? $labelArr->label->id : ''; 
            if($labelId == ''){return array("status"=>"error","message"=>"cancel request not completed by postmen");}
            $requestObj = new \stdClass(); 
            $requestObj->label = new \stdClass();
            $requestObj->label->id = $labelId;    
        }
        return $requestObj;
    }
    
    public static function preparePickupData($request)
    {                       
        $pickupRequest = array();
        $pickupRequest['credentials'] = (array)$request->credentials;
        $pickupRequest['carrier'] = $request->carrier;
        $pickupRequest['services'] = "";
        $pickupRequest['address'] = array( 
            "location_type" => ((boolean)$request->from->is_res == TRUE) ? 'R' : 'B',
            "package_location" => $request->pickup_detail->package_location,
            "company" => $request->from->company,
            "street1" => $request->from->street1,
            "street2" => $request->from->street2,
            "city" => $request->from->city,
            "state" => $request->from->state,
            "country" => self::getAlpha2CodeFromAlpha3($request->from->country),
            "zip" => $request->from->zip
        );
                                        
        $type_code = (strtotime(date('Y-m-d')) - strtotime($request->pickup_detail->pickup_date) == 0) ? 'S' : 'A';
        
        $pickupRequest['pickup_details'] = array(
            "pickup_date" => date('Y-m-d', strtotime($request->pickup_detail->pickup_date)),
            "ready_time" => $request->pickup_detail->earliest_pickup_time,
            "close_time" => $request->pickup_detail->latest_pickup_time,
            "number_of_pieces" => $request->pickup_detail->package_quantity,
            "instructions" => $request->pickup_detail->pickup_instruction,
			"type_codes" => $type_code,
			"package_location" => $request->pickup_detail->package_location,
			"confirmation_number" => (isset($request->pickup_detail->collectionjobnumber) && $request->pickup_detail->collectionjobnumber != '') ? $request->pickup_detail->collectionjobnumber :''
        );
        
        $pickupRequest['pickup_contact'] = array (
            "name" => $request->from->name,
            "phone" => $request->from->phone,
            "email" => $request->from->email
        );
         
        $pickupRequest['confirmation_number'] = '';
        $pickupRequest['method_type'] = 'post';
        $pickupRequest['pickup'] = '';                                                                                                    
        return $pickupRequest;
    }
    
    public function createPickup($request, $res)
    {
        $date = date('Y-m-d H:i:s');        
        $dhlApiObj = new \v1\module\RateEngine\core\dhl\DhlApi();
        $bkgModel = new \Booking_Model_Booking();        
        $providerInfo = $bkgModel->getProviderInfo('PICKUP',ENV,'PROVIDER',$request->carrier);
        
        $pickupRequest = self::preparePickupData($request);   //print_r($pickupRequest);die;                                     
        $formatedReq = $dhlApiObj->formatPickupData($pickupRequest);                                
        $formatedReq->callType = 'createpickup';
        $formatedReq->pickupEndPoint = $providerInfo['rate_endpoint'];
        $formatedReq->carrier = $request->carrier;
        
        $xmlRequest = $dhlApiObj->getPickupRequest($formatedReq);        
        $rawConfirmationDetail = $dhlApiObj->postDataToDhl($xmlRequest);
        $confirmationDetail = $dhlApiObj->formatPickupResponseData(json_encode($rawConfirmationDetail));
                                
        if(isset($confirmationDetail->pickup->confirmation_number) && $confirmationDetail->pickup->confirmation_number != '')
        { 
            $respDetail = $confirmationDetail->pickup;
            $pickupData['confirmation_number'] =  isset( $respDetail->confirmation_number ) ? $respDetail->confirmation_number : '';
            $pickupData['currency_code'] =  isset( $respDetail->currency_code ) ? $respDetail->currency_code : '';
            $pickupData['charge'] =  isset( $respDetail->charge ) ? $respDetail->charge : '';
            $pickupData['origin_service_area'] =  isset( $respDetail->origin_service_area ) ? $respDetail->origin_service_area : '';
            $pickupData['ready_time'] =  isset( $respDetail->ready_time ) ? $respDetail->ready_time : '';
            $pickupData['second_time'] =  isset( $respDetail->second_time ) ? $respDetail->second_time : '';
            $pickupData['status'] =  isset($data->status) ? $data->status : 1;             
            $pickupData['carrier_id'] =  $this->getCarrierIdByName($request->carrier);
            $pickupData['company_id'] =  $request->company_id;
            $pickupData['customer_id'] =  $request->customer_id;
            $pickupData['account_number'] =  $pickupRequest['credentials']['account_number'];
            $pickupData['user_id'] =  $request->collection_user_id;
            $pickupData['address_line1'] = $pickupRequest['address']['street1'];
            $pickupData['address_line2'] = $pickupRequest['address']['street2'];
            $pickupData['city'] = $pickupRequest['address']['city'];
            $pickupData['state'] = $pickupRequest['address']['state'];
            $pickupData['country'] = $pickupRequest['address']['country'];
            $pickupData['postal_code'] = $pickupRequest['address']['zip'];
            $pickupData['address_type'] = $pickupRequest['address']['location_type'];
            $pickupData['package_quantity'] = $pickupRequest['pickup_details']['number_of_pieces'];
            $pickupData['package_type'] = "Package";
            $pickupData['is_overweight'] = "";
            $pickupData['package_location'] = (isset($pickupRequest['pickup_details']['package_location'])) ? $pickupRequest['pickup_details']['package_location']:'Front Desk';
            $pickupData['pickup_date'] = $pickupRequest['pickup_details']['pickup_date'];
            $pickupData['earliest_pickup_time'] = $pickupRequest['pickup_details']['ready_time'];
            $pickupData['latest_pickup_time'] = $pickupRequest['pickup_details']['close_time'];
            $pickupData['pickup_reference'] = "";
            $pickupData['instruction_todriver'] = $pickupRequest['pickup_details']['instructions'];						            
            $pickupData['name'] = $pickupRequest['pickup_contact']['name'];						
            $pickupData['company_name'] = $pickupRequest['address']['company'];						
            $pickupData['phone'] = $pickupRequest['pickup_contact']['phone'];						
            $pickupData['email'] = $pickupRequest['pickup_contact']['email'];						                                                
            $pickupData['status'] = 1;                                    
            $pickupData['created'] =  $date;
            $pickupData['updated'] =  $date;
            $pickupData['carrier_code'] =  $request->carrier;
                                                
            $this->db->startTransaction();            
            
            $pickupId = $this->db->save("pickups", $pickupData);   
            
            $this->db->commitTransaction();
            
            $label_json_arr = json_decode($res['label_json']);
            $label_json_arr->label->collectionjobnumber = $confirmationDetail->pickup->confirmation_number;            
            $label_json_arr->label->collectionstatus = 'created';            
            $res['label_json'] = json_encode($label_json_arr);                                                            
        }
        else
        {
            $label_json_arr = json_decode($res['label_json']);             
            $cNo = (isset($pickupRequest['pickup_details']['confirmation_number'])  && $pickupRequest['pickup_details']['confirmation_number'] != '' ) ? $pickupRequest['pickup_details']['confirmation_number'] : '';
            $cStatus = ($cNo != '') ? 'created':'failed';                        
            $label_json_arr->label->collectionjobnumber = $cNo;            
            $label_json_arr->label->collectionstatus = $cStatus;
            $res['label_json'] = json_encode($label_json_arr);
        }
        return $res;
    }
    
    public function calculateInsuranceAmount($amount, $currency)
    {
        $calculatedAmount = ( $amount * 1.5 ) / 100;                 
        if($calculatedAmount <= 12)
        {
            return 12;
        }
        else
        {
            return $calculatedAmount;
        } 
    }
    
    /**
     * This function check if innerRate(before push) has same rate 
     * and it's transit time is more than existing same price rate then return index to unset 
     * otherwise nothing to do means return "false"
     * @param Array $servicRates          is array of rate within particular service
     * @param Array $innerRate  is rate
     * @return String|Integer
     */
    public function changeRateWithMaxTransitTime($servicRates, $innerRate)
    {            
        $res = "false";
        if(count($servicRates) > 0)
        {
            foreach($servicRates as $key => $servicRate)
            {                                
                if($servicRate['rate']['price'] == $innerRate['rate']['price'])
                {
                    if($servicRate['service_options']['others']['transit_time'] > $innerRate['service_options']['others']['transit_time'])
                    {                        
                      continue;  
                    }
                    else
                    {
                        $res = $key;
                    }
                }
                
                if($res != "false")
                {
                    return $res;
                }
            }
        }        
        return $res;
    }
    
}
