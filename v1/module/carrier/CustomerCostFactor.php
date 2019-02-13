<?php

class CustomerCostFactor
{
    public $modelObj = NULL;

    public function __construct()
    {
        $this->modelObj = Carrier_Model_Carrier::_getInstance();
    }

    private function _getCcfOfService($customer_id, $service_code)
    {
        return $this->modelObj->getCustomerCcfService($customer_id, $service_code);
    }

    private function _getCustomerCcfSurcharge($customer_id, $surcharge_code)
    {
        return $this->modelObj->getCustomerCcfSurcharge($customer_id, $surcharge_code);
    }

    private function _getCustomerAllCourier($customer_id)
    {
        $item = $this->modelObj->getCustomerAllCourier($customer_id);
        $customerCarriers = new stdClass;
        foreach ($items as $item) {
            $carrier_code = $item["courier_code"];
            $customerCarriers->$carrier_code = new stdClass();

            $customerCarriers->$carrier_code->name = $item["courier_name"];
            $customerCarriers->$carrier_code->icon = $item["courier_icon"];
            $customerCarriers->$carrier_code->description = $item["courier_description"];
            $customerCarriers->$carrier_code->code = $item["courier_id"];
        }
        return $customerCarriers;
    }

    private function _getCustomerCourierByCourierCode($customer_id, $company_id, $carrier_code)
    {
        $item = $this->modelObj->getCustomerCourierByCourierCode($customer_id, $company_id, $carrier_code);
        $customerCarriers = new stdClass;
        $customerCarriers->$courier_code = new stdClass();
        $customerCarriers->$courier_code->courier_name = $items["courier_name"];
        $customerCarriers->$courier_code->courier_icon = $items["courier_icon"];
        $customerCarriers->$courier_code->courier_description = $items["courier_description"];
        $customerCarriers->$courier_code->courier_id = $items["courier_id"];
        return $customerCarriers;
    }

    private function _getCustomerInfoSurcharge($customer_id)
    {
        $item = $this->modelObj->getCustomerInfoSurcharge($customer_id);
        return $item;
    }

    private function _getCourierSurchargeCompany($company_id, $surcharge_code)
    {
        $item = $this->modelObj->getCourierSurchargeCompany($company_id, $surcharge_code);
        return $item;
    }

    private function _getCompanyCourierSurcharge($company_id, $carrier_code)
    {
        $item = $this->modelObj->getCompanyCourierSurcharge($company_id, $carrier_code);
        return $item;
    }

    private function _calculateCcf($price, $ccf_value, $operator, $company_service_code, $company_service_name, $courier_service_code, $courier_service_name, $level, $service_id)
    {
        if ($operator == "FLAT") {
            $ccfprice = $ccf_value;
        } elseif ($operator == "PERCENTAGE") {
            $ccfprice = ($price * $ccf_value / 100);
        }
        return array("original_price" => number_format($price, 2,'.', ''), "ccf_value" => number_format($ccf_value, 2,'.', ''), "operator" => $operator, "price" => number_format($ccfprice, 2,'.', ''), "company_service_code" => $company_service_code, "company_service_name" => $company_service_name, "courier_service_code" => $courier_service_code, "courier_service_name" => $courier_service_name, "level" => $level, 'service_id' => $service_id);
    }

    private function _calculateSurcharge($price, $surcharge_value, $operator, $company_surcharge_code, $company_surcharge_name, $courier_surcharge_code, $courier_surcharge_name, $level, $surcharge_id)
    { 
        $price = (float)$price;
        $surcharge_value = (float)$surcharge_value;
        
        if ($operator == "FLAT") {
            $surchargePrice = $surcharge_value;
        } elseif ($operator == "PERCENTAGE") {
            $surchargePrice = ($price * $surcharge_value / 100);
        }
        return array("original_price" => number_format($price, 2,'.', ''), "surcharge_value" => number_format($surcharge_value, 2,'.', ''), "operator" => $operator, "price" => number_format($surchargePrice, 2,'.', ''), "company_surcharge_code" => $company_surcharge_code, "company_surcharge_name" => $company_surcharge_name, "courier_surcharge_code" => $courier_surcharge_code, "courier_surcharge_name" => $courier_surcharge_name, "level" => $level, 'surcharge_id' => $surcharge_id);
    }

