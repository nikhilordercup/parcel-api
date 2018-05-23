<?php
/**
 * Created by PhpStorm.
 * User: nishant
 * Date: 22/05/18
 * Time: 10:34 AM
 */
final Class Collection{

    public

    function __construct(){
        $this->modelObj = new Booking_Model_Booking();

        $this->collectionList    = Array();
        $this->pickupSurcharge   = "00.00";
        $this->collectionStartAt = "00:00:00";
        $this->collectionEndAt   = "00:00:00";
        $this->isRegularPickup   = false; // regular pickup false means initilly it is set to schedule pickup
    }

    public

    function findInOperationalArea($operational_area, $needle){
        $cols = array_column($operational_area, 'postcode');

        $key = array_search($needle, $cols);

        if (!$key) {
            $temp = array();
            foreach ($cols as $key => $col) {
                if (stristr($needle, $col))
                    array_push($temp, $key);
            }
            if (count($temp) > 0)
                $data = $this->_search_in_array($temp);
        }
        return $data;
    }

    public

    function getJobCollectionList($carriers, $address, $customer_id, $company_id, $collection_date){
        $this->carriers          = $carriers;
        $this->collectionAddress = $address;
        $this->customerId        = $customer_id;
        $this->companyId         = $company_id;
        $this->today             = date("Y-m-d", strtotime("now"));

        $this->collectionDateTimestamp = strtotime($collection_date);

        $this->collectionDate    = date("Y-m-d H:i", $this->collectionDateTimestamp);

        $this->_getCollectionAddressString();

        $this->_findList();

        print_r($this->collectionList);die;
        return $this->collectionList;
    }

    private

    function _findList(){
        $this->_findCourier($this->carriers);

        //if collection not found then internal carrier will check the booking address is within the operational area
        if(count($this->collectionList)==0) {
            $this->_findInternalCourier();
        }
    }

    private

    function _isWeekend($date) {
        $weekDay = date('w', strtotime($date));
        return ($weekDay == 0 || $weekDay == 6);
    }

    private

    function _nextCollectionTime($collection_start_at, $collection_date=null){
        $todayStartTimeTimestamp = strtotime($this->today.' '.$collection_start_at);

        $collection_date = ($collection_date!=null) ? $collection_date : $this->collectionDate ;
        $collectionDateTimestamp = strtotime($collection_date);
        $nextCollectionDate = "";

        if($this->_isWeekend($collection_date)){
            $nextMonday = date("Y-m-d", strtotime("next monday"));
            $nextCollectionDate = date("Y-m-d H:i", strtotime("$nextMonday $collection_start_at"));
        }
        else{
            $nextDateStamp      = strtotime($collection_date);
            $colletionDateStamp = strtotime($this->collectionDate);

            if($todayStartTimeTimestamp > $collectionDateTimestamp){
                $nextCollectionDate = date("Y-m-d H:i");
            }elseif($nextDateStamp > $colletionDateStamp){
                $nextCollectionDate = "$collection_date $collection_start_at";
                $nextCollectionDate = date("Y-m-d H:i", strtotime($nextCollectionDate));
            }else{
                $nextCollectionDateTime = date("Y-m-d", strtotime('+1 day', strtotime($collection_date)));
                $nextCollectionDateTime = $nextCollectionDateTime;//.' '.$collection_start_at;
                return $this->_nextCollectionTime($collection_start_at, $nextCollectionDateTime);
            }
        }
        return $nextCollectionDate;
    }

    private

    function _prepareCollectionList($item){
        $collectionStartTimeStamp = strtotime($item["collection_start_at"]);
        $collectionEndTimeStamp   = strtotime($item["collection_end_at"]);

        $this->collectionList[] = array(
            "collection_date_time" => $item["collection_date_time"],
            "collection_start_at"  => date("H:i", $collectionStartTimeStamp),
            "collection_end_at"    => date("H:i", $collectionEndTimeStamp),
            "pickup_surcharge"     => $item["pickup_surcharge"],
            "carrier_id"           => $item["carrier_id"],
            "internal"             => $item["internal"],
            "pickup"               => $item["pickup"],
            "icon"                 => $item["icon"],
            "name"                 => $item["name"]
        );
    }

    private

    function _findCourier($list){
        $this->carrierList = array();
        //$this->carrierCode = array();

        //$todayStartTimeTimestamp = 0;

        $this->_findCollectionAddressIsRegularPickup();

        foreach($list as $item){
            if($item["pickup"]==1 || $this->isRegularPickup){

                $collectionDateTime = $this->_nextCollectionTime($item["collection_start_at"]);

                $item["collection_date_time"] = $collectionDateTime;

                $this->_prepareCollectionList($item);
            }
            //array_push($this->carrierCode, $item["carrier_code"]);
            $this->carrierList[$item["carrier_id"]] = $item;
        }

        //$this->carrierCode = implode(",", $this->carrierCode);
        //$this->carrierCode = "'$this->carrierCode'";
    }

    private

    function _findInternalCourier(){
        $internalCarrier = $this->_getInternalCarrier();
        if(count($internalCarrier)>0){
            //step 1
            if(count($this->_findInOperationalArea())>0){
                //step 2
                $this->_findCollectionAddressIsRegularPickup();

                $pickupSurcharge = 0;
                if(!$this->isRegularPickup){
                    //add pickup surcharge
                    $pickupSurcharge = $internalCarrier["pickup_surcharge"];
                }
                $collectionDateTime = $this->_nextCollectionTime($internalCarrier["collection_start_at"]);

                $internalCarrier["pickup_surcharge"] = $pickupSurcharge;

                $internalCarrier["collection_date_time"] = $collectionDateTime;

                $this->_prepareCollectionList($internalCarrier);
            }
        }
    }

    private

    function _getAddressString($address){
        $address = strtolower(preg_replace('/[\s]+/mu', '', $address["street1"].$address["city"].$address["zip"].$address["country_name"]));
        return $address;
    }

    private

    function _getCollectionAddressString(){//business name need to supply
        $this->CollectionAddressStr = $this->_getAddressString($this->collectionAddress);
    }

    private

    function _findCollectionAddressIsRegularPickup(){
        $this->defaultCollectionAddress = $this->modelObj->getDefaultCollectionAddress($this->customerId);
        $this->defaultCollectionAddStr = $this->_getAddressString($this->defaultCollectionAddress);
        echo "$this->CollectionAddressStr<br>$this->defaultCollectionAddStr<br<br><br<br><br<br><br<br>";
        if($this->CollectionAddressStr==$this->defaultCollectionAddStr)
            $this->isRegularPickup = true;
    }

    private

    function _search_in_array($array)
    {
        array_multisort(array_map('strlen', $array), $array);
        $data = array_pop($array);
        return $data;
    }

    private

    function _findInOperationalArea(){
        $operationalArea = $this->modelObj->getCourierOperationalArea($this->companyId);

        $needle = $this->collectionAddress["zip"];

        $data = $this->findInOperationalArea($operationalArea, $needle);

        return $operationalArea[$data];
    }

    private

    function _getInternalCarrier(){
        $internalCarrier = $this->modelObj->getInternalCarrier($this->companyId);
        if(count($internalCarrier)>0){
            $this->pickupSurcharge   = $internalCarrier["pickup_surcharge"];
            $this->collectionStartAt = $internalCarrier["collection_start_at"];
            $this->collectionEndAt   = $internalCarrier["collection_end_at"];
            $this->pickup            = $internalCarrier["pickup"];
        }
        return $internalCarrier;
    }
}