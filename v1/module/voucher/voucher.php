<?php
class Voucher extends Icargo{
    public $modelObj = null;
    
   public function __construct($param){
        parent::__construct(array("email"=>$param->email,"access_token"=>$param->access_token));
        $this->modelObj  = Voucher_Model::getInstanse();
    }
    
   public function getallvoucher($param){
         $voucherData = $this->modelObj->getAllInvice($param->warehouse_id,$param->company_id);
         return $voucherData;
   }
}
?>