<?php
class Carrier_Model_Carrier{

    public static $_db = NULL;
    public static $_modelObj = NULL;

    public static function _getInstance(){
        if(self::$_modelObj==NULL){
            self::$_modelObj = new Carrier_Model_Carrier();
        }
        return self::$_modelObj;
    }

    public static function _getDbInstance(){
        if(self::$_db==NULL){
            self::$_db = new DbHandler();
        }
        return self::$_db;
    }

    public function getCustomerCcfService($customer_id, $service_code){
        $sql = "SELECT CCST.".COL_COMPANY_CUST_SERVICE_CCF." AS ccf, CCST.".COL_COMPANY_CUST_SERVICE_CCF_OPERATOR." AS operator, SCT.service_name AS service_name, SCT.service_code AS service_code FROM ". DB_PREFIX . TBL_COMPANY_CUST_SERVICE . " CCST INNER JOIN ". DB_PREFIX . TBL_COURIER_SERVICE." AS SCT ON SCT.".COL_ID." = CCST.".COL_SERVICE_ID."  WHERE CCST.".COL_STATUS."=1 AND CCST.".COL_COMPANY_CUSTOMER_ID."=$customer_id AND CCST.".COL_SERVICE_ID."='$service_code' AND CCST.".COL_COMPANY_CUST_SERVICE_CCF." > 0";
        return $this->_getDbInstance()->getRowRecord($sql);
    }

    public function getCustomerCcfSurcharge($customer_id, $surcharge_code){
        $sql = "SELECT CCST.".COL_COMPANY_CUST_SURCHARGE_CCF." AS ccf, CCST.".COL_COMPANY_CUST_SERVICE_CCF_OPERATOR." AS operator, CSCT.".COL_COMPANY_SURCHARGE_CODE." AS surcharge_code, CSCT.".COL_COMPANY_SURCHARGE_NAME." AS surcharge_name, CCCT.".COL_CUSTOMER_SURCHARGE_VALUE." as carrier_ccf, CCCT.".COL_COMPANY_CCF_OPERATOR_SURCHARGE." AS carrier_operator  FROM ". DB_PREFIX . TBL_COMPANY_CUST_SURCHARGE . " AS CCST LEFT JOIN ". DB_PREFIX . TBL_COURIER_SURCHARGE_COMPANY ." AS CSCT ON CSCT.".COL_SURCHARGE_ID."=CCST.".COL_SURCHARGE_ID." LEFT JOIN " . DB_PREFIX . TBL_COURIER_COMPANY_CUST. " AS CCCT ON CCCT.".COL_COURIER_ID." = CCST.". COL_COURIER_ID ." WHERE CCST.".COL_STATUS."=1 AND CCST.".COL_COMPANY_CUSTOMER_ID."=$customer_id AND CSCT.".COL_COMPANY_SURCHARGE_CODE."='$surcharge_code'";
        return $this->_getDbInstance()->getRowRecord($sql);
    }

    public function getCustomerAllCourier($customer_id){
        $sql = "SELECT CT.".COL_NAME." AS courier_name, CT.".COL_CODE." AS courier_code, CT.".COL_ICON." AS courier_icon, CT.".COL_DESCRIPTION." AS courier_description, CT.".COL_ID." AS courier_id FROM ". DB_PREFIX . TBL_COURIER_COMPANY_CUST . " AS CCT INNER JOIN ". DB_PREFIX . TBL_COURIER." AS CT ON CT.".COL_ID." = CCT.".COL_COURIER_ID." WHERE CCT.".COL_STATUS."=1 AND CT.".COL_STATUS."=1 AND CCT.".COL_COMPANY_ID."=$customer_id";
        return $this->_getDbInstance()->getAllRecords($sql);
    }

    public function getCustomerCourierByCourierCode($customer_id, $courier_code){
        $sql = "SELECT CT.".COL_NAME." AS courier_name, CT.".COL_CODE." AS courier_code, CT.".COL_ICON." AS courier_icon, CT.".COL_DESCRIPTION." AS courier_description, CT.".COL_ID." AS courier_id FROM ". DB_PREFIX . TBL_COURIER_COMPANY_CUST . " AS CCT INNER JOIN ". DB_PREFIX . TBL_COURIER." AS CT ON CT.".COL_ID." = CCT.".COL_COURIER_ID." WHERE CT.".COL_CODE."='$courier_code' AND CCT.".COL_STATUS."=1 AND CT.".COL_STATUS."=1 AND CCT.".COL_COMPANY_ID."=$customer_id";
        return $this->_getDbInstance()->getRowRecord($sql);
    }

