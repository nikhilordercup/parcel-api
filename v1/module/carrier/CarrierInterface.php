<?php
interface CarrierInterface{
    public function validateParams();
    public function validateCollectionAddress();
    public function validateDeliveryAddress();
    public function validateShipDate();
    public function validatePackage();
    public function prepareParams();
    public function searchService($param);
    public function carrierInfo();
    public function serviceInfo($param);
}
?>