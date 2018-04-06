<?php
interface CarrierInterface{
    public function validateParams();
    public function validateCollectionAddress();
    public function validateDeliveryAddress();
    public function validateShipDate();
    public function validatePackage();
    public function prepareParams();
    //public function send();
    public function searchService($param);
}
?>