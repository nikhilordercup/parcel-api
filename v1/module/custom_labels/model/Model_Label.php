<?php
class Model_Label{
  public static $dbObj = null;
  private $_db = null;

  public function __construct(){
    if(self::$dbObj===null){
      self::$dbObj = new DbHandler();
    }
    $this->_db = self::$dbObj;
  }

  public function getParcelTicketByLoadIdentity($load_identity){
    $sql = "SELECT `shipment_ticket`, `parcel_ticket`, `parcel_weight`, `instaDispatch_loadIdentity`, `company_id` FROM `" . DB_PREFIX . "shipments_parcel` WHERE instaDispatch_loadIdentity = '$load_identity'";
    return $this->_db->getAllRecords($sql);
  }

  public function getConsigneeAddress($shipment_ticket){
    $sql = "SELECT shipment_customer_name, shipment_address1, shipment_address2, shipment_address3, shipment_contact_mobile, shipment_customer_phone, shipment_customer_email, shipment_postcode, shipment_customer_city, shipment_customer_country, shipment_county FROM " . DB_PREFIX . "shipment WHERE shipment_ticket LIKE '$shipment_ticket'";
    return $this->_db->getRowRecord($sql);
  }

  public function getParcelWeight($parcel_ticket){
    $sql = "SELECT parcel_weight AS parcel_weight FROM " . DB_PREFIX . "shipments_parcel WHERE parcel_ticket LIKE '$parcel_ticket'";
    return $this->_db->getRowRecord($sql);
  }

  public function getSenderAddress(){

  }

  public function getServiceDate($shipment_ticket){
    $sql = "SELECT shipment_required_service_date AS service_date FROM " . DB_PREFIX . "shipment WHERE shipment_ticket LIKE '$shipment_ticket'";
    return $this->_db->getRowRecord($sql);
  }

  public function getServiceName($load_identity){
    $sql = "SELECT service_name FROM " . DB_PREFIX . "shipment_service WHERE load_identity = '$load_identity'";
    return $this->_db->getRowRecord($sql);
  }

  public function getUrl(){

  }

  public function getCompanyLogo($company_id){
    $sql = "SELECT logo FROM " . DB_PREFIX . "configuration WHERE company_id = '$company_id'";
    return $this->_db->getRowRecord($sql);
  }

  public function getBarcodeNo(){

  }

  public function getConsigneePostcode($shipment_ticket){
    $sql = "SELECT shipment_postcode FROM " . DB_PREFIX . "shipment WHERE shipment_ticket LIKE '$shipment_ticket'";
    return $this->_db->getRowRecord($sql);
  }

  public function getConsigneeCountry($shipment_ticket){
    $sql = "SELECT shipment_customer_country AS shipment_country FROM " . DB_PREFIX . "shipment WHERE shipment_ticket LIKE '$shipment_ticket'";
    return $this->_db->getRowRecord($sql);
  }
}

?>
