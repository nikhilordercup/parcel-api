<?php
require_once "model/Model_Label.php";

class Label{

  public $methodArray = array(
    "__CONSIGNEE_ADDRESS__" => "findConsigneeAddress",
    "__WEIGHT__" => "findWeight",
    "__PIECE_COUNTER__" => "findPieceCounter",
    "__TOTAL_PIECE__" => "findTotalPiece",
    "__CUSTOMER_REFERENCE__" => "findCustomerReference",
    "__ALETRNATE_REFERENCE__" => "findAlternateReference",
    "__SERVICE_DATE__" => "findServiceDate",
    "__COMPANY_LOGO__" => "findCompanyLogo",
    "__SERVICE_NAME__" => "findServiceName",
    "__DELIVERY_POSTCODE__" => "findDeliveryPostcode",
    "__DELIVERY_COUNTRY__" => "findDeliveryCountry",
    "__COLLECTION_POSTCODE__" => "findCollectionPostcode",
    "__COLLECTION_COUNTRY__" => "findCollectionCountry",
    "__COMPANY_URL__" => "findCompanyUrl",
    "__PARCEL_BARCODE__" => "findParcelBarcode",
    "__SHIPMENT_INSTRUCTION__" => "findShipmentInstruction",
    "__SHIPMENT_CUSTOMER_NAME__" => "findShipmentCustomerName",
    "__SHIPMENT_CUSTOMER_PHONE_NO__" => "findShipmentCustomerPhoneNo",
    "__SHIPMENT_CUSTOMER_MOBILE_NO__" => "findShipmentCustomerMobileNo",
    "__PARCEL_REFERENCE__" => "findParcelReference"
  );

  public function __construct(){
    $this->modelObj = new Model_Label();
  }

  protected function getParcelTicketByLoadIdentity($load_identity){
    $items = array();
    $records = $this->modelObj->getParcelTicketByLoadIdentity($load_identity);
    foreach($records as $record){
      array_push($items, array(
        "shipment_ticket" => $record["shipment_ticket"],
        "parcel_ticket" => $record["parcel_ticket"],
        "parcel_weight" => $record["parcel_weight"],
        "load_identity" => $record["instaDispatch_loadIdentity"],
        "company_id" => $record["company_id"]
      ));
    }
    return $items;
  }

  protected function findConsigneeAddress($param){
    $record = $this->modelObj->getConsigneeAddress($param["shipment_ticket"]);
    return $record;
  }

  protected function findWeight($param){
    $record = $this->modelObj->getParcelWeight($param["parcel_ticket"]);
    return $record;
  }

  protected function findPieceCounter($param){
    return $param;
  }

  protected function findTotalPiece($param){
    return $param;
  }

  protected function findCustomerReference($param){
    $record = $this->modelObj->getCustomerReference($param["load_identity"]);
    return $record;
  }

  protected function findAlternateReference($param){
    $record = $this->modelObj->getAlternateReference($param["load_identity"]);
    return $record;
  }

  protected function findServiceDate($param){
    $record = $this->modelObj->getServiceDate($param["shipment_ticket"]);
    return $record;
  }

  protected function findCompanyLogo($param){
    $record = $this->modelObj->getCompanyLogo($param["company_id"]);
    return $record;
  }

  protected function findServiceName($param){
    $record = $this->modelObj->getServiceName($param["load_identity"]);
    return $record;
  }

  protected function findDeliveryPostcode($param){
    $record = $this->modelObj->getDeliveryPostcode($param["load_identity"]);
    return $record;
  }

  protected function findDeliveryCountry($param){
    $record = $this->modelObj->getDeliveryCountry($param["load_identity"]);
    return $record;
  }

  protected function findCollectionPostcode($param){
    $record = $this->modelObj->getCollectionPostcode($param["load_identity"]);
    return $record;
  }

  protected function findCollectionCountry($param){
    $record = $this->modelObj->getCollectionCountry($param["load_identity"]);
    return $record;
  }

  protected function findCompanyUrl($param){
    $record = $this->modelObj->getCompanyUrl($param["company_id"]);
    return $record;
  }

  protected function findParcelBarcode($param){
    $record = $this->modelObj->getParcelReference($param["parcel_ticket"]);
    return $record;
    //$record = $this->modelObj->getBarcodeNo($param["load_identity"]);
    //return $record;
  }

  protected function findShipmentInstruction($param){
    $record = $this->modelObj->getShipmentInstruction($param["shipment_ticket"]);
    return $record;

  }

  protected function findShipmentCustomerName($param){
    $record = $this->modelObj->getShipmentCustomerName($param["shipment_ticket"]);
    return $record;

  }

  protected function findShipmentCustomerPhoneNo($param){
    $record = $this->modelObj->getShipmentContactPhoneNo($param["shipment_ticket"]);
    return $record;
  }

  protected function findShipmentCustomerMobileNo($param){
    $record = $this->modelObj->getShipmentContactMobileNo($param["shipment_ticket"]);
    return $record;
  }

  protected function findParcelReference($param){
    $record = $this->modelObj->getParcelReference($param["parcel_ticket"]);
    return $record;
  }
}
?>
