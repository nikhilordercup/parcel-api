<?php
class Carrier_Model_Carrier{

    public static $_db = NULL;
    public static $_modelObj = NULL;


    public static function _getInstance(){
        if(self::$_modelObj==NULL){
            self::$_modelObj = new Carrier_Model_Carrier();
        }
        if(self::$_db==NULL){
            self::$_db = new DbHandler();
        }

        return self::$_modelObj;
    }

    public static function _getDbInstance(){
        return self::$_db;
    }

    public function getCustomerCcfService($customer_id, $service_code){
        $sql = "SELECT CCST.customer_ccf AS ccf, CCST.ccf_operator AS operator, SCT.service_name AS service_name, SCT.service_code AS service_code FROM ". DB_PREFIX . "company_vs_customer_vs_services CCST INNER JOIN ". DB_PREFIX ."courier_vs_services AS SCT ON SCT.id = CCST.service_id  WHERE CCST.status=1 AND CCST.company_customer_id=$customer_id AND CCST.service_id='$service_code' AND CCST.customer_ccf > 0";
        return $this->_getDbInstance()->getRowRecord($sql);
    }

    public function getCustomerCcfSurcharge($customer_id, $surcharge_code){
        $sql = "SELECT CCST.customer_surcharge AS ccf, CCST.ccf_operator AS operator, CSCT.company_surcharge_code AS surcharge_code, CSCT.company_surcharge_name AS surcharge_name, CCCT.customer_surcharge_value as carrier_ccf, CCCT.company_ccf_operator_surcharge AS carrier_operator  FROM ". DB_PREFIX . "company_vs_customer_vs_surcharge AS CCST LEFT JOIN ". DB_PREFIX ."courier_vs_surcharge_vs_company AS CSCT ON CSCT.surcharge_id=CCST.surcharge_id LEFT JOIN " . DB_PREFIX . "courier_vs_company_vs_customer AS CCCT ON CCCT.courier_id = CCST.courier_id WHERE CCST.status=1 AND CCST.company_customer_id=$customer_id AND CSCT.company_surcharge_code='$surcharge_code'";
        return $this->_getDbInstance()->getRowRecord($sql);
    }

    public function getCustomerAllCourier($customer_id){
        $sql = "SELECT CT.name AS courier_name, CT.code AS courier_code, CT.icon AS courier_icon, CT.description AS courier_description, CT.id AS courier_id FROM ". DB_PREFIX . "courier_vs_company_vs_customer AS CCT INNER JOIN ". DB_PREFIX."courier AS CT ON CT.id = CCT.courier_id WHERE CCT.status=1 AND CT.status=1 AND CCT.company_id=$customer_id";
        return $this->_getDbInstance()->getAllRecords($sql);
    }

    public function getCustomerCourierByCourierCode($customer_id, $company_id, $courier_code){
        $sql = "SELECT CT.name AS courier_name, CT.code AS courier_code, CT.icon AS courier_icon, CT.description AS courier_description, CT.id AS courier_id FROM ". DB_PREFIX . "courier_vs_company_vs_customer AS CCT INNER JOIN ". DB_PREFIX ."courier AS CT ON CT.id = CCT.courier_id WHERE CT.code='$courier_code' AND CCT.status=1 AND CT.status=1 AND CCT.company_id=$company_id AND CCT.customer_id=$customer_id";
        return $this->_getDbInstance()->getRowRecord($sql);
    }

    public function getCustomerInfoSurcharge($customer_id){
        $sql = "SELECT CT.surcharge AS surcharge_ccf, CT.ccf_operator_surcharge AS surcharge_operator FROM ". DB_PREFIX . "customer_info AS CT WHERE CT.user_id=$customer_id";
        return $this->_getDbInstance()->getRowRecord($sql);
    }

    public function getCourierSurchargeCompany($company_id, $surcharge_code){
        $sql = "SELECT CSCT.company_surcharge_surcharge AS surcharge_ccf, CSCT.company_ccf_operator AS surcharge_operator, CSCT.company_surcharge_name AS surcharge_name, CSCT.company_surcharge_code as surcharge_code FROM ". DB_PREFIX . "courier_vs_surcharge_vs_company AS CSCT WHERE CSCT.company_surcharge_code='$surcharge_code' AND company_id='$company_id'";
        return $this->_getDbInstance()->getRowRecord($sql);
    }

