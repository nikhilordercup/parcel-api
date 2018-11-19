<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * This class is responsible for all type of surcharge calculation.
 *
 * @author Mandeep Singh Nain
 */
class SurchargeManager {

    protected $_surcharge = null;
    protected $_transitData = null;
    protected $_packages = null;
    protected $_rate = null;
    protected $_requestData = null;
    protected $_countedSurcharge = [];
    protected $_fuelSurcharge = null;
    //put your code here
    public $surcharges = [
        "long_length_surcharge",
        "remote_area_surcharge",
        "manual_handling_surcharge",
        "fuel_surcharge",
        "collection_pickup",
        "bookin_surcharge",
        "insurance_surcharge",
        "timed_services_surcharge",
        "return_surcharge",
        "isle_weight_surcharge",
        "isle_scilly_surcharge",
        "saturday_delivery_surcharge",
        "pobox_surcharge",
        "congestion_surcharge",
        "same_day_drop_surcharge",
        "same_day_waiting_surcharge"
    ];

    /**
     * Start point for every surcharge calculation. This function will filter surcharge type 
     * and call appropriate method for surcharge calculation.
     * @param object $surcharges Surcharge Information.
     * @param object $transitData Shipment transition data like from and to.
     * @param object $packages Array of parcels in shipment.
     * @param object $rate Rate object on which surcharge will be calculated.
     * @param object $request Complete request data which we are receiving in api request.
     */
    public function filterSurcharge($surcharges, $transitData, $packages, $rate, $request) {
        $this->_packages = $packages;
        $this->_rate = $rate;
        $this->_transitData = $transitData;
        $this->_requestData = $request;
        if (is_null($surcharges)) {
            $surcharges = [];
        }
        foreach ($surcharges as $surcharge) {
            $surchargeObj = json_decode($surcharge["surchargeRule"]);
            $this->_surcharge = $surchargeObj;
            if (!in_array($rate['service_id'], $surchargeObj->services)) {
                continue;
            }

            switch ($surchargeObj->surcharge) {
                case 1:
                    $this->longLengthSurcharge($rate);
                    break;
                case 3:
                    $this->manualHandlingSurcharge($rate);
                    break;
                case 4:
                    $this->_fuelSurcharge = $surcharge;
                    break;
                case 6:
                    $this->bookingSurcharge();
                    break;
                case 2:
                case 5:
                case 10:
                case 11:
                case 13:
                case 14:
                    $this->commonSurcharge($rate, $surchargeObj->surcharge);
                    break;
                case 15:
                case 16:
                    $this->sameDayDropWaitSurcharge($rate, $surchargeObj->surcharge);
                    break;
                default:
                    break;
            }
        }
        if (!is_null($this->_fuelSurcharge)) {
            $this->fuelSurcharge($rate,$this->_countedSurcharge);
        }
    }

    public function longLengthSurcharge($rate) {
        $finalSurcharge = 0;
            foreach ($this->_packages as $p) {
                $lengthApplicable = $this->isConditionTrue($p->length, $this->_surcharge->lengthData->lconditions, $this->_surcharge->lengthData->lUnit);
                $widthApplicable = $this->isConditionTrue($p->width, $this->_surcharge->lengthData->wconditions, $this->_surcharge->lengthData->wUnit);
                $heightApplicable = $this->isConditionTrue($p->height, $this->_surcharge->lengthData->hconditions, $this->_surcharge->lengthData->hUnit);
                if (($heightApplicable && $widthApplicable && $lengthApplicable) || $this->isSideApplicable($p, 2) || $this->isSideApplicable($p, 1)) {
                    $finalSurcharge += $this->calculateSurcharge($this->_surcharge->commonData, $rate);
                }
            }

        $this->_countedSurcharge["long_length_surcharge"] = $finalSurcharge;
    }

