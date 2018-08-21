<?php

class Carrier_Model_Carrier
{

    public static $_db = NULL;
    public static $_modelObj = NULL;


    public static function _getInstance()
    {
        if (self::$_modelObj == NULL) {
            self::$_modelObj = new Carrier_Model_Carrier();
        }
        if (self::$_db == NULL) {
            self::$_db = new DbHandler();
        }

        return self::$_modelObj;
    }

    public function getCustomerCcfService__($customer_id, $service_code)
    {
        $sql = "SELECT CCST.customer_ccf AS ccf, CCST.ccf_operator AS operator, SCT.service_name AS service_name, SCT.service_code AS service_code FROM " . DB_PREFIX . "company_vs_customer_vs_services CCST INNER JOIN " . DB_PREFIX . "courier_vs_services AS SCT ON SCT.id = CCST.service_id  WHERE CCST.status=1 AND CCST.company_customer_id=$customer_id AND CCST.service_id='$service_code' AND CCST.customer_ccf > 0";
        return $this->_getDbInstance()->getRowRecord($sql);
    }

    public static function _getDbInstance()
    {
        return self::$_db;
    }

    public function getCustomerCcfSurcharge__($customer_id, $surcharge_code)
    {
        $sql = "SELECT CCST.customer_surcharge AS ccf, CCST.ccf_operator AS operator, CSCT.company_surcharge_code AS surcharge_code, CSCT.company_surcharge_name AS surcharge_name, CCCT.customer_surcharge_value as carrier_ccf, CCCT.company_ccf_operator_surcharge AS carrier_operator  FROM " . DB_PREFIX . "company_vs_customer_vs_surcharge AS CCST LEFT JOIN " . DB_PREFIX . "courier_vs_surcharge_vs_company AS CSCT ON CSCT.surcharge_id=CCST.surcharge_id LEFT JOIN " . DB_PREFIX . "courier_vs_company_vs_customer AS CCCT ON CCCT.courier_id = CCST.courier_id WHERE CCST.status=1 AND CCST.company_customer_id=$customer_id AND CSCT.company_surcharge_code='$surcharge_code'";
        return $this->_getDbInstance()->getRowRecord($sql);
    }

    public function getCustomerAllCourier__($customer_id)
    {
        $sql = "SELECT CT.name AS courier_name, CT.code AS courier_code, CT.icon AS courier_icon, CT.description AS courier_description, CT.id AS courier_id FROM " . DB_PREFIX . "courier_vs_company_vs_customer AS CCT INNER JOIN " . DB_PREFIX . "courier AS CT ON CT.id = CCT.courier_id WHERE CCT.status=1 AND CT.status=1 AND CCT.company_id=$customer_id";
        return $this->_getDbInstance()->getAllRecords($sql);
    }

    public function getCustomerCourierByCourierCode__($customer_id, $company_id, $courier_code)
    {
        $sql = "SELECT CT.name AS courier_name, CT.code AS courier_code, CT.icon AS courier_icon, CT.description AS courier_description, CT.id AS courier_id FROM " . DB_PREFIX . "courier_vs_company_vs_customer AS CCT INNER JOIN " . DB_PREFIX . "courier AS CT ON CT.id = CCT.courier_id WHERE CT.code='$courier_code' AND CCT.status=1 AND CT.status=1 AND CCT.company_id=$company_id AND CCT.customer_id=$customer_id";
        return $this->_getDbInstance()->getRowRecord($sql);
    }

    public function getCustomerInfoSurcharge__($customer_id)
    {
        $sql = "SELECT CT.surcharge AS surcharge_ccf, CT.ccf_operator_surcharge AS surcharge_operator FROM " . DB_PREFIX . "customer_info AS CT WHERE CT.user_id=$customer_id";
        return $this->_getDbInstance()->getRowRecord($sql);
    }

    public function getCourierSurchargeCompany__($company_id, $surcharge_code)
    {
        $sql = "SELECT CSCT.company_surcharge_surcharge AS surcharge_ccf, CSCT.company_ccf_operator AS surcharge_operator, CSCT.company_surcharge_name AS surcharge_name, CSCT.company_surcharge_code as surcharge_code FROM " . DB_PREFIX . "courier_vs_surcharge_vs_company AS CSCT WHERE CSCT.company_surcharge_code='$surcharge_code' AND CSCT.company_id='$company_id'";
        return $this->_getDbInstance()->getRowRecord($sql);
    }

