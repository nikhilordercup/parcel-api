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
        
        header('Content-Type: application/json');
        echo json_encode($formatedRates,TRUE); die;       
        exit(1);        
    }
    
    public function formatShipmentRate($shipmentRates)
    {                          
        $shipmentRates = $shipmentRates->rates;              
        $rates['rate'] = array(); 
        if (count($shipmentRates) > 0) 
        {       
            $differentAcIds = array();
            foreach($shipmentRates as $shipmentRate)
            {                         
                $innerRate = array();
                $innerRate['rate']['id'] = $shipmentRate->id;
                $innerRate['rate']['carrier_name'] = $shipmentRate->carrier;
                $innerRate['rate']['service_name'] = $shipmentRate->service;
                $innerRate['rate']['service_code'] = $shipmentRate->service;
                $innerRate['rate']['rate_type'] = '';
                $innerRate['rate']['rate_unit'] = $shipmentRate->rate;
                $innerRate['rate']['price'] = $shipmentRate->retail_rate;
                $innerRate['rate']['act_number'] = $shipmentRate->carrier_account_id; 
                
                if(!in_array($shipmentRate->carrier_account_id, $differentAcIds))
                {
                    $differentAcIds[] = $shipmentRate->carrier_account_id;
                }
                $length = array_search($shipmentRate->carrier_account_id, $differentAcIds);
                                
                if(isset($rates['rate'][$shipmentRate->carrier]))
                {  
                    if(isset($rates['rate'][$shipmentRate->carrier][$length][$shipmentRate->carrier_account_id]))
                    { 
                        if(isset($rates['rate'][$shipmentRate->carrier][$length][$shipmentRate->carrier_account_id][0][$shipmentRate->service]))
                        {
                            $rates['rate'][$shipmentRate->carrier][$length][$shipmentRate->carrier_account_id][0][$shipmentRate->service][0][] = $innerRate;
                        }
                        else
                        {                                                     
                            $rates['rate'][$shipmentRate->carrier][$length][$shipmentRate->carrier_account_id][0][$shipmentRate->service][] = $innerRate;                                                        
                        }
                    }
                    else
                    { 
                        $serviceTemp = array();
                        $serviceTemp[$shipmentRate->service][] = $innerRate;
                        
                        $account = array();
                        $account[$shipmentRate->carrier_account_id][] = $serviceTemp;                                                                                            
                        
                        $rates['rate'][$shipmentRate->carrier][] = $account;                        
                    }
                }
                else
                { 
                    $serviceTemp = array();
                    $serviceTemp[$shipmentRate->service][] = $innerRate;
                    
                    $account = array();
                    $account[$shipmentRate->carrier_account_id][] = $serviceTemp;
                    
                    $rates['rate'][$shipmentRate->carrier][] = $account;                                       
                }                                                
            }
        }                      
        return $rates;
    }
    
    
  }