    public function commonSurcharge($rate, $key) {
        $finalSurcharge = 0;
        $key = $this->surcharges[$key - 1];
        $from = $this->isLocationChargeable($this->_requestData->from);
        $to = $this->isLocationChargeable($this->_requestData->to);
        if ($from || $to) {
            if ($this->_surcharge->commonData->applyPer != 'per_consignment') {
                for ($i = 0; $i < count($this->_packages); $i++) {
                    $finalSurcharge += $this->calculateSurcharge($this->_surcharge->commonData, $rate);
                }
            } else {
                $finalSurcharge += $this->calculateSurcharge($this->_surcharge->commonData, $rate);
            }
        }
        if ($from && $to) {
            $finalSurcharge *= 2;
        }
        $this->_countedSurcharge[$key] = $finalSurcharge;
    }

    public function sameDayDropWaitSurcharge($rate, $key) {
        $finalSurcharge = 0;
        $key = $this->surcharges[$key - 1];
        $chargableUnit = 0;
        if ($key == 'same_day_drop_surcharge') {
            $free = $this->_surcharge->sameDay->drop??0;
            $drops = $this->_requestData->transit[0]->number_of_drops;
            $chargableUnit = $drops - $free;
        } else {
            $baseUnit = $this->_surcharge->sameDay->wait;
            $extra = $this->_requestData->transit[0]->total_waiting_time;
            $chargableUnit = ($extra / count($this->_packages)) -$baseUnit;
        }
        if ($this->_surcharge->commonData->applyPer != 'per_consignment') {
            foreach ($this->_packages as $p) {
                $finalSurcharge += $this->calculateSurcharge($this->_surcharge->commonData, $rate);
            }
        } else {
            $finalSurcharge += $this->calculateSurcharge($this->_surcharge->commonData, $rate);
        }
        $finalSurcharge *= $chargableUnit;
        $this->_countedSurcharge[$key] = ($finalSurcharge>0)?round($finalSurcharge,2):0;
    }

    public function manualHandlingSurcharge($rate) {
        /**
         * Vol Weight=L*H*W/Params
         * Params for DHL 5000
         */
        $totalWeight = $this->calculateWeight();
        $this->_surcharge->commonData->{"factorValue"} = $this->getWeightSurchargeRate($totalWeight);
        $finalSurcharge = $this->calculateSurcharge($this->_surcharge->commonData, $rate);
        $this->_countedSurcharge["manual_handling_surcharge"] = $finalSurcharge;
    }

    public function fuelSurcharge($rate,$appliedSurcharges = [] ) {
        $finalSurcharge = 0;
        $this->_fuelSurcharge=(object)$this->_fuelSurcharge;
        $d= json_decode($this->_fuelSurcharge->surchargeRule);
        if(!isset($d->fuleSurcharge)){
            return 0;
        }
        foreach ($d->fuleSurcharge->applyOn as $id) {
            if (array_key_exists($this->surcharges[$id - 1], $appliedSurcharges)) {
                $finalSurcharge += $this->calculateSurcharge($d->commonData, ['rate' => $appliedSurcharges[$this->surcharges[$id - 1]]]);
            }
        }
        $finalSurcharge += $this->calculateSurcharge($d->commonData, $rate);
        $this->_countedSurcharge["fuel_surcharge"] = round($finalSurcharge,2);
    }

    public function bookingSurcharge() {
        $finalSurcharge = 0;
        if (isset($this->_surcharge->extra) && $this->_surcharge->extra) {
            if ($this->_surcharge->commonData->applyPer != 'per_consignment') {
                foreach ($this->_packages as $p) {
                    $finalSurcharge += $this->calculateSurcharge($this->_surcharge->commonData, $rate);
                }
            } else {
                $finalSurcharge += $this->calculateSurcharge($this->_surcharge->commonData, $rate);
            }
        }
        $this->_countedSurcharge["bookin_surcharge"] = $finalSurcharge;
    }

    public function isConditionTrue($firstValue, $operator, $secondValue) {
        switch ($operator) {
            case '>':
                return $firstValue > $secondValue;
            case '<';
                return $firstValue < $secondValue;
            case '<=':
                return $firstValue <= $secondValue;
            case '>=':
                return $firstValue >= $secondValue;
            default :
                return FALSE;
        }
    }

