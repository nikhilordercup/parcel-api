<?php

class Reconciled_Model { 

    public static $_modelObj = NULL;
    public static $_db = NULL;

    public
     function __construct(){
        if (self::$_db == NULL){
            self::$_db = new DbHandler();
            }
        $this->db = self::$_db;
        }

    public

    static function _getInstance()
    {
        if(self::$_modelObj==NULL)
        {
            self::$_modelObj = new Reconciled_Model();
        }
        return self::$_modelObj;
    }
    
    public

    function addContent($table_name, $data)
        {
        return $this->db->save($table_name, $data);
        }

    public

    function editContent($table_name, $data, $condition)
        {
        return $this->db->update($table_name, $data, $condition);
        }

    public

    function deleteContent($sql)
        {
        return $this->db->delete($sql);
        }

    public

    function getAffectedRows()
        {
        return $this->db->getAffectedRows();
        }
    
     public function getFualSurchargeofUKMAIL($identity){
         $record = array();
         $sqldata = 'B.fual_surcharge';
         $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment_service AS S
                 LEFT JOIN " . DB_PREFIX . "courier_vs_company as B on B.id = S.carrier
                 LEFT JOIN " . DB_PREFIX . "courier as C on C.id = B.courier_id AND C.code = 'UKMAIL'
                 WHERE S.label_tracking_number = '" . $identity . "'";
        $record = $this->db->getRowRecord($sql);
        return $record['fual_surcharge'];
      } 
    
     public function getAllReconciled($componyId){
        $record = array();
        $sqldata = 'C.name,S.*';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "reconciled_reports as S
                INNER JOIN " . DB_PREFIX . "courier as C on C.id = S.carrier
                WHERE S.company_id  = '" . $componyId . "' order by id DESC ";
        $record = $this->db->getAllRecords($sql);
        return $record;
    }
    public function getCompanyReconciledBuffer($company_id){
      $record = $this->db->getRowRecord("SELECT reconciled_buffer_amt FROM " . DB_PREFIX . "configuration WHERE company_id = ".$company_id);
      return $record['reconciled_buffer_amt'];
     }
}
?>
