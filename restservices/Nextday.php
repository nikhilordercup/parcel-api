<?php 
final class Nextday extends Booking
{
    public $modelObj        = null;
    public $customerccf     = null;
    public $googleApi       = null;
    public $googleRequest   = array();
    public $bookparam       = array();
    public $endpoint        = null;
    public $request         = null;
    public $allShipmentsObj = null;
    public $webApiToken     = null;
    public $collectionModel = null;
    public $nextDay         = null;
    public function __construct($data){
        $this->_parentObj = parent::__construct(array("email" => $data->email, "access_token" => $data->access_token));
        $this->_param = $data;
        $this->customerccf              = new CustomerCostFactor();
        $this->googleApi                = new Module_Google_Api((object) array("email" => $data->email, "access_token" => $data->access_token));
        $this->modelObjCorePrime        = new Coreprime_Model_Api();
        $this->resrServiceModel         = new restservices_Model();
        $this->allShipmentsObj          = new allShipments((object) array("email" => $data->email, "access_token" => $data->access_token));
        $this->endpoint                 = $data->endpoint;
        $this->webApiToken              = $data->web_token;
        $this->collectionModel = Collection::_getInstance(); //new Collection();
    }
    public function isValidServiceDate($date, $format = 'Y-m-d H:i'){
                    $d = DateTime::createFromFormat($format, $date);
                    return $d && $d->format($format) == $date;
        }
    private function getGeoLocationByPostCode($postcode,$counter=0){
       $geoData =  $this->googleApi->getGeoPositionFromPostcode((object) array('postcode'=>$postcode));
       if($geoData['status']!='success'){
          $counter++;
          if($counter>3){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Invalid postcode '.$postcode;
                 $response["error_code"] = "ERROR006";
                 return $response;
          }
          return $this->getGeoLocationByPostCode($postcode,$counter);
       }
       return $geoData;
    }
     private
    function _getCarrierInfo($data,$param)
    {  
        foreach($data as $carrier_code => $lists)
        { 
            switch (strtoupper($carrier_code))
            {
                case 'UKMAIL':
                    $this->getUkmailServiceList($carrier_code, $lists,$param);
                    break;

                case 'DHL':
                    $this->getDhlServiceList($carrier_code, $lists,$param);
                    break;

                case 'PNP':
                    $this->getPNPServiceList($carrier_code, $lists,$param);
                    break;
				default:
                    $this->getPNPServiceList($carrier_code, $lists,$param);	
            }
        }
          
                   
        return $data;
    }
    
    private
    function getPNPServiceList($carrier_code, $lists,$param)
    {
        foreach($lists as $key1 => $list)
        {
            foreach($list as $accountNumber => $items)
            {
                foreach($items as $key3 => $item)
                {
                    foreach($item as $service_code => $services)
                    {
                        if (!isset($services[0]->rate->error))
                        {
                            $ratePrice = $services[0]->rate->price;
                            $accountId = isset($this->carrierList[$accountNumber]["account_id"]) ? $this->carrierList[$accountNumber]["account_id"] : $this->carrierList[$accountNumber]["carrier_id"];
                            $serviceCcf = $this->customerccf->calculateServiceCcf($service_code, $ratePrice, $accountId, $param->customer_id, $param->company_id);
                            $services[0]->rate->price = $serviceCcf["price_with_ccf"];
                            $services[0]->rate->info = $serviceCcf;
                            foreach($services as $key5 => $service)
                            {
                                if (isset($service->rate->error))
                                {
                                    return (object)array(
                                        "status" => "error",
                                        "message" => $service->rate->error
                                    );
                                }

                                if (isset($service->taxes))
                                {
                                    if (isset($service->taxes->total_tax))
                                    {
                                        $service->taxes->total_tax = number_format($service->taxes->total_tax, 2);
                                    }

                                    if (isset($service->taxes->tax_percentage))
                                    {
                                        $service->taxes->tax_percentage = number_format($service->taxes->tax_percentage, 2);
                                    }
                                }

                                $surchargeWithCcfPrice = 0;
                                $surchargePrice = 0;
                                $service->collected_by = $this->carrierList[$accountNumber]["collected_by"];
                                foreach($service->collected_by as $collected_key => $collected_item)
                                {
                                    $surchargeWithCcfPrice = 0;
                                    $surchargePrice = 0;
                                    if (isset($service->surcharges))
                                    {
                                        foreach($service->surcharges as $surcharge_code => $surcharge_price)
                                        {
                                            if ($collected_item["carrier_code"] != $carrier_code and $surcharge_code == "collection_surcharge")
                                            {
                                                $surchargeCcf["original_price"] = $collected_item["pickup_surcharge"];
                                                $surchargeCcf["surcharge_value"] = $collected_item["pickup_surcharge"];
                                                $surchargeCcf["operator"] = "FLAT";
                                                $surchargeCcf["price"] = $collected_item["pickup_surcharge"];
                                                $surchargeCcf["level"] = "level 1";
                                                $surchargeCcf["surcharge_id"] = "0";
                                                $surchargeCcf["price_with_ccf"] = $collected_item["pickup_surcharge"];
                                                $surchargeCcf["company_surcharge_code"] = "collection_surcharge";
                                                $surchargeCcf["company_surcharge_name"] = "Collection Surcharge";
                                                $surchargeCcf["courier_surcharge_code"] = "collection_surcharge";
                                                $surchargeCcf["courier_surcharge_name"] = "Collection Surcharge";
                                                $surchargeCcf["carrier_id"] = $collected_item["carrier_id"];
                                            }
                                            else
                                            {
                                                $surchargeCcf = $this->customerccf->calculateSurchargeCcf($surcharge_code, $param->customer_id, $param->company_id, $this->carrierList[$accountNumber]["account_id"], $surcharge_price);
                                            }

                                            $collected_item["surcharges"][$surcharge_code] = $surchargeCcf;
                                            $surchargeWithCcfPrice+= $surchargeCcf["price_with_ccf"];
                                            if ($surchargeCcf["operator"] != "FLAT")
                                            {
                                                $surchargePrice+= $surchargeCcf["original_price"];
                                            }
                                        }
                                    }

                                    $collected_item["carrier_price_info"]["price"] = $serviceCcf["original_price"];
                                    $collected_item["customer_price_info"]["price"] = $serviceCcf["price_with_ccf"];
                                    $collected_item["carrier_price_info"]["surcharges"] = number_format($surchargePrice, 2);
                                    $collected_item["customer_price_info"]["surcharges"] = number_format($surchargeWithCcfPrice, 2);
                                    $collected_item["carrier_price_info"]["taxes"] = isset($service->taxes->total_tax) ? number_format($service->taxes->total_tax, 2) : 0;
                                    $collected_item["customer_price_info"]["taxes"] = number_format((($serviceCcf["price_with_ccf"] + $surchargeWithCcfPrice) * (isset($service->taxes->tax_percentage) ? $service->taxes->tax_percentage : 0) / 100) , 2);
                                    $collected_item["carrier_price_info"]["grand_total"] = number_format($serviceCcf["original_price"] + $surchargePrice + (isset($service->taxes->total_tax) ? $service->taxes->total_tax : 0) , 2);
                                    $collected_item["customer_price_info"]["grand_total"] = number_format($serviceCcf["price_with_ccf"] + $surchargeWithCcfPrice + $collected_item["customer_price_info"]["taxes"], 2);
                                    $service->collected_by[$collected_key] = $collected_item;
                                }

                                $service->carrier_info = array(
                                    "highlight_class" => $this->carrierList[$accountNumber]["highlight_class"],
                                    "carrier_id" => $this->carrierList[$accountNumber]["carrier_id"],
                                    "name" => $this->carrierList[$accountNumber]["name"],
                                    "icon" => $this->carrierList[$accountNumber]["icon"],
                                    "code" => $this->carrierList[$accountNumber]["carrier_code"],
                                    "description" => $this->carrierList[$accountNumber]["description"],
                                    "account_number" => $this->carrierList[$accountNumber]["account_number"],
                                    "account_id" => $this->carrierList[$accountNumber]["account_id"],
                                    "is_internal" => $this->carrierList[$accountNumber]["internal"]
                                );
                                $service->service_info = array(
                                    "code" => $this->carrierList[$accountNumber]["services"][$service_code]["service_code"],
                                    "name" => $this->carrierList[$accountNumber]["services"][$service_code]["service_name"]
                                );
                            }
                        }
                        else
                        {
                            unset($item->$service_code);
                        }
                    }
                }
            }
        }
    }

