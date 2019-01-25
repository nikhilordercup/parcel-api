<?php
namespace v1\module\RateEngine;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use v1\module\RateEngine\tuffnells\TuffnellsLabels;
use v1\module\RateEngine\postmen\ShipmentManager;
use v1\module\RateEngine\ukmail\src\UkmailMaster;
use v1\Library;
/**
 * Description of RateApiController
 *
 * @author perce
 */
class RateApiController
{

    /**
     *
     * @var RateEngineModel
     */
    private $_reateEngineModel;
    private $_responseData = [];
    public $_isSameDay = false;
    public $_isLabelCall = false;
    private $_taxRate = null;
    private $_requestData = null;
    private $_requestedServices = [];
    private $_tempRateContainer = [];
    private $_doLabelCancel= false;
    
    //put your code here
    private function __construct()
    {
        $this->_reateEngineModel = new RateEngineModel();
    }

    public static function initRoutes($app)
    {
        $app->post('/rate-engine/getRate', function () use ($app) { 
            $r = json_decode($app->request->getBody());  
            $controller = new RateApiController();
            $controller->_requestData = $r;
//            $date = date('Y-m-d');
//            $match_date = date('Y-m-d', strtotime($r->ship_date));
            if (!isset($r->package) || count($r->package) == 0) {
                $controller->_isSameDay = true;
            }
            if (isset($r->label)) { 
                $controller->_isLabelCall = true;
            }
                        
            $env = ( ENV == 'live' ) ? 'PROD' : 'DEV';
            $callType = ($controller->_isLabelCall) ? 'LABEL':'RATE';                                                                              
                                      
            if(isset($r->providerInfo) && $r->providerInfo->provider == 'Postmen' && isset($r->doLabelCancel))
            { 
                $controller->_doLabelCancel = true;
                $shipmentManagerObj = \v1\module\RateEngine\postmen\ShipmentManager::getShipmentManagerObj();                                
                $formatedRequest = $shipmentManagerObj->getFormatedCancelLabelRequest($r);                                
                $shipmentManagerObj->cancelLabelAction($formatedRequest);                  
            }
            
            if (!$controller->_isLabelCall) 
            {                                
                $pd = $controller->_reateEngineModel->getServiceProvider($env,$callType,'PROVIDER', $r);
                $controller->_tempRateContainer['postmen'] = \v1\module\RateEngine\postmen\ShipmentManager::getShipmentManagerObj()->calculateRateAction($r, $pd);                
                $controller->breakRequest($r);
            } 
            else 
            {                
                $controller->getLabelProvider();
            }                                                
        });
        
        $app->post('/rate-engine/getCorePoint', function () use ($app) 
        {                
            $r = json_decode($app->request->getBody());   
            $controller = new RateApiController();
            $controller->_responseData = array();
            if($r->callType == 'createpickup')
            {                
                if($r->carrier == 'DHL')
                {                    
                    $dhlApiObj = new \v1\module\RateEngine\core\dhl\DhlApi();
                    $xmlRequest = $dhlApiObj->getPickupRequest($r);                     
                    $controller->_responseData = $dhlApiObj->postDataToDhl($xmlRequest);
                }                
            }            
            exit(json_encode($controller->_responseData));                                     
        });
        
    }

