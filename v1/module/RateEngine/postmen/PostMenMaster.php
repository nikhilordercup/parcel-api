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
    private $db = NULL;    
    public static $apikey = 'b5585973-d041-4c4a-9b1f-014bf56e65e7';//pro#b353df5f-6bfc-4bc3-bb5e-ed82d5cf6c4c san#b5585973-d041-4c4a-9b1f-014bf56e65e7        
    public static $region = 'sandbox';    //sandbox  //production
    public $api = NULL;


    public function __construct()
    {         
        $this->db = new \DbHandler(); 
        $this->api = new Postmen(self::$apikey, self::$region);        
    }
    
    public function convertAddress($address)
    {       
        $finalAddress = [];                
        $finalAddress['contact_name'] = $address->name;
        $finalAddress['street1']= $address->street1;        
        $finalAddress['city']= $address->city;
        $finalAddress['state']= $address->state;
        $finalAddress['country']= $address->country;
        $finalAddress['phone']= $address->phone;
        $finalAddress['email']= '';
        $finalAddress['type']= '';                                 
        $finalAddress['postal_code']= $address->zip;
        $finalAddress['type'] = 'business'; // need discussion
        $finalAddress['email'] = 'test@test.test'; // need discussion
        return $finalAddress;
    }
    
    public function convertPackage($package,$currency)
    {                     
        $finalPackage = [];
        $finalPackage['description'] = $package->packaging_type;
        $finalPackage['box_type'] = $package->packaging_type;
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
            'description' => $package->packaging_type,
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
        return $result->rates;
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
        $result = $this->api->create('manifests', $payload);
        return $result;
    }
    
    public function createShipperAc($payload)
    {
        $conifg['safe'] = TRUE;
        $result = $this->api->createV2('shipper-accounts', $payload, $conifg);
        return $result;
    }
    
    public function deleteShipperAc($payload)
    {
        $result = $this->api->createV2('delete', $payload);
        return $result;
    }
    
    public function createBulkDownload($payload)
    {         
        $conifg['safe'] = TRUE;
        $result = $this->api->create('bulk-downloads', $payload, $conifg);
        return $result;
    }
    
    public function updateShipperAc($payload)
    {        
        $result = $this->api->createV2('shipper-accounts/de598545-f8ca-4b68-a096-2afaaced9060/credentials', $payload);
        return $result;
    }
            
}