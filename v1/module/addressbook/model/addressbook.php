<?php

class Addressbook_Model extends Icargo{
	
    public
    
    static $modelObj = NULL;
    
    public
    
    static $_dbObj = NULL;
    
    public
    
    function __construct()
    {
        if(self::$_dbObj==NULL)
        {
            self::$_dbObj = new DbHandler();
        }
        $this->_db = self::$_dbObj;
    }
    
    public
    
    static function _getInstance()
    {
        if(self::$modelObj==NULL)
        {
            self::$modelObj = new Addressbook_Model();
        }
        return self::$modelObj;
    }
	
    private
    
    function _searchAddress($search_str, $customer_id)
    {
		$search_str = strtolower(str_replace(" ","",$search_str));
        $sql = "SELECT * FROM `" . DB_PREFIX ."address_book` where `search_string` LIKE '%$search_str%' AND `customer_id` = '$customer_id'";
        return $this->_db->getAllRecords($sql);
        
    }
    
	public
    
    function searchAllAddress($param)
    {
        return $this->_searchAddress($param['postcode'], $param['customer_id']);
    }
	
	public function searchAddressByAddressId($param) 
	{
		$sql = "SELECT * FROM `" . DB_PREFIX ."address_book` where `id` = ".$param['address_id']."";
		return $this->_db->getRowRecord($sql);

	}
}
?>