    public function breakRequest($param)
    {
        $carriers = $param->carriers;
        $fromAddress = $param->from;
        $toAddress = $param->to;
        $this->loadTaxRate($fromAddress);

        foreach ($carriers as $c) {  
            foreach ($c->account as $a) {
            
                $ca = $this->_reateEngineModel
                    ->fetchCarrierByAccountNumber($a->credentials->account_number);
                $this->_requestedServices[$a->credentials->account_number] = array_merge($this->_requestedServices[$a->credentials->account_number] ?? [], explode(',', $a->services));

                if ($ca) {
                    if (!isset($this->_responseData['surchargeList'][$ca['courier_id']][$ca['id']])) {
                        $surchargeList = $this->_reateEngineModel->fetchSurcharge($ca['courier_id'], [], [$ca['id']], []);
                        foreach ($surchargeList as $i => $l) {
                            $this->_responseData['surchargeList'][$c->name][$a->credentials->account_number][$l['serviceCode']][] = $l;
                        }
                        if (!isset($this->_responseData['surchargeList'][$c->name][$a->credentials->account_number])) {
                            $this->_responseData['surchargeList'][$c->name][$a->credentials->account_number] = [];
                        }
                        if (!isset($this->_responseData['surchargeList'])) {
                            $this->_responseData['surchargeList'] = [];
                        }
                    }
                    $this->_responseData['accountInfo'][$c->name][] = $ca;
                    $fromZone = $this->_reateEngineModel
                        ->searchZone($fromAddress, $ca['courier_id']);
                    $toZone = $this->_reateEngineModel
                        ->searchZone($toAddress, $ca['courier_id']);
                    $this->_responseData['zone'][$c->name]['fromZone'] = $fromZone;
                    $this->_responseData['zone'][$c->name]['toZone'] = $toZone;
                } else {
                    $this->_responseData['accountInfo'][$c->name][] = [
                        'error' => [
                            'account_found' => false,
                            'message' => 'No account found with provided details.'
                        ]
                    ];
                }
            }
        } 
        $this->getRates($this->_responseData);
        $this->applyPriceRules($param);
        $this->addErrorMessages();  
         
        $postmenRates = json_decode($this->_tempRateContainer['postmen'],TRUE);        
        if(count($postmenRates) > 0)
        {
            $this->_responseData = $this->mergeOtherCarrierRates($this->_responseData, $postmenRates); 
        }                
        header('Content-Type: application/json'); 
        exit(json_encode($this->_responseData));
    }

    public function getRates($param)
    {
        if (!isset($param['zone'])) {
            return false;
        }
        foreach ($param['zone'] as $name => $carrier) {
            if (count($carrier["fromZone"]) && count($carrier["toZone"])) {
                $rates = $this->_reateEngineModel->searchPriceForZone(
                    $param['zone'][$name]["fromZone"]['carrier_id'],
                    $param['zone'][$name]["fromZone"]['zone_id'],
                    $param['zone'][$name]["toZone"]['zone_id']
                );
                foreach ($rates as $k => $r) {
                    if (!isset($this->_requestedServices[$r['account_number']])) continue;
                    $result = array_search($r['service_code'], $this->_requestedServices[$r['account_number']]);
                    if (gettype($result) == 'integer' && $result >= 0) {
                        $this->_responseData['rate'][$name][$r['account_number']][$r['service_code']][]['rate'] = $rates[$k];
                    }
                }
            } else {

            }
        }
    }

    public function addressToZone($address, $carrierId)
    {
        $availabeZones = $this->_reateEngineModel->searchZone($address, $carrierId);
        $filtered = [];
        foreach ($availabeZones as $zone) {
            $filtered[$zone->level][] = $zone;
        }
        return $filtered;
    }

