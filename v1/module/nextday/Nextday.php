<?php
require_once "../v1/module/business_day_cal/Carrier_Business_Day.php";

final class Nextday extends Booking
    {
    private $_param = array();
    protected static $_ccf = NULL;
    public

    function __construct($data)
        {
        $this->_parentObj = parent::__construct(array(
            "email" => $data->email,
            "access_token" => $data->access_token
        ));
        $this->_param = $data;
        $this->customerccf = new CustomerCostFactor();
        $this->collectionModel = Collection::_getInstance(); //new Collection();
        }

    private
    function _getJobCollectionList($carriers, $address)
        {
        $jobCollectionList = $this->collectionModel->getJobCollectionList($carriers, $address, $this->_param->customer_id, $this->_param->company_id, $this->_param->collection_date);
        $data = array(
            'carrier_list' => array()
        );
        foreach($jobCollectionList['carrier_list'] as $key => $value)
            {
            $data['carrier_list'][$value['account_number']] = $value;
            }

        $this->regular_pickup = $jobCollectionList["regular_pickup"];
        return $data["carrier_list"];
        }

    private
    function _getCustomerCarrierAccount()
        {
        $result = array();
        foreach($this->_param->collection as $collection)
            {
            $collectionCountry = $collection->country;
            }

        foreach($this->_param->delivery as $delivery)
            {
            $deliveryCountry = $delivery->country;
            }

        $customerInfo = $this->modelObj->getCompanyInfo($this->_param->company_id);
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

        $carrier = $this->getCustomerCarrierAccount($this->_param->company_id, $this->_param->customer_id, $this->collection_postcode, $this->_param->collection_date);
        if (count($carrier) > 0)
            {
            foreach($carrier as $key => $item)
                {
                $accountId = isset($item["account_id"]) ? $item["account_id"] : $item["carrier_id"];
                $carrier[$key]["account_id"] = $accountId;
                foreach($this->_param->parcel as $parceldata)
                    {
                    $checkPackageSpecificService = $this->modelObj->checkPackageSpecificService($this->_param->company_id, $parceldata->package_code, $item['carrier_code'], $flowType);
                    if (count($checkPackageSpecificService) > 0)
                        {
                        foreach($checkPackageSpecificService as $serviceData)
                            {
                            $carrier[$key]["services"][$serviceData["service_code"]] = $serviceData;
                            }
                        }
                      else
                        {
                        $services = $this->modelObj->getCustomerCarrierServices($this->_param->customer_id, $accountId, $item["account_number"], $flowType);
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
            $collectionList = $this->_getJobCollectionList($carrier, $this->_getAddress($this->_param->collection->$collectionIndex));
            if (count($collectionList) > 0)
                {
                foreach($collectionList as $item)
                    {
                    if ((strtotime($this->_param->collection_date) > strtotime($item['collection_date_time'])) || (strtotime($this->_param->collection_date) == strtotime($item['collection_date_time'])))
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

    private
    function _getCarrierInfo($data)
        {
        foreach($data as $carrier_code => $lists)
            {
            switch (strtoupper($carrier_code))
                {
            case 'UKMAIL':
                $this->getUkmailServiceList($carrier_code, $lists);
                break;

            case 'DHL':
                $this->getDhlServiceList($carrier_code, $lists);
                break;

            case 'PNP':
                $this->getPNPServiceList($carrier_code, $lists);
                break;
                }
            }

        return $data;
        }

    private
    function getPNPServiceList($carrier_code, $lists)
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
                            $serviceCcf = $this->customerccf->calculateServiceCcf($service_code, $ratePrice, $accountId, $this->_param->customer_id, $this->_param->company_id);
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
                                                $surchargeCcf = $this->customerccf->calculateSurchargeCcf($surcharge_code, $this->_param->customer_id, $this->_param->company_id, $this->carrierList[$accountNumber]["account_id"], $surcharge_price);
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
    function getUkmailServiceList($carrier_code, $lists)
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
                            $serviceCcf = $this->customerccf->calculateServiceCcf($service_code, $ratePrice, $accountId, $this->_param->customer_id, $this->_param->company_id);
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
                                                $surchargeCcf = $this->customerccf->calculateSurchargeCcf($surcharge_code, $this->_param->customer_id, $this->_param->company_id, $this->carrierList[$accountNumber]["account_id"], $surcharge_price);
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

    /*     * **********DHL Service list (Start from Here) ********* */
    private
    function getDhlServiceList($carrier_code, $lists)
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
                            $ratePrice = $services[0]->rate->weight_charge;
                            $accountId = isset($this->carrierList[$accountNumber]["account_id"]) ? $this->carrierList[$accountNumber]["account_id"] : $this->carrierList[$accountNumber]["carrier_id"];
                            $serviceCcf = $this->customerccf->calculateServiceCcf($service_code, $ratePrice, $accountId, $this->_param->customer_id, $this->_param->company_id);
                            $services[0]->rate->price = $serviceCcf["price_with_ccf"];
                            $services[0]->rate->info = $serviceCcf;
                            if (!isset($services[0]->rate->currency))
                                {
                                $currencyKey = 0;
                                $currency = $this->_param->collection->$currencyKey->country->currency_code;
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

                                $surchargeWithCcfPrice = 0;
                                $surchargePrice = 0;
                                $service->collected_by = $this->carrierList[$accountNumber]["collected_by"];
                                $collected_item["carrier_price_info"]["taxes"] = isset($service->taxes->tax_percentage) ? (number_format((($serviceCcf["original_price"] + $surchargePrice) * $service->taxes->tax_percentage / 100) , 2)) : 0;
                                $collected_item["customer_price_info"]["taxes"] = isset($service->taxes->tax_percentage) ? (number_format((($serviceCcf["price_with_ccf"] + $surchargeWithCcfPrice) * $service->taxes->tax_percentage / 100) , 2)) : 0;
                                foreach($service->collected_by as $collected_key => $collected_item)
                                    {
                                    $surchargeWithCcfPrice = 0;
                                    $surchargePrice = 0;
                                    isset($service->rate->fuel_surcharge) ? (@$services[$key5]->surcharges->fuel_surcharge = $service->rate->fuel_surcharge) : '';
                                    isset($service->rate->remote_area_delivery) ? (@$services[$key5]->surcharges->remote_area_delivery = $service->rate->remote_area_delivery) : '';
                                    isset($service->rate->insurance_charge) ? (@$services[$key5]->surcharges->insurance_charge = $service->rate->insurance_charge) : '';
                                    isset($service->rate->over_weight_charge) ? (@$services[$key5]->surcharges->over_weight_charge = $service->rate->over_weight_charge) : '';
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
                                                $surchargeCcf = $this->customerccf->calculateSurchargeCcf($surcharge_code, $this->_param->customer_id, $this->_param->company_id, $this->carrierList[$accountNumber]["account_id"], $surcharge_price);
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
    function _getAddress($item)
        {
        return array(
            "name" => "",
            "company" => "",
            "phone" => "",
            "street1" => (isset($item->address_line1)) ? $item->address_line1 : "",
            "street2" => (isset($item->address_line2)) ? $item->address_line2 : "",
            "city" => (isset($item->city) && !empty($item->city)) ? $item->city : "oxford",
            "state" => (isset($item->state)) ? $item->state : "",
            "zip" => $item->postcode,
            "country" => $item->country->alpha2_code,
            "country_name" => $item->country->short_name

        );
        }

    private
    function _setPostRequest()
        {
        $this->data = array();
        $carrierLists = $this->_getCustomerCarrierAccount();
        if ($carrierLists["status"] == "success")
            {
            $key = 0;
            $isDocument = '';
            $currencyCode = (isset($this->_param->collection->$key->country->currency_code) && !empty($this->_param->collection->$key->country->currency_code)) ? $this->_param->collection->$key->country->currency_code : 'GBP';
            $this->data = array(
                "carriers" => $carrierLists["data"],
                "from" => $this->_getAddress($this->_param->collection->$key) ,
                "to" => $this->_getAddress($this->_param->delivery->$key) ,
                "ship_date" => date("Y-m-d", strtotime($this->_param->collection_date)) ,
                "extra" => array(
                    'is_document' => false
                ) ,
                "currency" => $currencyCode

            );
            $this->data["package"] = array();
            foreach($this->_param->parcel as $item)
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
            if (isset($this->_param->is_insured))
                {
                if ($this->_param->is_insured == true) $this->data["insurance"] = array(
                    "value" => $this->_param->insurance_amount,
                    "currency" => $this->_param->collection->$key->country->currency_code
                );
                }

            $this->data["status"] = "success";
            }
          else
            {
            $this->data = $carrierLists;
            }
        }

    function searchNextdayCarrierAndPrice()
        {
        $todayStamp = strtotime(date("Y-m-d", strtotime("now")));
        $collectionDateStamp = strtotime(date("Y-m-d", strtotime($this->_param->collection_date)));
        if ($collectionDateStamp < $todayStamp)
            {
            return array(
                "status" => "error",
                "message" => "previous collection date booking error"
            );
            }

        $accountStatus = $this->_checkCustomerAccountStatus($this->_param->customer_id);
        if ($accountStatus["status"] == "error")
            {
            return $accountStatus;
            }

        $available_credit = $this->_getCustomerAccountBalence($this->_param->customer_id, 0.00);
        $key = 0;
        $destinations = array();
        $this->collection_postcode = $this->_param->collection->$key->postcode;
        $this->_setPostRequest();
        if ($this->data["status"] == "success")
            {
            $requestStr = json_encode($this->data);
            $responseStr = $this->_postRequest($this->data);
            $response = json_decode($responseStr);
            $response = $this->_getCarrierInfo($response->rate);
            if (isset($response->status) and $response->status = "error")
                {
                return array(
                    "status" => "error",
                    "message" => $response->message
                );
                }

            return array(
                "status" => "success",
                "message" => "Rate found",
                "service_request_string" => base64_encode($requestStr) ,
                "service_response_string" => base64_encode($responseStr) ,
                "data" => $response,
                "service_time" => date("H:i", strtotime($this->_param->collection_date)) ,
                "service_date" => date("d/M/Y", strtotime($this->_param->collection_date)) ,
                "availiable_balence" => $available_credit['available_credit']
            );
            }
          else
            {
            return array(
                "status" => "error",
                "message" => $this->data["message"]
            );
            }
        }

    public

    function saveBooking()
        {
        $accountStatus = $this->_checkCustomerAccountStatus($this->_param->customer_id);
        if ($accountStatus["status"] == "error")
            {
            return $accountStatus;
            }

        $this->_param->manualbookingreference = (isset($this->_param->manualbookingreference) && $this->_param->manualbookingreference != '') ? $this->_param->manualbookingreference : '';
        $bookingShipPrice = $this->_param->service_opted->collection_carrier->customer_price_info->grand_total;
        $available_credit = $this->_getCustomerAccountBalence($this->_param->customer_id, $bookingShipPrice);
        if ($available_credit["status"] == "error")
            {
            return $available_credit;
            }


        $company_code = $this->_getCompanyCode($this->_param->company_id);
        $serviceId = $this->_param->service_opted->rate->info->service_id;
        $customerWarehouseId = $this->getCustomerWarehouseIdByCustomerId($this->_param->company_id, $this->_param->customer_id);
        $this->_param->service_opted->rate->shipment_type = "Next";
        $this->serviceRequestString = $this->_param->service_request_string;
        $this->serviceResponseString = $this->_param->service_response_string;
        $this->startTransaction();
        $execution_order = 0;
        $collection_date_time = $this->_param->service_opted->collection_carrier->collection_date_time;


        $collection_end_at = $this->_param->service_opted->collection_carrier->collection_end_at;
        $carrier_account_number = $this->_param->service_opted->collection_carrier->account_number;
        $is_internal = $this->_param->service_opted->collection_carrier->is_internal;
        $searchString = $companyName = $contactName = '';

        $this->maxTransitDays = 0;

        if(isset($this->_param->service_opted->service_options->max_transit_days))
            $this->maxTransitDays = (int)$this->_param->service_opted->service_options->max_transit_days;

        $carrierBusinessDay = new Carrier_Business_Day();

        $collectionDateTimeObj = $carrierBusinessDay->_findBusinessDay(new DateTime($collection_date_time), 0 , $this->_param->service_opted->carrier_info->code);
        $collection_date_time = $collectionDateTimeObj->format('Y-m-d');

        foreach($this->_param->collection as $key => $item)
            {
            $execution_order++;
            $addressInfo = $this->_saveAddressData($item, $this->_param->customer_id);
            if ($addressInfo["status"] == "error")
                {
                $this->rollBackTransaction();
                return $addressInfo;
                }

            $shipmentStatus = $this->_saveShipment($this->_param, $this->_param->collection->$key, $this->_param->parcel, $addressInfo["address_data"], $customerWarehouseId, $this->_param->company_id, $company_code, $collection_date_time, $collection_end_at, "next", "COLL", "NEXT", "P", $execution_order, $carrier_account_number, $is_internal);
            $sStr["postcode"] = $item->postcode;
            $sStr["address_line1"] = $item->address_line1;
            $sStr["iso_code"] = $item->country->alpha3_code;
            $searchString = str_replace(' ', '', implode('', $sStr));
            $companyName = $item->company_name;
            $contactName = $item->name;
            if ($shipmentStatus["status"] == "error")
                {
                $this->rollBackTransaction();
                return $shipmentStatus;
                }

            if ($key == 0) $loadIdentity = $shipmentStatus["shipment_ticket"];
            foreach($this->_param->parcel as $item)
                {
                for ($i = 0; $i < $item->quantity; $i++)
                    {
                    $parcelStatus = $this->_saveParcel($shipmentStatus["shipment_id"], $shipmentStatus["shipment_ticket"], $customerWarehouseId, $this->_param->company_id, $company_code, $item, "P", $loadIdentity);
                    if ($parcelStatus["status"] == "error")
                        {
                        $this->rollBackTransaction();
                        return $parcelStatus;
                        }
                    }
                }

            $shipmentDimension = $this->_getParcelDimesionByShipmentId($shipmentStatus["shipment_id"]);
            $this->_saveShipmentDimension($shipmentDimension, $shipmentStatus["shipment_id"]);
            $surchargesArr = isset($this->_param->service_opted->collection_carrier->surcharges) ? $this->_param->service_opted->collection_carrier->surcharges : array();
            $otherDetail = array(
                'reason_for_export' => isset($this->_param->reason_for_export) ? $this->_param->reason_for_export : '',
                'tax_status' => isset($this->_param->tax_status) ? $this->_param->tax_status : '',
                'terms_of_trade' => isset($this->_param->terms_of_trade) ? $this->_param->terms_of_trade : '',
                'is_insured' => isset($this->_param->is_insured) ? $this->_param->is_insured : false
            );
            $this->_param->customer_reference1 = (isset($this->_param->customer_reference1)) ? $this->_param->customer_reference1 : "";
            $this->_param->customer_reference2 = (isset($this->_param->customer_reference2)) ? $this->_param->customer_reference2 : "";
            $this->_param->service_opted->collection_carrier->surcharges = isset($this->_param->service_opted->collection_carrier->surcharges) ? $this->_param->service_opted->collection_carrier->surcharges : 0;
            $serviceStatus = $this->_saveShipmentService($this->_param->service_opted, $this->_param->service_opted->collection_carrier->surcharges, $loadIdentity, $this->_param->customer_id, "pending", $otherDetail, $serviceId, $this->_param->customer_reference1, $this->_param->customer_reference2, $this->_param->ismanualbooking, $this->_param->manualbookingreference);
            $this->_saveInfoReceived($loadIdentity);
            if ($serviceStatus["status"] == "error")
                {
                $this->rollBackTransaction();
                return $serviceStatus;
                }

            $paymentStatus = $this->_manageAccounts($serviceStatus["service_id"], $loadIdentity, $this->_param->customer_id, $this->_param->company_id);
            if ($paymentStatus["status"] == "error")
                {
                $this->rollBackTransaction();
                return $paymentStatus;
                }

            $collectedBy = $this->_param->service_opted->collection_carrier;
            $collectedBy->service_id = $serviceStatus["service_id"];
            $collectedByStatus = $this->_saveShipmentCollection($collectedBy);
            if ($collectedByStatus["status"] == "error")
                {
                $this->rollBackTransaction();
                return $collectedByStatus;
                }

            $serviceOptions = isset($this->_param->service_opted->service_options) ? $this->_param->service_opted->service_options : array();
            $attributeStatus = $this->_saveShipmentAttribute($serviceOptions, $loadIdentity);
            if ($attributeStatus["status"] == "error")
                {
                $this->rollBackTransaction();
                return $attributeStatus;
                }
            }

        $deliveryDateTimeObj = $carrierBusinessDay->_findBusinessDay(new DateTime($collection_date_time), $this->maxTransitDays , $this->_param->service_opted->carrier_info->code);
        $delivery_date_time = $deliveryDateTimeObj->format('Y-m-d');

        //$collection_date_time = "1970-01-01 00:00:00";
        $collection_end_at = "00:00:00";
        $carrier_account_number = $this->_param->service_opted->carrier_info->account_number;
        $is_internal = $this->_param->service_opted->carrier_info->is_internal;
        foreach($this->_param->delivery as $key => $item)
            {
            $execution_order++;
            $addressInfo = $this->_saveAddressData($item, $this->_param->customer_id);
            if ($addressInfo["status"] == "error")
                {
                $this->rollBackTransaction();
                return $addressInfo;
                }

            $this->_param->delivery->$key->load_identity = $loadIdentity;
            $shipmentStatus = $this->_saveShipment($this->_param, $this->_param->delivery->$key, $this->_param->parcel, $addressInfo["address_data"], $customerWarehouseId, $this->_param->company_id, $company_code, $delivery_date_time, $collection_end_at, "next", "DELV", "NEXT", "D", $execution_order, $carrier_account_number, $is_internal);
            if ($shipmentStatus["status"] == "error")
                {
                $this->rollBackTransaction();
                return $shipmentStatus;
                }

            foreach($this->_param->parcel as $item)
                {
                for ($i = 0; $i < $item->quantity; $i++)
                    {
                    $parcelStatus = $this->_saveParcel($shipmentStatus["shipment_id"], $shipmentStatus["shipment_ticket"], $customerWarehouseId, $this->_param->company_id, $company_code, $item, "D", $loadIdentity);
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

        if (isset($this->_param->items))
            {
            foreach($this->_param->items as $item)
                {
                $itemStatus = $this->_saveShipmentItems($item, $loadIdentity, $this->_param->customer_id, $booking_status = 0);
                if ($itemStatus["status"] == "error")
                    {
                    $this->rollBackTransaction();
                    return $itemStatus;
                    }
                }
            }

        $allData = $this->_param;
        $carrier_code = $this->_param->service_opted->carrier_info->code;
        $rateDetail = (strtolower($carrier_code) == 'dhl') ? $this->_param->service_opted->rate : array();
        $this->commitTransaction();
        $labelHttpPath = Library::_getInstance()->base_url() . '/' . LABEL_FOLDER;
        if ((strtolower($carrier_code) == 'pnp'))
            {
            $label_path = "$labelHttpPath/$loadIdentity/pnp/$loadIdentity.pdf";
            $customLabel = new Custom_Label();
            $customLabel->createLabel($loadIdentity);
            $labelData = array(
                "label_file_pdf" => $label_path
            );
            $saveLabelInfo = $this->_saveLabelInfoByLoadIdentity($labelData, $loadIdentity);
            $statusArr = array(
                "status" => "success"
            );
            $this->modelObj->updateBookingStatus($statusArr, $loadIdentity);
            $autoPrint = $this->modelObj->getAutoPrintStatusByCustomerId($this->_param->customer_id);
            $checkPickupExist = array();
            if ($saveLabelInfo)
                {
                if (strtolower($carrier_code) == 'dhl')
                    {
                    $userId = $this->_param->collection_user_id;
                    $carrierId = $this->_param->service_opted->carrier_info->carrier_id;
                    $collectionDate = date('Y-m-d', strtotime($this->_param->service_opted->collection_carrier->collection_date_time));
                    $checkPickupExist = $this->modelObj->checkExistingPickupForShipment($this->_param->customer_id, $carrierId, $userId, $collectionDate, $searchString, $companyName, $contactName, $loadIdentity);
                    }

                Consignee_Notification::_getInstance()->sendNextdayBookingConfirmationNotification(array(
                    "load_identity" => $loadIdentity,
                    "company_id" => $this->_param->company_id,
                    "warehouse_id" => $this->_param->warehouse_id,
                    "customer_id" => $this->_param->customer_id
                ));
                Consignee_Notification::_getInstance()->sendNextdayBookingConfirmationNotificationToCourier(array(
                    "load_identity" => $loadIdentity,
                    "company_id" => $this->_param->company_id,
                    "warehouse_id" => $this->_param->warehouse_id,
                    "customer_id" => $this->_param->customer_id
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

        if((strtolower($carrier_code) != 'pnp') && $this->_param->manualbookingreference=='')
            {
            $labelInfo = $this->getLabelFromLoadIdentity($loadIdentity, $rateDetail, $allData);
            if ($labelInfo['status'] == 'success')
                {
                $labelData = array(
                    "label_tracking_number" => isset($labelInfo['label_tracking_number']) ? $labelInfo['label_tracking_number'] : '0',
                    "label_files_png" => isset($labelInfo['label_files_png']) ? $labelInfo['label_files_png'] : '',
                    "label_file_pdf" => isset($labelInfo['file_path']) ? $labelInfo['file_path'] : '',
                    "label_json" => isset($labelInfo['label_json']) ? $labelInfo['label_json'] : ''
                );
                $saveLabelInfo = $this->_saveLabelInfoByLoadIdentity($labelData, $loadIdentity);

                // $obj = new Create_Tracking();

                $statusArr = array(
                    "status" => "success"
                );
                $this->modelObj->updateBookingStatus($statusArr, $loadIdentity);
                $autoPrint = $this->modelObj->getAutoPrintStatusByCustomerId($this->_param->customer_id);
                $checkPickupExist = array();
                if ($saveLabelInfo)
                    {
                    if (strtolower($carrier_code) == 'dhl')
                        {
                        $userId = $this->_param->collection_user_id;
                        $carrierId = $this->_param->service_opted->carrier_info->carrier_id;
                        $collectionDate = date('Y-m-d', strtotime($this->_param->service_opted->collection_carrier->collection_date_time));
                        $checkPickupExist = $this->modelObj->checkExistingPickupForShipment($this->_param->customer_id, $carrierId, $userId, $collectionDate, $searchString, $companyName, $contactName, $loadIdentity);
                        }

                    Consignee_Notification::_getInstance()->sendNextdayBookingConfirmationNotification(array(
                        "load_identity" => $loadIdentity,
                        "company_id" => $this->_param->company_id,
                        "warehouse_id" => $this->_param->warehouse_id,
                        "customer_id" => $this->_param->customer_id
                    ));
                    Consignee_Notification::_getInstance()->sendNextdayBookingConfirmationNotificationToCourier(array(
                        "load_identity" => $loadIdentity,
                        "company_id" => $this->_param->company_id,
                        "warehouse_id" => $this->_param->warehouse_id,
                        "customer_id" => $this->_param->customer_id
                    ));
                    return array(
                        "status" => "success",
                        "message" => "Shipment booked successful. Shipment ticket $loadIdentity",
                        "file_path" => $labelInfo['file_path'],
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
        }

    public

    function getLabelFromLoadIdentity($loadIdentity, $rateDetail, $allData = array())
        {
        $carrierObj = new Carrier();
        $bookingInfo = $carrierObj->getShipmentInfo($loadIdentity, $rateDetail, $allData);
        return $bookingInfo;
        }

    public

    function assignPickupForShipment($pickupData)
        {
        $flag = $this->modelObj->updateShipment($pickupData->pickup_id, $pickupData->shipment_id);
        if ($flag)
            {
            return array(
                'status' => 'success',
                'message' => 'Pickup assigned successfully'
            );
            }
          else
            {
            return array(
                'status' => 'error',
                'message' => 'Problem in assigning the pickup, please create new one.'
            );
            }
        }

    protected
    function _deleteBooking($loadIdentity)
        {
        return $this->modelObj->deleteBookingDataByLoadIdentity($loadIdentity);
        }

    public

    function _getCarrierInfoManual($param)
        {
        $selectedCarrier = json_decode($param->manualprice->selectedcarrier, 1);
        $selectedService = json_decode($param->manualprice->selected_service, 1);
        $servicePrice = $param->manualprice->service_price;
        $taxval = $param->manualprice->taxval;
        $surcharges = array();
        $surchargesPrice = array();
        $returnarray = array();
        $accountdata = array();
        $servicedata = array();
        if (isset($param->manualprice->newsurcharges) and count($param->manualprice->newsurcharges) > 0)
            {
            foreach($param->manualprice->newsurcharges as $key => $val)
                {
                $surcharges[$key] = json_decode($val, 1);
                }
            }

        if (isset($param->manualprice->newsurchargesprice) and count($param->manualprice->newsurchargesprice) > 0)
            {
            foreach($param->manualprice->newsurchargesprice as $key => $val)
                {
                $surchargesPrice[$key] = json_decode($val, 1);
                }
            }

        $returnarray[$selectedCarrier['code']] = array();
        $accountdata[$selectedCarrier['account_number']] = array();
        $servicedata[$selectedService['service_code']] = array();
        $getInternalCarrier = $this->modelObj->getCarriersofCompany($param->company_id);
        $innerData = array();
        $surchargesDetails = array(
            'surcharges' => array()
        );
        $innerData['rate']['flow_type'] = $selectedService['flow_type'];
        $innerData['rate']['price'] = $servicePrice;
        $innerData['rate']['rate_type'] = '';
        $innerData['rate']['act_number'] = $selectedCarrier['account_number'];
        $innerData['rate']['message'] = '';
        $innerData['rate']['currency'] = '';
        $innerData['rate']['info'] = array(
            'original_price' => $servicePrice,
            'ccf_value' => 0,
            'operator' => 'FLAT',
            'price' => '0',
            'company_service_code' => $selectedService['company_service_code'],
            'company_service_name' => $selectedService['company_service_name'],
            'courier_service_code' => $selectedService['service_code'],
            'courier_service_name' => $selectedService['service_name'],
            'level' => 0,
            'service_id' => $selectedService['service_id'],
            'price_with_ccf' => $servicePrice
        );
        $innerData['service_options']['dimensions'] = array(
            'length' => '9999',
            'width' => '9999',
            'height' => '9999',
            'unit' => 'CM'
        );
        $innerData['service_options']['weight'] = array(
            'weight' => '9999',
            'unit' => 'KG'
        );
        $innerData['service_options']['time'] = array(
            'max_waiting_time' => '',
            'unit' => ''
        );
        $innerData['service_options']['category'] = '';
        $innerData['service_options']['charge_from_base'] = '';
        $innerData['service_options']['icon'] = $selectedCarrier['icon'];
        $innerData['service_options']['max_delivery_time'] = '';
        $innerData['service_times'] = array(
            'last_booking_time' => '',
            'last_pickup_time' => ''
        );
        $totalSurcharge = 0;
        if (count($surcharges) > 0)
            {
            foreach($surcharges as $keysur => $valsur)
                {
                $innerData['surcharges'][$valsur['surcharge_code']] = $surchargesPrice[$keysur];
                $surchargesDetails['surcharges'][$valsur['surcharge_code']]['original_price'] = $surchargesPrice[$keysur];
                $surchargesDetails['surcharges'][$valsur['surcharge_code']]['surcharge_value'] = 0;
                $surchargesDetails['surcharges'][$valsur['surcharge_code']]['operator'] = 'FLAT';
                $surchargesDetails['surcharges'][$valsur['surcharge_code']]['price'] = 0;
                $surchargesDetails['surcharges'][$valsur['surcharge_code']]['price_with_ccf'] = $surchargesPrice[$keysur];
                $surchargesDetails['surcharges'][$valsur['surcharge_code']]['company_surcharge_code'] = $valsur['company_surcharge_code'];
                $surchargesDetails['surcharges'][$valsur['surcharge_code']]['company_surcharge_name'] = $valsur['company_surcharge_name'];
                $surchargesDetails['surcharges'][$valsur['surcharge_code']]['courier_surcharge_code'] = $valsur['surcharge_code'];
                $surchargesDetails['surcharges'][$valsur['surcharge_code']]['courier_surcharge_name'] = $valsur['surcharge_name'];
                $surchargesDetails['surcharges'][$valsur['surcharge_code']]['level'] = 0;
                $surchargesDetails['surcharges'][$valsur['surcharge_code']]['surcharge_id'] = $valsur['surcharge_id'];
                $surchargesDetails['surcharges'][$valsur['surcharge_code']]['carrier_id'] = $selectedCarrier['courier_id'];
                }

            $totalSurcharge = array_sum($innerData['surcharges']);
            }

        $taxprice = number_format((($servicePrice + $totalSurcharge) * $taxval / 100) , 2);
        $innerData['taxes'] = array(
            'total_tax' => $taxprice,
            'tax_percentage' => $taxval
        );
        $tempCollectedBy = array();
        $tempCollectedBy['carrier_code'] = $selectedCarrier['code'];
        $tempCollectedBy['account_number'] = $selectedCarrier['account_number'];
        $tempCollectedBy['is_internal'] = $selectedCarrier['is_internal'];
        $tempCollectedBy['name'] = $selectedCarrier['name'];
        $tempCollectedBy['icon'] = $selectedCarrier['icon'];
        $tempCollectedBy['pickup_surcharge'] = $selectedCarrier['pickup_surcharge'];
        $tempCollectedBy['collection_date_time'] = $param->collection_date;
        $tempCollectedBy['collection_start_at'] = $selectedCarrier['collection_start_at'];
        $tempCollectedBy['collection_end_at'] = $selectedCarrier['collection_end_at'];
        $tempCollectedBy['is_regular_pickup'] = 'yes';
        $tempCollectedBy['carrier_id'] = $selectedCarrier['courier_id'];
        $tempCollectedBy['pickup'] = $selectedCarrier['pickup'];
        $tempCollectedBy['surcharges'] = $surchargesDetails['surcharges'];
        $tempCollectedBy['carrier_price_info'] = array(
            'price' => $servicePrice,
            'surcharges' => $totalSurcharge,
            'taxes' => $taxprice,
            'grand_total' => number_format(($servicePrice + $totalSurcharge + $taxprice) , 2)
        );
        $tempCollectedBy['customer_price_info'] = array(
            'price' => $servicePrice,
            'surcharges' => $totalSurcharge,
            'taxes' => $taxprice,
            'grand_total' => number_format(($servicePrice + $totalSurcharge + $taxprice) , 2)
        );
        $innerData['collected_by'][] = $tempCollectedBy;

        // internal

        $tempCollectedBy = array();
        $tempCollectedBy['carrier_code'] = $getInternalCarrier[0]['code'];
        $tempCollectedBy['account_number'] = $getInternalCarrier[0]['account_number'];
        $tempCollectedBy['is_internal'] = $getInternalCarrier[0]['is_internal'];
        $tempCollectedBy['name'] = $getInternalCarrier[0]['name'];
        $tempCollectedBy['icon'] = $getInternalCarrier[0]['icon'];
        $tempCollectedBy['pickup_surcharge'] = $getInternalCarrier[0]['pickup_surcharge'];
        $tempCollectedBy['collection_date_time'] = $param->collection_date;
        $tempCollectedBy['collection_start_at'] = $getInternalCarrier[0]['collection_start_at'];
        $tempCollectedBy['collection_end_at'] = $getInternalCarrier[0]['collection_end_at'];
        $tempCollectedBy['is_regular_pickup'] = 'yes';
        $tempCollectedBy['carrier_id'] = $getInternalCarrier[0]['courier_id'];
        $tempCollectedBy['pickup'] = $getInternalCarrier[0]['pickup'];
        $tempCollectedBy['surcharges'] = $surchargesDetails['surcharges'];
        $tempCollectedBy['carrier_price_info'] = array(
            'price' => $servicePrice,
            'surcharges' => $totalSurcharge,
            'taxes' => $taxprice,
            'grand_total' => number_format(($servicePrice + $totalSurcharge + $taxprice) , 2)
        );
        $tempCollectedBy['customer_price_info'] = array(
            'price' => $servicePrice,
            'surcharges' => $totalSurcharge,
            'taxes' => $taxprice,
            'grand_total' => number_format(($servicePrice + $totalSurcharge + $taxprice) , 2)
        );
        $innerData['collected_by'][] = $tempCollectedBy;
        $innerData['carrier_info'] = array(
            'highlight_class' => 'highlighted-datetime',
            'carrier_id' => $selectedCarrier['courier_id'],
            'name' => $selectedCarrier['name'],
            'icon' => $selectedCarrier['icon'],
            'code' => $selectedCarrier['code'],
            'description' => 'courier information goes here',
            'account_number' => $selectedCarrier['account_number'],
            'is_internal' => $selectedCarrier['is_internal']
        );
        $innerData['service_info'] = array(
            'code' => $selectedService['service_code'],
            'name' => $selectedService['service_name']
        );
        $servicedata[$selectedService['service_code']][] = $innerData;
        $accountdata[$selectedCarrier['account_number']][] = $servicedata;
        $returnarray[$selectedCarrier['code']][] = $accountdata;
        return $returnarray;
        }

    public

    function searchNextdayCarrierAndPriceManual($manualReq)
        {
        $accountStatus = $this->_checkCustomerAccountStatus($this->_param->customer_id);
        if ($accountStatus["status"] == "error")
            {
            return $accountStatus;
            }

        $available_credit = $this->_getCustomerAccountBalence($this->_param->customer_id, 0.00);
        $response = $this->_getCarrierInfoManual($manualReq);
        if (isset($response->status) and $response->status = "error")
            {
            return array(
                "status" => "error",
                "message" => $response->message
            );
            }

        return array(
            "status" => "success",
            "message" => "Rate found",
            "service_request_string" => "",
            "service_response_string" => base64_encode(json_encode($response)) ,
            "data" => $response,
            "service_time" => date("H:i", strtotime($this->_param->collection_date)) ,
            "service_date" => date("d/M/Y", strtotime($this->_param->collection_date)) ,
            "availiable_balence" => $available_credit['available_credit']
        );
        }
    }
