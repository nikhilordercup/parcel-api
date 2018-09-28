<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RateApiController
 *
 * @author perce
 */
class RateApiController {

    /**
     *
     * @var RateEngineModel
     */
    private $_reateEngineModel;
    private $_responseData = [];

    //put your code here
    private function __construct() {
        $this->_reateEngineModel = new RateEngineModel();
    }

    public static function initRoutes($app) {
        $app->post('/rate-engine/getRate', function() use ($app) {
            $r = json_decode($app->request->getBody());
//            print_r($r);
            verifyRequiredParams(array('access_token', 'company_id'), $r);
            $controller = new RateApiController;
            $controller->breakRequest($r);
        });
    }

    public function breakRequest($param) {
        $carriers = $param->carriers;
        $fromAddress = $param->from;
        $toAddress = $param->to;

        foreach ($carriers as $c) {
            foreach ($c->account as $a) {
                $ca = $this->_reateEngineModel
                        ->fetchCarrierByAccountNumber($a->credentials->account_number, $param->company_id);
                if ($ca) {
                    $this->_responseData['accountInfo'][$c->name][] = $ca;

                    $fromZone = $this->_reateEngineModel
                            ->searchZone($fromAddress, $ca['company_id'], $ca['courier_id']);
                    $toZone = $this->_reateEngineModel
                            ->searchZone($toAddress, $ca['company_id'], $ca['courier_id']);
                    $this->_responseData['zone'][$c->name]['fromZone'] = $fromZone;
                    $this->_responseData['zone'][$c->name]['toZone'] = $toZone;
                } else {
                    $this->_responseData['accountInfo'][$c->name][] = [
                        'error' => [
                            'account_found' => FALSE,
                            'message' => 'No account found with provided details.'
                        ]
                    ];
                }
            }
        }
        $this->getRates($this->_responseData);
        $this->applyPriceRules($param);
        $this->addErrorMessages();
        header('Content-Type: application/json');
        exit(json_encode($this->_responseData));
    }

    public function getFormatedData() {
        $data = [
            'rate' => [
                'carrier_name' => [
                    'account_number' => [
                        'service_name' => [
                        ]
                    ]
                ]
            ]
        ];
    }

    public function getRates($param) {
        $rateList = [];
        if (!isset($param['zone']))
            return FALSE;
        foreach ($param['zone'] as $name => $carrier) {
            if (count($carrier["fromZone"]) && count($carrier["toZone"])) {
                $rates = $this->_reateEngineModel->searchPriceForZone(
                        $param['zone'][$name]["fromZone"][0]['carrier_id'], $param['zone'][$name]["fromZone"][0]['id'], $param['zone'][$name]["toZone"][0]['id']);
                $this->_responseData['rate'][$name] = $rates;
            } else {
                
            }
        }
    }

    public function addressToZone($address, $companyId) {
        $availabeZones = $this->_reateEngineModel->searchZone($address, $companyId);
        $filtered = [];
        foreach ($availabeZones as $zone) {
            $filtered[$zone->level][] = $zone;
        }
        return $filtered;
    }

    public function applyPriceRules($request) {
        $packages = $request->package;
        $packagesCount = count($packages);
        $packagesWeight = 0;
        foreach ($packages as $package) {
            $packagesWeight += $package->weight;
        }

        $distance = 0;
        $time = 0;
        $drops = 0;
        foreach ($request->transit as $transitInfo) {
            $distance += $transitInfo->transit_distance;
            $time += $transitInfo->transit_time;
            $drops += $transitInfo->number_of_drops;
        }
        if (!isset($param['zone']))
            return FALSE;
        foreach ($this->_responseData['rate'] as $name => $d) {
            foreach ($d as $k => $p) {
                switch ($p["rate_type"]) {
                    case 'Weight':
                        $this->_responseData['rate'][$name][$k]['final_cost'] = $this->filterRateFormRange($p, $packagesWeight);
                        break;
                    case 'Box':
                        $this->_responseData['rate'][$name][$k]['final_cost'] = $this->filterRateFormRange($p, $packagesCount);
                        break;
                    case 'Time':
                        $this->_responseData['rate'][$name][$k]['final_cost'] = $this->filterRateFormRange($p, $time);
                        break;
                    case 'Distance':
                        $this->_responseData['rate'][$name][$k]['final_cost'] = $this->filterRateFormRange($p, $distance);
                        break;
                    case 'Drop Rate':
                        $this->_responseData['rate'][$name][$k]['final_cost'] = $this->filterRateFormRange($p, $drops);
                        break;
                }
            }
        }
    }

    public function filterRateFormRange($priceInfo, $units) {
        if ($priceInfo["end_unit"] >= $units && $priceInfo["start_unit"] <= $units) {
            return $priceInfo["rate"];
        } else if ($priceInfo["additional_base_unit"] > 0) {
            $extra = $units - $priceInfo["end_unit"];
            $chargableUnits = $extra / $priceInfo["additional_base_unit"];
            $remaining = $extra % $priceInfo["additional_base_unit"];
            if ($remaining > 0) {
                $chargableUnits++;
            }
            return $priceInfo["rate"] + ($priceInfo["additional_cost"] * $chargableUnits);
        }
    }

    public function addErrorMessages() {
        if (!isset($this->_responseData['zone'])) {
            $this->_responseData['zone'] = [
                'error' => [
                    'zone_found' => FALSE,
                    'message' => 'No zone found.'
                ]
            ];
            return FALSE;
        }
        if (!isset($this->_responseData['rate'])) {
            $this->_responseData['zone'] = [
                'error' => [
                    'rate_found' => FALSE,
                    'message' => 'No rate found.'
                ]
            ];
            return FALSE;
        }
        foreach ($this->_responseData['accountInfo'] as $k => $z) {
            if (!array_key_exists($k, $this->_responseData['rate'])) {
                $this->_responseData['rate'][$k] = [
                    'error' => [
                        'rate_found' => FALSE,
                        'message' => 'No rate found.'
                    ]
                ];
            }
            if (!array_key_exists($k, $this->_responseData['zone'])) {
                $this->_responseData['zone'][$k] = [
                    'error' => [
                        'zone_found' => FALSE,
                        'message' => 'No zone found.'
                    ]
                ];
            }
        }
    }

}
