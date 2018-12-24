<?php
/**
 * Created by PhpStorm.
 * User: perce
 * Date: 22-11-2018
 * Time: 05:14 PM
 */

namespace v1\module\RateEngine\easypost;
use EasyPost\EasyPost;

class ShipmentManager extends EasyPostMaster
  {
    private static $shipmentManagerObj = NULL;
    private $db = NULL;
    
    public static function shipmentRoutes($app)
    {
        $app->post('/createShipment', function () use ($app) {            
            $request = json_decode($app->request->getBody());            
            $shipmentManager = self::getShipmentManagerObj();                                    
            $shipmentManager->createShipmentAction($request);                                   
        });
    }
    
    public static function getShipmentManagerObj()
    {
        if(!self::$shipmentManagerObj instanceof ShipmentManager)
        {
            $authData = new \stdClass();
            $authData->apiKey = "sR0Q8I2yZuHrHpZa0T7i0A";            
            self::$shipmentManagerObj = new ShipmentManager($authData); 
            self::$shipmentManagerObj->db = new \dbConnect();
        }
        return self::$shipmentManagerObj;
    }
  
    public function createShipmentAction($request)
    {                         
        $order = NULL;
        $shipment = NULL;
        $formatedRates = [];
        
        $fromAddress = $this->convertAddress($request->from);                
        $toAddress = $this->convertAddress($request->to);                                                          
                                       
        $carrierAccounts = $this->carrierAccounts($request->carriers);         
        if(count($request->package) > 1)
        {
           $packages = $this->packagesToOrder($request->package);
           $order = $this->createOrder($fromAddress, $toAddress, $packages,$carrierAccounts);                                 
           $orderRates = array(); 
           if($order) 
           {
               $orderRates = $this->getOrderRates($order);
               $formatedRates = $this->formatShipmentRate($orderRates);
           }           
        }
        else
        {                       
            $customs_info = array();
            $package = $this->convertPackage($request->package[0]);
            $shipment = $this->createShipment($fromAddress,$toAddress,$package,$carrierAccounts,$customs_info);
            
            $shipmentRates = array(); 
            if ($shipment && count($shipment->rates) > 0) 
            {
                $shipmentRates = $this->getShipmentRates($shipment);
                $formatedRates = $this->formatShipmentRate($shipmentRates);
            }
        }
        
        print_r($formatedRates);die;
        print_r($shipment);
        print_r($order);die;         
    }
    
    public function formatShipmentRate($shipmentRates)
    {                        
        $shipmentRates = $shipmentRates->rates;                
        $rates = array(); 
        if (count($shipmentRates) > 0) 
        {
            foreach($shipmentRates as $shipmentRate)
            {                
                $rates['rate'][$shipmentRate->carrier][$shipmentRate->carrier_account_id][$shipmentRate->service]['rate']['id'] = $shipmentRate->id;
                $rates['rate'][$shipmentRate->carrier][$shipmentRate->carrier_account_id][$shipmentRate->service]['rate']['carrier_name'] = $shipmentRate->carrier;
                $rates['rate'][$shipmentRate->carrier][$shipmentRate->carrier_account_id][$shipmentRate->service]['rate']['service_name'] = $shipmentRate->service;
                $rates['rate'][$shipmentRate->carrier][$shipmentRate->carrier_account_id][$shipmentRate->service]['rate']['service_code'] = $shipmentRate->service;
                $rates['rate'][$shipmentRate->carrier][$shipmentRate->carrier_account_id][$shipmentRate->service]['rate']['rate_type'] = '';
                $rates['rate'][$shipmentRate->carrier][$shipmentRate->carrier_account_id][$shipmentRate->service]['rate']['rate_unit'] = $shipmentRate->rate;
                $rates['rate'][$shipmentRate->carrier][$shipmentRate->carrier_account_id][$shipmentRate->service]['rate']['price'] = $shipmentRate->retail_rate;
                $rates['rate'][$shipmentRate->carrier][$shipmentRate->carrier_account_id][$shipmentRate->service]['rate']['act_number'] = $shipmentRate->carrier_account_id;                
            }
        }          
        return $rates;
    }
    
    
  }