    public function getCompanyCourierSurcharge($company_id, $courier_code){
        $sql = "SELECT CCT.company_surcharge_value AS surcharge_ccf, CCT.company_ccf_operator_surcharge AS surcharge_operator  FROM ". DB_PREFIX . "courier_vs_company AS CCT INNER JOIN ". DB_PREFIX ."courier AS CT ON CT.id=CCT.courier_id WHERE CSCT.company_surcharge_code='$courier_code' AND company_id='$company_id'";
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
                FROM ". DB_PREFIX . "company_vs_customer_vs_services CCST
                INNER JOIN ". DB_PREFIX . "courier_vs_company_vs_customer as CCC on CCC.customer_id = CCST.company_customer_id AND CCC.courier_id = CCST.courier_id
                INNER JOIN ". DB_PREFIX . "customer_info as CINFO on CINFO.user_id = CCST.company_customer_id
                INNER JOIN ". DB_PREFIX . "courier_vs_services_vs_company as COMSER on (COMSER.service_id = CCST.company_service_id AND COMSER.courier_id = CCST.courier_id)
                INNER JOIN ". DB_PREFIX . "courier_vs_company as COMCOUR on (COMCOUR.courier_id = CCST.courier_id)
                INNER JOIN ". DB_PREFIX . "courier_vs_services as COURSER on (COURSER.id = CCST.service_id)
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
                FROM ". DB_PREFIX . "courier_vs_company_vs_customer CCC
                INNER JOIN ". DB_PREFIX . "customer_info as CINFO on CINFO.user_id = CCC.customer_id
                INNER JOIN ". DB_PREFIX . "courier_vs_company as COMCOUR on (COMCOUR.courier_id = CCC.courier_id)
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
                FROM ". DB_PREFIX . "company_vs_customer_vs_surcharge CCST
                INNER JOIN ". DB_PREFIX . "courier_vs_company_vs_customer as CCC on CCC.customer_id = CCST.company_customer_id AND CCC.courier_id = CCST.courier_id
                INNER JOIN ". DB_PREFIX . "customer_info as CINFO on CINFO.user_id = CCST.company_customer_id
                INNER JOIN ". DB_PREFIX . "courier_vs_surcharge_vs_company as COMSER on (COMSER.surcharge_id = CCST.company_surcharge_id AND COMSER.courier_id = CCST.courier_id)
                INNER JOIN ". DB_PREFIX . "courier_vs_company as COMCOUR on (COMCOUR.courier_id = CCST.courier_id)
                INNER JOIN ". DB_PREFIX . "courier_vs_surcharge as COURSER on (COURSER.id = CCST.surcharge_id)
                WHERE
                CCST.status = 1
                AND CCST.company_customer_id = '$customer_id'
                AND CCST.surcharge_id = '$surchrage_code'";
        return $this->_getDbInstance()->getRowRecord($sql);
    }

    public function getCarrierServiceInfo($carrier_id, $service_code){
        $sql = "SELECT service_name, service_code, service_icon, service_description FROM ". DB_PREFIX . "courier_vs_services AS CST WHERE courier_id='$carrier_id' AND service_code='$service_code'";
        return $this->_getDbInstance()->getRowRecord($sql);
    }

    public function getTicketNo($company_id){
        $record = $this->_getDbInstance()->getRowRecord("SELECT (shipment_end_number + 1) AS shipment_ticket_no, shipment_ticket_prefix AS shipment_ticket_prefix FROM " . DB_PREFIX . "configuration WHERE company_id = '$company_id'");
        return $record;
    }

    public function saveLastTicketNo($company_id){
        return $this->_getDbInstance()->updateData("UPDATE " . DB_PREFIX . "configuration SET shipment_end_number = shipment_end_number + 1 WHERE company_id = '$company_id'");
    }

    public function testShipmentTicket($shipment_ticket){
        $record = $this->_getDbInstance()->getOneRecord("SELECT COUNT(1) AS exist FROM " . DB_PREFIX . "shipment WHERE shipment_ticket = '". $shipment_ticket ."'");
        if($record['exist'] > 0)
            return true;
        else
            return false;
    }

    public function getCompanyCode($company_id){
        $record = $this->_getDbInstance()->getRowRecord("SELECT code AS code FROM " . DB_PREFIX . "user_code WHERE id = '$company_id'");
        return $record['code'];
    }
}
?>