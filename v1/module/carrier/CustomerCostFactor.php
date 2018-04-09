<?php
class CustomerCostFactor{
    public $modelObj = NULL;

    public function __construct(){
        $this->modelObj = Carrier_Model_Carrier::_getInstance();
    }

    private function _getCcfOfService($customer_id, $service_code){
        return $this->modelObj->getCustomerCcfService($customer_id, $service_code);
    }

    private function _getCustomerCcfSurcharge($customer_id, $surcharge_code){
        return $this->modelObj->getCustomerCcfSurcharge($customer_id, $surcharge_code);
    }

    private function _getCustomerAllCourier($customer_id){
        $item = $this->modelObj->getCustomerAllCourier($customer_id);
        $customerCarriers = new stdClass;
        foreach($items as $item){
            $carrier_code = $item["courier_code"];
            $customerCarriers->$carrier_code = new stdClass();

            $customerCarriers->$carrier_code->name = $item["courier_name"];
            $customerCarriers->$carrier_code->icon = $item["courier_icon"];
            $customerCarriers->$carrier_code->description = $item["courier_description"];
            $customerCarriers->$carrier_code->code = $item["courier_id"];
        }
        return $customerCarriers;
    }

    private function _getCustomerCourierByCourierCode($customer_id, $company_id, $carrier_code){
        $item = $this->modelObj->getCustomerCourierByCourierCode($customer_id, $company_id, $carrier_code);
        print_r($item);die;
        $customerCarriers = new stdClass;
        $customerCarriers->$carrier_code = new stdClass();

        $customerCarriers->$carrier_code->name = $item["courier_name"];
        $customerCarriers->$carrier_code->icon = $item["courier_icon"];
        $customerCarriers->$carrier_code->description = $item["courier_description"];
        $customerCarriers->$carrier_code->code = $item["courier_id"];

        return $customerCarriers;
    }

    private function _getCustomerInfoSurcharge($customer_id){
        $item = $this->modelObj->getCustomerInfoSurcharge($customer_id);
        return $item;
    }

    private function _getCourierSurchargeCompany($company_id, $surcharge_code){
        $item = $this->modelObj->getCourierSurchargeCompany($company_id, $surcharge_code);
        return $item;
    }

    private function _getCompanyCourierSurcharge($company_id, $carrier_code){
        $item = $this->modelObj->getCompanyCourierSurcharge($company_id, $carrier_code);
        return $item;
    }

    private function _calculateCcf($price, $ccf_value, $operator, $company_service_code, $company_service_name, $courier_service_code, $courier_service_name, $level){
        if($operator=="FLAT"){
            $price = $ccf_value;
        }elseif($operator=="PERCENTAGE"){ //echo "$price,";die;
            $price = ($price*$ccf_value/100);
        }
        return array("ccf_value"=>$ccf_value,"operator"=>$operator,"price"=>$price,"company_service_code"=>$company_service_code,"company_service_name"=>$company_service_name,"courier_service_code"=>$courier_service_code,"courier_service_name"=>$courier_service_name,"level"=>$level);
    }

