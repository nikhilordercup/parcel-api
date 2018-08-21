<?php
class Tracking_Model_Index
{
    public

    function __construct()
    {
        $this->_db = new DbHandler();
    }

    public

    function findLoadIdentityByShipmentTicket($shipment_ticket)
    {
        $sql = "SELECT instaDispatch_loadIdentity AS load_identity FROM " . DB_PREFIX . "shipment AS ST WHERE ST.shipment_ticket = '$shipment_ticket'";
        $record = $this->_db->getOneRecord($sql);
        return $record;
    }

    public

    function findShipmentTicketsByLoadIdentity($load_identity)
    {
        $sql = "SELECT shipment_ticket AS shipment_ticket FROM " . DB_PREFIX . "shipment AS ST WHERE ST.instaDispatch_loadIdentity = '$load_identity'";
        $records = $this->_db->getAllRecords($sql);
        return $records;
    }

    public function findShipmentHistoryTicketByLoadIdentity($load_identity)
    {
        $sql = "SELECT shipment_ticket AS shipment_ticket FROM " . DB_PREFIX . "shipment_life_history AS ST WHERE ST.instaDispatch_loadIdentity = '$load_identity'";
        $records = $this->_db->getAllRecords($sql);
        return $records;
    }

    public

    function findInternalActionCodeByLoadIdentity($load_identity)
    {
        //$sql = "SELECT DISTINCT(internel_action_code) AS action_code FROM " . DB_PREFIX . "shipment_life_history AS HT WHERE HT.instaDispatch_loadIdentity = '$load_identity' AND internel_action_code <>'ROUTEPAUSED'";
        $sql = "SELECT internel_action_code AS action_code FROM " . DB_PREFIX . "shipment_life_history AS HT WHERE HT.instaDispatch_loadIdentity = '$load_identity' AND (internel_action_code <>'ROUTEPAUSED' AND internel_action_code <>'ROUTECOMPLETED')";
        $records = $this->_db->getAllRecords($sql);
        return $records;
    }

    public

    function findShipmentStatusByShipmentTickets($shipment_ticket)
    {
        $sql = "SELECT current_status AS current_status, shipment_total_attempt AS attempt_count FROM " . DB_PREFIX . "shipment AS ST WHERE ST.shipment_ticket IN('$shipment_ticket')";
        $records = $this->_db->getAllRecords($sql);
        return $records;
    }

    public

    function findStatusCodeDetail($code)
    {
        $sql = "SELECT name AS name, code AS code, tracking_code AS tracking_code FROM " . DB_PREFIX . "shipment_tracking_code AS STT INNER JOIN " . DB_PREFIX . "shipments_master AS SMT ON SMT.code = STT.shipment_code WHERE STT.tracking_code = '$code'";
        $record = $this->_db->getRowRecord($sql);
        return $record;
    }

    /*public

    function findAllMappedCode()
    {
        $sql = "SELECT shipment_code AS shipment_code, tracking_code AS tracking_code FROM " . DB_PREFIX . "shipment_tracking_code";
        $records = $this->_db->getAllRecords($sql);
        return $records;
    }*/



    /************************/
    public

    function findAssignedLoadIdentityByShipmentTicket($shipment_ticket)
        {
        $record = array();
        $sql = "SELECT instaDispatch_loadIdentity AS load_identity, instaDispatch_loadGroupTypeCode AS load_type, shipment_ticket AS shipment_ticket, assigned_driver AS assigned_driver, shipment_routed_id AS shipment_route_id, company_id AS company_id, warehouse_id AS warehouse_id, current_status AS current_status FROM " . DB_PREFIX . "shipment WHERE shipment_ticket IN('$shipment_ticket')";/*assigned_driver > 0 AND */
        $records = $this->_db->getAllRecords($sql);
        return $records;
        }

    public

    function findNotCollectedShipmentCountByLoadIdentity($load_identity){
        $sql = "SELECT COUNT(1) AS shipment_count FROM " . DB_PREFIX . "shipment where instaDispatch_loadIdentity='$load_identity' AND current_status<>'D' AND shipment_service_type='P'";
        return $this->_db->getOneRecord($sql);
    }