    //public function calculateServiceCcf($courier_id, $customer_id, $company_id, $service_code, $service_price)
    public function calculateServiceCcf($service_code, $serviceprice, $courier_id, $customer_id, $company_id )
    {
        $serviceCcf = $this->modelObj->getCcfOfCarrierServices($service_code, $customer_id, $company_id, $courier_id);

        if ($serviceCcf) {
            if (isset($serviceCcf["customer_carrier_service_ccf"]) and $serviceCcf["customer_carrier_service_ccf"] > 0 and $serviceCcf["customer_carrier_service_operator"] != 'NONE') {
                $service_ccf_price = $this->_calculateCcf($serviceprice, $serviceCcf["customer_carrier_service_ccf"], $serviceCcf["customer_carrier_service_operator"], $serviceCcf["company_service_code"], $serviceCcf["company_service_name"], $serviceCcf["courier_service_code"], $serviceCcf["courier_service_name"], "level 1", $serviceCcf['service_id']);
            } elseif (isset($serviceCcf["customer_carrier_ccf"]) and $serviceCcf["customer_carrier_ccf"] > 0 and $serviceCcf["customer_carrier_operator"] != 'NONE') {
                $service_ccf_price = $this->_calculateCcf($serviceprice, $serviceCcf["customer_carrier_ccf"], $serviceCcf["customer_carrier_operator"], $serviceCcf["company_service_code"], $serviceCcf["company_service_name"], $serviceCcf["courier_service_code"], $serviceCcf["courier_service_name"], "level 2", $serviceCcf['service_id']);
            } elseif (isset($serviceCcf["customer_ccf"]) and $serviceCcf["customer_ccf"] > 0 and $serviceCcf["customer_operator"] != 'NONE') {
                $service_ccf_price = $this->_calculateCcf($serviceprice, $serviceCcf["customer_ccf"], $serviceCcf["customer_operator"], $serviceCcf["company_service_code"], $serviceCcf["company_service_name"], $serviceCcf["courier_service_code"], $serviceCcf["courier_service_name"], "level 3", $serviceCcf['service_id']);
            } elseif (isset($serviceCcf["company_carrier_service_ccf"]) and $serviceCcf["company_carrier_service_ccf"] > 0 and $serviceCcf["company_carrier_service_operator"] != 'NONE') {
                $service_ccf_price = $this->_calculateCcf($serviceprice, $serviceCcf["company_carrier_service_ccf"], $serviceCcf["company_carrier_service_operator"], $serviceCcf["company_service_code"], $serviceCcf["company_service_name"], $serviceCcf["courier_service_code"], $serviceCcf["courier_service_name"], "level 4", $serviceCcf['service_id']);
            } elseif (isset($serviceCcf["company_carrier_ccf"]) and $serviceCcf["company_carrier_operator"] != 'NONE') {
                $service_ccf_price = $this->_calculateCcf($serviceprice, $serviceCcf["company_carrier_ccf"], $serviceCcf["company_carrier_operator"], $serviceCcf["company_service_code"], $serviceCcf["company_service_name"], $serviceCcf["courier_service_code"], $serviceCcf["courier_service_name"], "level 5", $serviceCcf['service_id']);
            }
        } else {
            $customerCcf = $this->modelObj->getCcfOfCarrier($customer_id, $company_id, $courier_id);

            if (isset($customerCcf["customer_carrier_ccf"]) and $customerCcf["customer_carrier_ccf"] > 0 and $customerCcf["customer_carrier_operator"] != 'NONE') {
                $service_ccf_price = $this->_calculateCcf($serviceprice, $customerCcf["customer_carrier_ccf"], $customerCcf["customer_carrier_operator"], $service_code, $service_code, $service_code, $service_code, "level 2", 0);
            } elseif (isset($customerCcf["customer_ccf"]) and $customerCcf["customer_ccf"] > 0 and $customerCcf["customer_operator"] != 'NONE') {
                $service_ccf_price = $this->_calculateCcf($serviceprice, $customerCcf["customer_ccf"], $customerCcf["customer_operator"], $service_code, $service_code, $service_code, $service_code, "level 3", 0);
            } elseif (isset($customerCcf["company_carrier_ccf"]) and $customerCcf["company_carrier_operator"] != 'NONE') {
                $service_ccf_price = $this->_calculateCcf($serviceprice, $customerCcf["company_carrier_ccf"], $customerCcf["company_carrier_operator"], $service_code, $service_code, $service_code, $service_code, "level 5", 0);
            }
        }

        $serviceCcfPrice = isset($service_ccf_price["price"]) ? $service_ccf_price["price"] : '0';
        
        $service_ccf_price["price_with_ccf"] = number_format( $serviceCcfPrice + $serviceprice, 2,'.', '');
        $service_ccf_price["original_price"] = isset($service_ccf_price["original_price"]) ? $service_ccf_price["original_price"] : 0;
        
        return $service_ccf_price;
    }

