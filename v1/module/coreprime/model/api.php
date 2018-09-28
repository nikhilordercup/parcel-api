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

    function getCustomerCarrierData($customerId, $company)
    {
        //$sql = "SELECT C3.courier_id,C1.account_number,C3.token,C3.currency,C2.code,C2.icon FROM " . DB_PREFIX . "courier_vs_company_vs_customer as C1 INNER JOIN " . DB_PREFIX . "courier as C2 on C1.courier_id = C2.id INNER JOIN " . DB_PREFIX . "courier_vs_company as C3 on C1.courier_id = C3.courier_id AND C3.company_id = '$company' WHERE C1.customer_id = '$customerId' AND C3.courier_id = '$courierId' AND C1.status = 1";
        $sql = "SELECT C3.username,C3.password,C1.company_courier_account_id as courier_account_id,C1.courier_id as courier_id,C1.account_number,C3.token,C3.currency,C2.code,C2.icon,C2.is_self
        FROM " . DB_PREFIX . "courier_vs_company_vs_customer as C1 
        INNER JOIN " . DB_PREFIX . "courier as C2 on C1.courier_id = C2.id 
        INNER JOIN " . DB_PREFIX . "courier_vs_company as C3 on C1.company_courier_account_id = C3.id AND C3.company_id = '$company' 
        WHERE C1.customer_id = '$customerId' AND C2.is_apiused = 'YES' AND C1.status = 1";
        return $this->_db->getAllRecords($sql);
    }
    function isServiceAvailableforCustomer($service_code,$customer_id,$company_id,$courier_id)
    {
        $sql = "SELECT CUSTSER.status AS status,CSER.status AS courierstatus FROM " . DB_PREFIX . "company_vs_customer_vs_services  AS CUSTSER
                LEFT JOIN " . DB_PREFIX . "courier_vs_services AS CSER ON CSER.id = CUSTSER.service_id
                where CUSTSER.company_id = '$company_id'  AND CUSTSER.company_customer_id = '$customer_id' AND CUSTSER.courier_id = '$courier_id'
                AND CSER.service_code = '$service_code'";
        return $this->_db->getRowRecord($sql);
    }
    function getCustomerSamedayServiceData($customer_id,$company_id,$courier_id)
    {
        $sql = "SELECT CSER.service_code 
                FROM " . DB_PREFIX . "company_vs_customer_vs_services  AS CUSTSER
                LEFT JOIN " . DB_PREFIX . "courier_vs_services AS CSER ON CSER.id = CUSTSER.service_id
                where CUSTSER.company_id = '$company_id'  AND CUSTSER.company_customer_id = '$customer_id' AND CUSTSER.courier_id = '$courier_id'
                AND CSER.service_type = 'SAMEDAY'
                AND CUSTSER.status = '1'
                AND CSER.status = '1'";
        return $this->_db->getAllRecords($sql);
    }
     public
    function getCarrierIdByCode($companyId,$customerid,$account)
    {  
        //$sql = "SELECT id FROM " . DB_PREFIX . "courier WHERE code = '$code'";
        $sql = "SELECT company_courier_account_id as id FROM " . DB_PREFIX . "courier_vs_company_vs_customer WHERE account_number = '$account' AND customer_id = '$customerid' AND company_id = '$companyId' ";
        return $this->_db->getRowRecord($sql);
    }
 public function getCustomerChargeFromBase($customerId)
    {
        $sql = "SELECT charge_from_base FROM " . DB_PREFIX . "customer_info WHERE user_id = '$customerId'";
        return $this->_db->getRowRecord($sql);
   
    }
    public function getCustomerAccountBalence($customer_id){
        $sql = "SELECT available_credit FROM " . DB_PREFIX . "customer_info WHERE user_id = '$customer_id'";
        return $this->_db->getRowRecord($sql);
    }
    public function getTaxExemptStatus($customerId){
        $sql = "SELECT tax_exempt FROM " . DB_PREFIX . "customer_info WHERE user_id = '$customerId'";
        return $this->_db->getRowRecord($sql);
   
    }   
    public function getCustomerCarrierDataByServiceId($customerId,$serviceId, $company,$carrierId){ 
        $subquery = ($carrierId>0)?"CCST.courier_id = '$carrierId'":"1 = 1";
        $sql = "SELECT C3.username,C3.password,CCST.courier_id,C3.account_number,C3.token,C3.currency,C2.code,C2.icon 
                  FROM `" . DB_PREFIX . "company_vs_customer_vs_services` AS CCST 
                  INNER JOIN " . DB_PREFIX . "courier_vs_services_vs_company AS C1 ON C1.id = CCST.company_service_id
				  INNER JOIN  " . DB_PREFIX . "courier_vs_company as C3 on CCST.courier_id = C3.id 
                  INNER JOIN  " . DB_PREFIX . "courier as C2 on C3.courier_id = C2.id 
                  WHERE CCST.company_customer_id = '$customerId'
                  AND CCST.company_id = '$company' AND CCST.service_id = '$serviceId' AND $subquery AND CCST.status = 1 AND C1.status = 1";
        $data =  $this->_db->getAllRecords($sql);
        return $data;
    }
    public function getCustomerSamedayServiceDataFromServiceId($customer_id,$company_id,$courier_id,$service_id){
        $sql = "SELECT CSER.service_code 
                FROM " . DB_PREFIX . "company_vs_customer_vs_services  AS CUSTSER
                LEFT JOIN " . DB_PREFIX . "courier_vs_services AS CSER ON CSER.id = CUSTSER.service_id
                where CUSTSER.company_id = '$company_id'  AND CUSTSER.company_customer_id = '$customer_id' AND CUSTSER.courier_id = '$courier_id'
                AND CUSTSER.service_id = '$service_id'
                AND CSER.service_type = 'SAMEDAY'
                AND CUSTSER.status = '1'
                AND CSER.status = '1'";
        return $this->_db->getAllRecords($sql);
    }   
}