    private
    function getUkmailServiceList($carrier_code, $lists,$param)
    {   
        foreach($lists as $key1 => $list)
        {
            foreach($list as $accountNumber => $items)
            {
                foreach($items as $key3 => $item)
                {
                    foreach($item as $service_code => $services)
                    {
                        if (!isset($services[0]->rate->error))
                        {
                            $ratePrice = $services[0]->rate->price;
                            $accountId = isset($this->carrierList[$accountNumber]["account_id"]) ? $this->carrierList[$accountNumber]["account_id"] : $this->carrierList[$accountNumber]["carrier_id"];
                            $serviceCcf = $this->customerccf->calculateServiceCcf($service_code, $ratePrice, $accountId, $param->customer_id, $param->company_id);
                            
                            $services[0]->rate->price = $serviceCcf["price_with_ccf"];
                            $services[0]->rate->info = $serviceCcf;
                            foreach($services as $key5 => $service)
                            {
                                if (isset($service->rate->error))
                                {
                                    return (object)array(
                                        "status" => "error",
                                        "message" => $service->rate->error
                                    );
                                }

                                if (isset($service->taxes))
                                {
                                    if (isset($service->taxes->total_tax))
                                    {
                                        $service->taxes->total_tax = number_format($service->taxes->total_tax, 2);
                                    }

                                    if (isset($service->taxes->tax_percentage))
                                    {
                                        $service->taxes->tax_percentage = number_format($service->taxes->tax_percentage, 2);
                                    }
                                }
                                    
                                $surchargeWithCcfPrice = 0;
                                $surchargePrice = 0;
                                $service->collected_by = $this->carrierList[$accountNumber]["collected_by"];
                                foreach($service->collected_by as $collected_key => $collected_item)
                                {
                                    $surchargeWithCcfPrice = 0;
                                    $surchargePrice = 0;
                                    if (isset($service->surcharges))
                                    {
                                        foreach($service->surcharges as $surcharge_code => $surcharge_price)
                                        {
                                            if ($collected_item["carrier_code"] != $carrier_code and $surcharge_code == "collection_surcharge")
                                            {
                                                $surchargeCcf["original_price"] = $collected_item["pickup_surcharge"];
                                                $surchargeCcf["surcharge_value"] = $collected_item["pickup_surcharge"];
                                                $surchargeCcf["operator"] = "FLAT";
                                                $surchargeCcf["price"] = $collected_item["pickup_surcharge"];
                                                $surchargeCcf["level"] = "level 1";
                                                $surchargeCcf["surcharge_id"] = "0";
                                                $surchargeCcf["price_with_ccf"] = $collected_item["pickup_surcharge"];
                                                $surchargeCcf["company_surcharge_code"] = "collection_surcharge";
                                                $surchargeCcf["company_surcharge_name"] = "Collection Surcharge";
                                                $surchargeCcf["courier_surcharge_code"] = "collection_surcharge";
                                                $surchargeCcf["courier_surcharge_name"] = "Collection Surcharge";
                                                $surchargeCcf["carrier_id"] = $collected_item["carrier_id"];
                                            }
                                            else
                                            {
                                                $surchargeCcf = $this->customerccf->calculateSurchargeCcf($surcharge_code, $param->customer_id, $param->company_id, $this->carrierList[$accountNumber]["account_id"], $surcharge_price);
                                            }

                                            $collected_item["surcharges"][$surcharge_code] = $surchargeCcf;
                                            $surchargeWithCcfPrice+= $surchargeCcf["price_with_ccf"];
                                            if ($surchargeCcf["operator"] != "FLAT")
                                            {
                                                $surchargePrice+= $surchargeCcf["original_price"];
                                            }
                                        }
                                    }
                                         
                                    $collected_item["carrier_price_info"]["price"] = $serviceCcf["original_price"];
                                    $collected_item["customer_price_info"]["price"] = $serviceCcf["price_with_ccf"];
                                    $collected_item["carrier_price_info"]["surcharges"] = number_format($surchargePrice, 2);
                                    $collected_item["customer_price_info"]["surcharges"] = number_format($surchargeWithCcfPrice, 2);
                                    $collected_item["carrier_price_info"]["taxes"] = isset($service->taxes->total_tax) ? number_format($service->taxes->total_tax, 2) : 0;
                                    $collected_item["customer_price_info"]["taxes"] = number_format((($serviceCcf["price_with_ccf"] + $surchargeWithCcfPrice) * (isset($service->taxes->tax_percentage) ? $service->taxes->tax_percentage : 0) / 100) , 2);
                                    $collected_item["carrier_price_info"]["grand_total"] = number_format($serviceCcf["original_price"] + $surchargePrice + (isset($service->taxes->total_tax) ? $service->taxes->total_tax : 0) , 2);
                                    $collected_item["customer_price_info"]["grand_total"] = number_format($serviceCcf["price_with_ccf"] + $surchargeWithCcfPrice + $collected_item["customer_price_info"]["taxes"], 2);
                                    $service->collected_by[$collected_key] = $collected_item;
                                }
                                
                                $service->carrier_info = array(
                                    "highlight_class" => $this->carrierList[$accountNumber]["highlight_class"],
                                    "carrier_id" => $this->carrierList[$accountNumber]["carrier_id"],
                                    "name" => $this->carrierList[$accountNumber]["name"],
                                    "icon" => $this->carrierList[$accountNumber]["icon"],
                                    "code" => $this->carrierList[$accountNumber]["carrier_code"],
                                    "description" => $this->carrierList[$accountNumber]["description"],
                                    "account_number" => $this->carrierList[$accountNumber]["account_number"],
                                    "account_id" => $this->carrierList[$accountNumber]["account_id"],
                                    "is_internal" => $this->carrierList[$accountNumber]["internal"]
                                );
                                $service->service_info = array(
                                    "code" => $this->carrierList[$accountNumber]["services"][$service_code]["service_code"],
                                    "name" => $this->carrierList[$accountNumber]["services"][$service_code]["service_name"]
                                );
                            }
                            
                        }
                        else
                        {
                            unset($item->$service_code);
                        }
                    }
                }
            }
        }
        return $lists;
    }

