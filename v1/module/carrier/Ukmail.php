<?php
require_once "CarrierInterface.php";
final class Ukmail extends Carrier implements CarrierInterface{

    public $param = array();

    public function __construct(){
        $this->param = array();
        $this->validationError = array();
        $this->_preparedData = new stdClass();

        $this->_preparedData->carrier = "ukmail";
        $this->_preparedData->service = "1";
    }

    public function carrierInfo(){
        $this->carrierInfo = Carrier_Model_Carrier::_getInstance()->getCustomerCourierByCourierCode($this->param->customer_id,$this->param->company_id,"ukmail");
    }

    public function validateCollectionAddress(){
        if(!isset($this->param->from)){
            $this->validationError["from"] = "Collection address required";
        }else{
            if(!isset($this->param->from->name)){
                $this->validationError["from"]["name"] = "Collection name is required";
            }

            if(!isset($this->param->from->company)){
                $this->validationError["from"]["company"] = "Collection company is required";
            }

            if(!isset($this->param->from->street1)){
                $this->validationError["from"]["street1"] = "Collection street1 is required";
            }

            if(!isset($this->param->from->street2)){
                $this->validationError["from"]["street2"] = "Collection street2 is required";
            }

            if(!isset($this->param->from->city)){
                $this->validationError["from"]["city"] = "Collection city is required";
            }

            if(!isset($this->param->from->state)){
                $this->validationError["from"]["state"] = "Collection State is required";
            }

            if(!isset($this->param->from->zip)){
                $this->validationError["from"]["zip"] = "Collection postcode is required";
            }

            if(!isset($this->param->from->alpha3_code)){
                $this->validationError["from"]["alpha3_code"] = "Collection country alphacode 3 is required";
            }

            if(!isset($this->param->from->country_name)){
                $this->validationError["from"]["country_name"] = "Collection country is required";
            }
        }
    }

    public function validateDeliveryAddress(){
        if(!isset($this->param->to)){
            $this->validationError["to"] = "Delivery address required";
        }else{
            /*if(!isset($this->param->to->name)){
                $this->validationError["to"]["name"] = "Delivery name is required";
            }

            if(!isset($this->param->to->company)){
                $this->validationError["to"]["company"] = "Delivery company is required";
            }

            if(!isset($this->param->to->street1)){
                $this->validationError["to"]["street1"] = "Delivery street1 is required";
            }

            if(!isset($this->param->to->street2)){
                $this->validationError["to"]["street2"] = "Delivery street2 is required";
            }

            if(!isset($this->param->to->city)){
                $this->validationError["to"]["city"] = "Delivery city is required";
            }

            if(!isset($this->param->to->state)){
                $this->validationError["to"]["state"] = "Delivery State is required";
            }*/

            if(!isset($this->param->to->zip)){
                $this->validationError["to"]["zip"] = "Delivery postcode is required";
            }

            if(!isset($this->param->to->alpha3_code)){
                $this->validationError["to"]["alpha3_code"] = "Delivery country alphacode 3 is required";
            }

            if(!isset($this->param->to->country_name)){
                $this->validationError["to"]["country_name"] = "Delivery country is required";
            }
        }
    }

    public function validateShipDate(){
        if(!isset($this->param->ship_date)){
            $this->validationError["ship_date"] = "Ship date is required";
        }
    }

    public function validatePackage(){
        if(!isset($this->param->package)){
            $this->validationError["package"] = "Package is required";
        }
        if(!is_array($this->param->package)){
            $this->validationError["package"] = "Package must be array";
        }
        else{
            foreach($this->param->package as $key=>$item){
                if(!isset($item->packaging_type) || empty($item->packaging_type)){
                    $this->validationError["package"][$key]["packaging_type"] = "Packaging type is required";
                }
                if(!isset($item->width) || empty($item->width)){
                    $this->validationError["package"][$key]["width"] = "Width is required";
                }

                if(!isset($item->length) || empty($item->length)){
                    $this->validationError["package"][$key]["length"] = "Length is required";
                }
                if(!isset($item->height) || empty($item->height)){
                    $this->validationError["package"][$key]["height"] = "Height is required";
                }
                if(!isset($item->dimension_unit) || empty($item->dimension_unit)){
                    $this->validationError["package"][$key]["dimension_unit"] = "Dimension unit is required";
                }
                if(!isset($item->weight) || empty($item->weight)){
                    $this->validationError["package"][$key]["weight"] = "Weight is required";
                }
                if(!isset($item->weight_unit) || empty($item->weight_unit)){
                    $this->validationError["package"][$key]["weight_unit"] = "Weight unit is required";
                }
            }
        }
    }

