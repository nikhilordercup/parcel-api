<?php
require_once "../v1/module/custom_labels/Label.php";
require_once "Design1_Data_Format.php";

final class Design1_Label extends Label{
  public $html = '<table align="center">
    <tr>
      <td colspan="2" align="center">
         <barcode size="3" height="0.5" code="__PARCEL_BARCODE__" class="barcode" style="margin-bottom:50pt"></barcode>
      </td>
    </tr>
    <tr>
      <td><p>__CONSIGNEE_ADDRESS__</p></td>
      <td>
        <table class="invoice-booking">
          <tbody>
            <tr>
              <td><p style="font-weight:bold; font-size:20pt;">Reference :</p></td>
              <td><p style="font-weight:bold; font-size:20pt;letter-spacing:3px;">__PARCEL_REFERENCE__</p></td>
            </tr>
            <tr>
              <td><p style="font-size:14pt;letter-spacing:1px;">Weight :</p></td>
              <td><p style="font-size:14pt;letter-spacing:1px;">__WEIGHT__</p></td>
            </tr>
            <tr>
              <td><p style="font-size:14pt;letter-spacing:1px;">Piece :</p></td>
              <td><p style="font-size:14pt;letter-spacing:1px;">__PIECE_COUNTER__ of __TOTAL_PIECE__</p></td>
            </tr>
            <tr>
              <td><p style="font-size:14pt;letter-spacing:1px;">Cust Ref :</p></td>
              <td><p style="font-size:14pt;letter-spacing:1px;">__CUSTOMER_REFERENCE__</p></td>
            </tr>
            <tr>
              <td><p style="font-size:14pt;letter-spacing:1px;">Alt Ref :</p></td>
              <td><p style="font-size:14pt;letter-spacing:1px;">__ALETRNATE_REFERENCE__</p></td>
            </tr>
            <tr>
              <td colspan="2"><p style="font-size:30pt;">__SERVICE_DATE__</p></td>
            </tr>
          </tbody>
        </table>
      </td>
    </tr>

    <tr>
      <td>
        <table style="margin-top:30pt">
          <tr>
            <td><p style="font-size:14pt;letter-spacing:1px;">__SHIPMENT_CUSTOMER_NAME__</p></td>
          </tr>
          <tr>
            <td><p style="font-size:14pt;letter-spacing:1px;">__SHIPMENT_CUSTOMER_MOBILE_NO__</p></td>
          </tr>
          <tr>
            <td><p style="font-size:14pt;letter-spacing:1px;">__SHIPMENT_INSTRUCTION__</p></td>
          </tr>
        </table>
      </td>
      <td>
        <table>
          <tr>
            <td><img src="__COMPANY_LOGO__" height="100px" width="140px"></td>
          </tr>
          <tr>
            <td><p style="font-size:14pt;letter-spacing:1px;">__SERVICE_NAME__</p></td>
          </tr>
          <tr>
            <td><p style="font-size:14pt;letter-spacing:1px;">__DELIVERY_POSTCODE__ -  __DELIVERY_COUNTRY__</p></td>
          </tr>
          <tr>
            <td><p style="font-size:14pt;letter-spacing:1px;">__COLLECTION_POSTCODE__ -  __COLLECTION_COUNTRY__</p></td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td><p style="font-weight:bold; font-size:25pt;letter-spacing:1px;">__COMPANY_URL__</p></td>
    </tr>
  </table>';
  public function __construct(){
    $this->labelObj = new Label();

    $this->dataFormatObj = new Design1_Data_Format();
  }

  private function _createLabelByParcelTicket($item){
    $replaceData = array();
    foreach($this->labelVariables as $m){
      $data = call_user_func_array(array($this->labelObj, $this->labelObj->methodArray[$m]), array($item));

      if(!is_array($data)){
        array_push($replaceData, '');
      }else{
        $data["total_items"] = $item["total_items"];
        $data["item_count"] = $item["item_count"];

        array_push($replaceData, call_user_func_array(array($this->dataFormatObj, $this->dataFormatObj->formatData[$m]), array($data)));
      }
    }
    return str_replace($this->labelVariables, $replaceData, $this->html);
  }

  private function _generateLable(){
    $items = $this->labelObj->getParcelTicketByLoadIdentity($this->load_identity);
    $totalItems = count($items);
    $labels = array();
    $counter = 1;
    foreach($items as $key => $item){
      $item["total_items"] = $totalItems;
      $item["item_count"] = $counter;
      array_push($labels, $this->_createLabelByParcelTicket($item));
      $counter++;
    }
    return $labels;
  }

  public function createLable($load_identity){
    $this->load_identity = $load_identity;

    preg_match_all('/__(.*?)__/',$this->html, $match);
    $this->labelVariables = $match[0];
    return $this->_generateLable();
  }
}
?>