    /************DHL Service list (Start from Here)**********/
    private
    function getDhlServiceList($carrier_code, $lists,$param)
    {
        foreach($lists as $key1 => $list)
        {
            foreach($list as $accountNumber => $items)
            {
                foreach($items as $key3 => $item)
                {
                    foreach($item as $service_code => $services)
                    {
                        if (!isset($services[0]->rate->error))
                        {
							$services = $this->calculateSurchargesAndBasePrice($services);/**surcharges and base price calculation**/
                            $ratePrice = $services[0]->rate->weight_charge;
                            $accountId = isset($this->carrierList[$accountNumber]["account_id"]) ? $this->carrierList[$accountNumber]["account_id"] : $this->carrierList[$accountNumber]["carrier_id"];
                            $serviceCcf = $this->customerccf->calculateServiceCcf($service_code, $ratePrice, $accountId, $param->customer_id, $param->company_id);
                            $services[0]->rate->price = $serviceCcf["price_with_ccf"];
                            $services[0]->rate->info = $serviceCcf;
                            if (!isset($services[0]->rate->currency))
                            {
                                $currencyKey = 0;
                                
                                $currency = $param->collection->address->currency_code;
                                $services[0]->rate->currency = $currency;
                            }
                           
                            !isset($services[0]->rate->rate_type) ? ($services[0]->rate->rate_type = 'Weight') : '';
                            foreach($services as $key5 => $service)
                            {
                                if (isset($service->rate->error))
                                {
                                    return (object)array(
                                        "status" => "error",
                                        "message" => $service->rate->error
                                    );
                                }

                                if (isset($service->taxes))
                                {
                                    if (isset($service->taxes->total_tax))
                                    {
                                        $service->taxes->total_tax = number_format($service->taxes->total_tax, 2);
                                    }

                                    if (isset($service->taxes->tax_percentage))
                                    {
                                        $service->taxes->tax_percentage = number_format($service->taxes->tax_percentage, 2);
                                    }
                                }
								
								if((isset($services[0]->rate->total_tax)) && ($services[0]->rate->total_tax!=0)){
									$service->taxes = (object)array();
									$service->taxes->total_tax = 0;
									$service->taxes->total_tax = number_format($services[0]->rate->total_tax, 2);
									$service->taxes->tax_percentage = 20.00;
								}

                                $surchargeWithCcfPrice = 0;
                                $surchargePrice = 0;
                                $service->collected_by = $this->carrierList[$accountNumber]["collected_by"];
                                $collected_item["carrier_price_info"]["taxes"] = isset($service->taxes->tax_percentage) ? (number_format((($serviceCcf["original_price"] + $surchargePrice) * $service->taxes->tax_percentage / 100) , 2)) : 0;
                                $collected_item["customer_price_info"]["taxes"] = isset($service->taxes->tax_percentage) ? (number_format((($serviceCcf["price_with_ccf"] + $surchargeWithCcfPrice) * $service->taxes->tax_percentage / 100) , 2)) : 0;
                                foreach($service->collected_by as $collected_key => $collected_item)
                                {
                                    $surchargeWithCcfPrice = 0;
                                    $surchargePrice = 0;
									
                                    if (isset($service->surcharges))
                                    {
                                        foreach($service->surcharges as $surcharge_code => $surcharge_price)
                                        {
                                            if ($collected_item["carrier_code"] != $carrier_code and $surcharge_code == "collection_surcharge")
                                            {
                                                $surchargeCcf["original_price"] = $collected_item["pickup_surcharge"];
                                                $surchargeCcf["surcharge_value"] = $collected_item["pickup_surcharge"];
                                                $surchargeCcf["operator"] = "FLAT";
                                                $surchargeCcf["price"] = $collected_item["pickup_surcharge"];
                                                $surchargeCcf["level"] = "level 1";
                                                $surchargeCcf["surcharge_id"] = "0";
                                                $surchargeCcf["price_with_ccf"] = $collected_item["pickup_surcharge"];
                                                $surchargeCcf["company_surcharge_code"] = "collection_surcharge";
                                                $surchargeCcf["company_surcharge_name"] = "Collection Surcharge";
                                                $surchargeCcf["courier_surcharge_code"] = "collection_surcharge";
                                                $surchargeCcf["courier_surcharge_name"] = "Collection Surcharge";
                                                $surchargeCcf["carrier_id"] = $collected_item["carrier_id"];
                                            }
                                            else
                                            {
                                                $surchargeCcf = $this->customerccf->calculateSurchargeCcf($surcharge_code, $param->customer_id, $param->company_id, $this->carrierList[$accountNumber]["account_id"], $surcharge_price);
                                            }

                                            $collected_item["surcharges"][$surcharge_code] = $surchargeCcf;
                                            $surchargeWithCcfPrice+= $surchargeCcf["price_with_ccf"];
                                            if (isset($surchargeCcf["operator"]) && $surchargeCcf["operator"] != "FLAT")
                                            {
                                                $surchargePrice+= $surchargeCcf["original_price"];
                                            }
                                        }
                                    }

                                    $collected_item["carrier_price_info"]["price"] = isset($serviceCcf["original_price"]) ? $serviceCcf["original_price"] : 0;
                                    $collected_item["customer_price_info"]["price"] = isset($serviceCcf["price_with_ccf"]) ? $serviceCcf["price_with_ccf"] : 0;
                                    $collected_item["carrier_price_info"]["surcharges"] = number_format($surchargePrice, 2);
                                    $collected_item["customer_price_info"]["surcharges"] = number_format($surchargeWithCcfPrice, 2);
                                    $collected_item["carrier_price_info"]["taxes"] = isset($service->taxes->total_tax) ? number_format($service->taxes->total_tax, 2) : 0;
                                    $collected_item["customer_price_info"]["taxes"] = number_format((($serviceCcf["price_with_ccf"] + $surchargeWithCcfPrice) * (isset($service->taxes->tax_percentage) ? $service->taxes->tax_percentage : 0) / 100) , 2);
                                    $collected_item["carrier_price_info"]["grand_total"] = number_format((isset($serviceCcf["original_price"]) ? $serviceCcf["original_price"] : 0) + $surchargePrice + (isset($service->taxes->total_tax) ? $service->taxes->total_tax : 0) , 2);
                                    $collected_item["customer_price_info"]["grand_total"] = number_format((isset($serviceCcf["price_with_ccf"]) ? $serviceCcf["price_with_ccf"] : 0) + $surchargeWithCcfPrice + $collected_item["customer_price_info"]["taxes"], 2);
                                    $service->collected_by[$collected_key] = $collected_item;
                                }

                                $service->carrier_info = array(
                                    "carrier_id" => $this->carrierList[$accountNumber]["carrier_id"],
                                    "name" => $this->carrierList[$accountNumber]["name"],
                                    "icon" => $this->carrierList[$accountNumber]["icon"],
                                    "code" => $this->carrierList[$accountNumber]["carrier_code"],
                                    "description" => $this->carrierList[$accountNumber]["description"],
                                    "account_number" => $this->carrierList[$accountNumber]["account_number"],
                                    "account_id" => $this->carrierList[$accountNumber]["account_id"],
                                    "is_internal" => $this->carrierList[$accountNumber]["internal"]
                                );
                                $service->service_info = array(
                                    "code" => $this->carrierList[$accountNumber]["services"][$service_code]["service_code"],
                                    "name" => $this->carrierList[$accountNumber]["services"][$service_code]["service_name"]
                                );
                            }
                        }
                        else
                        {
                            unset($item->$service_code);
                        }
                    }
                }
            }
        }
    }
   public function calculateSurchargesAndBasePrice($services){
	 foreach($services as $key => $service)
		{
			$services[$key]->surcharges = (object)array();
			if((isset($services[0]->rate->total_tax)) && ($services[0]->rate->total_tax!=0)){			
				if(isset($service->rate->fuel_surcharge)){
					$service->rate->fuel_surcharge = ($service->rate->fuel_surcharge/6)*5;
					$service->rate->fuel_surcharge = number_format($service->rate->fuel_surcharge,2);
					$services[$key]->surcharges->fuel_surcharge = $service->rate->fuel_surcharge;
				}else{
					$services[$key]->surcharges->fuel_surcharge = '';
				}
				if(isset($service->rate->remote_area_delivery)){
					$service->rate->remote_area_delivery = ($service->rate->remote_area_delivery/6)*5;
					$service->rate->remote_area_delivery = number_format($service->rate->remote_area_delivery,2);
					$services[$key]->surcharges->remote_area_delivery = $service->rate->remote_area_delivery;
				}else{
					$services[$key]->surcharges->remote_area_delivery = '';
				}
				if(isset($service->rate->insurance_charge)){
					$service->rate->insurance_charge = ($service->rate->insurance_charge/6)*5;
					$service->rate->insurance_charge = number_format($service->rate->insurance_charge,2);
					$services[$key]->surcharges->insurance_charge = $service->rate->insurance_charge;
				}else{
					$services[$key]->surcharges->insurance_charge = '';
				}
				if(isset($service->rate->over_weight_charge)){
					$service->rate->over_weight_charge = ($service->rate->over_weight_charge/6)*5;
					$service->rate->over_weight_charge = number_format($service->rate->over_weight_charge,2);
					$services[$key]->surcharges->over_weight_charge = $service->rate->over_weight_charge;
				}else{
					$services[$key]->surcharges->over_weight_charge = '';
				}
			}else{
				if(isset($service->rate->fuel_surcharge)){
					$service->rate->fuel_surcharge = number_format($service->rate->fuel_surcharge,2);
					$services[$key]->surcharges->fuel_surcharge = $service->rate->fuel_surcharge;
				}else{
					$services[$key]->surcharges->fuel_surcharge = '';
				}
				if(isset($service->rate->remote_area_delivery)){
					$service->rate->remote_area_delivery = number_format($service->rate->remote_area_delivery,2);
					$services[$key]->surcharges->remote_area_delivery = $service->rate->remote_area_delivery;
				}else{
					$services[$key]->surcharges->remote_area_delivery = '';
				}
				if(isset($service->rate->insurance_charge)){
					$service->rate->insurance_charge = number_format($service->rate->insurance_charge,2);
					$services[$key]->surcharges->insurance_charge = $service->rate->insurance_charge;
				}else{
					$services[$key]->surcharges->insurance_charge = '';
				}
				if(isset($service->rate->over_weight_charge)){
					$service->rate->over_weight_charge = number_format($service->rate->over_weight_charge,2);
					$services[$key]->surcharges->over_weight_charge = $service->rate->over_weight_charge;
				}else{
					$services[$key]->surcharges->over_weight_charge = '';
				}
			}
			
			$service->rate->total_surcharge = $service->rate->fuel_surcharge + $service->rate->remote_area_delivery + $service->rate->insurance_charge + $service->rate->over_weight_charge;
			$service->rate->weight_charge_with_tax = $service->rate->weight_charge;
			$service->rate->weight_charge = $service->rate->weight_charge - ($service->rate->total_surcharge + $service->rate->total_tax);
		}	
		return $services;
	}
    private function _getCarrierInfo____($data,$param){ 
        foreach($data as $carrier_code => $lists){
            foreach($lists as $key1 => $list){
                foreach($list as $accountNumber => $items){
                    foreach($items as $key3 => $item){
                        foreach($item as $service_code => $services) {
                            //calculate service ccf
                            if (!isset($services[0]->rate->error)) {
                                $accountId = isset($this->carrierList[$accountNumber]["account_id"]) ? $this->carrierList[$accountNumber]["account_id"] :
                                $this->carrierList[$accountNumber]["carrier_id"];
                                $serviceCcf = $this->customerccf->calculateServiceCcf($service_code, $services[0]->rate->price, $accountId, $param->customer_id, $param->company_id);//$services[0]->rate
                                
                                $services[0]->rate->price = $serviceCcf["price_with_ccf"];
                                $services[0]->rate->info = $serviceCcf;
                                
                                foreach ($services as $key5 => $service) {
                                    if (isset($service->rate->error)) {
                                        return (object)array("status" => "error", "message" => $service->rate->error);
                                    }

                                    //set tax number format
                                    if (isset($service->taxes)) {
                                        if (isset($service->taxes->total_tax)) {
                                            $service->taxes->total_tax = number_format($service->taxes->total_tax, 2);
                                        }
                                        if (isset($service->taxes->tax_percentage)) {
                                            $service->taxes->tax_percentage = number_format($service->taxes->tax_percentage, 2);
                                        }
                                    }

                                    //calculate surcharge ccf
                                    $surchargeWithCcfPrice = 0;
                                    $surchargePrice = 0;
                                    $service->collected_by = $this->carrierList[$accountNumber]["collected_by"];
                                    
                                    foreach ($service->collected_by as $collected_key => $collected_item) {
                                        $surchargeWithCcfPrice = 0;
                                        $surchargePrice = 0;
                                        foreach ($service->surcharges as $surcharge_code => $surcharge_price) {
                                            if ($collected_item["carrier_code"] != $carrier_code and $surcharge_code == "collection_surcharge") {
                                                $surchargeCcf["original_price"] = $collected_item["pickup_surcharge"];
                                                $surchargeCcf["surcharge_value"] = $collected_item["pickup_surcharge"];
                                                $surchargeCcf["operator"] = "FLAT";
                                                $surchargeCcf["price"] = $collected_item["pickup_surcharge"];
                                                $surchargeCcf["level"] = "level 1";
                                                $surchargeCcf["surcharge_id"] = "0";
                                                $surchargeCcf["price_with_ccf"] = $collected_item["pickup_surcharge"];

                                                $surchargeCcf["company_surcharge_code"] = "collection_surcharge";
                                                $surchargeCcf["company_surcharge_name"] = "Collection Surcharge";
                                                $surchargeCcf["courier_surcharge_code"] = "collection_surcharge";
                                                $surchargeCcf["courier_surcharge_name"] = "Collection Surcharge";
                                                $surchargeCcf["carrier_id"] = $this->carrierList[$accountNumber]["account_id"];
                                                $surchargeCcf["carrier_id"] = $collected_item["account_id"];   
                                            } else {
                                               // $surchargeCcf = $this->customerccf->calculateSurchargeCcf($surcharge_code, $param->customer_id, $param->company_id, $this->carrierList[$accountNumber]["account_id"], $surcharge_price);
                                                $surchargeCcf = $this->customerccf->calculateSurchargeCcf($surcharge_code, $param->customer_id, $param->company_id,$collected_item["account_id"], $surcharge_price);
                                            }
                                            //$collected_item["carrier_id"] = $this->carrierList[$accountNumber]["account_id"];// update id
                                            $collected_item["carrier_id"] = $collected_item["account_id"];
                                            $collected_item["surcharges"][$surcharge_code] = $surchargeCcf;

                                            $surchargeWithCcfPrice += $surchargeCcf["price_with_ccf"];

                                            if ($surchargeCcf["operator"] != "FLAT") {
                                                $surchargePrice += $surchargeCcf["original_price"];
                                            }

                                        }

                                        $collected_item["carrier_price_info"]["price"] = $serviceCcf["original_price"];
                                        $collected_item["customer_price_info"]["price"] = $serviceCcf["price_with_ccf"];

                                        $collected_item["carrier_price_info"]["surcharges"] = number_format($surchargePrice, 2);
                                        $collected_item["customer_price_info"]["surcharges"] = number_format($surchargeWithCcfPrice, 2);

                                        //$collected_item["carrier_price_info"]["taxes"] = number_format($service->taxes->total_tax, 2);
                                        $collected_item["carrier_price_info"]["taxes"] = number_format((($serviceCcf["original_price"] + $surchargePrice) * $service->taxes->tax_percentage / 100), 2);

                                        $collected_item["customer_price_info"]["taxes"] = number_format((($serviceCcf["price_with_ccf"] + $surchargeWithCcfPrice) * $service->taxes->tax_percentage / 100), 2);

                                        $collected_item["carrier_price_info"]["grand_total"] = number_format($serviceCcf["original_price"] + $surchargePrice + $service->taxes->total_tax, 2);
                                        $collected_item["customer_price_info"]["grand_total"] = number_format($serviceCcf["price_with_ccf"] + $surchargeWithCcfPrice + $collected_item["customer_price_info"]["taxes"], 2);


                                        $service->collected_by[$collected_key] = $collected_item;
                                    }
                                    
                                    $service->carrier_info = array(
                                        "carrier_id" => $this->carrierList[$accountNumber]["carrier_id"],
                                        "name" => $this->carrierList[$accountNumber]["name"],
                                        "icon" => $this->carrierList[$accountNumber]["icon"],
                                        "code" => $this->carrierList[$accountNumber]["carrier_code"],
                                        "description" => $this->carrierList[$accountNumber]["description"],
                                        "account_number" => $this->carrierList[$accountNumber]["account_number"],
                                        "is_internal" => $this->carrierList[$accountNumber]["internal"],
                                        "account_id" => $this->carrierList[$accountNumber]["account_id"]
                                    );
                                    $service->service_info = array(
                                        "code" => $this->carrierList[$accountNumber]["services"][$service_code]["service_code"],
                                        "name" => $this->carrierList[$accountNumber]["services"][$service_code]["service_name"]
                                    );
                                }
                            }else{
                                unset($item->$service_code);
                            }
                        }
                    }
                }
            }
        }
        return $data;
    }
    
    private function _setPostRequest($param){
        $this->data = array();
        $carrierLists = $this->_getCustomerCarrierAccount($param);
        if ($carrierLists["status"] == "success")
        {
            $key = 0;
            $isDocument = '';
            $currencyCode = (isset($param->collection->address->currency_code) && !empty($param->collection->address->currency_code)) ? $param->collection->address->currency_code : 'GBP';
            $this->data = array(
                "carriers" => $carrierLists["data"],
                "from" => $this->_getAddress($param->collection),
                "to" => $this->_getAddress($param->delivery[0]),
                "ship_date" => date("Y-m-d", strtotime($param->service_date)),
                "extra" => array(
                    'is_document' => false
                ) ,
                "currency" => $currencyCode
            );
            
            $this->data["package"] = array();
            foreach($param->parcel as $item)
            {
                for ($i = 0; $i < $item->quantity; $i++)
                {
                    array_push($this->data["package"], array(
                        "packaging_type" => $item->package_code,
                        "width" => $item->width,
                        "length" => $item->length,
                        "height" => $item->height,
                        "dimension_unit" => "CM",
                        "weight" => $item->weight,
                        "weight_unit" => "KG"
                    ));
                    $isDocument = (isset($item->is_document)) ? (($item->is_document && !is_bool($isDocument)) ? "true" : "false") : "false";
                }
            }

            $this->data['extra']['is_document'] = $isDocument;
            ($isDocument === "false") ? ($this->data['extra']['customs_form_declared_value'] = "0") : '';
            $this->data["transit"][] = array(
                "transit_distance" => 0,
                "transit_time" => 0,
                "number_of_collections" => 0,
                "number_of_drops" => 0,
                "total_waiting_time" => 0
            );
            if (isset($param->is_insured))
            {
                if ($param->is_insured == true) 
                {
                    $this->data["insurance"] = array(
                    "value" => $param->insurance_amount,
                    "currency" => $param->collection->address->currency_code
                );
                }
            }

            $this->data["status"] = "success";
         }
        
        else
        {
            $this->data = $carrierLists;
        }
    }
    