    public function isSideApplicable($p, $side) {
        $unit = ($side == 1) ? $this->_surcharge->lengthData->tUnit : $this->_surcharge->lengthData->sUnit;
        $lengthApplicable = $this->isConditionTrue($p->length, '>', $unit);
        $widthApplicable = $this->isConditionTrue($p->width, '>', $unit);
        $heightApplicable = $this->isConditionTrue($p->height, '>', $unit);
        if ($side == 1 && ($lengthApplicable || $widthApplicable || $heightApplicable)) {
            return TRUE;
        } else if ($side == 1) {
            return FALSE;
        }
        if ($lengthApplicable) {
            return $widthApplicable || $heightApplicable;
        } elseif ($widthApplicable) {
            return $lengthApplicable || $heightApplicable;
        } elseif ($heightApplicable) {
            return $widthApplicable || $lengthApplicable;
        }
        return FALSE;
    }

    /**
     * 
     * @param object $commonData
     * @param array() $rate
     * @return type
     */
    public function calculateSurcharge($commonData, $rate) {
        $surcharge = 0;
        if ($commonData->factor == 'constant') {
            $surcharge += $commonData->factorValue;
        } else {
            $surcharge += ($commonData->factorValue / 100) * $rate['rate'];
        }
        return $surcharge;
    }

    public function isLocationChargeable($location) {
        if (!in_array($location->country, $this->_surcharge->remoteArea->selectedCountry)) {
            return FALSE;
        } else {
            if (!isset($this->_surcharge->remoteArea->postCode) || $this->_surcharge->remoteArea->postCode == "") {
                return TRUE;
            } else {
                $postcodes = explode(',', $this->_surcharge->remoteArea->postCode);
                if ($location->country != 'GB') {
                    foreach ($postcodes as $p) {
                        if ($p == $location->zip) {
                            return TRUE;
                        }
                    }
                }                
                $model = new RateEngineModel();
                $tPost = $model->searchUkPost($postcodes, $location->zip, TRUE);
                if (count($tPost)) {
                    return TRUE;
                }
            }
        }
        return FALSE;
    }

    public function calculateWeight() {
        $totalWeight = 0;
        foreach ($this->_packages as $p) {
            $volWeight = ($p->length * $p->width * $p->height) / 4000;
            $weight = $p->weight;
            $totalWeight += ($weight > $volWeight) ? $weight : $volWeight;
        }
        return $totalWeight;
    }

    public function getWeightSurchargeRate($weight) {
        $rate = 0;
        if ($weight < 25 && (isset($this->_surcharge->manualHandling->weight0to24) && $this->_surcharge->manualHandling->weight0to24 == 1)) {
            $rate = $this->_surcharge->manualHandling->weight0to24Rate ?? 0;
        } else if ($weight > 24 && $weight < 51 && (isset($this->_surcharge->manualHandling->weight25to50) && $this->_surcharge->manualHandling->weight25to50 == 1)) {
            $rate = $this->_surcharge->manualHandling->weight25to50Rate ?? 0;
        } else if ($weight > 50 && $weight < 76 && (isset($this->_surcharge->manualHandling->weight51to75) && $this->_surcharge->manualHandling->weight51to75 == 1)) {
            $rate = $this->_surcharge->manualHandling->weight51to75Rate ?? 0;
        } else if ($weight > 75 && $weight < 101 && (isset($this->_surcharge->manualHandling->weight76to100) && $this->_surcharge->manualHandling->weight76to100 == 1)) {
            $rate = $this->_surcharge->manualHandling->weight76to100Rate ?? 0;
        } else if ($weight > 100 && $weight < 201 && (isset($this->_surcharge->manualHandling->weight101to200) && $this->_surcharge->manualHandling->weight101to200 == 1)) {
            $rate = $this->_surcharge->manualHandling->weight101to200Rate ?? 0;
        } if ($weight > 200 && (isset($this->_surcharge->manualHandling->weight201plus) && $this->_surcharge->manualHandling->weight201plus == 1)) {
            $rate = $this->_surcharge->manualHandling->weight201plusRate ?? 0;
        }
        return $rate;
    }

    /**
     * Here we are returning all calculated surcharge
     * @return type array
     */
    public function getAppliedSurcharge() {
        return $this->_countedSurcharge;
    }

}
