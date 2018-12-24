<?php
/**
 * Created by PhpStorm.
 * User: perce
 * Date: 22-11-2018
 * Time: 05:20 PM
 */ 

namespace v1\module\RateEngine\easypost;
use EasyPost\EasyPost;

abstract class EasyPostMaster extends EasyPost
{    
    private $db = NULL;
    
    public function __construct($authData)
    {         
        $this->db = new \DbHandler();                
        self::setApiKey($authData->apiKey);
    }
    
    public function convertAddress($address)
    {       
        $tempAddress = [];        
        $tempAddress['verify_strict'] = [];
        $tempAddress['name'] = $address->name;
        $tempAddress['street1']= $address->street1;
        $tempAddress['street2']= $address->street2;
        $tempAddress['city']= $address->city;
        $tempAddress['state']= $address->state;
        $tempAddress['zip']= $address->zip;
        $tempAddress['country']= $address->country;
        $tempAddress['company']= $address->company;
        $tempAddress['phone']= $address->phone;
                    
        $finalAddress = \EasyPost\Address::create($tempAddress);        
        return $finalAddress;
    }
    
    public function convertPackage($package)
    {             
        $tmpPackage = array(
                            "length" => $package->length,
                            "width" => $package->width,
                            "height" => $package->height,
                            "weight" => $package->weight
                        );
         
        $finalPackage = \EasyPost\Parcel::create($tmpPackage);                        
        return $finalPackage;
    }
    
    public function packagesToOrder($packages)
    {
        $orders=[];
        foreach ($packages as $i=>$package)
        {
            $orders[$i]['parcel']=$this->convertPackage($package);
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
                $carrierAccounts = $carrier->account;
                if(count($carrierAccounts) > 0)
                {
                    foreach($carrierAccounts as $carrierAccount)
                    {
                        $credentials = $carrierAccount->credentials;                                                
                        $carrierAccountDetails = $this->getCarrierAccount($credentials->username,$credentials->password,$credentials->account_number);                        
                        if($carrierAccountDetails)
                        {
                            $account_numbers[] = $carrierAccountDetails->carrierAccount;                        
                        }
                    }
                }                                                
            }
        }          
        return array_unique($account_numbers);
    }
    
    public function getCarrierAccount($username,$password,$account_number)
    { 
        //$username = 'nikhil.kumar@ordercup.com';
        //$password = 'b60yj24ek35ro91';
        //$account_number = 'K906430';
        return \v1\module\Database\Model\CourierVsCompanyModel::all()
            ->where('username','=',$username)
            ->where('password','=',$password)
            ->where('account_number','=',$account_number)
            ->first();                                        
    }
    
    public function processRates()
    {
        
    }
    
    public function buildRequest()
    {
        
    }
        
    /**
     * If single parcel then shipment created
     * @param Object $fromAddress
     * @param Object $toAddress
     * @param Array $package
     * @param Array $customs_info
     * @param Array $carrierAccounts
     * @return Mixed
     */
    public function createShipment($fromAddress, $toAddress, $package, $carrierAccounts, $customs_info=array())
    { 
        $shipment = \EasyPost\Shipment::create(array(
            "from_address" => $fromAddress,
            "to_address" => $toAddress,            
            "parcel" => $package,
            "customs_info" => $customs_info,
            "carrier_accounts" => $carrierAccounts
          ));        
        return $shipment;
    }
    
    /**
     * In case of multiple parcel, we create order
     * @param Object $fromAddress
     * @param Object $toAddress
     * @param Array $packages
     * @param Array $carrierAccounts
     * @return Mixed
     */
    public function createOrder($fromAddress, $toAddress, $packages,$carrierAccounts)
    {
        $order = \EasyPost\Order::create(array(
                "to_address" => $toAddress,
                "from_address" => $fromAddress,
                "shipments" => $packages,
                "carrier_accounts" => $carrierAccounts
            ));         
        return $order;
    }
    
    /**
     * This function give shipment rates
     * @param Object $shipment
     * @return Mixed
     */
    public function getShipmentRates($shipment)
    {     
        return $shipment->get_rates();              
    }
    
    /**
     * This function give order rates
     * @param Object $order
     * @return Mixed
     */
    public function getOrderRates($order)
    {     
        return $order->get_rates();              
    }
    
}