    private function _getCustomerCarrierAccount($param){
        $result = array();
        $collectionCountry = $param->collection->address;
        $deliveryCountry = $param->delivery[0]->address;
        $customerInfo = $this->modelObj->getCompanyInfo($param->company_id);
        $homeCountry = strtolower($customerInfo['country']);
        $flowType = 'Domestic';
       
        if ($collectionCountry->id == $deliveryCountry->id)
        {
            $flowType = 'Domestic';
        }
        else
            if ($homeCountry == strtolower($collectionCountry->short_name) && $homeCountry != strtolower($deliveryCountry->short_name))
            {
                $flowType = 'Export';
            }
            else
                if ($homeCountry == strtolower($deliveryCountry->short_name) && $homeCountry != strtolower($collectionCountry->short_name))
                {
                    $flowType = 'Import';
                }

        $carrier = $this->getCustomerCarrierAccount($param->company_id, $param->customer_id, $this->collection_postcode, $param->service_date);
        
        if (count($carrier) > 0)
        {
            foreach($carrier as $key => $item)
            {
                $accountId = isset($item["account_id"]) ? $item["account_id"] : $item["carrier_id"];
                $carrier[$key]["account_id"] = $accountId;
                foreach($param->parcel as $parceldata)
                {
                    $checkPackageSpecificService = $this->modelObj->checkPackageSpecificService($param->company_id, $parceldata->package_code, $item['carrier_code'], $flowType);
                    if (count($checkPackageSpecificService) > 0)
                    {
                        foreach($checkPackageSpecificService as $serviceData)
                        {
                            $carrier[$key]["services"][$serviceData["service_code"]] = $serviceData;
                        }
                    }
                    else
                    {
                        $services = $this->modelObj->getCustomerCarrierServices($param->customer_id, $accountId, $item["account_number"], $flowType);
                        if (count($services) > 0)
                        {
                            foreach($services as $service)
                            {
                                $carrier[$key]["services"][$service["service_code"]] = $service;
                            }
                        }
                        else
                        {
                            unset($carrier[$key]);
                        }
                    }
                }
            }
           
            $collectionIndex = 0;
           
            $collectionList = $this->_getJobCollectionList($carrier, $this->_getAddress($param->collection),$param);
           
            if (count($collectionList) > 0)
            {
                foreach($collectionList as $item)
                {
                    if ((strtotime($param->service_date) > strtotime($item['collection_date_time'])) || (strtotime($param->service_date) == strtotime($item['collection_date_time'])))
                    {
                        $item['highlight_class'] = '';
                    }
                    else
                    {
                        $item['highlight_class'] = 'highlighted-datetime';
                    }

                    if (count($item["services"]) > 0)
                    {
                        $serviceItems = array();
                        $isRegularPickup = ($item["is_regular_pickup"] == "no") ? "1" : "0";
                        foreach($item["services"] as $service)
                        {
                            array_push($serviceItems, $service["service_code"]);
                        }

                        $result[$item["carrier_code"]]["name"] = $item["carrier_code"];
                        if (strtolower($item["carrier_code"]) == 'dhl')
                        {
                            $result[$item["carrier_code"]]["account"][] = array(
                                "credentials" => array(
                                    "username" => $item["username"],
                                    "password" => $item["password"],
                                    "account_number" => $item["account_number"],
                                    "inxpress" => false,
                                    "other_reseller_account" => false
                                ) ,
                                "services" => implode(",", $serviceItems) ,
                                "pickup_scheduled" => $isRegularPickup
                            );
                        }
                        else
                        {
                            $result[$item["carrier_code"]]["account"][] = array(
                                "credentials" => array(
                                    "username" => $item["username"],
                                    "password" => $item["password"],
                                    "account_number" => $item["account_number"]
                                ) ,
                                "services" => implode(",", $serviceItems) ,
                                "pickup_scheduled" => $isRegularPickup
                            );
                        }

                        $this->carrierList[$item["account_number"]] = $item;
                    }
                }

                if (count($result) > 0)
                {
                    return array(
                        "status" => "success",
                        "data" => array_values($result)
                    );
                }

                return array(
                    "status" => "error",
                    "message" => "Service not configured"
                );
            }

            return array(
                "status" => "error",
                "message" => "Collection list not configured"
            );
        }

        return array(
            "status" => "error",
            "message" => "Carrier not configured"
        );
    }
    
