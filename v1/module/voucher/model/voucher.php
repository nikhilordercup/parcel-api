<?php
class Voucher_Model
    {
    public static $_modelObj = NULL;
    public static $_db = NULL;

    public

    function __construct(){
        if (self::$_db == NULL){
            self::$_db = new DbHandler();
            }
        $this->db = self::$_db;
        }

    public static

    function getInstanse()
        {
        if (self::$_modelObj == NULL)
            {
            self::$_modelObj = new Voucher_Model();
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
        return $this->db->query($sql);
        }

    public

    function getAffectedRows()
        {
        return $this->db->getAffectedRows();
        }

   
    public function getAllInvice($whareHouseId,$componyId){
        $record = array();
        $sqldata = 'V.voucher_type,
                    V.total as amount,
                    V.shipment_reference,
                    V.create_date,
                    V.is_invoiced,
                    V.voucher_reference as voucher_reference,
                    V.is_Paid as status,
                    CI.accountnumber as shipment_customer_account,
                    CI.billing_full_name as shipment_customer_name';
         $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "vouchers AS V
                    LEFT JOIN " . DB_PREFIX . "customer_info AS CI ON CI.user_id = V.customer_id
                    WHERE V.company_id  = '" . $componyId . "'";
        $record = $this->db->getAllRecords($sql);
        return $record;
    }
  }
?>