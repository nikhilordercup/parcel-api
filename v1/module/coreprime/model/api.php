<?php
class Coreprime_Model_Api
{
    public static $db = NULL;
    
    public
    
    function __construct()
    {
        if(self::$db==NULL)
        {
            self::$db  = new DbHandler();
        }
        $this->_db = self::$db;
    }
    
    public
    
    function getCustomerCode($id){
        $sql = "SELECT code FROM " . DB_PREFIX . "user_code WHERE id = '$id'";
        return $this->_db->getRowRecord($sql);
    }

    public

    function getCustomerCcfByCustomerId($id){
        $sql = "SELECT ccf FROM " . DB_PREFIX . "customer_info WHERE user_id = '$id' AND apply_ccf=1";
        return $this->_db->getRowRecord($sql);
    }
}