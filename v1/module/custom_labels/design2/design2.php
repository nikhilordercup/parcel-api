<?php
require_once "../v1/module/custom_labels/Label.php";
require_once "Design2_Data_Format.php";

final class Design2_Label extends Label{
  public $html = '
  <table>
    <tr>
      <td colspan="2" align="center" class="widecells"><p>Courier Tracked</p></td>
    </tr>

    <tr>
      <td colspan="2" align="center">SIGNATURE NEXT DAY</td>
    </tr>

    <tr>
      <td colspan="2" align="center"><barcode code="__BARCODE__" type="RM4SCC" height="0.66" text="1" /></td>
    </tr>

    <tr>
      <td>
        <div style="position: fixed; right: 0mm; bottom: 0mm; rotate: -90;">
          <barcode code="978-0-9542246-0" class="barcode" />
        </div>
      </td>

      <td>
        <table>
          <tr>
            <td>
              FAO :
            </td>
            <td>
              Nhi Pta hai
            </td>
          </tr>

          <tr>
            <td>
              S Ref :
            </td>
            <td>
              Nhi Pta hai
            </td>
          </tr>

          <tr>
            <td>
              Co. :
            </td>
            <td>
              Nhi Pta hai
            </td>
          </tr>

          <tr>
            <td>
              Address :
            </td>
            <td>
              __CONSIGNEE_ADDRESS__
            </td>
          </tr>
        </table>
      <td>
    </tr>
  </table>';
  public function __construct(){
    $this->labelObj = new Label();
    $this->dataFormatObj = new Design2_Data_Format();
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
