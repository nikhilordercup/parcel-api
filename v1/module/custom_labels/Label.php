<?php
require_once "model/Model_Label.php";

class Label{

  public $methodArray = array(
    "__CONSIGNEE_ADDRESS__" => "findConsigneeAddress",
    "__REFERENCE_NO__" => "findReferenceNo",
    "__WEIGHT__" => "findWeight",
    "__PIECE_COUNTER__" => "findPieceCounter",
    "__TOTAL_PIECE__" => "findTotalPiece",
    "__CUSTOMER_REFERENCE__" => "findCustomerReference",
    "__ALETRNATE_REFERENCE__" => "findAlternateReference",
    "__SERVICE_DATE__" => "findServiceDate",
    "__COMPANY_LOGO__" => "findCompanyLogo",
    "__SERVICE_NAME__" => "findServiceName",
    "__SENDER_POSTCODE__" => "findSenderPostcode",
    "__SENDER_COUNTRY__" => "findSenderCountry",
    "__CONSIGNEE_POSTCODE__" => "findConsigneePostcode",
    "__CONSIGNEE_COUNTRY__" => "findConsigneeCountry",
    "__COMPANY_URL__" => "findCompanyUrl",
    "__BARCODE__" => "findBarcode"
  );

  public $formatData = array(
    "__CONSIGNEE_ADDRESS__" => "formateConsigneeAddress",
    "__REFERENCE_NO__" => "formateReferenceNo",
    "__WEIGHT__" => "formateWeight",
    "__PIECE_COUNTER__" => "formatePieceCounter",
    "__TOTAL_PIECE__" => "formateTotalPiece",
    "__CUSTOMER_REFERENCE__" => "formateCustomerReference",
    "__ALETRNATE_REFERENCE__" => "formateAlternateReference",
    "__SERVICE_DATE__" => "formateServiceDate",
    "__COMPANY_LOGO__" => "formateCompanyLogo",
    "__SERVICE_NAME__" => "formateServiceName",
    "__SENDER_POSTCODE__" => "formateSenderPostcode",
    "__SENDER_COUNTRY__" => "formateSenderCountry",
    "__CONSIGNEE_POSTCODE__" => "formateConsigneePostcode",
    "__CONSIGNEE_COUNTRY__" => "formateConsigneeCountry",
    "__COMPANY_URL__" => "formateCompanyUrl",
    "__BARCODE__" => "formateBarcode"
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

  protected function findReferenceNo(){

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

  protected function findCustomerReference(){
    return array("customer_reference"=>"customer_reference nahi pta hai");
  }

  protected function findAlternateReference(){

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

  protected function findSenderPostcode(){

  }

  protected function findSenderCountry(){

  }

  protected function findConsigneePostcode(){

  }

  protected function findConsigneeCountry(){

  }

  protected function findCompanyUrl(){

  }

  protected function findBarcode($load_identity){

  }

  /*Format data*/
  protected function formateConsigneeAddress($param){
    return "<table>
      <tr>
        <td>" . $param["shipment_customer_name"] . "</td>
      </tr>
      <tr>
        <td>" . $param["shipment_address1"] . "</td>
      </tr>
      <tr>
        <td>" . $param["shipment_address2"] . "</td>
      </tr>
      <tr>
        <td>" . $param["shipment_customer_city"] . "</td>
      </tr>
      <tr>
        <td>" . $param["shipment_postcode"] . "</td>
      </tr>
      <tr>
        <td>" . $param["shipment_customer_country"] . "</td>
      </tr>
    </table>";
  }

  protected function formateReferenceNo(){

  }

  protected function formateWeight($param){
    $weight = number_format($param["parcel_weight"], 2, '.', ',');
    return "$weight kg";
  }

  protected function formatePieceCounter($param){
    return $param["item_count"];
  }

  protected function formateTotalPiece($param){
    return $param["total_items"];
  }

  protected function formateCustomerReference($param){
    return $param["customer_reference"];
  }

  protected function formateAlternateReference(){

  }

  protected function formateServiceDate($param){
    return $param["service_date"];
  }

  protected function formateCompanyLogo($param){
    $path = "../assets/logo";
    $logo = $param["logo"];
    return "$path/$logo";
  }

  protected function formateServiceName($param){
    return $param["service_name"];
  }

  protected function formateSenderPostcode(){

  }

  protected function formateSenderCountry(){

  }

  protected function formateConsigneePostcode(){

  }

  protected function formateConsigneeCountry(){

  }

  protected function formateCompanyUrl(){

  }

  protected function formateBarcode($load_identity){

  }

}
?>
