<?php
require_once "Label.php";

final class Design1_Label extends Label{
  public $html = '<table>
    <tr>
      <td>__BARCODE__</td>
    </tr>

    <tr>
      <td>__CONSIGNEE_ADDRESS__</td>

      <td>
        <table>
          <tr>
            <td>Reference :</td>
            <td>__REFERENCE_NO__</td>
          </tr>

          <tr>
            <td>Weight :</td>
            <td>__WEIGHT__</td>
          </tr>

          <tr>
            <td>Piece :</td>
            <td>__PIECE_COUNTER__ of __TOTAL_PIECE__</td>
          </tr>

          <tr>
            <td>Cust Ref :</td>
            <td>__CUSTOMER_REFERENCE__</td>
          </tr>

          <tr>
            <td>Alt Ref :</td>
            <td>__ALETRNATE_REFERENCE__</td>
          </tr>

          <tr>
            <td colspan="2">__SERVICE_DATE__</td>
          </tr>

        </table>
      </td>
    </tr>

    <tr>
      <td>Left 2 Column</td>
      <td>
        <table>
          <tr>
            <td><img src="__COMPANY_LOGO__"></td>
          </tr>
          <tr>
            <td>__SERVICE_NAME__</td>
          </tr>
          <tr>
            <td>__SENDER_POSTCODE__ -  __SENDER_COUNTRY__</td>
          </tr>
          <tr>
            <td>__CONSIGNEE_POSTCODE__ -  __CONSIGNEE_COUNTRY__</td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td>__COMPANY_URL__</td>
    </tr>
  </table>';
  public function __construct(){
    $this->labelObj = new Label();
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
        array_push($replaceData, call_user_func_array(array($this->labelObj, $this->labelObj->formatData[$m]), array($data)));
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
