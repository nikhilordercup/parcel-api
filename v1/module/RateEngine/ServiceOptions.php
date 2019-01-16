<?php
/**
 * Created by PhpStorm.
 * User: perce
 * Date: 03-12-2018
 * Time: 05:37 PM
 */
namespace v1\module\RateEngine;
class ServiceOptions
{
    private $_serviceOptions = null;
    private $_requestData = [];

    public function __construct($request, $serviceOption)
    {
        //Load all rules
        $this->_requestData = $request;
        $this->_serviceOptions = $serviceOption;
    }

    public function verifyRules()
    { 
        if (  is_null($this->_serviceOptions) || !count($this->_serviceOptions)) {
            return true; 
        }
        $this->_serviceOptions = (array)$this->_serviceOptions;
        if (!$this->isResidential()) {
            return false;
        }
        if (!$this->isAmDelivery()) {
            return false;
        }
        if (!$this->isSaturdayDelivery()) {
            return false;
        }
        if (!$this->isDuitable()) {
            return false;
        }
        if (!$this->isHoldAtLocation()) {
            return false;
        }
        if (!$this->isHolidayDelivery()) {
            return false;
        }
        if (!$this->checkLength()) {
            return false;
        }
        if (!$this->checkWidth()) {
            return false;
        }
        if (!$this->checkHeight()) {
            return false;
        }
        if (!$this->isGirth()) {
            return false;
        }
        if (!$this->checkServiceType()) {
            return false;
        }
        if (!$this->checkServiceLevel()) {
            return false;
        }
        if (!$this->checkBarcodeValue()) {
            return false;
        }
        if (!$this->checkMaxWaitingTime()) {
            return false;
        }
        if (!$this->isChangeFromBase()) {
            return false;
        }
        if (!$this->checkMinWeight()) {
            return false;
        }
        if (!$this->checkMaxWeight()) {
            return false;
        }
        if (!$this->checkMaxBoxCount()) {
            return false;
        }
        if (!$this->checkMaxTransitDays()) {
            return false;
        }
        if (!$this->checkMinTransitDays()) {
            return false;
        }
        if (!$this->checkServiceTime()) {
            return false;
        }
        return true;

    }

    private function isResidential()
    {
        return $this->commonConditionProcessor('is_residential');
    }

    private function isAmDelivery()
    {
        return $this->commonConditionProcessor('am_delivery');
    }

    private function isSaturdayDelivery()
    {
        return $this->commonConditionProcessor('saturday_delivery');
    }

    private function isDuitable()
    {
        return $this->commonConditionProcessor('duitable');
    }

    private function isHoldAtLocation()
    {
        return $this->commonConditionProcessor('hold_at_location');
    }

    private function isHolidayDelivery()
    {
        return $this->commonConditionProcessor('holiday_delivery');
    }

    private function checkLength()
    {
        return $this->packageFactorProcessor('length');
    }

    private function checkWidth()
    {
        return $this->packageFactorProcessor('width');
    }

    private function checkHeight()
    {
        return $this->packageFactorProcessor('height');
    }

    private function isGirth()
    {
        return true;
    }

    private function checkServiceType()
    {
        return true;
    }

    private function checkServiceLevel()
    {
        return true;
    }

    private function checkBarcodeValue()
    {
        return true;
    }

    private function checkMaxWaitingTime()
    {
        $waitTime = $this->_requestData->transit[0]->total_waiting_time;
        $allowed = $this->_serviceOptions['max_waiting_time'] ?? '';
        if ($allowed == "") {
            return true;
        } else if ($waitTime <= $allowed) {
            return true;
        }
        return false;
    }

    private function isChangeFromBase()
    {
        return true;
    }

    private function checkMinWeight()
    {
        $weight = $this->calculateWeight();
        $allowed = $this->_serviceOptions['min_weight'] ?? '';
        if ($allowed == "") {
            return true;
        } else if ($weight >= $allowed) {
            return true;
        }
        return false;
    }

    private function checkMaxWeight()
    {
        $weight = $this->calculateWeight();
        $allowed = $this->_serviceOptions['max_weight'] ?? '';
        if ($allowed == "") {
            return true;
        } else if ($weight <= $allowed) {
            return true;
        }
        return false;
    }

    private function checkMaxBoxCount()
    {
        $boxes = count($this->_requestData->package);
        $allowed = $this->_serviceOptions['max_box_count'] ?? '';
        if ($allowed == "") {
            return true;
        } else if ($boxes <= $allowed) {
            return true;
        }
        return false;
    }

    private function checkMaxTransitDays()
    {
        return true;
    }

    private function checkMinTransitDays()
    {
        return true;
    }

    private function checkServiceTime()
    {
        return true;
    }

    private function commonConditionProcessor($name)
    {

        if ($this->_serviceOptions[$name]
            && isset($this->_requestData->extra->{$name}) && $this->_requestData->extra->{$name}) {
            //Both Set and true
            return true;
        } elseif (!$this->_serviceOptions[$name]
            && isset($this->_requestData->extra->{$name}) && $this->_requestData->extra->{$name}) {
            //Option not true but request is true
            return false;
        } else if (!$this->_serviceOptions[$name] && !isset($this->_requestData->extra->{$name})) {
            //Option not true and request not true
            return true;
        } else if ($this->_serviceOptions[$name] && !isset($this->_requestData->extra->{$name})) {
            //Option true but request not asking for it
            return false;
        } else if ($this->_serviceOptions[$name] && isset($this->_requestData->extra->{$name})
            && !$this->_requestData->extra->{$name}) {
            //Option True request is asking but with false
            return false;
        }
    }

    private function packageFactorProcessor($name)
    {
        $status = true;
        foreach ($this->_requestData->package as $item) {
            if (isset($this->_serviceOptions[$name])
                && $this->_serviceOptions[$name] != ""
                && $this->_serviceOptions[$name] > 0
                && ($this->_serviceOptions[$name] < $item->{$name})) {
                $status = false;
            }
        }
        return false;
    }

    private function calculateWeight()
    {
        $totalWeight = 0;
        foreach ($this->_requestData->package as $p) {
            if (!isset($p->length)) continue;
            $volWeight = ($p->length * $p->width * $p->height) / 4000;
            $weight = $p->weight;
            $totalWeight += ($weight > $volWeight) ? $weight : $volWeight;
        }
        return $totalWeight;
    }

    public function formatOptionForResponse()
    {
        $serviceOption = [
            "dimensions" => [
                "length" => $this->_serviceOptions['length'] ?? '',
                "width" => $this->_serviceOptions['width'] ?? '',
                "height" => $this->_serviceOptions['height'] ?? '',
                "unit" => $this->_serviceOptions['dimension_unit'] ?? ''
            ],
            "weight" => [
                "weight" => $this->_serviceOptions['max_box_weight'] ?? '',
                "unit" => $this->_serviceOptions['weight_unit'] ?? ''
            ],
            "time" => [
                "max_waiting_time" => $this->_serviceOptions['max_waiting_time'] ?? '',
                "unit" => $this->_serviceOptions['time_unit']??''
            ]
        ];
        return $serviceOption;
    }

}