    public function applyPriceRules($request)
    {
        $packages = $request->package;
        $packagesCount = count($packages);
        $packagesWeight = $this->calculateWeight($packages);

        $distance = 0;
        $time = 0;
        $waitTime = 0;
        $drops = 0;
        foreach ($request->transit as $transitInfo) {
            $distance += $transitInfo->transit_distance;
            $time += $transitInfo->transit_time;
            $drops += $transitInfo->number_of_drops;
            $waitTime += $transitInfo->total_waiting_time;
        }

        $transitData = compact('distance', 'time', 'waitTime', 'drops');
        if (!isset($this->_responseData['rate'])) {
            unset($this->_responseData['surchargeList']);
            return false;
        }
        foreach ($this->_responseData['rate'] as $name => $d) {
            foreach ($d as $k => $p) {
                foreach ($p as $z => $t) {
                    foreach ($t as $key => $f) {
                        $f = $f['rate'];
                        switch ($f["rate_type"]) {
                            case 'Weight':
                                if ($this->_isSameDay) {
                                    break;
                                }
                                $this->_responseData['rate'][$name][$k][$z][$key]['rate']['final_cost'] = round($this->filterRateFormRange($f, $packagesWeight), 2);
                                break;
                            case 'Box':
                                if ($this->_isSameDay) break;
                                $this->_responseData['rate'][$name][$k][$z][$key]['rate']['final_cost'] = $this->filterRateFormRange($f, $packagesCount);
                                break;
                            case 'Time':
                                $this->_responseData['rate'][$name][$k][$z][$key]['rate']['final_cost'] = $this->filterRateFormRange($f, $time);
                                break;
                            case 'Distance':
                                $this->_responseData['rate'][$name][$k][$z][$key]['rate']['final_cost'] = $this->filterRateFormRange($f, $distance);
                                break;
                            case 'Drop Rate':
                                $this->_responseData['rate'][$name][$k][$z][$key]['rate']['final_cost'] = $this->filterRateFormRange($f, $drops);
                                break;
                        }
                        if (!isset($this->_responseData['rate'][$name][$k][$z][$key]['rate']['final_cost']) || $this->_responseData['rate'][$name][$k][$z][$key]['rate']['final_cost'] == null) {
                            unset($this->_responseData['rate'][$name][$k][$z][$key]);
                            continue;
                        }
                        if (count($this->_responseData['rate'][$name][$k][$z])) {
                            $serviceOption = $this->_reateEngineModel->getServiceOption($this->_responseData['rate'][$name][$k][$z][$key]['rate']['service_id']);
                            $serviceOptionManager = new \v1\module\RateEngine\ServiceOptions($request, $serviceOption);
                            if (!$serviceOptionManager->verifyRules()) {
                                unset($this->_responseData['rate'][$name][$k][$z][$key]);
                                continue;
                            }
                            $manager = new \v1\module\RateEngine\SurchargeManager();
                            $manager->filterSurcharge($this->_responseData['surchargeList'][$name][$k][$z] ?? null, $transitData, $packages, $this->_responseData['rate'][$name][$k][$z][$key]['rate'], $request);
                            $this->_responseData['rate'][$name][$k][$z][$key]['surcharges'] = $manager->getAppliedSurcharge();
                            $this->_responseData['rate'][$name][$k][$z][$key]['service_options'] = $serviceOptionManager->formatOptionForResponse();
                            $this->_responseData['rate'][$name][$k][$z][$key]['rate']['price'] = $this->_responseData['rate'][$name][$k][$z][$key]['rate']['final_cost'];
                            $this->_responseData['rate'][$name][$k][$z][$key]['taxes'] = $this->calculateTax($this->_responseData['rate'][$name][$k][$z][$key]['rate']['price'], $this->_responseData['rate'][$name][$k][$z][$key]['surcharges']);
                            $this->_responseData['rate'][$name][$k][$z][$key]['rate']['act_number'] = $this->_responseData['rate'][$name][$k][$z][$key]['rate']['account_number'];
                            unset($this->_responseData['rate'][$name][$k][$z][$key]['rate']['carrier_id'], $this->_responseData['rate'][$name][$k][$z][$key]['rate']['service_id'], $this->_responseData['rate'][$name][$k][$z][$key]['rate']['rate_type_id'], $this->_responseData['rate'][$name][$k][$z][$key]['rate']['from_zone_id'], $this->_responseData['rate'][$name][$k][$z][$key]['rate']['to_zone_id'], $this->_responseData['rate'][$name][$k][$z][$key]['rate']['start_unit'], $this->_responseData['rate'][$name][$k][$z][$key]['rate']['end_unit'], $this->_responseData['rate'][$name][$k][$z][$key]['rate']['additional_cost'], $this->_responseData['rate'][$name][$k][$z][$key]['rate']['additional_base_unit'], $this->_responseData['rate'][$name][$k][$z][$key]['rate']['rate_unit_id'], $this->_responseData['rate'][$name][$k][$z][$key]['rate']['account_id'], $this->_responseData['rate'][$name][$k][$z][$key]['rate']['rate'], $this->_responseData['rate'][$name][$k][$z][$key]['rate']['final_cost'], $this->_responseData['rate'][$name][$k][$z][$key]['rate']['account_number']);
                            if($this->_responseData['rate'][$name][$k][$z][$key]['rate']['price']==0)unset($this->_responseData['rate'][$name][$k][$z][$key]);
                        }
                    }
                }
            }
        }
        unset($this->_responseData['surchargeList']);
    }

    public function calculateWeight($packages)
    {
        $totalWeight = 0;
        foreach ($packages as $p) {
            if (!isset($p->length)) continue;
            $volWeight = ($p->length * $p->width * $p->height) / 4000;
            $weight = $p->weight;
            $totalWeight += ($weight > $volWeight) ? $weight : $volWeight;
        }
        return $totalWeight;
    }