    public function validateParams(){
        $this->validateCollectionAddress();
        $this->validateDeliveryAddress();
        $this->validateShipDate();
        $this->validatePackage();
    }

    public function prepareParams(){
        $this->_preparedData->from = new stdClass();
        $this->_preparedData->from->name = $this->param->from->name;
        $this->_preparedData->from->company = "BigCommerce";//$this->param->from->company;
        $this->_preparedData->from->phone = $this->param->from->phone;
        $this->_preparedData->from->street1 = $this->param->from->street1;
        $this->_preparedData->from->street2 = $this->param->from->street2;
        $this->_preparedData->from->city = $this->param->from->city;
        $this->_preparedData->from->state = $this->param->from->state;
        $this->_preparedData->from->zip = $this->param->from->zip;
        $this->_preparedData->from->country = $this->param->from->alpha3_code;
        $this->_preparedData->from->country_name = $this->param->from->country_name;

        $this->_preparedData->to = new stdClass();
        $this->_preparedData->to->name = "";//$this->param->to->name;
        $this->_preparedData->to->company = "";//$this->param->to->company;
        $this->_preparedData->to->street1 = "";//$this->param->to->street1;
        $this->_preparedData->to->street2 = "";//$this->param->to->street2;
        $this->_preparedData->to->city = "";//$this->param->to->city;
        $this->_preparedData->to->state = "";//$this->param->to->state;
        $this->_preparedData->to->zip = $this->param->to->zip;
        $this->_preparedData->to->country = $this->param->to->alpha3_code;
        $this->_preparedData->to->country_name = $this->param->to->country_name;

        $this->_preparedData->extra = new stdClass();
        $this->_preparedData->extra->service_key = "1";
        $this->_preparedData->extra->long_length = "";
        $this->_preparedData->extra->bookin = "";
        $this->_preparedData->extra->exchange_on_delivery = "";
        $this->_preparedData->extra->reference_id = "";// "123442122M";
        $this->_preparedData->extra->region_code = "";
        $this->_preparedData->extra->confirmation = "";// "Delivery Confirmation";
        $this->_preparedData->extra->is_document = "";
        $this->_preparedData->extra->auto_return = "";
        $this->_preparedData->extra->return_service_id = "";
        $this->_preparedData->extra->special_instruction = "";


        $this->_preparedData->package = array();
        foreach($this->param->package as $key => $item){
            array_push($this->_preparedData->package, array("packaging_type"=>$item->packaging_type, "width"=>$item->width, "length"=>$item->length, "height"=>$item->height, "dimension_unit"=>$item->dimension_unit, "weight"=>$item->weight,"weight_unit"=>$item->weight_unit));
        }

        $this->_preparedData->insurance = new stdClass();
        $this->_preparedData->insurance->value = "0.0";
        $this->_preparedData->insurance->currency = "GBP";

        $this->_preparedData->rate = new stdClass();
    }

    public function serviceInfo($param){
        foreach($param as $serviceKey => $items){
            $serviceInfo = Carrier_Model_Carrier::_getInstance()->getCarrierServiceInfo($this->carrierInfo["courier_id"], $serviceKey);

            if(count($serviceInfo)>0){
                foreach($items as $key => $item){
                    $item->service_options->service_name = $serviceInfo["service_name"];

                    $item->service_options->service_code = $serviceInfo["service_code"];
                    $item->service_options->service_icon = $serviceInfo["service_icon"];
                    $item->service_options->service_description = $serviceInfo["service_description"];
                }
            }
        }
        return $param;
    }

    public function searchService($param){
        $this->param = $param;

        $this->validateParams();
        $this->carrierInfo();

        if(count($this->validationError)>0){
            return array("status"=>"error", "response"=>$this->validationError, "carrier_code"=>$this->carrierInfo["courier_code"],"carrier_name"=>$this->carrierInfo["courier_name"],"carrier_icon"=>$this->carrierInfo["courier_icon"]);
        }else{
            $this->prepareParams();

            $this->_preparedData->ship_date = $param->ship_date;

            $ukmailService = $this->_send($this->_preparedData);
            print_r( $ukmailService );die;
            if(is_array($ukmailService) and count($ukmailService)>0){
                $ukmailService = $this->serviceInfo($ukmailService);

                return array("status"=>"success", "response"=>$ukmailService, "carrier_code"=>$this->carrierInfo["courier_code"],"carrier_name"=>$this->carrierInfo["courier_name"],"carrier_icon"=>$this->carrierInfo["courier_icon"],"carrier_description"=>$this->carrierInfo["courier_description"],"carrier_id"=>$this->carrierInfo["courier_id"]);

            }else{
                return array("status"=>"error", "message"=>"Service not found");
            }
        }
    }
}
?>