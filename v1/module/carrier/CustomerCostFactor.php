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
        $items = $this->modelObj->getCustomerAllCourier($customer_id);

        $customerCarriers = new stdClass;
        foreach($items as $item){
            $courier_code = $item["courier_code"];
            $customerCarriers->$courier_code = new stdClass();

            $customerCarriers->$courier_code->courier_name = $item["courier_name"];
            $customerCarriers->$courier_code->courier_icon = $item["courier_icon"];
            $customerCarriers->$courier_code->courier_description = $item["courier_description"];
            $customerCarriers->$courier_code->courier_code = $item["courier_id"];
        }
        return $customerCarriers;
    }

    private function _getCustomerCourierByCourierCode($customer_id, $courier_code){
        $items = $this->modelObj->getCustomerCourierByCourierCode($customer_id, $courier_code);
        $customerCarriers = new stdClass;
        $customerCarriers->$courier_code = new stdClass();

        $customerCarriers->$courier_code->courier_name = $item["courier_name"];
        $customerCarriers->$courier_code->courier_icon = $item["courier_icon"];
        $customerCarriers->$courier_code->courier_description = $item["courier_description"];
        $customerCarriers->$courier_code->courier_code = $item["courier_id"];

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

    private function _getCompanyCourierSurcharge($company_id, $courier_code){
        $item = $this->modelObj->getCompanyCourierSurcharge($company_id, $courier_code);
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

    public function calculate($data, $courier_code, $customer_id, $company_id){
        $this->customerCarriers = $this->_getCustomerCourierByCourierCode($company_id, $courier_code);
        $testingArray = array("1"=>"parcel_next_day");
       // $surchargeArray = array("1"=>"long_length_surcharge");
        foreach($data as $service_code=>$items){

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
            /*foreach($items[0]->surcharges as $surcharge_code=> $item) {
                $surchargeCcf = $this->_getCustomerCcfSurcharge($customer_id, $surcharge_code);

                if($surchargeCcf["ccf"]!="" and $surchargeCcf["operator"]!="NONE"){
                    $surcharge_ccf_price = $this->_calculateCcf($item,$surchargeCcf["ccf"],$surchargeCcf["operator"]);
                    $items[0]->ccf_surcharges->$surcharge_code = new StdClass();
                    $items[0]->ccf_surcharges->$surcharge_code->rate = $item;

                    $items[0]->ccf_surcharges->$surcharge_code->ccf = $surchargeCcf["ccf"];

                    $items[0]->ccf_surcharges->$surcharge_code->ccf_price = $surcharge_ccf_price;
                    $items[0]->ccf_surcharges->$surcharge_code->total_surcharge_price = $surcharge_ccf_price+$item;
                    $items[0]->ccf_surcharges->$surcharge_code->ccf_operator = $surchargeCcf["operator"];
                    $items[0]->ccf_surcharges->$surcharge_code->ccf_level = "customer_ccf level 1";
                    $items[0]->ccf_surcharges->$surcharge_code->surcharge_name = $surchargeCcf["surcharge_name"];
                    $items[0]->ccf_surcharges->$surcharge_code->surcharge_code = $surchargeCcf["surcharge_code"];
                }elseif($surchargeCcf["carrier_ccf"]!="" and $surchargeCcf["carrier_operator"]!="NONE"){
                    $surcharge_ccf_price = $this->_calculateCcf($item,$surchargeCcf["ccf"],$surchargeCcf["operator"]);
                    $items[0]->ccf_surcharges->$surcharge_code = new StdClass();
                    $items[0]->ccf_surcharges->$surcharge_code->rate = $item;

                    $items[0]->ccf_surcharges->$surcharge_code->ccf = $surchargeCcf["ccf"];

                    $items[0]->ccf_surcharges->$surcharge_code->ccf_price = $surcharge_ccf_price;
                    $items[0]->ccf_surcharges->$surcharge_code->total_surcharge_price = $surcharge_ccf_price+$item;
                    $items[0]->ccf_surcharges->$surcharge_code->ccf_operator = $surchargeCcf["operator"];
                    $items[0]->ccf_surcharges->$surcharge_code->ccf_level = "customer_carrier_ccf level 2";
                    $items[0]->ccf_surcharges->$surcharge_code->surcharge_name = $surchargeCcf["surcharge_name"];
                    $items[0]->ccf_surcharges->$surcharge_code->surcharge_code = $surchargeCcf["surcharge_code"];
                }else{
                    $surchargeCcf = $this->_getCustomerInfoSurcharge($customer_id);
                    $courierCompanyCcf = $this->_getCourierSurchargeCompany($company_id, $surcharge_code);
                    //$companyCourierCcf = $this->_getCompanyCourierSurcharge($company_id, $courier_code);

                    if($surchargeCcf){
                        $surcharge_ccf_price = $this->_calculateCcf($item,$surchargeCcf["surcharge_ccf"],$surchargeCcf["surcharge_operator"]);
                        $items[0]->ccf_surcharges->$surcharge_code = new StdClass();
                        $items[0]->ccf_surcharges->$surcharge_code->rate = $item;

                        $items[0]->ccf_surcharges->$surcharge_code->ccf = $surchargeCcf["surcharge_ccf"];

                        $items[0]->ccf_surcharges->$surcharge_code->ccf_price = $surcharge_ccf_price;
                        $items[0]->ccf_surcharges->$surcharge_code->total_surcharge_price = $surcharge_ccf_price+$item;
                        $items[0]->ccf_surcharges->$surcharge_code->ccf_operator = $surchargeCcf["surcharge_operator"];
                        $items[0]->ccf_surcharges->$surcharge_code->ccf_level = "customer_info_ccf level 3";
                        $items[0]->ccf_surcharges->$surcharge_code->surcharge_name = "N/A";
                        $items[0]->ccf_surcharges->$surcharge_code->surcharge_code = "N/A";
                    }elseif($companyCcf){
                        $surcharge_ccf_price = $this->_calculateCcf($item,$courierCompanyCcf["surcharge_ccf"],$courierCompanyCcf["surcharge_operator"]);
                        $items[0]->ccf_surcharges->$surcharge_code = new StdClass();
                        $items[0]->ccf_surcharges->$surcharge_code->rate = $item;

                        $items[0]->ccf_surcharges->$surcharge_code->ccf = $courierCompanyCcf["surcharge_ccf"];

                        $items[0]->ccf_surcharges->$surcharge_code->ccf_price = $surcharge_ccf_price;
                        $items[0]->ccf_surcharges->$surcharge_code->total_surcharge_price = $surcharge_ccf_price+$item;
                        $items[0]->ccf_surcharges->$surcharge_code->ccf_operator = $courierCompanyCcf["surcharge_operator"];
                        $items[0]->ccf_surcharges->$surcharge_code->ccf_level = "company_ccf level 4";
                        $items[0]->ccf_surcharges->$surcharge_code->surcharge_name = $courierCompanyCcf["surcharge_name"];
                        $items[0]->ccf_surcharges->$surcharge_code->surcharge_code = $courierCompanyCcf["surcharge_code"];
                    }
                }
            }*/
        }
        print_r($data);die;
        return $data;
    }
}
?>