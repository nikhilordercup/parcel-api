<?php
class Notification_Model_Index
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

    function saveTemplate($param){
        return $this->_db->save('notification',array("company_id"=>$param->company_id,"trigger_type"=>$param->trigger_type,"trigger_code"=>$param->trigger_code,"template"=>$param->template));
    }

    public

    function deleteTemplate($company_id, $type){
        return $this->_db->delete("DELETE FROM `" . DB_PREFIX . "notification` WHERE company_id='$company_id'");
    }

    public

    function updateTemplate($param){
        return $this->_db->update('notification',array("template"=>$param->template), "`company_id`='$param->company_id' AND `trigger_type`='$param->trigger_type' AND `trigger_code`='$param->trigger_code'");
    }

    public

    function getTemplate($param){
        return $this->_db->getRowRecord("SELECT * FROM `".DB_PREFIX."notification` AS NT WHERE `company_id`='$param->company_id' AND `trigger_type`='$param->trigger_type' AND `trigger_code`='$param->trigger_code'");
    }

    public

    function updateStatus($param){
        return $this->_db->update("notification", array("status"=>$param->status),"company_id='$param->company_id' AND trigger_type='$param->trigger_type' AND trigger_code='$param->trigger_code'");
    }

    public

    function saveStatus($param){
        return $this->_db->save("notification", array("company_id"=>$param->company_id,"trigger_type"=>$param->trigger_type,"trigger_code"=>$param->trigger_code,"status"=>$param->status));
    }

    public

    function getNotificationStatus($param){
        $sql = "SELECT * FROM `".DB_PREFIX."notification` AS NT WHERE `company_id`='$param->company_id'";
        return $this->_db->getAllRecords($sql);
    }

    public

    function getShipmentDetailByShipmentTicket($shipment_ticket){
        return $this->_db->getRowRecord("SELECT * FROM `".DB_PREFIX."shipment` AS ST WHERE `shipment_ticket`='$shipment_ticket'");
    }

    public

    function getCompanyNotificationSetting($company_id, $trigger_code){
        return $this->_db->getAllRecords("SELECT * FROM `".DB_PREFIX."notification` AS ST WHERE `company_id`='$company_id' AND trigger_code = '$trigger_code' AND status=1");
    }

    public

    function getUserInfo($customer_id){
        return $this->_db->getRowRecord("SELECT name AS name, email AS email FROM `".DB_PREFIX."users` AS UT WHERE `id`='$customer_id'");
    }

    public

    function saveNotificationHistory($param){
        return $this->_db->save('notification_history',$param);
    }

    public

    function getCollectionShipmentDetailByLoadIdentity($load_identity){
        return $this->_db->getRowRecord("SELECT * FROM `".DB_PREFIX."shipment` AS ST WHERE `instaDispatch_loadIdentity`='$load_identity' AND shipment_service_type='P'");
    }

    public

    function getDeliveryShipmentDetailByLoadIdentity($load_identity){
        return $this->_db->getRowRecord("SELECT * FROM `".DB_PREFIX."shipment` AS ST WHERE `instaDispatch_loadIdentity`='$load_identity' AND shipment_service_type='D'");
    }

    public

    function getAllDeliveryShipmentDetailByLoadIdentity($load_identity){
        return $this->_db->getAllRecords("SELECT * FROM `".DB_PREFIX."shipment` AS ST WHERE `instaDispatch_loadIdentity`='$load_identity' AND shipment_service_type='D'");
    }

    public

    function getShipmentDetailByLoadIdentity($load_identity){
        return $this->_db->getAllRecords("SELECT * FROM `".DB_PREFIX."shipment` AS ST WHERE `instaDispatch_loadIdentity`='$load_identity'");
    }

    public

    function getCompanyInfo($company_id){
        return $this->_db->getRowRecord("SELECT email AS email, name AS name, address_1 AS address_1, address_2 AS address_2, postcode AS postcode, city AS city, state AS state, country AS country FROM `".DB_PREFIX."users` AS UT WHERE `id`='$company_id'");
    }

    public

    function getLoadGroupTypeCodeByShipmentRouteId($shipment_route_id){
        return $this->_db->getRowRecord("SELECT instaDispatch_loadGroupTypeCode AS load_group_type_code FROM `".DB_PREFIX."shipment` AS ST WHERE `shipment_routed_id`='$shipment_route_id'");
    }

    public

    function getSamedayCollectionShipmentByShipmentRouteId($shipment_route_id){
        return $this->_db->getAllRecords("SELECT * FROM `".DB_PREFIX."shipment` AS ST WHERE `shipment_routed_id`='$shipment_route_id' AND shipment_service_type='P'");
    }

    public

    function getSamedayDeliveryShipmentByShipmentRouteId($shipment_route_id){
        return $this->_db->getAllRecords("SELECT * FROM `".DB_PREFIX."shipment` AS ST WHERE `shipment_routed_id`='$shipment_route_id' AND shipment_service_type='D'");
    }

    public

    function getNotificationStatusByTriggerCode($param){
        $sql = "SELECT * FROM `".DB_PREFIX."notification` AS NT WHERE `company_id`='$param->company_id' AND `trigger_code`='$param->trigger_code'";
        return $this->_db->getAllRecords($sql);
    }

    public

    function checkShipmentNotificationAlreadySent($param){
        $sql = "SELECT * FROM `".DB_PREFIX."notification_history` AS NHT WHERE `route_id`='$param->route_id' AND `trigger_code`='$param->trigger_code'";
        return $this->_db->getAllRecords($sql);
    }

    public

    function checkShipmentNotificationAlreadySentByShipmentTicket($shipment_ticket, $trigger_code_filter){
        $sql = "SELECT * FROM `".DB_PREFIX."notification_history` AS NHT WHERE `shipment_ticket`='$shipment_ticket' AND trigger_code IN('$trigger_code_filter')";
        return $this->_db->getRowRecord($sql);
    }

    public

    function getRecepientEmailByShipmentTicket($shipment_tickets){
        $sql = "SELECT `shipment_ticket` AS `shipment_ticket`,`shipment_customer_name` AS `customer_name`,`shipment_customer_email` AS `customer_email` FROM `".DB_PREFIX."shipment` WHERE shipment_ticket in ('$shipment_tickets') ORDER BY shipment_executionOrder ASC LIMIT 0,1";
        return $this->_db->getRowRecord($sql);
    }

    public

    function getServiceName($load_identity){
        $sql = "SELECT `service_name` AS `service_name` FROM `".DB_PREFIX."shipment_service` WHERE load_identity ='$load_identity'";
        return $this->_db->getRowRecord($sql);
    }

    public

    function getQuotationByQuotationNumber($quotation_number){
        $sql = "SELECT * FROM `".DB_PREFIX."quote_service` WHERE quote_number ='$quotation_number'";
        return $this->_db->getRowRecord($sql);
    }

    public function startTransaction() {
        $this->_db->startTransaction();
    }

    public function commitTransaction() {
        $this->_db->commitTransaction();
    }

    public function rollBackTransaction() {
        $this->_db->rollBackTransaction();
    }
    
    public function getInvoiceData($invoiceID){
        $sql = "SELECT I.*,COM.name as company_name,COM.email as company_email,CUS.email as customer_email,CUS.name as customer_name  FROM `".DB_PREFIX."invoices` as I
                LEFT JOIN `".DB_PREFIX."users` as CUS on (CUS.id = I.customer_id AND CUS.user_level = 5)
                LEFT JOIN `".DB_PREFIX."users` as COM on (COM.id = I.company_id AND COM.user_level = 2)
                WHERE I.id ='$invoiceID'";
        return $this->_db->getRowRecord($sql);
    }
    
        
}