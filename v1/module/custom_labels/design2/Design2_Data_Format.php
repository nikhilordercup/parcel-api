<?php
class Design2_Data_Format{
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

  /*Format data*/
  public function formateConsigneeAddress($param){
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
        <td>" . $param["shipment_customer_country"] . "</td>
      </tr>
      <tr>
        <td>" . $param["shipment_postcode"] . "</td>
      </tr>
    </table>";
  }

  public function formateReferenceNo(){

  }

  public function formateWeight($param){
    $weight = number_format($param["parcel_weight"], 2, '.', ',');
    return "$weight kg";
  }

  public function formatePieceCounter($param){
    return $param["item_count"];
  }

  public function formateTotalPiece($param){
    return $param["total_items"];
  }

  public function formateCustomerReference($param){
    return $param["customer_reference"];
  }

  public function formateAlternateReference(){

  }

  public function formateServiceDate($param){
    return $param["service_date"];
  }

  public function formateCompanyLogo($param){

    $path = Library::_getInstance()->base_url()."/assets/logo";
    $logo = $param["logo"];
    return "$path/$logo";
  }

  public function formateServiceName($param){
    return $param["service_name"];
  }

  public function formateSenderPostcode(){

  }

  public function formateSenderCountry(){

  }

  public function formateConsigneePostcode($param){
    return $param["shipment_postcode"];
  }

  public function formateConsigneeCountry($param){
    return $param["shipment_country"];
  }

  public function formateCompanyUrl($param){
    return $param["url"];
  }

  public function formateBarcode($param){
    return $param["tracking_number"];
  }
}
?>
