<?php
/**
 * Created by PhpStorm.
 * User: perce
 * Date: 22-11-2018
 * Time: 05:20 PM
 */ 

namespace v1\module\RateEngine\postmen;
use Postmen\Postmen;

abstract class PostMenMaster extends Postmen
{    
    protected $db = NULL;    
    protected static $apikey = 'b5585973-d041-4c4a-9b1f-014bf56e65e7';//pro#b353df5f-6bfc-4bc3-bb5e-ed82d5cf6c4c san#b5585973-d041-4c4a-9b1f-014bf56e65e7            
    protected static $region = 'sandbox';    //sandbox  //production
    protected $api = NULL;
    
    protected $baseUrl = 'https://sandbox-api.postmen.com';    
    protected $headers = array(
       "content-type: application/json",
       "postmen-api-key: b5585973-d041-4c4a-9b1f-014bf56e65e7"
    );    
    const UNKNOWN_ERROR = 4104;
    
    public function __construct()
    {         
        $this->db = new \DbHandler(); 
        $this->api = new Postmen(self::$apikey, self::$region);        
    }
    
    public function convertAddress($address)
    {       
        $finalAddress = [];                
        if( (isset($address->name)) && $address->name != '')
        {
            $finalAddress['contact_name'] = $address->name;
        }
        
        if( (isset($address->phone)) && $address->phone != '')
        {
            $finalAddress['phone']= $address->phone;
        }
        
        if( (isset($address->company_name)) && $address->company_name != '')
        {
            $finalAddress['company_name']= $address->company;
        }
        if( (isset($address->street1)) && $address->street1 != '')
        {
            $finalAddress['street1'] = $address->street1;
        }
        if( (isset($address->city)) && $address->city != '')
        {
            $finalAddress['city']= ($address->city == 'England') ? 'Oxford' : $address->city;
        } 
        if( (isset($address->state)) && $address->state != '')
        {
            $finalAddress['state']= $address->state;
        }
        if( (isset($address->country)) && $address->country != '')
        {          
            $finalAddress['country'] = (strlen($address->country) == 2) ? ShipmentManager::getAlpha3CodeFromAlpha2($address->country) : $address->country ;
        }
        
        $finalAddress['email']= ( isset($address->email) && $address->email != '' ) ? $address->email : 'test@test.test';  // need discussion
        $finalAddress['type']= ( isset($address->type) && $address->type != '' ) ? $address->type : 'business';  // need discussion     
        if( (isset($address->zip)) && $address->zip != '')
        {
             $finalAddress['postal_code']= $address->zip;
        }                    
        return $finalAddress;
    }
    
    public function convertPackage($package,$currency)
    {                     
        $finalPackage = [];
        $finalPackage['description'] = ($package->packaging_type == 'CP') ? 'custom' : $package->packaging_type;
        $finalPackage['box_type'] = ($package->packaging_type == 'CP') ? 'custom' : $package->packaging_type;
        $finalPackage['weight'] = array(
            'value'=> (float)$package->weight,
            'unit'=> strtolower($package->weight_unit)
        );
        $finalPackage['dimension'] = array(
            'width'=> (float)$package->width,
            'height'=> (float)$package->height,
            'depth'=> (float)$package->length,
            'unit'=> strtolower($package->dimension_unit)
        );        
        $finalPackage['items'][] = array(
            'description' => ($package->packaging_type == 'CP') ? 'custom' : $package->packaging_type,
            'quantity' => 1,
            'price' => array(
                'amount'=>0.01, //Need to discussed
                'currency'=>$currency
            ),
            'weight' => array(
                'value'=>   (float)$package->weight,
                'unit'=>   strtolower($package->weight_unit)
            )
        );                                                 
        return $finalPackage;
    }
    
    public function packagesToOrder($packages,$currency)
    {
        $orders=[];
        foreach ($packages as $i=>$package)
        {
            $orders[]=$this->convertPackage($package,$currency);
        }
        return $orders;
    }
    
    public function carrierAccounts($carriers)
    {                       
        $account_numbers = [];
        if(count($carriers) > 0)
        {
            foreach($carriers as $carrier)
            {
                $carrierName = $carrier->name;                 
                $carrierId = $this->getCarrierIdByName($carrierName);                
                $carrierAccounts = $carrier->account;
                if(count($carrierAccounts) > 0)
                {
                    foreach($carrierAccounts as $carrierAccount)
                    {                         
                        $credentials = $carrierAccount->credentials;  
                        $carrierAccountDetails = $this->getCarrierAccount($carrierId,$credentials->username,$credentials->password,$credentials->account_number);                                                
                        if(isset($carrierAccountDetails->carrierAccount) && $carrierAccountDetails->carrierAccount != '')
                        {                            
                            $tmp = [];
                            $tmp['id'] = $carrierAccountDetails->carrierAccount;
                            $tmp['RequestedAcId'] = $credentials->account_number;
                            $account_numbers[] = $tmp ;                       
                        }
                    }
                }                                                
            }
        }             
        return $account_numbers; 
    }
    
