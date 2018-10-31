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
    {//print_r($search_str);die;
		$search_str_modified = strtolower(str_replace(" ","",$search_str));
        $sql = "SELECT * FROM `" . DB_PREFIX ."address_book` where (`search_string` LIKE '%$search_str%' OR `search_string` LIKE '%$search_str_modified%') AND `customer_id` = '$customer_id' AND `status` = 1";
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

	public

    function searchAllDefaultWarehouseAddress($customer_id, $search_string){
        $search_str_modified = strtolower(str_replace(" ","",$search_string));
        $sql = "SELECT * FROM `" . DB_PREFIX ."address_book` AS ABT INNER JOIN " . DB_PREFIX . "user_address AS UAT ON UAT.address_id=ABT.id WHERE (`ABT`.`search_string` LIKE '%$search_string%' OR `ABT`.`search_string` LIKE '%$search_str_modified%') AND `UAT`.`user_id` = '$customer_id' AND `UAT`.`warehouse_address`='Y' AND ABT.status = 1";
        return $this->_db->getAllRecords($sql);
    }

	public function checkAddressByAddressString($customer_id, $search_string){
		$search_str_modified = strtolower(str_replace(" ","",$search_string));
		$sql = "SELECT COUNT(1) as exist FROM `" . DB_PREFIX ."address_book` AS ABT WHERE (`ABT`.`search_string` LIKE '%$search_string%' OR `ABT`.`search_string` LIKE '%$search_str_modified%') AND `ABT`.`customer_id` = '$customer_id'";
        return $this->_db->getRowRecord($sql);

	}

	public function searchAddressByAddressStringAndAddressId($address_id, $search_string){
         $sql = "SELECT * FROM " . DB_PREFIX . "address_book AS ABT WHERE search_string LIKE '$search_string' AND id ='$address_id'";
         return $this->_db->getRowRecord($sql);
    }

	public function getAllAddressesFromAddressBook(){
         $sql = "SELECT * FROM " . DB_PREFIX . "address_book AS ABT";
         return $this->_db->getAllRecords($sql);
    }

	public function updateAddressById($id,$search_string){
	     $data = array("search_string"=>$search_string);
         return $this->_db->update("address_book",$data,"id = $id");
    }
}
?>
