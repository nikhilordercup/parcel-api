<?php
class Design1_Data_Format{
  public $formatData = array(
    "__CONSIGNEE_ADDRESS__" => "formateConsigneeAddress",
    "__WEIGHT__" => "formateWeight",
    "__PIECE_COUNTER__" => "formatePieceCounter",
    "__TOTAL_PIECE__" => "formateTotalPiece",
    "__CUSTOMER_REFERENCE__" => "formateCustomerReference",
    "__ALETRNATE_REFERENCE__" => "formateAlternateReference",
    "__SERVICE_DATE__" => "formateServiceDate",
    "__COMPANY_LOGO__" => "formateCompanyLogo",
    "__SERVICE_NAME__" => "formateServiceName",
    "__DELIVERY_POSTCODE__" => "formateDeliveryPostcode",
    "__DELIVERY_COUNTRY__" => "formateDeliveryCountry",
    "__COLLECTION_POSTCODE__" => "formateCollectionPostcode",
    "__COLLECTION_COUNTRY__" => "formateCollectionCountry",
    "__COMPANY_URL__" => "formateCompanyUrl",
    "__PARCEL_BARCODE__" => "formateParcelBarcode",
    "__SHIPMENT_INSTRUCTION__" => "formateShipmentInstruction",
    "__SHIPMENT_CUSTOMER_NAME__" => "formateShipmentCustomerName",
    "__SHIPMENT_CUSTOMER_PHONE_NO__" => "formateShipmentCustomerPhoneNo",
    "__SHIPMENT_CUSTOMER_MOBILE_NO__" => "formateShipmentCustomerMobileNo",
    "__PARCEL_REFERENCE__" => "formateParcelReference"
  );

  /*Format data*/
  public function formateConsigneeAddress($param){
    return "<table>
      <tr>
        <td><p style=\"font-weight:bold; font-size:28pt;\">" . $param["shipment_customer_name"] . "</p></td>
      </tr>
      <tr>
        <td><p style=\"font-size:22pt;letter-spacing:1px;\">" . $param["shipment_address1"] . "</p></td>
      </tr>
      <tr>
        <td><p style=\"font-size:22pt;letter-spacing:1px;\">" . $param["shipment_address2"] . "</p></td>
      </tr>
      <tr>
        <td><p style=\"font-weight:bold; font-size:22pt;letter-spacing:3px;\">" . $param["shipment_customer_city"] . "</p></td>
      </tr>
      <tr>
        <td><p style=\"font-weight:bold; font-size:22pt;letter-spacing:3px;\">" . $param["shipment_postcode"] . "</p></td>
      </tr>
      <tr>
        <td><p style=\"font-weight:bold; font-size:22pt;letter-spacing:3px;\">" . $param["shipment_customer_country"] . "</p></td>
      </tr>
    </table>";
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

  public function formateAlternateReference($param){
    return $param["alternate_reference"];
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

  public function formateDeliveryPostcode($param){
    return $param["shipment_postcode"];
  }

  public function formateDeliveryCountry($param){
    return $param["shipment_country"];
  }

  public function formateCollectionPostcode($param){
    return $param["shipment_postcode"];
  }

  public function formateCollectionCountry($param){
    return $param["shipment_country"];
  }

  public function formateCompanyUrl($param){
    return $param["url"];
  }

  public function formateParcelBarcode($param){
    preg_match('!\d+!', $param["parcel_reference"], $matches);
    $barcodeNo = str_pad($matches[0], 12, "0", STR_PAD_LEFT);
    return $barcodeNo;
    //return $param["tracking_number"];
  }

  public function formateShipmentInstruction($param){
    return $param["shipment_instruction"];
  }

  public function formateShipmentCustomerName($param){
    return $param["customer_name"];
  }

  public function formateShipmentCustomerMobileNo($param){
    return $param["mobile_no"];
  }

  public function formateShipmentCustomerPhoneNo($param){
    return $param["contact_phone"];
  }

  public function formateParcelReference($param){
    return $param["parcel_reference"];
  }

}
?>