    public function getCarrierAccount($carrierId,$username,$password,$account_number)
    {                 
        return \v1\module\Database\Model\CourierVsCompanyModel::all()
            ->where('username','=',$username)
            ->where('password','=',$password)
            ->where('account_number','=',$account_number)            
            ->where('courier_id','=',$carrierId)                                    
            ->first();                                        
    }
    
    public function processRates()
    {
        
    }
    
    public function buildRequestToCalculateRate($fromAddress, $toAddress, $package, $shipper_accounts, $isDocument=FALSE, $async=FALSE)
    {
        $payload = array();                                                
        $payload['async'] = $async; 
        $payload['shipper_accounts'] = $shipper_accounts; 
        $payload['shipment'] = array(
                                    'parcels'=>$package,
                                    'ship_from'=>$fromAddress,
                                    'ship_to'=>$toAddress
                                );        
        $payload['is_document'] = $isDocument; 
        return $payload;
    }
             
    public function calculateRates($payload)
    {        
        $conifg['safe'] = TRUE;
        $result = $this->api->create('rates', $payload, $conifg);            
        return ($result) ? $result->rates : $result;
    }
            
    public function getCarrierIdByName($courierName)
    {          
        $sql = "SELECT id FROM " . DB_PREFIX . "courier WHERE name = '$courierName' limit 0,1";
        $res = $this->db->getRowRecord($sql); 
        return($res == NULL) ? 0 : $res['id'];        
    }
    
    public function buildCreateLabelRequest(
        $fromAddress
        ,$toAddress
        ,$package
        ,$shipperAccountId
        ,$others = array()
        ,$isDocument=FALSE,$returnShipment=FALSE,$async=FALSE)
    { 
        $payload = array();                                                                        
        $payload['async'] = $async; 
        $payload['is_document'] = $isDocument; 
        $payload['return_shipment'] = $returnShipment; 
        $payload['paper_size'] = $others['paper_size']; 
        $payload['service_type'] = $others['service_type'];         
        $accountNumber = ( $others['custom_paid_by'] == 'shipper' ) ? $shipperAccountId : $others['account_number'];
        
        $payload['billing'] = array('paid_by'=>$others['paid_by']);
        $payload['customs'] = array(
            'billing' => array(
                'paid_by' => $others['custom_paid_by']
            ),
            'purpose' => $others['purpose']
        );
                      
        $payload['shipper_account'] = array(
            'id'=>$shipperAccountId            
        );
        $payload['shipment']['parcels'][] = $package;
        $payload['shipment']['ship_from'] = $fromAddress;
        $payload['shipment']['ship_to'] = $toAddress;                
        $payload['references'] = $others['references'];                
        return $payload;
    }
    
    public function createLabel($payload)
    {        
        $conifg['safe'] = TRUE;
        $result = $this->api->create('labels', $payload,$conifg); 
        return $result;        
    }
    
    public function cancelLabel($payload)
    {
        $conifg['safe'] = TRUE;
        $result = $this->api->create('cancel-labels', $payload,$conifg);        
        return $result;
    }
    
    public function createManifest($payload)
    {
        $conifg['safe'] = TRUE;       
        $result = $this->api->create('manifests', $payload, $conifg);
        return $result;
    }
    
    public function createShipperAc($payload)
    {
        $conifg['safe'] = TRUE;
        $result = $this->api->createV2('shipper-accounts', $payload, $conifg);
        return $result;
    }
    
    public function deleteShipperAc($shipper_account_id)
    {                                        
        $url = $this->baseUrl.'/v3/shipper-accounts/'.$shipper_account_id; 
	    $method = 'DELETE';	   
        $body = [];
	    $result = $this->sendRequest($url, json_encode($body), $method);	                                   
        return array('response'=>$result['response'],'curl'=>$result['curl']);
    }
    
    public function createBulkDownload($payload)
    {         
        $conifg['safe'] = TRUE;
        $result = $this->api->create('bulk-downloads', $payload, $conifg);
        return $result;
    }
    
    public function updateShipperAc($payload, $shipper_account_id)
    {                              
        $url = $this->baseUrl.'/v3/shipper-accounts/'.$shipper_account_id.'/credentials';
	    $method = 'PUT';	    
	    $body = json_encode($payload);	
	    $result = $this->sendRequest($url, $body, $method);	                                   
        return array('response'=>$result['response'],'curl'=>$result['curl']);
    }
    
    public function updateShipperInfo($payload, $shipper_account)
    {        
        $shipper_account_id = $shipper_account['id'];                        
        $url = $this->baseUrl.'/v3/shipper-accounts/'.$shipper_account_id.'/info';
	    $method = 'PUT';	            
	    $body = json_encode($payload);	
        $result = $this->sendRequest($url, $body, $method);	                                   
        return array('response'=>$result['response'],'curl'=>$result['curl']);
    }
    
    public function sendRequest($url, $body, $method)
    { 
        $curl = curl_init();
        curl_setopt_array($curl, array(
	        CURLOPT_RETURNTRANSFER => true,
	        CURLOPT_URL => $url,
	        CURLOPT_CUSTOMREQUEST => $method,
	        CURLOPT_HTTPHEADER => $this->headers,
			CURLOPT_POSTFIELDS => $body
	    ));
        $response = curl_exec($curl);
        return array('response'=>$response,'curl'=>$curl);
    }
            
}