    public function calculate($data, $carrier_code, $customer_id, $company_id){

        $this->customerCarriers = $this->_getCustomerCourierByCourierCode($customer_id, $company_id, $carrier_code);
        echo "<pre>";print_r($this->customerCarriers);
        $testingArray = array("1"=>"parcel_next_day");
       // $surchargeArray = array("1"=>"long_length_surcharge");
        foreach($data as $service_code=>$items){
            echo "<pre>";print_r($service_code);
            $serviceCode = $testingArray[$service_code];

            $serviceCcf = $this->modelObj->getCcfOfCarrierServices($serviceCode, $customer_id);


            if($serviceCcf){
                if(isset($serviceCcf["customer_carrier_service_ccf"]) and $serviceCcf["customer_carrier_service_ccf"]>0){
                    $service_ccf_price = $this->_calculateCcf($items[0]->rate->price,$serviceCcf["customer_carrier_service_ccf"],$serviceCcf["customer_carrier_service_operator"],$serviceCcf["company_service_code"],$serviceCcf["company_service_name"],$serviceCcf["courier_service_code"],$serviceCcf["courier_service_name"],"level 1");
                }elseif(isset($serviceCcf["customer_carrier_ccf"]) and $serviceCcf["customer_carrier_ccf"]>0){
                    $service_ccf_price = $this->_calculateCcf($items[0]->rate->price,$serviceCcf["customer_carrier_ccf"],$serviceCcf["customer_carrier_operator"],$serviceCcf["company_service_code"],$serviceCcf["company_service_name"],$serviceCcf["courier_service_code"],$serviceCcf["courier_service_name"],"level 2");
                }elseif(isset($serviceCcf["customer_ccf"]) and $serviceCcf["customer_ccf"]>0){
                    $service_ccf_price = $this->_calculateCcf($items[0]->rate->price,$serviceCcf["customer_ccf"],$serviceCcf["customer_operator"],$serviceCcf["company_service_code"],$serviceCcf["company_service_name"],$serviceCcf["courier_service_code"],$serviceCcf["courier_service_name"],"level 3");
                }elseif(isset($serviceCcf["company_carrier_service_ccf"]) and $serviceCcf["company_carrier_service_ccf"]>0){
                    $service_ccf_price = $this->_calculateCcf($items[0]->rate->price,$serviceCcf["company_carrier_service_ccf"],$serviceCcf["company_carrier_service_operator"],$serviceCcf["company_service_code"],$serviceCcf["company_service_name"],$serviceCcf["courier_service_code"],$serviceCcf["courier_service_name"],"level 4");
                }elseif(isset($serviceCcf["company_carrier_ccf"]) and $serviceCcf["company_carrier_ccf"]>0){
                    $service_ccf_price = $this->_calculateCcf($items[0]->rate->price,$serviceCcf["company_carrier_ccf"],$serviceCcf["company_carrier_operator"],$serviceCcf["company_service_code"],$serviceCcf["company_service_name"],$serviceCcf["courier_service_code"],$serviceCcf["courier_service_name"],"level 4");
                }
            }else{
                $customerCcf = $this->modelObj->getCcfOfCustomer($customer_id);

                if(isset($customerCcf["customer_carrier_ccf"]) and $customerCcf["customer_carrier_ccf"]>0){
                    $service_ccf_price = $this->_calculateCcf($items[0]->rate->price,$customerCcf["customer_carrier_ccf"],$customerCcf["customer_carrier_operator"],$serviceCode,$serviceCode,$serviceCode,$serviceCode,"level 2");
                }elseif(isset($serviceCcf["customer_ccf"]) and $serviceCcf["customer_ccf"]>0){
                    $service_ccf_price = $this->_calculateCcf($items[0]->rate->price,$serviceCcf["customer_ccf"],$serviceCcf["customer_operator"],$serviceCode,$serviceCode,$serviceCode,$serviceCode,"level 3");
                }elseif(isset($serviceCcf["company_carrier_ccf"]) and $serviceCcf["company_carrier_ccf"]>0) {
                    $service_ccf_price = $this->_calculateCcf($items[0]->rate->price, $serviceCcf["company_carrier_ccf"], $serviceCcf["company_carrier_operator"], $serviceCode, $serviceCode, $serviceCode, $serviceCode, "level 5");
                }
            }

            $items[0]->rate->ccf = $service_ccf_price["ccf_value"];
            $items[0]->rate->ccf_price = $service_ccf_price["price"];
            $items[0]->rate->total_base_price = $service_ccf_price["price"]+$items[0]->rate->price;

            $items[0]->rate->ccf_operator = $service_ccf_price["operator"];
            $items[0]->rate->company_service_code = $service_ccf_price["company_service_code"];
            $items[0]->rate->company_service_name = $service_ccf_price["company_service_name"];

            $items[0]->rate->courier_service_code = $service_ccf_price["courier_service_code"];
            $items[0]->rate->courier_service_name = $service_ccf_price["courier_service_name"];

            $items[0]->rate->level = $service_ccf_price["level"];

            $items[0]->ccf_surcharges = new StdClass();
            foreach($items[0]->surcharges as $surcharge_code=> $surcharge_price) {
                $surchargeCcf = $this->modelObj->getCcfOfCarrierSurcharge($surcharge_code, $customer_id);
                print_r($surchargeCcf);die;
            }
        }
        print_r($data);die;
        return $data;
    }
}
?>