    public function getCompanyCourierSurcharge__($company_id, $courier_code)
    {
        $sql = "SELECT CCT.company_surcharge_value AS surcharge_ccf, CCT.company_ccf_operator_surcharge AS surcharge_operator  FROM " . DB_PREFIX . "courier_vs_company AS CCT INNER JOIN " . DB_PREFIX . "courier AS CT ON CT.id=CCT.courier_id WHERE CSCT.company_surcharge_code='$courier_code' AND company_id='$company_id' ";
        return $this->_getDbInstance()->getRowRecord($sql);
    }

    public function getCcfOfCarrierServices($service_code, $customer_id, $company_id, $courier_id)
    {
        $sql = "
        SELECT  
        COURSER.id as service_id,
        CCST.customer_ccf AS customer_carrier_service_ccf,
        CCST.ccf_operator AS customer_carrier_service_operator,
        CCC.customer_ccf_value AS customer_carrier_ccf,
        CCC.company_ccf_operator_service AS customer_carrier_operator,
        CINFO.ccf AS customer_ccf,
        CINFO.ccf_operator_service AS customer_operator,
        COMSER.company_service_ccf AS company_carrier_service_ccf,
        COMSER.company_ccf_operator AS company_carrier_service_operator,
        COMSER.company_service_code,
        COMSER.company_service_name,
        COMCOUR.company_ccf_value AS company_carrier_ccf,
        COMCOUR.company_ccf_operator_service AS company_carrier_operator,
        COURSER.service_name AS courier_service_name,
        COURSER.service_code AS courier_service_code
        FROM " . DB_PREFIX . "company_vs_customer_vs_services CCST
        INNER JOIN " . DB_PREFIX . "courier_vs_company_vs_customer as CCC on CCC.customer_id = CCST.company_customer_id AND CCC.company_courier_account_id = CCST.courier_id
        INNER JOIN " . DB_PREFIX . "customer_info as CINFO on CINFO.user_id = CCST.company_customer_id
        INNER JOIN " . DB_PREFIX . "courier_vs_services_vs_company as COMSER on (COMSER.service_id = CCST.service_id AND COMSER.courier_id = CCST.courier_id   AND COMSER.company_id =  '$company_id')
        INNER JOIN " . DB_PREFIX . "courier_vs_company as COMCOUR on (COMCOUR.id = CCST.courier_id AND  COMCOUR.company_id =  '$company_id')
        INNER JOIN " . DB_PREFIX . "courier_vs_services as COURSER on (COURSER.id = CCST.service_id)
        WHERE CCST.status = 1  AND CCC.status = 1 
        AND COMSER.status = 1 AND COMCOUR.status = 1 AND COURSER.status = 1
        AND CCST.company_customer_id = '$customer_id'
        AND CCST.company_id = '$company_id'
        AND CCST.courier_id = '$courier_id'
        AND COURSER.service_code = '$service_code'";         
        return $this->_getDbInstance()->getRowRecord($sql);
    }

    public function getCcfOfCarrier($customer_id, $company_id, $courier_id)
    {
        $sql = "
        SELECT 
        CCC.customer_ccf_value AS customer_carrier_ccf,
        CCC.company_ccf_operator_service AS customer_carrier_operator,
        CINFO.ccf AS customer_ccf,
        CINFO.ccf_operator_service AS customer_operator,
        COMCOUR.company_ccf_value AS company_carrier_ccf,
        COMCOUR.company_ccf_operator_service AS company_carrier_operator
        FROM " . DB_PREFIX . "courier_vs_company_vs_customer as CCC 
        INNER JOIN " . DB_PREFIX . "customer_info as CINFO on CINFO.user_id = CCC.customer_id
        INNER JOIN " . DB_PREFIX . "courier_vs_company as COMCOUR on (COMCOUR.id = CCC.company_courier_account_id AND  COMCOUR.company_id =  CCC.company_id )
        WHERE   CCC.status = 1 AND  COMCOUR.status = 1 
        AND CCC.customer_id = '$customer_id'
        AND CCC.company_id = '$company_id'
        AND CCC.company_courier_account_id = '$courier_id'";
        return $this->_getDbInstance()->getRowRecord($sql);
    }