    public

    function findCardedCollectedShipmentCountByLoadIdentity($load_identity){
        $sql = "SELECT COUNT(1) AS shipment_count FROM " . DB_PREFIX . "shipment where instaDispatch_loadIdentity='$load_identity' AND current_status='Ca' AND shipment_service_type='P'";
        return $this->_db->getOneRecord($sql);
    }

    public

    function findCollectedShipmentCountByLoadIdentity($load_identity){
        $sql = "SELECT COUNT(1) AS shipment_count FROM " . DB_PREFIX . "shipment where instaDispatch_loadIdentity='$load_identity' AND current_status='D' AND shipment_service_type='P'";
        return $this->_db->getOneRecord($sql);
    }

    public

    function findAllCollectionShipmentCountByLoadIdentity($load_identity){
        $sql = "SELECT COUNT(1) AS shipment_count FROM " . DB_PREFIX . "shipment where instaDispatch_loadIdentity='$load_identity' AND shipment_service_type='P'";
        return $this->_db->getOneRecord($sql);
    }

    public

    function findNotDeliveredShipmentCountByLoadIdentity($load_identity){
        $sql = "SELECT COUNT(1) AS shipment_count FROM " . DB_PREFIX . "shipment where instaDispatch_loadIdentity='$load_identity' AND current_status='C' AND shipment_service_type='D'";
        return $this->_db->getOneRecord($sql);
    }

    public

    function findCardedDeliveryShipmentCountByLoadIdentity($load_identity){
        $sql = "SELECT COUNT(1) AS shipment_count FROM " . DB_PREFIX . "shipment where instaDispatch_loadIdentity='$load_identity' AND current_status='Ca' AND shipment_service_type='D'";
        return $this->_db->getOneRecord($sql);
    }

    public

    function findDeliveredShipmentCountByLoadIdentity($load_identity){
        $sql = "SELECT COUNT(1) AS shipment_count FROM " . DB_PREFIX . "shipment where instaDispatch_loadIdentity='$load_identity' AND current_status='D' AND shipment_service_type='D'";
        return $this->_db->getOneRecord($sql);
    }

    public

    function findAllDeliveryShipmentCountByLoadIdentity($load_identity){
        $sql = "SELECT COUNT(1) AS shipment_count FROM " . DB_PREFIX . "shipment where instaDispatch_loadIdentity='$load_identity' AND shipment_service_type='D'";
        return $this->_db->getOneRecord($sql);
    }

    public

    function findTrackingHistoryCount($shipment_ticket, $load_identity, $code){
        $sql = "SELECT COUNT(1) AS code_count FROM " . DB_PREFIX . "shipment_tracking where load_identity='$load_identity' AND shipment_ticket='$shipment_ticket' AND code='$code'";
        return $this->_db->getOneRecord($sql);
    }

    public

    function saveTrackingHistory($param){
        return $this->_db->save("shipment_tracking", array(
            "shipment_ticket" => $param["shipment_ticket"],
            "load_identity" => $param["load_identity"],
            "code" => $param["code"]          
        ));
    }

    public

    function findAllTrackingCode($load_identity){
        $sql = "SELECT code AS tracking_code FROM " . DB_PREFIX . "shipment_tracking where load_identity='$load_identity'";
        return $this->_db->getAllRecords($sql);
    }

    public

    function saveTrackingcode($data)
        {
        return $this->_db->update("shipment_service", array("tracking_code"=>$data["code"]), "load_identity='".$data["load_identity"]."'");
        }

    public

    function findTrackingCodeCountByLoadTypeAndTrackingCode($code, $load_identity){
        $sql = "SELECT COUNT(1) AS tracking_code_count FROM " . DB_PREFIX . "shipment_tracking where load_identity='$load_identity' AND code='$code'";
        return $this->_db->getOneRecord($sql);
    }
}