    public function getCustomerInfoSurcharge($customer_id){
        $sql = "SELECT CT.".COL_SURCHARGE." AS surcharge_ccf, CT.".COL_CUSTOMER_SURCHARGE_OPERATOR." AS surcharge_operator FROM ". DB_PREFIX . TBL_CUSTOMER_INFO . " AS CT WHERE CT.".COL_USER_ID."=$customer_id";
        return $this->_getDbInstance()->getRowRecord($sql);
    }

    public function getCourierSurchargeCompany($company_id, $surcharge_code){
        $sql = "SELECT CSCT.".COL_CUSTOMER_SURCHARGE_SURCHARGE." AS surcharge_ccf, CSCT.".COL_COMPANY_CCF_OPERATOR." AS surcharge_operator, CSCT.".COL_COMPANY_SURCHARGE_NAME." AS surcharge_name, CSCT.".COL_COMPANY_SURCHARGE_CODE." as surcharge_code FROM ". DB_PREFIX . TBL_COURIER_SURCHARGE_COMPANY . " AS CSCT WHERE CSCT.".COL_COMPANY_SURCHARGE_CODE."='$surcharge_code' AND " . COL_COMPANY_ID . "='$company_id'";
        return $this->_getDbInstance()->getRowRecord($sql);
    }

    public function getCompanyCourierSurcharge($company_id, $courier_code){
        $sql = "SELECT CCT.".COL_COMPANY_SURCHARGE_VALUE." AS surcharge_ccf, CCT.".COL_COMPANY_CCF_OPERATOR_SURCHARGE." AS surcharge_operator  FROM ". DB_PREFIX . TBL_COURIER_COMPANY . " AS CCT INNER JOIN ". DB_PREFIX .TBL_COURIER." AS CT ON CT.".COL_ID."=CCT.".COL_COURIER_ID." WHERE CSCT.".COL_COMPANY_SURCHARGE_CODE."='$courier_code' AND " . COL_COMPANY_ID . "='$company_id'";
        return $this->_getDbInstance()->getRowRecord($sql);
    }

    public function getCcfOfCarrierServices($service_code, $customer_id){
        $sql = "SELECT  
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
                FROM icargo_company_vs_customer_vs_services CCST
                INNER JOIN icargo_courier_vs_company_vs_customer as CCC on CCC.customer_id = CCST.company_customer_id AND CCC.courier_id = CCST.courier_id
                INNER JOIN icargo_customer_info as CINFO on CINFO.user_id = CCST.company_customer_id
                INNER JOIN icargo_courier_vs_services_vs_company as COMSER on (COMSER.service_id = CCST.company_service_id AND COMSER.courier_id = CCST.courier_id)
                INNER JOIN icargo_courier_vs_company as COMCOUR on (COMCOUR.courier_id = CCST.courier_id)
                INNER JOIN icargo_courier_vs_services as COURSER on (COURSER.id = CCST.service_id)
                WHERE
                CCST.status = 1
                AND CCST.company_customer_id = '$customer_id'
                AND COMSER.company_service_code = '$service_code'";
        return $this->_getDbInstance()->getRowRecord($sql);
    }

    public function getCcfOfCustomer($customer_id){
        $sql = "SELECT
                CCC.customer_ccf_value AS customer_carrier_ccf,
                CCC.company_ccf_operator_service AS customer_carrier_operator,
                CINFO.ccf AS customer_ccf,
                CINFO.ccf_operator_service AS customer_operator,
                COMCOUR.company_ccf_value AS company_carrier_ccf,
                COMCOUR.company_ccf_operator_service AS company_carrier_operator
                FROM icargo_courier_vs_company_vs_customer CCC
                INNER JOIN icargo_customer_info as CINFO on CINFO.user_id = CCC.customer_id
                INNER JOIN icargo_courier_vs_company as COMCOUR on (COMCOUR.courier_id = CCC.courier_id)
                WHERE
                CCC.status = 1
                AND CCC.customer_id = '$customer_id'";
        return $this->_getDbInstance()->getRowRecord($sql);
    }

    public function getCcfOfCarrierSurcharge($surchrage_code, $customer_id){
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
        echo $sql;die;
        return $this->_getDbInstance()->getRowRecord($sql);
    }
}

?>