    public function getCcfOfCarrierSurcharge($surchrage_code, $customer_id, $company_id, $courier_id)
    {
       $sql = "
        SELECT  
                COURSER.id as surcharge_id,
                CCST.customer_surcharge AS customer_carrier_surcharge_ccf,
                CCST.ccf_operator AS customer_carrier_surcharge_operator,
                CCC.customer_surcharge_value AS customer_carrier_surcharge,
                CCC.company_ccf_operator_surcharge AS customer_carrier_operator,
                CINFO.surcharge AS customer_surcharge,
                CINFO.ccf_operator_surcharge AS customer_operator,
                COMSER.company_surcharge_surcharge AS company_carrier_surcharge_ccf,
                COMSER.company_ccf_operator AS company_carrier_surcharge_operator,
                COMSER.company_surcharge_code,
                COMSER.company_surcharge_name,
                COMCOUR.company_surcharge_value AS company_carrier_ccf,
                COMCOUR.company_ccf_operator_surcharge AS company_carrier_operator,
                COURSER.surcharge_name AS courier_surcharge_name,
                COURSER.surcharge_code AS courier_surcharge_code,
                COMCOUR.pickup,COMCOUR.pickup_surcharge
                FROM " . DB_PREFIX . "company_vs_customer_vs_surcharge CCST
                INNER JOIN " . DB_PREFIX . "courier_vs_company_vs_customer as CCC on CCC.customer_id = CCST.company_customer_id AND CCC.company_courier_account_id = CCST.courier_id
                INNER JOIN " . DB_PREFIX . "customer_info as CINFO on CINFO.user_id = CCST.company_customer_id
                INNER JOIN " . DB_PREFIX . "courier_vs_surcharge_vs_company as COMSER on (COMSER.surcharge_id = CCST.surcharge_id AND COMSER.courier_id = CCST.courier_id   AND COMSER.company_id =  '$company_id')
                INNER JOIN " . DB_PREFIX . "courier_vs_company as COMCOUR on (COMCOUR.id = CCST.courier_id AND  COMCOUR.company_id =  '$company_id')
                INNER JOIN " . DB_PREFIX . "courier_vs_surcharge as COURSER on (COURSER.id = CCST.surcharge_id)
                WHERE CCST.status = 1  
                AND CCC.status = 1 
                AND COMSER.status = 1 
                AND COMCOUR.status = 1 
                AND COURSER.status = 1
                AND CCST.company_customer_id = '$customer_id'
                AND CCST.company_id = '$company_id'
                AND CCST.courier_id = '$courier_id'
                AND COURSER.surcharge_code = '$surchrage_code'";
        return $this->_getDbInstance()->getRowRecord($sql);
    }

    public function getSurchargeOfCarrier($customer_id, $company_id, $courier_id)
    {
        $sql = "
        SELECT 
        CCC.customer_surcharge_value AS customer_surcharge_value,
        CCC.company_ccf_operator_surcharge AS company_ccf_operator_surcharge,
        CINFO.surcharge AS customer_surcharge,
        CINFO.ccf_operator_surcharge AS customer_operator,
        COMCOUR.company_surcharge_value AS company_carrier_ccf,
        COMCOUR.company_ccf_operator_surcharge AS company_carrier_operator,
        COMCOUR.pickup,COMCOUR.pickup_surcharge
        FROM " . DB_PREFIX . "courier_vs_company_vs_customer as CCC 
        INNER JOIN " . DB_PREFIX . "customer_info as CINFO on CINFO.user_id = CCC.customer_id
        INNER JOIN " . DB_PREFIX . "courier_vs_company as COMCOUR on (COMCOUR.id = CCC.company_courier_account_id AND  COMCOUR.company_id =  CCC.company_id )
        WHERE   CCC.status = 1 AND  COMCOUR.status = 1 
        AND CCC.customer_id = '$customer_id'
        AND CCC.company_id = '$company_id'
        AND CCC.courier_id = '$courier_id'";
        return $this->_getDbInstance()->getRowRecord($sql);
    }

    public function getCcfOfCustomer($customer_id)
    {
        $sql = "SELECT
                CCC.customer_ccf_value AS customer_carrier_ccf,
                CCC.company_ccf_operator_service AS customer_carrier_operator,
                CINFO.ccf AS customer_ccf,
                CINFO.ccf_operator_service AS customer_operator,
                COMCOUR.company_ccf_value AS company_carrier_ccf,
                COMCOUR.company_ccf_operator_service AS company_carrier_operator
                FROM " . DB_PREFIX . "courier_vs_company_vs_customer CCC
                INNER JOIN " . DB_PREFIX . "customer_info as CINFO on CINFO.user_id = CCC.customer_id
                INNER JOIN " . DB_PREFIX . "courier_vs_company as COMCOUR on (COMCOUR.id = CCC.company_courier_account_id)
                WHERE
                CCC.status = 1
                AND CCC.customer_id = '$customer_id'";
        return $this->_getDbInstance()->getRowRecord($sql);
    }

