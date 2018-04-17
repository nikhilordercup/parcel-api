<?php
/**
 * Created by PhpStorm.
 * User: nishant
 * Date: 12/04/18
 * Time: 3:17 PM
 */

class Package_Model_Index
{
    private static $db = NULL;

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

    function startTransaction(){
        $this->_db->startTransaction();
    }

    public

    function commitTransaction(){
        $this->_db->commitTransaction();
    }

    public

    function rollBackTransaction(){
        $this->_db->rollBackTransaction();
    }

    public

    function savePackage($data){
        $id = $this->_db->save('package_type', $data);
        return $id;
    }

    public

    function updatePackage($data, $condition){
        $status = $this->_db->update('package_type', $data, $condition);
        return $status;
    }

    public

    function getAllPackagesByCreatedUserId($created_by){
        return $this->_db->getAllRecords("SELECT * FROM " . DB_PREFIX ."package_type WHERE created_by='$created_by'");
    }

    public

    function getAllCustomerAndUserByCompanyId($company_id){
        return $this->_db->getAllRecords("SELECT T1.id AS user_id FROM " . DB_PREFIX ."users AS T1 INNER JOIN " . DB_PREFIX ."company_users AS T2 ON T2.user_id=T1.id WHERE T2.company_id='$company_id' AND T1.user_level IN (5,6)");
    }

    public

    function saveAllowedUserPackage($data){
        $id = $this->_db->save('package_type_allowed_users', $data);
        return $id;
    }
}