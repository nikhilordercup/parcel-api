<?php
class Google_Model_Api{
    public static $_dbObj = null;
    private $_db = null;

    public function __construct(){
        if(self::$_dbObj===null){
            self::$_dbObj = new DbHandler();
        }
        $this->_db = self::$_dbObj;
    }

    public function findGoogleConfByCustomerId($customer_id){
        $sql = "SELECT `driving_mode`, `round_trip` FROM " . DB_PREFIX . "customer_info AS CIT WHERE CIT.user_id='$customer_id'";
        return $this->_db->getRowRecord($sql);
    }

    public function findGoogleConfByCompanyId($company_id){
        $sql = "SELECT `configuration_json` FROM " . DB_PREFIX . "configuration AS CT WHERE CT.company_id='$company_id'";
        return $this->_db->getRowRecord($sql);
    }
}