    public function getCcfOfCarrierSurcharge__($surchrage_code, $customer_id)
    {
        $sql = "SELECT  
                CCST.customer_surcharge AS customer_carrier_surcharge_ccf,
                CCST.ccf_operator AS customer_carrier_surcharge_operator,
                CCC.customer_surcharge_value AS customer_carrier_ccf,
                CCC.company_ccf_operator_surcharge AS customer_carrier_operator,
                CINFO.surcharge AS customer_ccf,
                CINFO.ccf_operator_surcharge AS customer_operator,
                COMSER.company_surcharge_surcharge AS company_carrier_surcharge_ccf,
                COMSER.company_ccf_operator AS company_carrier_surcharge_operator,
                COMSER.company_surcharge_code,
                COMSER.company_surcharge_name,
                COMCOUR.company_surcharge_value AS company_carrier_ccf,
                COMCOUR.company_ccf_operator_surcharge AS company_carrier_operator,
                COURSER.surcharge_name AS corier_surcharge_name,
                COURSER.surcharge_code AS corier_surcharge_code
                FROM icargo_company_vs_customer_vs_surcharge CCST
                INNER JOIN icargo_courier_vs_company_vs_customer as CCC on CCC.customer_id = CCST.company_customer_id AND CCC.courier_id = CCST.courier_id
                INNER JOIN icargo_customer_info as CINFO on CINFO.user_id = CCST.company_customer_id
                INNER JOIN icargo_courier_vs_surcharge_vs_company as COMSER on (COMSER.surcharge_id = CCST.company_surcharge_id AND COMSER.courier_id = CCST.courier_id)
                INNER JOIN icargo_courier_vs_company as COMCOUR on (COMCOUR.courier_id = CCST.courier_id)
                INNER JOIN icargo_courier_vs_surcharge as COURSER on (COURSER.id = CCST.surcharge_id)
                WHERE
                CCST.status = 1
                AND CCST.company_customer_id = '$customer_id'
                AND CCST.surcharge_id = '$surchrage_code'";
        return $this->_getDbInstance()->getRowRecord($sql);
    }

    public function checkServiceExist($serviceName, $courier_id)
    {
        $sql = "SELECT CT.id as service_id
               FROM " . DB_PREFIX . "courier_vs_services AS CT 
               WHERE CT.service_code = '$serviceName' AND CT.courier_id = '$courier_id'";
        $servicedetails = $this->_getDbInstance()->getRowRecord($sql);
        return $servicedetails['service_id'];
    }

    public function getServiceDetail($serviceName, $courier_id, $customerid)
    {
        $sql = "SELECT CUSTSER.service_id,CTS.company_service_code,CTS.company_service_name,CTT.service_name,CTT.service_code,
              CTS.company_service_ccf,CTS.company_ccf_operator,CUSTSER.customer_ccf,CUSTSER.ccf_operator
              FROM " . DB_PREFIX . "company_vs_customer_vs_services AS CUSTSER
              INNER JOIN " . DB_PREFIX . "courier_vs_services AS CTT ON CTT.id = CUSTSER.service_id
              INNER JOIN " . DB_PREFIX . "courier_vs_services_vs_company AS  CTS ON CTS.id = CUSTSER.company_service_id 
              WHERE CUSTSER.service_id = '$serviceName' AND CUSTSER.courier_id = '$courier_id' AND CUSTSER.company_customer_id = '$customerid'";
        return $this->_getDbInstance()->getRowRecord($sql);
    }
    /*public function checkServiceExist($serviceName,$courier_code){
        $sql = "SELECT CT.surcharge AS surcharge_ccf, CT.ccf_operator_surcharge AS surcharge_operator
                FROM ". DB_PREFIX . "courier_vs_services AS CT
                INNER JOIN ". DB_PREFIX . "courier_vs_services AS CT
                WHERE CT.user_id=$customer_id";
         return $this->_getDbInstance()->getRowRecord($sql);
     } */

}

?>