    public function   getNextDayQuotation($param,$quotation_ref){
       if(key_exists('parcel',(array)$param)){
           if(count($param->delivery)==1){
            try{ 
        $endpoint      = $this->endpoint;
        $param->customer_id = $param->customer_id;
        if(!isset($param->service_date) || ($param->service_date=='') || !($this->isValidServiceDate($param->service_date))){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'service date and time missing';
                 $response["error_code"] = "ERRORN0018";
                 return $response;
        }elseif(!isset($param->warehouse_id) || ($param->warehouse_id=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'warehouse id missing.';
                 $response["error_code"] = "ERRORN0064";
                 return $response;
        }elseif(!isset($param->warehouse_latitude) || ($param->warehouse_latitude=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'warehouse latitude missing.';
                 $response["error_code"] = "ERRORN0065";
                 return $response;
        }elseif(!isset($param->warehouse_longitude) || ($param->warehouse_longitude=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'warehouse longitude missing.';
                 $response["error_code"] = "ERRORN0066";
                 return $response;
        }elseif(!isset($param->collection) || ($param->collection=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Collection key missing';
                 $response["error_code"] = "ERRONR007";
                 return $response;
        }
        elseif(!isset($param->collection->address) || ($param->collection->address=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Collection address missing';
                 $response["error_code"] = "ERRORN0041";
                 return $response;
        }
        elseif(!isset($param->collection->address->postcode) || ($param->collection->address->postcode=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Collection Postcode parameter missing';
                 $response["error_code"] = "ERRORN008";
                 return $response;
        }elseif(isset($param->collection->address->city) && ($param->collection->address->city=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Collection city parameter missing';
                 $response["error_code"] = "ERRORNN018";
                 return $response;
        }elseif(!isset($param->collection->address->country) || ($param->collection->address->country=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Collection country parameter missing';
                 $response["error_code"] = "ERRORNN008";
                 return $response;
        }else{
            if(!isset($param->collection->address->latitude) && !isset($param->collection->address->longitude)){
              $collectionGeo = $this->getGeoLocationByPostCode($param->collection->address->postcode,0);
              if($collectionGeo['status'] != 'success'){
                return $collectionGeo;
              }
              else{
               $param->collection->address->latitude = $collectionGeo['latitude'];
               $param->collection->address->longitude = $collectionGeo['longitude'];
               $param->collection->address->geo_position = array('latitude'=>$collectionGeo['latitude'],'longitude'=>$collectionGeo['longitude']);
               $googleRequest['collection_postcodes'][] = (object) array('postcode'=>$param->collection->address->postcode,'latitude'=>$collectionGeo['latitude'],'longitude'=>$collectionGeo['longitude'],'geo_position'=> (object) array('latitude'=>$collectionGeo['latitude'],'longitude'=>$collectionGeo['longitude']));
            }
            }else{
                $googleRequest['collection_postcodes'][] = (object) array(
                                'postcode'=>$param->collection->address->postcode,
                                'latitude'=>$param->collection->address->latitude,
                                'longitude'=>$param->collection->address->longitude,
                                'geo_position'=> (object) array('latitude'=>$param->collection->address->latitude,'longitude'=>$param->collection->address->longitude));
            }
        }
        if(!isset($param->delivery)){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery key missing';
                 $response["error_code"] = "ERRORN0012";
                 return $response;
        }
        elseif(empty((array)$param->delivery)){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery data missing';
                 $response["error_code"] = "ERRORN0013";
                 return $response;
        }elseif(!isset($param->parcel)){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Parcel key missing';
                 $response["error_code"] = "ERRORN0020";   
                 return $response;
        }elseif(empty((array)$param->parcel)){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Parcel data missing';
                 $response["error_code"] = "ERRORN0021";
                 return $response;
        }
        else{
            foreach($param->delivery as $key=>$val){
             if(!isset($val->address)  || ($val->address=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery Postcode parameter missing';
                 $response["error_code"] = "ERRORN0042";
                 return $response;
            }
             elseif(!isset($val->address->postcode)  || ($val->address->postcode=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery Postcode parameter missing';
                 $response["error_code"] = "ERRORN0014";
                 return $response;
            }elseif(isset($val->address->city)  && ($val->address->city=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery city parameter missing';
                 $response["error_code"] = "ERRORN0024";
                 return $response;
            }elseif(!isset($val->address->country)  || ($val->address->country=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery country parameter missing.';
                 $response["error_code"] = "ERRORN0017";
                 return $response;
            }else{
                if(!isset($val->address->latitude) && !isset($val->address->longitude)){
                    $deliveryGeo = $this->getGeoLocationByPostCode($val->address->postcode,0);
                    if($deliveryGeo['status'] != 'success'){
                    return $deliveryGeo;
                }else{
                    $val->address->latitude = $deliveryGeo['latitude'];
                    $val->address->longitude = $deliveryGeo['longitude'];
                    $val->address->geo_position = array('latitude'=>$deliveryGeo['latitude'],'longitude'=>$deliveryGeo['longitude']);
                   $googleRequest['delivery_postcodes'][] = (object) array('postcode'=>$val->address->postcode,'latitude'=>$deliveryGeo['latitude'],'longitude'=>$deliveryGeo['longitude'],'geo_position'=> (object) array('latitude'=>$deliveryGeo['latitude'],'longitude'=>$deliveryGeo['longitude']));
                }
                }else{
                   $googleRequest['delivery_postcodes'][] = (object) array(
                       'postcode'=>$val->address->postcode,
                       'latitude'=>$val->address->latitude,
                       'longitude'=>$val->address->longitude,
                       'geo_position'=> (object) array('latitude'=>$val->address->latitude,'longitude'=>$val->address->longitude));
                }
             }
           }
        
            foreach($param->parcel as $key=>$val){ 
            if(!isset($val->quantity) || ($val->quantity=='')){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Parcel quantity parameter missing';
                 $response["error_code"] = "ERRORN0022";   
                 return $response;
            }
            elseif(!isset($val->weight) || ($val->weight=='')){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Parcel weight parameter missing';
                 $response["error_code"] = "ERRORN0023";   
                 return $response;
            } 
            elseif(!isset($val->length) || ($val->length=='')){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Parcel length parameter missing';
                 $response["error_code"] = "ERRORN0024";    
                 return $response;
            } 
            elseif(!isset($val->width) || ($val->width=='')){
                 $response = array(); 
                 $response["status"] = "fail";
                  $response["message"] = 'Parcel width parameter missing';
                 $response["error_code"] = "ERRORN0025";    
                 return $response;
            }
            elseif(!isset($val->height) || ($val->height=='')){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Parcel height parameter missing';
                 $response["error_code"] = "ERRORN0026";   
                 return $response;
            }
            elseif(!isset($val->package_code) || ($val->package_code=='')){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = 'Parcel package_code parameter missing';
                 $response["error_code"] = "ERRORN0027";  
                 return $response;
            }
            else{
                // 
                }  
           }  
        
       
           $accountStatus = $this->_checkCustomerAccountStatus($param->customer_id);
           if($accountStatus["status"]=="error"){
                 $response = array(); 
                 $response["status"] = "fail";
                 $response["message"] = $accountStatus['message'];
                 $response["error_code"] = "ERRORN004";   
                 return $response;
            }
            
            $this->collection_postcode = $param->collection->address->postcode;
            $collectionCountry =  $this->resrServiceModel->getCountryData($param->collection->address->country);
            $param->collection->address->id = $collectionCountry['id'];
            $param->collection->address->alpha2_code = $collectionCountry['alpha2_code'];
            $param->collection->address->alpha3_code = $collectionCountry['alpha3_code'];
            $param->collection->address->short_name = $collectionCountry['short_name'];
            $param->collection->address->currency_code = $collectionCountry['currency_code'];
            $deliveryCountry =  $this->resrServiceModel->getCountryData($param->delivery[0]->address->country);
            $param->delivery[0]->address->id = $deliveryCountry['id'];
            $param->delivery[0]->address->alpha2_code = $deliveryCountry['alpha2_code'];
            $param->delivery[0]->address->alpha3_code = $deliveryCountry['alpha3_code'];
            $param->delivery[0]->address->short_name = $deliveryCountry['short_name'];
            $param->delivery[0]->address->currency_code = $deliveryCountry['currency_code'];
            
             
            $this->_setPostRequest($param);
            $available_credit = $this->_getCustomerAccountBalence($param->customer_id,0.00); 
           if($this->data["status"] == "success"){
            $requestStr = json_encode($this->data);
            $responseStr = $this->_postRequest($this->data); //print_r($response);die;
            $response = json_decode($responseStr);
            $response = $this->_getCarrierInfo($response->rate,$param);
            if(isset($response->status) and $response->status="error"){   
                     $response = array(); 
                     $response["status"] = "fail";
                     $response["message"] = $response->message;
                     $response["error_code"] = "ERRORN005";   
                     return $response;
                 }
                $response  = $this->_getWbFormat($response,$param,$endpoint,$quotation_ref);
                if(!empty($response)){
                    return array(
                        "status"=>"success",
                        "rate"=>$response, 
                        "service_date"=>$param->service_date,
                        "availiable_balence" => $available_credit['available_credit']);
                }else{
                     $response = array(); 
                     $response["status"] = "fail";
                     $response["error_code"] = "ERRORN028";   
                     return $response;
                }
        }
        else
        {
            return array(
                "status" => "error",
                "message" => $this->data["message"]
            );
            }
          }
        }
            catch(Exception $e){
            print_r($e->getMessage());die;
        }
        }else{
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'only one delivery address allowed.';
                 $response["error_code"] = "ERRORN00N165";
                 return $response;
       }
      }else{
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'parcel data missing.';
                 $response["error_code"] = "ERRORN00N165";
                 return $response;
    }
    }
   
    private function _getJobCollectionList($carriers, $address,$param){
        $jobCollectionList = $this->collectionModel->getJobCollectionList($carriers, $address, $param->customer_id, $param->company_id, $param->service_date);
        
        $this->regular_pickup = $jobCollectionList["regular_pickup"];
        return $jobCollectionList["carrier_list"];
    }
    
    private function _getWbFormat($response,$param,$endpoint,$quotation_ref){
       $endpoint          = isset($endpoint)?$endpoint:'getQuotation';
       $reqSessionId      = ($quotation_ref!='')?$quotation_ref:date('dmY').microtime(true);
       $savedTempService  = array('session_id'=>$reqSessionId,'request'=>json_encode((array)$param),'status'=>1,'create_date'=>date('Y-m-d'),'create_time'=>date('H:m:s'),'end_point'=>'getQuotation','request_status'=>'NC'); 
       $responsedata      = array('quotation_ref'=>$reqSessionId);
       foreach($response as $key=>$val){ 
            foreach($val as $key1=>$val2){ 
              foreach($val2 as $key3=>$val3){
                 foreach($val3 as $key4=>$val4){ 
                      foreach($val4 as $key5=>$val5){ //print_r($val5);print_r($response); die;
                      $surcharges = array();
                      $collectionKey = ($param->collected_by!='self')?0:1;
                      if(key_exists('surcharges',$val5[0]->collected_by[$collectionKey]) and count($val5[0]->collected_by[$collectionKey]['surcharges'])>0){
                          foreach($val5[0]->collected_by[$collectionKey]['surcharges'] as $keydata=>$data){
                           $surcharges[$keydata] = $data['price_with_ccf'];
                        }
                      }
                          
                      if($param->collected_by=='self'){ 
                        $surcharges['pickup_surcharge'] = $val5[0]->collected_by[$collectionKey]['pickup_surcharge'];
                            $val5[0]->surcharges->pickup_surcharge = $val5[0]->collected_by[$collectionKey]['pickup_surcharge'];
                            $pickupSurcharge = $val5[0]->collected_by[$collectionKey]['pickup_surcharge'];
                            $val5[0]->collected_by[$collectionKey]['surcharges']['collection_surcharge']["original_price"] = $pickupSurcharge;
                            $val5[0]->collected_by[$collectionKey]['surcharges']['collection_surcharge']["surcharge_value"] = $pickupSurcharge;           
                            $val5[0]->collected_by[$collectionKey]['surcharges']['collection_surcharge']["operator"] = "FLAT";
                            $val5[0]->collected_by[$collectionKey]['surcharges']['collection_surcharge']["price"] = $pickupSurcharge; 
                            $val5[0]->collected_by[$collectionKey]['surcharges']['collection_surcharge']["level"] = "level 1"; 
                            $val5[0]->collected_by[$collectionKey]['surcharges']['collection_surcharge']["surcharge_id"] = "0"; 
                            $val5[0]->collected_by[$collectionKey]['surcharges']['collection_surcharge']["price_with_ccf"] = $pickupSurcharge; 
                            $val5[0]->collected_by[$collectionKey]['surcharges']['collection_surcharge']["company_surcharge_code"] = "collection_surcharge"; 
                            $val5[0]->collected_by[$collectionKey]['surcharges']['collection_surcharge']["company_surcharge_name"] = "collection_surcharge"; 
                            $val5[0]->collected_by[$collectionKey]['surcharges']['collection_surcharge']["courier_surcharge_code"] = "collection_surcharge"; 
                            $val5[0]->collected_by[$collectionKey]['surcharges']['collection_surcharge']["courier_surcharge_name"] = "collection_surcharge"; 
                            $val5[0]->collected_by[$collectionKey]['surcharges']['collection_surcharge']["carrier_id"] = $val5[0]->collected_by[$collectionKey]['account_id']; 

                         }
                       
                      $temp                          =   array();
                      $temp['job_type']              =   'NEXTDAY';  
                      $temp['service_code']          =   $val5[0]->rate->info['courier_service_code'];
                      $temp['service_name']          =   $val5[0]->rate->info['courier_service_name'];
                      $temp['service_id']            =   $val5[0]->rate->info['service_id'];
                      $temp['act_number']            =   $val5[0]->carrier_info['account_number'];
                      $temp['max_delivery_time']     =  '00:00:00';
                      $temp['max_waiting_time']      =  '00:00:00';
                      $val5[0]->collected_by[$collectionKey]['customer_price_info']['surcharges'] = array_sum($surcharges);
                      $temp['surcharges_info']       =  $surcharges;
                      $temp['price']                 =  number_format($val5[0]->collected_by[$collectionKey]['customer_price_info']['price'],2);
                      $temp['surcharges']            =  number_format($val5[0]->collected_by[$collectionKey]['customer_price_info']['surcharges'],2);
                      $calculatedTax                 =  number_format(((($temp['surcharges'] + $temp['price'])* $val5[0]->taxes->tax_percentage)/100),2);
                      $temp['taxes']                 =  $calculatedTax;
                      //$temp['total']               =  $val5[0]->collected_by[$collectionKey]['customer_price_info']['grand_total'];
                      //$temp['carrier']               =  $val5[0]->collected_by[$collectionKey]['name'];
                      $temp['carrier']               =  $val5[0]->carrier_info['code'];
                      $temp['collection_carrier']    =  $val5[0]->collected_by[$collectionKey]['carrier_code'];
                      $temp['total']                 =  ($temp['surcharges'] + $temp['price'] + $temp['taxes']);
                      $temp['transit_distance']      =  '';
                      $temp['transit_time']          =  '';
                      $temp['transit_distance_text'] =  '';
                      $temp['transit_time_text']     =  '';
                      $temp['service_options']       =  isset($val5[0]->service_options)?$val5[0]->service_options:array();
                      $temp['flow_type']             =  isset($val5[0]->rate->flow_type)?$val5[0]->rate->flow_type:'Domestic';
                      $temp['rate_type']             =  $val5[0]->rate->rate_type;
                      $val5['job_type']              = 'NEXTDAY';
                      $val5[0]->collection_carrier   =  $val5[0]->collected_by[$collectionKey];
                      $responsedata['services'][]    =  $temp;
                      unset($val5[0]->collected_by);
                      $savedTempService['response'][$val5[0]->rate->info['service_id']] = $val5;
                      }    
                    }
                  }
               } 
            }
          
       $savedTempService['response'] = json_encode($savedTempService['response']);
       $quoteRefData = $this->resrServiceModel->getQuotationData($reqSessionId);
        if($quoteRefData!=''){
            $preResponse = json_decode($quoteRefData['response'],1);
            $preResponse = $preResponse + json_decode($savedTempService['response'],1);
            $savedTempService = json_encode($preResponse);
            $updatestatus = $this->resrServiceModel->editContent('webapi_request_response',
                array('response'=>$savedTempService)," session_id = '".$reqSessionId."'");
          }
        else{
           $this->resrServiceModel->addContent('webapi_request_response',$savedTempService);
        }
       return $responsedata; 
   }
    
    private function _getAddress($item){
     return array(
            "zip" => $item->address->postcode,
            "country" =>$item->address->alpha2_code,
            "city" =>isset($item->address->city)?$item->address->city:''
        );
    }
    
    public function bookNextDayQuotation($param,$records){ 
        $qRef = $param->quation_reference;
        $validReqData    = $this->validateReqest($param,json_decode(json_encode($records['request_data'])));
        if($validReqData['status']!='success'){
            return $validReqData;
        }
        $formatedReqData = $this->formattedReqest($param,json_decode(json_encode($records['request_data'])));
        $param = json_decode(json_encode($formatedReqData));
        $param->service_opted = json_decode(json_encode($records['job_data'][0]));
        $param->address_op  = 'add';
        $param->booked_by  = $param->customer_id;
        $param->collection_user_id  = $param->customer_id;
        $accountStatus = $this->_checkCustomerAccountStatus($param->customer_id);
        if ($accountStatus["status"] == "error"){
            return $accountStatus;
         }
        $param->ismanualbooking = false;
        $param->manualbookingreference = '';
        $param->quation_reference = $qRef;
        
            
        $bookingShipPrice = $param->service_opted->collection_carrier->customer_price_info->grand_total;
        $available_credit = $this->_getCustomerAccountBalence($param->customer_id, $bookingShipPrice);
        if ($available_credit["status"] == "error"){
            return $available_credit;
            }
       
       
        $company_code = $this->_getCompanyCode($param->company_id);
        $serviceId = $param->service_opted->rate->info->service_id;
        $customerWarehouseId = $this->getCustomerWarehouseIdByCustomerId($param->company_id, $param->customer_id);
        $param->service_opted->rate->shipment_type = "Next";
        $this->serviceRequestString = isset($param->service_request_string)?$param->service_request_string:'';
        $this->serviceResponseString = isset($param->service_response_string)?$param->service_response_string:'';
        $this->startTransaction();
        $execution_order = 0;
        $collection_date_time = $param->service_opted->collection_carrier->collection_date_time;
        

        $collection_end_at = $param->service_opted->collection_carrier->collection_end_at;
        $carrier_account_number = $param->service_opted->collection_carrier->account_number;
        $is_internal = $param->service_opted->collection_carrier->is_internal;
        $searchString = $companyName = $contactName = '';

        $this->maxTransitDays = 0;

        if(isset($param->service_opted->service_options->max_transit_days))
            $this->maxTransitDays = (int)$param->service_opted->service_options->max_transit_days;

        $carrierBusinessDay = new Carrier_Business_Day();

        $collectionDateTimeObj = $carrierBusinessDay->_findBusinessDay(new DateTime($collection_date_time), 0 , $param->service_opted->carrier_info->code);
        $collection_date_time = $collectionDateTimeObj->format('Y-m-d');
        $param->collection = array($param->collection);
        foreach($param->collection as  $key=>$item){ 
            $execution_order++;
            $item->address->country               = (object)array();
            $item->address->country->id           = $item->address->id;
            $item->address->country->alpha3_code  = $item->address->alpha3_code;
            $item->address->country->short_name   = $item->address->short_name;
            $addressInfo = $this->_saveAddressData($item->address,$param->customer_id,$param->address_op);
            if ($addressInfo["status"] == "error")
                {
                $this->rollBackTransaction();
                return $addressInfo;
                }
            
            $shipmentStatus = $this->_saveShipment($param, $param->collection[0]->address, $param->parcel, $addressInfo["address_data"], $customerWarehouseId, $param->company_id, $company_code, $collection_date_time, $collection_end_at, "next", "COLL", "NEXT", "P", $execution_order, $carrier_account_number, $is_internal);
            
            $sStr["postcode"] = $item->address->postcode;
            $sStr["address_line1"] = isset($item->address->address_line1)?$item->address->address_line1:'';
            $sStr["iso_code"] = $item->address->country->alpha3_code;
            $searchString = str_replace(' ', '', implode('', $sStr));
            $companyName = isset($item->address->company_name)?$item->address->company_name:'';
            $contactName = isset($item->consignee->name)?$item->consignee->name:'';
            if ($shipmentStatus["status"] == "error")
                {
                $this->rollBackTransaction();
                return $shipmentStatus;
                }

            if ($key == 0) $loadIdentity = $shipmentStatus["shipment_ticket"];
            foreach($param->parcel as $item)
                {
                for ($i = 0; $i < $item->quantity; $i++)
                    {
                    $parcelStatus = $this->_saveParcel($shipmentStatus["shipment_id"], $shipmentStatus["shipment_ticket"], $customerWarehouseId, $param->company_id, $company_code, $item, "P", $loadIdentity);
                    if ($parcelStatus["status"] == "error")
                        {
                        $this->rollBackTransaction();
                        return $parcelStatus;
                        }
                    }
                }

            $shipmentDimension = $this->_getParcelDimesionByShipmentId($shipmentStatus["shipment_id"]);
            $this->_saveShipmentDimension($shipmentDimension, $shipmentStatus["shipment_id"]);
            $surchargesArr = isset($param->service_opted->collection_carrier->surcharges)?$param->service_opted->collection_carrier->surcharges : array();
            $otherDetail = array(
                'reason_for_export' => isset($param->reason_for_export) ? $param->reason_for_export : '',
                'tax_status' => isset($param->tax_status) ? $param->tax_status : '',
                'terms_of_trade' => isset($param->terms_of_trade) ? $param->terms_of_trade : '',
                'is_insured' => isset($param->is_insured) ? $param->is_insured : false
            );
            $param->customer_reference1 = (isset($param->customer_reference1)) ? $param->customer_reference1 : "";
            $param->customer_reference2 = (isset($param->customer_reference2)) ? $param->customer_reference2 : "";
            $param->service_opted->collection_carrier->surcharges = isset($param->service_opted->collection_carrier->surcharges) ? $param->service_opted->collection_carrier->surcharges : 0;
            
            $serviceStatus = $this->_saveShipmentService($param->service_opted, $param->service_opted->collection_carrier->surcharges, $loadIdentity, $param->customer_id, "pending", $otherDetail, $serviceId, $param->customer_reference1, $param->customer_reference2, $param->ismanualbooking, $param->manualbookingreference);
            $this->_saveInfoReceived($loadIdentity);
            if ($serviceStatus["status"] == "error")
                {
                $this->rollBackTransaction();
                return $serviceStatus;
                }

            $paymentStatus = $this->_manageAccounts($serviceStatus["service_id"], $loadIdentity, $param->customer_id, $param->company_id);
            if ($paymentStatus["status"] == "error")
                {
                $this->rollBackTransaction();
                return $paymentStatus;
                }

            $collectedBy = $param->service_opted->collection_carrier;
            $collectedBy->service_id = $serviceStatus["service_id"];
            $collectedByStatus = $this->_saveShipmentCollection($collectedBy);
            if ($collectedByStatus["status"] == "error")
                {
                $this->rollBackTransaction();
                return $collectedByStatus;
                }

            $serviceOptions = isset($param->service_opted->service_options) ? $param->service_opted->service_options : array();
            $attributeStatus = $this->_saveShipmentAttribute($serviceOptions, $loadIdentity);
            if ($attributeStatus["status"] == "error")
                {
                $this->rollBackTransaction();
                return $attributeStatus;
                }
            }

        $deliveryDateTimeObj = $carrierBusinessDay->_findBusinessDay(new DateTime($collection_date_time), $this->maxTransitDays , $param->service_opted->carrier_info->code);
        $delivery_date_time = $deliveryDateTimeObj->format('Y-m-d');

        //$collection_date_time = "1970-01-01 00:00:00";
        $collection_end_at = "00:00:00";
        $carrier_account_number = $param->service_opted->carrier_info->account_number;
        $is_internal = $param->service_opted->carrier_info->is_internal;
        foreach($param->delivery as $key => $item)
            {
            $execution_order++;
            
            $item->address->country               = (object)array();
            $item->address->country->id           = $item->address->id;
            $item->address->country->alpha3_code  = $item->address->alpha3_code;
            $item->address->country->short_name   = $item->address->short_name;
            
            
            $addressInfo = $this->_saveAddressData($item->address, $param->customer_id,$param->address_op);
            if ($addressInfo["status"] == "error")
                {
                $this->rollBackTransaction();
                return $addressInfo;
                }

            $param->delivery[$key]->address->load_identity = $loadIdentity;
            
            $shipmentStatus = $this->_saveShipment($param, $param->delivery[$key]->address, $param->parcel, $addressInfo["address_data"], $customerWarehouseId, $param->company_id, $company_code, $delivery_date_time, $collection_end_at, "next", "DELV", "NEXT", "D", $execution_order, $carrier_account_number, $is_internal);
           
            if ($shipmentStatus["status"] == "error")
                {
                $this->rollBackTransaction();
                return $shipmentStatus;
                }

            foreach($param->parcel as $item)
                {
                for ($i = 0; $i < $item->quantity; $i++)
                    {
                    
                    $parcelStatus = $this->_saveParcel($shipmentStatus["shipment_id"], $shipmentStatus["shipment_ticket"], $customerWarehouseId, $param->company_id, $company_code, $item, "D", $loadIdentity);
                     
                    if ($parcelStatus["status"] == "error")
                        {
                        $this->rollBackTransaction();
                        return $parcelStatus;
                        }
                    }
                }

            $shipmentDimension = $this->_getParcelDimesionByShipmentId($shipmentStatus["shipment_id"]);
            $this->_saveShipmentDimension($shipmentDimension, $shipmentStatus["shipment_id"]);
            }

        if (isset($param->items))
            {
            foreach($param->items as $item)
                {
                $itemStatus = $this->_saveShipmentItems($item, $loadIdentity, $param->customer_id, $booking_status = 0);
                if ($itemStatus["status"] == "error")
                    {
                    $this->rollBackTransaction();
                    return $itemStatus;
                    }
                }
            }

        $allData = $param;
        $carrier_code = $param->service_opted->carrier_info->code;
        $rateDetail = (strtolower($carrier_code) == 'dhl') ? $param->service_opted->rate : array();
        $this->commitTransaction();
        $isInternalCarrier = $this->_isInternalCarrier($carrier_code);
        $labelHttpPath = Library::_getInstance()->base_url() . '/' . LABEL_FOLDER;

        /*
        if (($isInternalCarrier==='YES'))
        {
            $label_path = "$labelHttpPath/$loadIdentity/$carrier_code/$loadIdentity.pdf";
            $customLabel = new Custom_Label();
            $customLabel->createLabel($loadIdentity,$carrier_code);
            $labelData = array(
                "label_file_pdf" => $label_path
            );
            $saveLabelInfo = $this->_saveLabelInfoByLoadIdentity($labelData, $loadIdentity);
            $statusArr = array(
                "status" => "success"
            );
            $this->modelObj->updateBookingStatus($statusArr, $loadIdentity);
            $autoPrint = $this->modelObj->getAutoPrintStatusByCustomerId($param->customer_id);
            $checkPickupExist = array();
            if ($saveLabelInfo)
                {
                if (strtolower($carrier_code) == 'dhl')
                    {
                    $userId = $param->collection_user_id;
                    $carrierId = $param->service_opted->carrier_info->carrier_id;
                    $collectionDate = date('Y-m-d', strtotime($param->service_opted->collection_carrier->collection_date_time));
                    $checkPickupExist = $this->modelObj->checkExistingPickupForShipment($param->customer_id, $carrierId, $userId, $collectionDate, $searchString, $companyName, $contactName, $loadIdentity);
                    }

                Consignee_Notification::_getInstance()->sendNextdayBookingConfirmationNotification(array(
                    "load_identity" => $loadIdentity,
                    "company_id" => $param->company_id,
                    "warehouse_id" => $param->warehouse_id,
                    "customer_id" => $param->customer_id
                ));
                Consignee_Notification::_getInstance()->sendNextdayBookingConfirmationNotificationToCourier(array(
                    "load_identity" => $loadIdentity,
                    "company_id" => $param->company_id,
                    "warehouse_id" => $param->warehouse_id,
                    "customer_id" => $param->customer_id
                ));
                return array(
                    "status" => "success",
                    "message" => "Shipment booked successful. Shipment ticket $loadIdentity",
                    "file_path" => $label_path,
                    "auto_print" => $autoPrint['auto_label_print'],
                    'pickups' => $checkPickupExist,
                    'carrier_code' => strtolower($carrier_code)
                );
                }
              else
                {
                return array(
                    "status" => "error",
                    "message" => "Shipment not booked successfully,error while saving label!",
                    "file_path" => "",
                    "auto_print" => ""
                );
                }
            }


        if(($isInternalCarrier ==='NO') && $param->manualbookingreference=='')
        {
            $labelInfo = $this->getLabelFromLoadIdentity($loadIdentity, $rateDetail, $allData);
            if ($labelInfo['status'] == 'success')
                {
                $labelData = array(
                    "label_tracking_number" => isset($labelInfo['label_tracking_number']) ? $labelInfo['label_tracking_number'] : '0',
                    "label_files_png" => isset($labelInfo['label_files_png']) ? $labelInfo['label_files_png'] : '',
                    "label_file_pdf" => isset($labelInfo['file_path']) ? $labelInfo['file_path'] : '',
                    "label_json" => isset($labelInfo['label_json']) ? $labelInfo['label_json'] : '',
					"invoice_created"=>isset($labelInfo['invoice_created']) ? $labelInfo['invoice_created'] : 0
                );
                $saveLabelInfo = $this->_saveLabelInfoByLoadIdentity($labelData, $loadIdentity);

                $obj = new Create_Tracking();

                $obj->createTracking($labelData["label_tracking_number"], $carrier_code);

                $statusArr = array(
                    "status" => "success"
                );
				
				
				if ((strtolower($carrier_code) == 'ukmail') && (isset($labelInfo['child_account_data']) && count($labelInfo['child_account_data'])>0)){
				    $this->modelObj->updateChildAccountData("shipment_service",array("accountkey"=>$labelInfo['child_account_data']['child_account_number']),"load_identity='".$loadIdentity."'");
					$this->modelObj->updateChildAccountData("shipment",array("carrier_account_number"=>$labelInfo['child_account_data']['child_account_number']),"instaDispatch_loadIdentity='".$loadIdentity."'");
				}
				
                $this->modelObj->updateBookingStatus($statusArr, $loadIdentity);
                $autoPrint = $this->modelObj->getAutoPrintStatusByCustomerId($param->customer_id);
                $checkPickupExist = array();
                if ($saveLabelInfo)
                    {
                    if (strtolower($carrier_code) == 'dhl')
                        {
                        $userId = $param->collection_user_id;
                        $carrierId = $param->service_opted->carrier_info->carrier_id;
                        $collectionDate = date('Y-m-d', strtotime($param->service_opted->collection_carrier->collection_date_time));
                        $checkPickupExist = $this->modelObj->checkExistingPickupForShipment($param->customer_id, $carrierId, $userId, $collectionDate, $searchString, $companyName, $contactName, $loadIdentity);
                        }

                    Consignee_Notification::_getInstance()->sendNextdayBookingConfirmationNotification(array(
                        "load_identity" => $loadIdentity,
                        "company_id" => $param->company_id,
                        "warehouse_id" => $param->warehouse_id,
                        "customer_id" => $param->customer_id
                    ));
                    Consignee_Notification::_getInstance()->sendNextdayBookingConfirmationNotificationToCourier(array(
                        "load_identity" => $loadIdentity,
                        "company_id" => $param->company_id,
                        "warehouse_id" => $param->warehouse_id,
                        "customer_id" => $param->customer_id
                    ));
					
					if(isset($labelInfo['invoice_created']) && $labelInfo['invoice_created']==1){
						$fileUrl = $this->libObj->get_api_url();
						return array(
							"status" => "success",
							"message" => "Shipment booked successful. Shipment ticket $loadIdentity",
							"file_path" => $labelInfo['file_path'],
							"invoice_file"=>$fileUrl."/label/".$loadIdentity.'/dhl/'.$loadIdentity.'-custom.pdf',
							"auto_print" => $autoPrint['auto_label_print'],
							'pickups' => $checkPickupExist,
							'carrier_code' => strtolower($carrier_code)
						);
					}else{
						return array(
							"status" => "success",
							"message" => "Shipment booked successful. Shipment ticket $loadIdentity",
							"file_path" => $labelInfo['file_path'],
							"auto_print" => $autoPrint['auto_label_print'],
							'pickups' => $checkPickupExist,
							'carrier_code' => strtolower($carrier_code)
						);
					}
                    
                }
                else
                {
                    return array(
                        "status" => "error",
                        "message" => "Shipment not booked successfully,error while saving label!",
                        "file_path" => "",
                        "auto_print" => ""
                    );
                    }
                }
              else
                {
                $deleteBooking = $this->_deleteBooking($loadIdentity);
                if ($deleteBooking)
                    {
                    return array(
                        "status" => "error",
                        "message" => $labelInfo['message'],
                        "file_path" => ""
                    );
                    }

                return array(
                    "status" => "error",
                    "message" => $labelInfo['message'],
                    "file_path" => ""
                );
                }
            }
          else
            { 
            return array(
                "status" => "success",
                "message" => "Shipment booked successful. Shipment ticket $loadIdentity",
                "file_path" => "",
                "auto_print" => "",
                'pickups' => "",
                'carrier_code' => strtolower($carrier_code)
            );
            }
    */   //$status['status'] = 'success';
        //if($status['status']=='success'){
            $updatestatus = $this->resrServiceModel->editContent('webapi_request_response',
            array('request_status'=>'C')," session_id = '".$param->quation_reference."'");
       //}
       //return $status;
        return array(
                "status" => "success",
                "message" => "Shipment booked successful. Shipment ticket $loadIdentity",
                "file_path" => "",
                "auto_print" => "",
                'pickups' => "",
                'identity' => $loadIdentity,
                'carrier_code' => strtolower($carrier_code)
            );
        }
    
    public function getLabelFromLoadIdentity($loadIdentity, $rateDetail, $allData = array()){
        $carrierObj = new Carrier();
        $bookingInfo = $carrierObj->getShipmentInfo($loadIdentity, $rateDetail, $allData,$this->_param);
        return $bookingInfo;
    }
    
    public function formattedReqest($param,$quoteRequest){ 
      foreach($quoteRequest->delivery as $key=>$datainer){  
          if(isset($param->delivery) and count($param->delivery)>0){
             unset($param->delivery[$key]->address->country);
             unset($param->delivery[$key]->address->postcode);
             unset($param->delivery[$key]->address->currency_code);
             unset($param->delivery[$key]->address->country_code);
           }else{
              $param->delivery[] = (object)array('address'=>array());
           }
             $quoteRequest->delivery[$key]->address = (array)$param->delivery[$key]->address + (array)$datainer->address;
             unset($param->delivery[$key]->address);
             $quoteRequest->delivery[$key] = (array)$quoteRequest->delivery[$key] + (array)$param->delivery[$key];
         }
        $quoteRequest->collection = array($quoteRequest->collection);
        foreach($quoteRequest->collection as $keys=>$datainer){
            
          if(isset($param->collection)){
             unset($param->collection->address->country);
             unset($param->collection->address->postcode);
             unset($param->collection->address->currency_code);
             unset($param->collection->address->country_code);
          }else{
             $param->collection = (object)array('address'=>array()); 
          }
             $quoteRequest->collection[$keys]->address = (array)$param->collection->address + (array)$datainer->address;
             unset($param->collection->address);
             $quoteRequest->collection = (array)$quoteRequest->collection[$keys] + (array)$param->collection;
        }
     return $quoteRequest;  
    }
    
    public  function validateReqest($current_request,$pre_request){
        $response = array();
        $temparray = array();
        
        if(isset($current_request->service_date) and ($current_request->service_date  != $pre_request->service_date)){
             $response["status"] = "fail";
             $response["message"] = 'Requested Service date and time is mismatch with Quotation';
             $response["error_code"] = "ERROR00N44";
        }
        /*elseif(count($current_request->collection)!=count($pre_request->collection)){
             $response["status"] = "fail";
             $response["message"] = 'Requested Collection mismatch with Quotation';
             $response["error_code"] = "ERROR0N45";
        }*/
        elseif(isset($current_request->delivery) and (count($current_request->delivery)!=count($pre_request->delivery))){
             $response["status"] = "fail";
             $response["message"] = 'Requested Delivery mismatch with Quotation';
             $response["error_code"] = "ERROR00N45";
        }
        elseif(isset($current_request->collection->address->country) and ($current_request->collection->address->country=='')){
             $response["status"] = "fail";
             $response["message"] = 'country is missing in collection address.';
             $response["error_code"] = "ERROR00N46";
        }
        elseif(isset($current_request->collection->address->postcode) and ($current_request->collection->address->postcode=='')){
             $response["status"] = "fail";
             $response["message"] = 'postcode is missing in collection address.';
             $response["error_code"] = "ERROR00N47";
        }
        /*elseif(!isset($current_request->collection->address->address_line1) || ($current_request->collection->address->address_line1=='')){
             $response["status"] = "fail";
             $response["message"] = 'address Line 1 is missing in collection address.';
             $response["error_code"] = "ERROR00N48";
        }*/
        elseif(isset($current_request->collection->address->country) and ($current_request->collection->address->country != $pre_request->collection->address->country)){
             $response["status"] = "fail";
             $response["message"] = 'collection country mismatch with Quotation';
             $response["error_code"] = "ERROR00N70";
        }
        elseif(isset($current_request->collection->address->postcode) and ($current_request->collection->address->postcode != $pre_request->collection->address->postcode)){
             $response["status"] = "fail";
             $response["message"] = 'collection postcode mismatch with Quotation';
             $response["error_code"] = "ERROR00N71";
        }else{
        $response["status"] = 'success';
        if(isset($current_request->delivery) and count($current_request->delivery)>0){
             if(count($current_request->delivery) != count($pre_request->delivery)){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery count mismatch with Quotation request';
                 $response["error_code"] = "ERROR00N173";
                 return $response;
             }
            /*foreach($current_request->delivery as $key=>$val){
             if(!isset($val->address)  || ($val->address=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery address parameter missing in Quotation';
                 $response["error_code"] = "ERROR00N73";
                 return $response;
             }
             elseif(!isset($val->address->postcode)  || ($val->address->postcode=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery Postcode parameter missing.';
                 $response["error_code"] = "ERROR00N74";
                 return $response;
            }
             elseif($val->address->postcode != $pre_request->delivery[$key]->address->postcode){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'requested delivery postcode are mismatch or order mismatch';
                 $response["error_code"] = "ERROR00N75";
                 return $response;
            }
             elseif(!isset($val->address->country)  || ($val->address->country=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'Delivery country parameter missing';
                 $response["error_code"] = "ERROR00N76";
                 return $response;
            }
             elseif($val->address->country != $pre_request->delivery[$key]->address->country){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'requested delivery country are mismatch or order mismatch';
                 $response["error_code"] = "ERROR00N77";
                 return $response;
            }
           }*/
        }
        
       /* if(isset($current_request->collection->consignee)){
                $current_request->collection->address->name  = $current_request->collection->consignee->name;
                $current_request->collection->address->email = $current_request->collection->consignee->email;
                $current_request->collection->address->phone = $current_request->collection->consignee->phone;
            }
            
        $temparray['collection_address'][] = isset($current_request->collection->address)?$current_request->collection->address:'';
            if(isset($current_request->delivery) and count($current_request->delivery)>0){
               
                foreach($current_request->delivery as $key=>$vals){
                 
                if($vals->address->postcode != $pre_request->delivery[$key]->address->postcode){
                    $response["status"] = "fail";
                    $response["message"] = 'Delivery postcode are missmatch in delivery address.';
                    $response["error_code"] = "ERROR00N49";
                    break;
                }else{
                     if(isset($current_request->delivery[$key]->consignee)){
                         $vals->address->name = $current_request->delivery[$key]->consignee->name;
                         $vals->address->email = $current_request->delivery[$key]->consignee->email;
                         $vals->address->phone = $current_request->delivery[$key]->consignee->phone; 
                     }
                    $temparray['delivery_address'][] = $vals->address;
                    $response["status"] = "success";
                }
             }
            } */
            
          }
        if($response["status"] == 'success'){
           return array('status'=>'success','data'=>$temparray);
        }
        else{
           return $response;
        }

    }
    
    
    /*================================Direct Booking================================*/
    public function bookNextDayJobWithoutQuotion($param){ 
        if(!isset($param->service_date) || ($param->service_date=='') || !($this->isValidServiceDate($param->service_date))){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'service date and time missing';
                 $response["error_code"] = "ERRORN00N18";
                 return $response;
        }elseif(!isset($param->collected_by) || ($param->collected_by=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'collected by code  missing';
                 $response["error_code"] = "ERROR00N155";
                 return $response;
        }elseif(!isset($param->service_code) || ($param->service_code=='')){
                 $response = array();
                 $response["status"] = "fail";
                 $response["message"] = 'service code  missing';
                 $response["error_code"] = "ERROR00N256";
                 return $response;
        }else{
            $serviceId = $this->resrServiceModel->getCustomerCarrierDataByServiceCode($param->customer_id,$param->service_code, $param->company_id);
            $param->service_id = $serviceId['service_id'];
            $quoteData  = $this->getNextDayQuotation($param,'');
                if($quoteData['status']!='success'){
                return $quoteData;
                }else{
                        $quotation_ref  = $quoteData['rate']['quotation_ref'];
                        $ablServices = array();
                        if(count($quoteData['rate']['services'])>0)
                           foreach($quoteData['rate']['services'] as $ikey=>$ival){
                             $ablServices[] = $ival['service_code']; 
                        }
                        if(in_array($param->service_code,$ablServices)){
                           $param->quation_reference =  $quotation_ref;
                            
                            $commonObj      = new Commonservices();
                            $records        = $commonObj->getRequestedQuotationInfo((object)array(
                            'service_code'=>$param->service_code,
                            'quation_reference'=>$quotation_ref,
                            'customer_id'=>$param->customer_id,
                            'company_id'=>$param->company_id));
                            return $this->bookNextDayQuotation($param,$records);
                        }else{
                         $response = array();
                         $response["status"] = "fail";
                         $response["message"] = 'Please check your service code';
                         $response["error_code"] = "ERROR0056";
                         return $response;
                       }
                    }
               }
           }
    public function executeNextDayRecurringJob(){
        $reccuringBucket =  array();
        $return  =  array();
        $reccuringBucket = $this->getEligibleRecurringJob();
        if(count($reccuringBucket)>0){
            foreach($reccuringBucket as $key=>$data){
                $returnData = $this->bookNextdayByLoadIdentity($data['load_identity']);
                if($returnData['status']=='error' || $returnData['status']=='fail'){
                    Consignee_Notification::_getInstance()->sendRecurringNotification(
                    array('rowdata'=>$data['rowdata'],'returnData'=>$returnData));
                    $updatestatus = $this->resrServiceModel->editContent('recurring_jobs',
                                                         array('status'=>'fail')," job_id = '".$data['rowdata']['job_id']."'");
                    return $returnData;
                }else{
                   if($returnData['status']=='success'){
                       $id = $data['rowdata']['job_id'];
                       $tempdata   = array();
                       $tempdata['last_booking_date'] = date('Y-m-d');
                       $tempdata['last_booking_time'] = date('H:m:s');
                       $tempdata['last_booking_reference'] = $returnData['identity'];
                       $tempdata['status'] = ($data['rowdata']['recurring_type']  == 'ONCE')?false:true;
                       $updatestatus = $this->resrServiceModel->editContent('recurring_jobs',
                                                         $tempdata," job_id = '".$id."'");
                       if($updatestatus){
                         $updatestatus = $this->resrServiceModel->editContent('shipment_service',
                                                         array('booked_by_recurring'=>'YES')," load_identity = '".$returnData['identity']."'");
                       }
                   }
                 $return[]   = $returnData; 
                }
            }
        }
        return $return;
    }
    public function getEligibleRecurringJob(){
        $reccuringBucket =  array();
        $recurringData = $this->resrServiceModel->getNextdayReccuringJobs();
        $currenttime  = date("H:i:s");
        if(count($recurringData)>0){
            foreach($recurringData as $reccuringVal){
                switch($reccuringVal['recurring_type']){
                    case 'DAILY':
                      if(strtotime($currenttime) >= strtotime($reccuringVal['recurring_time']) && (strtotime(date('Y-m-d')) > strtotime($reccuringVal['last_booking_date']))){
                          $reccuringBucket[] = array('load_identity'=>$reccuringVal['load_identity'],'rowdata'=>$reccuringVal);
                      }
                    break;
                    case 'WEEKLY':
                      if((strtotime($currenttime) >= strtotime($reccuringVal['recurring_time'])) && (strtoupper(date("D"))===$reccuringVal['recurring_day']) && (strtotime(date('Y-m-d')) > strtotime($reccuringVal['last_booking_date']))){
                          $reccuringBucket[] = array('load_identity'=>$reccuringVal['load_identity'],'rowdata'=>$reccuringVal);
                      }
                    break;
                    case 'MONTHLY':
                      if((strtotime($currenttime) >= strtotime($reccuringVal['recurring_time'])) && (date("d")===$reccuringVal['recurring_month_date']) && (strtotime(date('Y-m-d')) > strtotime($reccuringVal['last_booking_date']))){
                          $reccuringBucket[] = array('load_identity'=>$reccuringVal['load_identity'],'rowdata'=>$reccuringVal);
                      }
                    break;
                    case 'ONCE':
                      if((strtotime($currenttime) >= strtotime($reccuringVal['recurring_time'])) && (strtotime(date("Y-m-d"))===strtotime($reccuringVal['recurring_date']))){
                          $reccuringBucket[] = array('load_identity'=>$reccuringVal['load_identity'],'rowdata'=>$reccuringVal && ($reccuringVal['last_booking_date'] == '1970-01-01' ) );
                      }
                    break;
                 }
               }
            }
        return $reccuringBucket;
    }
    public function bookNextdayByLoadIdentity($loadidentity){
        $jsonData                           = array('collection'=>array(),'delivery'=>array());
        $loadServicedetails                 = $this->resrServiceModel->getLoadServiceDetails($loadidentity);
        $jsonData['service_date']           =   date("Y-m-d h:i");
        $jsonData['service_code']           =   $loadServicedetails['service_code'];
        $jsonData['service_id']             =   $loadServicedetails['service_id'];
        $jsonData['customer_id']            =   $loadServicedetails['customer_id'];
        $jsonData['carrier_id']             =   $loadServicedetails['carrier'];
        $jsonData['callback_url']           =   'https://api-sandbox.noqu.delivery/callback/mydelivery';
        $loadDetails                        =   $this->resrServiceModel->getLoadDetails($loadidentity);
        $parcelDetails                      =   $this->resrServiceModel->getParcelDetails($loadidentity);
        foreach($loadDetails as $key=>$data){
          if($data['shipment_service_type'] =='P'){
              $temp = array();
              $temp['address'] = array(
                  'country'=>$data['shipment_customer_country'],
                  'country_code'=>$data['shipment_country_code'],
                  'currency_code'=>$loadServicedetails['currency'],
                  'postcode'=>$data['shipment_postcode'],
                  'company_name'=>$data['shipment_companyName'],
                  'address_line1'=>$data['shipment_address1'],
                  'address_line2'=>$data['shipment_address2'],
                  'city'=>$data['shipment_customer_city'],
                  'notes'=>$data['shipment_notes'],
                  'latitude'=>$data['shipment_latitude'],
                  'longitude'=>$data['shipment_longitude'],
                  'geo_position' => array('latitude'=>$data['shipment_latitude'],'longitude'=>$data['shipment_longitude'])
              );
              $temp['consignee'] = array('name'=>$data['shipment_customer_name'],'phone'=>$data['shipment_customer_phone'],'email'=>$data['shipment_customer_email']);
              $jsonData['collection'] = $temp;
              $jsonData['collected_by'] = ($data['is_internal']==1)?'self':'notself';
          }
          elseif($data['shipment_service_type']=='D'){
              $temp = array();
              $temp['address'] = array(
                  'country'=>$data['shipment_customer_country'],
                  'country_code'=>$data['shipment_country_code'],
                  'currency_code'=>$loadServicedetails['currency'],
                  'postcode'=>$data['shipment_postcode'],
                  'company_name'=>$data['shipment_companyName'],
                  'address_line1'=>$data['shipment_address1'],
                  'address_line2'=>$data['shipment_address2'],
                  'city'=>$data['shipment_customer_city'],
                  'notes'=>$data['shipment_notes'],
                  'latitude'=>$data['shipment_latitude'],
                  'longitude'=>$data['shipment_longitude'],
                  'geo_position' => array('latitude'=>$data['shipment_latitude'],'longitude'=>$data['shipment_longitude'])
              );
              $temp['consignee'] = array('name'=>$data['shipment_customer_name'],'phone'=>$data['shipment_customer_phone'],'email'=>$data['shipment_customer_email']);
              array_push($jsonData['delivery'],$temp);
          }
        }
        $jsonData['parcel']                 =   $parcelDetails;
        $jsonData['user_id']                =   $loadDetails[0]['user_id'];
        $jsonData['warehouse_id']           =   $loadDetails[0]['warehouse_id'];
        $jsonData['warehouse_latitude']     =   $loadDetails[0]['warehouse_latitude'];
        $jsonData['warehouse_longitude']    =   $loadDetails[0]['warehouse_longitude'];
        $jsonData['company_id']             =   $loadDetails[0]['company_id'];
        $this->endpoint = 'bookedRecurringJob';
        $data =  $this->bookNextDayJobWithoutQuotion(json_decode(json_encode($jsonData)));
        return $data;

     }
}
?>