    public function calculateSurchargeCcf($surcharge_code, $customer_id, $company_id, $courier_id, $price)
    {
        $surchargeCcf = $this->modelObj->getCcfOfCarrierSurcharge($surcharge_code, $customer_id, $company_id, $courier_id);

        if ($surchargeCcf) {
            if (isset($surchargeCcf["customer_carrier_surcharge_ccf"]) and $surchargeCcf["customer_carrier_surcharge_ccf"] > 0 and $surchargeCcf["customer_carrier_surcharge_operator"] != 'NONE') {
                $surcharge_ccf_price = $this->_calculateSurcharge($price, $surchargeCcf["customer_carrier_surcharge_ccf"], $surchargeCcf["customer_carrier_surcharge_operator"], $surchargeCcf["company_surcharge_code"], $surchargeCcf["company_surcharge_name"], $surchargeCcf["courier_surcharge_code"], $surchargeCcf["courier_surcharge_name"], "level 1", $surchargeCcf["surcharge_id"]);
            } elseif (isset($surchargeCcf["customer_carrier_surcharge"]) and $surchargeCcf["customer_carrier_surcharge"] > 0 and $surchargeCcf["customer_carrier_operator"] != 'NONE') {
                $surcharge_ccf_price = $this->_calculateSurcharge($price, $surchargeCcf["customer_carrier_surcharge"], $surchargeCcf["customer_carrier_operator"], $surchargeCcf["company_surcharge_code"], $surchargeCcf["company_surcharge_name"], $surchargeCcf["courier_surcharge_code"], $surchargeCcf["courier_surcharge_name"], "level 2", $surchargeCcf["surcharge_id"]);
            } elseif (isset($surchargeCcf["customer_surcharge"]) and $surchargeCcf["customer_surcharge"] > 0 and $surchargeCcf["customer_operator"] != 'NONE') {
                $surcharge_ccf_price = $this->_calculateSurcharge($price, $surchargeCcf["customer_surcharge"], $surchargeCcf["customer_operator"], $surchargeCcf["company_surcharge_code"], $surchargeCcf["company_surcharge_name"], $surchargeCcf["courier_surcharge_code"], $surchargeCcf["courier_surcharge_name"], "level 3", $surchargeCcf["surcharge_id"]);
            } elseif (isset($surchargeCcf["company_carrier_surcharge_ccf"]) and $surchargeCcf["company_carrier_surcharge_ccf"] > 0 and $surchargeCcf["company_carrier_surcharge_operator"] != 'NONE') {
                $surcharge_ccf_price = $this->_calculateSurcharge($price, $surchargeCcf["company_carrier_surcharge_ccf"], $surchargeCcf["company_carrier_surcharge_operator"], $surchargeCcf["company_surcharge_code"], $surchargeCcf["company_surcharge_name"], $surchargeCcf["courier_surcharge_code"], $surchargeCcf["courier_surcharge_name"], "level 4", $surchargeCcf["surcharge_id"]);
            } elseif (isset($surchargeCcf["company_carrier_ccf"]) and $surchargeCcf["company_carrier_operator"] != 'NONE') {
                $surcharge_ccf_price = $this->_calculateSurcharge($price, $surchargeCcf["company_carrier_ccf"], $surchargeCcf["company_carrier_operator"], $surchargeCcf["company_surcharge_code"], $surchargeCcf["company_surcharge_name"], $surchargeCcf["courier_surcharge_code"], $surchargeCcf["courier_surcharge_name"], "level 5", $surchargeCcf["surcharge_id"]);
            }
        } else {
            $customerCcf = $this->modelObj->getSurchargeOfCarrier($customer_id, $company_id, $courier_id);
            if (isset($customerCcf["customer_surcharge_value"]) and $customerCcf["customer_surcharge_value"] > 0 and $customerCcf["company_ccf_operator_surcharge"] != 'NONE') {
                $surcharge_ccf_price = $this->_calculateSurcharge($price, $customerCcf["customer_surcharge_value"], $customerCcf["company_ccf_operator_surcharge"], $surcharge_code, $surcharge_code, $surcharge_code, $surcharge_code, "level 2", 0);
            } elseif (isset($customerCcf["customer_surcharge"]) and $customerCcf["customer_surcharge"] > 0 and $customerCcf["customer_operator"] != 'NONE') {
                $surcharge_ccf_price = $this->_calculateSurcharge($price, $customerCcf["customer_surcharge"], $customerCcf["customer_operator"], $surcharge_code, $surcharge_code, $surcharge_code, $surcharge_code, "level 3", 0);
            } elseif (isset($customerCcf["company_carrier_ccf"]) and $customerCcf["company_carrier_operator"] != 'NONE') {
                $surcharge_ccf_price = $this->_calculateSurcharge($price, $customerCcf["company_carrier_ccf"], $customerCcf["company_carrier_operator"], $surcharge_code, $surcharge_code, $surcharge_code, $surcharge_code, "level 5", 0);
            }
        }
        $surchargeCcfPrice = isset($surcharge_ccf_price["price"]) ? $surcharge_ccf_price["price"] : '0';
        $surcharge_ccf_price["price_with_ccf"] = number_format( (float)$surchargeCcfPrice + (float)$price, 2,'.', '');
        $surcharge_ccf_price["carrier_id"] = $courier_id;

        return $surcharge_ccf_price;
    }
}
?>