    public function filterRateFormRange($priceInfo, $units)
    {
        if ($priceInfo["end_unit"] >= $units && $priceInfo["start_unit"] <= $units) {
            $extra = $units - ($priceInfo["start_unit"]);
            if ($extra > 0 && $priceInfo["additional_base_unit"] > 0) {
                $chargableUnits = $extra / $priceInfo["additional_base_unit"];
                $remaining = $extra % $priceInfo["additional_base_unit"];
                if ($remaining > 0) {
                    $chargableUnits++;
                }
                return $priceInfo["rate"] + ($priceInfo["additional_cost"] * $chargableUnits);
            }
            return $priceInfo["rate"];
        } else {
            return null;
        }
    }

    public function addErrorMessages()
    {
        if (!isset($this->_responseData['zone'])) {
            $this->_responseData['zone'] = [
                'error' => [
                    'zone_found' => false,
                    'message' => 'No zone found.'
                ]
            ];
        }
        if (!isset($this->_responseData['rate'])) {
            $this->_responseData['rate'] = [];
        } else {
            foreach ($this->_responseData['rate'] as $name => $v) {
                $i = 0;
                $final = [];
                foreach ($v as $account => $d) {
                    $j = 0;
                    foreach ($d as $service => $rate) {
                        if (!count(array_values($rate))) {
                            unset($this->_responseData['rate'][$name][$account][$service]);
                            continue;
                        }
                        $this->_responseData['rate'][$name][$account][$service] = array_values($rate);
                        $final[$i][$account][$j][$service] = array_values($rate);
                        $j++;
                    }
                    $i++;
                }
                $this->_responseData['rate'][$name] = $final;
            }
        }
        foreach ($this->_responseData['accountInfo'] as $k => $z) {
            if (!array_key_exists($k, $this->_responseData['rate'])) {
                $this->_responseData['rate'][$k] = [];
            }
            if (!array_key_exists($k, $this->_responseData['zone'])) {
                $this->_responseData['zone'][$k] = [
                    'error' => [
                        'zone_found' => false,
                        'message' => 'No zone found.'
                    ]
                ];
            }
        }
        unset($this->_responseData['accountInfo'], $this->_responseData['zone']);
    }

    /**
    * If provider is not set then set it in db and add code for your provider
    * @param String $provider
    * @return type
    */
   public function getLabelProvider()
   { 
	    $provider = isset($this->_requestData->providerInfo->provider) ? $this->_requestData->providerInfo->provider : "Local"; 
        if($provider == 'Postmen'){ 
           $this->_requestData->directlyCallForPostmen = "false";            
            \v1\module\RateEngine\postmen\ShipmentManager::getShipmentManagerObj()->createLabelAction($this->_requestData);                                  
        }elseif($provider == 'Ukmail'){
           $ukmailObj = new UkmailMaster();
           $ukmailObj::initRoutes($this->_requestData); 
		   exit;
        }else{ 
           $tuffnells = new TuffnellsLabels($this->_requestData);
           $resp = $tuffnells->tuffnellLabelData($this->_requestData); 
           exit($resp);
        }       
    }

    public function loadTaxRate($fromAddress)
    {
        $this->_taxRate = $this->_reateEngineModel->getTaxInfoByIso2($fromAddress->country);
    }

    public function calculateTax($price, $surcharges = [])
    {
        foreach ($surcharges as $s) {
            $price += $s;
        }
        if (is_null($this->_taxRate)) {
            return [
                'total_tax' => 0,
                'tax_percentage' => 0
            ];
        } else {
            return [
                'total_tax' => (($this->_taxRate['tax_factor_value'] / 100) * $price),
                'tax_percentage' => $this->_taxRate['tax_factor_value'] ?? 0
            ];
        }
    }
    
    public function mergeOtherCarrierRates($rateResponse, $rateResponse1)
    {                
        if( isset($rateResponse1['rate']) && count($rateResponse1['rate']) > 0)
        {
            foreach($rateResponse1 as $rate => $rateData)
            { 
                foreach ($rateData as $c => $cdata) 
                {                                        
                    $rateResponse['rate'][$c] = $cdata;
                }                                                
            }
        }        
        return $rateResponse;
    }
    
}
