<?php
class Settings_Model {
	
	public function __construct(){
		$this->db = new DbHandler();
	}
	public static function getInstanse(){
		return new Settings_Model();
	}
    public function addContent($table_name, $data){ return $this->db->save($table_name, $data);}
	public function editContent($table_name, $data, $condition){return$this->db->update($table_name, $data, $condition);}
	public function deleteContent($sql){return $this->db->query($sql);}
	public function getAffectedRows(){return $this->db->getAffectedRows();}
	
	
   public function getAllInvoiceShipmentStatus(){
     $record = array();
	 $sqldata ='t1.*';
     $sql = "SELECT ".$sqldata." FROM " . DB_PREFIX . "shipments_master AS t1";
	 $record = $this->db->getAllRecords($sql);
	 return $record;   
   }
    
    public function getAllInvoiceStatus(){
         $record = array();
         $sqldata ='t1.*';
         $sql = "SELECT ".$sqldata." FROM " . DB_PREFIX . "invoice_master AS t1";
         $record = $this->db->getAllRecords($sql);
         return  $record;  
	 }
    public function getAllShipmentsStatus(){
         $record = array();
         $sqldata ='t1.*';
         $sql = "SELECT ".$sqldata." FROM " . DB_PREFIX . "shipments_master AS t1";
         $records = $this->db->getAllRecords($sql);
         return  $records;  
	}
    public function getAllCarrier($companyid){
         $record = array();
         $sqldata ='t1.courier_id as id,t2.name';
         $sql = "SELECT ".$sqldata." FROM " . DB_PREFIX . "courier_vs_company AS t1
                LEFT JOIN " . DB_PREFIX . "courier AS t2 on t1.courier_id = t2.id
                WHERE t1.company_id = '$companyid'";
         $record = $this->db->getAllRecords($sql);
         return  $record;  
	}
    public function getAllShipmentTrackingCode(){
         $record = array();
         $sql = "SELECT tracking_id, tracking_code FROM " . DB_PREFIX . "tracking_code AS t1 ORDER BY tracking_code";
         $records = $this->db->getAllRecords($sql);
         return  $records;  
     }
     public function deleteShipmentTrackingCodeByShipmentCode($shipment_code){
        $sql = "DELETE FROM " . DB_PREFIX . "shipment_tracking_code WHERE shipment_code = '$shipment_code'";
        return $this->db->delete($sql);
     }

     public function saveShipmentTrackingCode($code, $tracking_code){
        return $this->db->save("shipment_tracking_code", array(
            "tracking_code"=>$tracking_code,
            "shipment_code"=>$code
        ));
     }

     public function findTrackingCodeByShipmentCode($code){
        $sql = "SELECT tracking_code FROM " . DB_PREFIX . "shipment_tracking_code AS T1 WHERE T1.shipment_code='$code'";
        $records = $this->db->getAllRecords($sql);
        return  $records;  
     }
}
?>