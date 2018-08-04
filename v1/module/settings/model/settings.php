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
         $record = $this->db->getAllRecords($sql);
         return  $record;  
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
    
    
    
    
  }
?>