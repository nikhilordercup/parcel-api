<?php

class Coreprime_Model_Api
{
    public static $db = NULL;

    public

    function __construct()
    {
        if (self::$db == NULL) {
            self::$db = new DbHandler();
        }
        $this->_db = self::$db;
    }

    public

    function getCustomerCode($id)
    {
        $sql = "SELECT code FROM " . DB_PREFIX . "user_code WHERE id = '$id'";
        return $this->_db->getRowRecord($sql);
    }

    public

    function getCustomerCcfByCustomerId($id)
    {
        $sql = "SELECT ccf FROM " . DB_PREFIX . "customer_info WHERE user_id = '$id' AND apply_ccf=1";
        return $this->_db->getRowRecord($sql);
    }

    public

    function getCustomerCarrierData($customerId, $company,$courierId)
    {
        $sql = "SELECT C3.courier_id,C1.account_number,C3.token,C3.currency,C2.code,C2.icon FROM " . DB_PREFIX . "courier_vs_company_vs_customer as C1 INNER JOIN " . DB_PREFIX . "courier as C2 on C1.courier_id = C2.id INNER JOIN " . DB_PREFIX . "courier_vs_company as C3 on C1.courier_id = C3.courier_id AND C3.company_id = '$company' WHERE C1.customer_id = '$customerId' AND C3.courier_id = '$courierId' AND C1.status = 1";
        return $this->_db->getRowRecord($sql);
    }
}