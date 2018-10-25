<?php
/**
 * Created by PhpStorm.
 * User: nishant
 * Date: 22/05/18
 * Time: 10:34 AM
 */
final Class Collection
{
    public static $_collectionObj = null;

    public static

    function _getInstance()
    {
        if (self::$_collectionObj == null)
        {
            self::$_collectionObj = new Collection();
        }
        return self::$_collectionObj;
    }

    public

    function __construct()
    {
        $this->modelObj = new Booking_Model_Booking();
        $this->collectionList = Array();
        $this->pickupSurcharge = "00.00";
        $this->collectionStartAt = "00:00:00";
        $this->collectionEndAt = "00:00:00";
        $this->isRegularPickup = "no"; // regular pickup no means initilly it is set to schedule pickup
        $this->skipPickupProcess = false;
    }

    public

    function findInOperationalArea($operational_area, $needle)
    {
        $cols = array_column($operational_area, 'postcode');
        $key = array_search($needle, $cols);
        if (!$key)
        {
            $temp = array();
            foreach($cols as $key => $col)
            {
                if (stristr($needle, $col)) array_push($temp, $key);
            }

            if (count($temp) > 0) $key = $this->_search_in_array($temp);
        }

        return $key;
    }

    public

    function getJobCollectionList($carriers, $address, $customer_id, $company_id, $collection_date)
    {
        $this->carriers = $carriers;
        $this->collectionAddress = $address;
        $this->customerId = $customer_id;
        $this->companyId = $company_id;
        $this->today = date("Y-m-d", strtotime("now"));
        $this->collectionDateTimestamp = strtotime($collection_date);
        //BKP $this->collectionDate = date("Y-m-d H:i", $this->collectionDateTimestamp);

        $this->collectionDate = date("Y-m-d", $this->collectionDateTimestamp);
        $this->collectionTime = date("H:i", $this->collectionDateTimestamp);
        $this->collectionDateTime = date("Y-m-d H:i", $this->collectionDateTimestamp);

        $this->_getCollectionAddressString();
        $this->_findList();
        return array(
            "carrier_list" => $this->collectionList,
            "regular_pickup" => $this->isRegularPickup
        );
    }

    public

    function getCarrierAccountList($carriers, $address, $customer_id, $company_id, $collection_date)
    {
        $this->carriers = $carriers;
        $this->collectionAddress = $address;
        $this->customerId = $customer_id;
        $this->companyId = $company_id;
        $this->today = date("Y-m-d", strtotime("now"));
        $this->collectionDateTimestamp = strtotime($collection_date);
        //BKP $this->collectionDate = date("Y-m-d H:i", $this->collectionDateTimestamp);

        $this->collectionDateTime = date("Y-m-d H:i", $this->collectionDateTimestamp);

        $this->collectionDate = date("Y-m-d", $this->collectionDateTimestamp);
        $this->collectionTime = date("H:i", $this->collectionDateTimestamp);
        $this->currentTime = date("H:i", strtotime("now"));

        $this->_getCollectionAddressString();
        $this->_findCourier($this->carriers);
        $result = array();
        if (count($this->carrierList) > 0)
        {
            foreach($this->carrierList as $item)
            {
                array_push($result, $item);
            }

            $this->_findInternalCourier();
            if (isset($this->internalCarrier))
            {
                array_push($result, $this->internalCarrier);
            }
        }

        return $result;
    }

    private
    function _findList()
    {
        $this->_findInternalCourier();
        $this->_findCourier($this->carriers);
    }

    private
    function _isWeekend($date)
    {
        $weekDay = date('w', strtotime($date));
        return ($weekDay == 0 || $weekDay == 6);
    }

    private
    function _nextCollectionTimeBKP($collection_start_at, $collection_end_at, $collection_date = null)
    {
        $todayStartTimeTimestamp = strtotime($this->today . ' ' . $collection_start_at);
        $todayEndTimeTimestamp = strtotime($this->today . ' ' . $collection_end_at);
        $collection_date = ($collection_date != null) ? $collection_date : $this->collectionDateTime;
        $collectionDateTimestamp = strtotime($collection_date);
        $nextCollectionDate = "";
        if ($this->_isWeekend($collection_date))
        {
            $nextMonday = date("Y-m-d", strtotime("next monday"));
            $nextCollectionDate = date("Y-m-d H:i", strtotime("$nextMonday $collection_start_at"));
            $nextCollectionDate = "$nextCollectionDate";
        }
        else
        {
            $nextDateStamp = strtotime(date("Y-m-d " . $collection_start_at, strtotime('+1 day', strtotime($collection_date)))); //strtotime($collection_date);
            //BKP $colletionDateStamp = strtotime($this->collectionDate);
            $colletionDateStamp = strtotime($this->collectionDateTime);

            if (($collectionDateTimestamp >= $todayStartTimeTimestamp) AND ($collectionDateTimestamp <= $todayEndTimeTimestamp))
            {
                $nextCollectionDate = date("Y-m-d H:i", $colletionDateStamp);
            }
            elseif ($nextDateStamp > $colletionDateStamp)
            {
                $nextCollectionDate = date("Y-m-d H:i", $nextDateStamp);
            }
            else
            {
                $nextCollectionDateTime = date("Y-m-d", strtotime('+1 day', strtotime($collection_date)));
                $nextCollectionDateTime = $nextCollectionDateTime . ' ' . $collection_start_at;
                return $this->_nextCollectionTime($collection_start_at, $collection_end_at, $nextCollectionDateTime);
            }
        }
        return $nextCollectionDate;
    }

    private

    function _nextCollectionTime($collection_start_at, $collection_end_at, $collection_date = null)
    {
        if ($this->_isWeekend($collection_date))
        {
            $nextMonday = date("Y-m-d", strtotime("next monday"));
            $nextCollectionDate = date("Y-m-d H:i", strtotime("$nextMonday $collection_start_at"));
            $nextCollectionDate = "$nextCollectionDate";
        }
        else
        {
            $nextCollectionDate = $collection_date;
        }
        return $nextCollectionDate;
    }

    private

    function _prepareCollectionList($item)
    {
        $collectionStartTimeStamp = strtotime($item["collection_start_at"]);
        $collectionEndTimeStamp = strtotime($item["collection_end_at"]);
        return array(
            "collection_date_time" => $item["collection_date_time"],
            "collection_start_at" => date("H:i", $collectionStartTimeStamp) ,
            "collection_end_at" => date("H:i", $collectionEndTimeStamp) ,
            "is_regular_pickup" => $item["is_regular_pickup"],
            "pickup_surcharge" => $item["pickup_surcharge"],
            "account_number" => $item["account_number"],
            "carrier_code" => $item["carrier_code"],
            "description" => $item["description"],
            "carrier_id" => $item["carrier_id"],
            "account_id" => isset($item["account_id"]) ? $item["account_id"] : $item["carrier_id"],
            "username" => $item["username"],
            "password" => $item["password"],
            "internal" => $item["internal"],
            "services" => (isset($item["services"])) ? $item["services"] : array() ,
            "pickup" => $item["pickup"],
            "icon" => $item["icon"],
            "name" => $item["name"]
        );
    }

    private

    function _findCourier($list)
    {
        $this->carrierList = array();
        $defaultCollectionAddress = $this->modelObj->getDefaultCollectionAddress($this->customerId);

        $this->_findCollectionAddressIsRegularPickup($defaultCollectionAddress);
        foreach($list as $item)
        {
            // first check current date then only check time between collection start time and collection end posix_times
            if(strtotime($this->collectionDate) >= strtotime($this->today)){
                //address
                $startTimeFound = $this->modelObj->checkCollectionTime($defaultCollectionAddress['address_id'], $this->customerId, $item['carrier_code'], $this->collectionTime);
                if($startTimeFound){
                    $collectionDateTime = $this->_nextCollectionTime($startTimeFound["collection_start_time"], $startTimeFound["collection_end_time"], $this->collectionDateTime);
                }
                else{
                    $collectionDateTime = $this->modelObj->findCarrierCollectionStartTime($this->companyId, $item['carrier_id'], $item["account_number"], $item["username"], $item["password"]);
                    $collectionTime = $this->modelObj->checkCarrierCollectionStartTime($this->companyId, $item['carrier_id'], $item["account_number"], $item["username"], $item["password"],$this->collectionTime);
                    if($collectionTime["num_count"]>0){
                        $collectionDateTime = $this->_nextCollectionTime($collectionDateTime["collection_start_time"], $collectionDateTime["collection_end_time"], $this->collectionDateTime);
                    } else {
                        $nextDate = date("Y-m-d", strtotime($this->collectionDate . ' +1 day'));
                        $nextDate = "$nextDate $this->collectionTime";
                        $collectionStartTime = $collectionDateTime["collection_start_time"];
                        $collectionDateTime = $this->_nextCollectionTime($collectionDateTime["collection_start_time"], $collectionDateTime["collection_end_time"], $nextDate);
                    }
                }
            }

            $item["is_regular_pickup"] = $this->isRegularPickup;
            $item["collection_date_time"] = $collectionDateTime;

            $collectionList = $this->_prepareCollectionList($item);
            if ($item["pickup"] == 1 || $this->isRegularPickup == "yes" || $this->skipPickupProcess == true)
            {
                // collected by carrier itself
                $collectionList["collected_by"][] = array(
                    "carrier_code" => $collectionList["carrier_code"],
                    "account_number" => $collectionList["account_number"],
                    "is_internal" => $collectionList["internal"],
                    "name" => $collectionList["name"],
                    "icon" => $collectionList["icon"],
                    "pickup_surcharge" => $collectionList["pickup_surcharge"],
                    "collection_date_time" => $collectionList["collection_date_time"],
                    "collection_start_at" => $collectionList["collection_start_at"],
                    "collection_end_at" => $collectionList["collection_end_at"],
                    "is_regular_pickup" => $this->isRegularPickup,
                    "carrier_id" => $collectionList["carrier_id"],
                    "pickup" => $collectionList["pickup"]
                );
            }

            if (isset($this->internalCarrier))
            {
                $collectionList["collected_by"][] = array(
                    "carrier_code" => $this->internalCarrier["carrier_code"],
                    "account_number" => $this->internalCarrier["account_number"],
                    "is_internal" => $this->internalCarrier["internal"],
                    "name" => $this->internalCarrier["name"],
                    "icon" => $this->internalCarrier["icon"],
                    "pickup_surcharge" => $this->internalCarrier["pickup_surcharge"],
                    "collection_date_time" => $this->internalCarrier["collection_date_time"],
                    "collection_start_at" => $this->internalCarrier["collection_start_at"],
                    "collection_end_at" => $this->internalCarrier["collection_end_at"],
                    "is_regular_pickup" => $this->isRegularPickup,
                    "carrier_id" => $this->internalCarrier["carrier_id"],
                    "pickup" => $this->internalCarrier["pickup"]
                );
            }

            $this->collectionList[] = $collectionList;
            $this->carrierList[$item["account_number"]] = $item;
        }
    }

    private

    function _findInternalCourier()
    {
        $internalCarrier = $this->_getInternalCarrier();
        if (count($internalCarrier) > 0)
        {
            // step 1
            if (count($this->_findInOperationalArea()) > 0)
            {
                // step 2
                // find customer default collection address
                $defaultCollectionAddress = $this->modelObj->getCustomerDefaultCollectionAddress($this->customerId);
                $this->_findCollectionAddressIsRegularPickup($defaultCollectionAddress);
                $pickupSurcharge = 0;
                if ($this->isRegularPickup == "no")
                {
                    // add pickup surcharge
                    $pickupSurcharge = $internalCarrier["pickup_surcharge"];
                }
                $collectionDateTime = $this->_nextCollectionTime($internalCarrier["collection_start_at"], $internalCarrier["collection_end_at"]);
                $internalCarrier["pickup_surcharge"] = $pickupSurcharge;
                $internalCarrier["collection_date_time"] = $collectionDateTime;
                $internalCarrier["is_regular_pickup"] = $this->isRegularPickup;
                $this->internalCarrier = $this->_prepareCollectionList($internalCarrier);
            }
        }
    }

    private
    function _getAddressString($address)
    {
        $street1 = (isset($address["street1"])) ? $address["street1"] : "";
        $city = (isset($address["city"])) ? $address["city"] : "";
        $zip = (isset($address["zip"])) ? $address["zip"] : "";
        $country_name = (isset($address["country_name"])) ? $address["country_name"] : "";
        $address = strtolower(preg_replace('/[\s]+/mu', '', $street1 . $city . $zip . $country_name));
        return $address;
    }

    private
    function _getCollectionAddressString() //business name need to supply
    {
        $this->CollectionAddressStr = $this->_getAddressString($this->collectionAddress);
    }

    private
    function _findCollectionAddressIsRegularPickup($defaultCollectionAddress)
    {
        $defaultCollectionAddStr = $this->_getAddressString($defaultCollectionAddress);
        if ($this->CollectionAddressStr == $defaultCollectionAddStr) $this->isRegularPickup = "yes";
        else $this->skipPickupProcess = true;
    }

    private
    function _search_in_array($array)
    {
        array_multisort(array_map('strlen', $array) , $array);
        $data = array_pop($array);
        return $data;
    }

    private
    function _findInOperationalArea()
    {
        if (isset($this->collectionAddress["zip"]))
        {
            $operationalArea = $this->modelObj->getCourierOperationalArea($this->companyId);
            $needle = $this->collectionAddress["zip"];
            $data = $this->findInOperationalArea($operationalArea, $needle);
            return $operationalArea[$data];
        }
    }

    private
    function _getInternalCarrier()
    {
        $internalCarrier = $this->modelObj->getInternalCarrier($this->companyId);
        if (count($internalCarrier) > 0)
        {
            $this->pickupSurcharge = $internalCarrier["pickup_surcharge"];
            $this->collectionStartAt = $internalCarrier["collection_start_at"];
            $this->collectionEndAt = $internalCarrier["collection_end_at"];
            $this->pickup = $internalCarrier["pickup"];
        }

        return $internalCarrier;
    }
}
