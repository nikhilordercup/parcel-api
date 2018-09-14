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

    function findAssignedLoadIdentityByShipmentTicket($shipment_ticket)
    {
        $record = array();
        $sql = "SELECT instaDispatch_loadIdentity AS load_identity, instaDispatch_loadGroupTypeCode AS load_type, shipment_ticket AS shipment_ticket, assigned_driver AS assigned_driver, shipment_routed_id AS shipment_route_id, company_id AS company_id, warehouse_id AS warehouse_id, current_status AS current_status FROM " . DB_PREFIX . "shipment WHERE shipment_ticket IN('$shipment_ticket') ORDER BY FIELD (shipment_service_type, 'P','D')";
        $records = $this->_db->getAllRecords($sql);
        return $records;
    }*/

    public

    function findAssignedLoadIdentityByShipmentTicket($shipment_ticket)
    {
        $record = array();
        $sql = "SELECT DISTINCT(instaDispatch_loadIdentity) AS load_identity FROM " . DB_PREFIX . "shipment WHERE shipment_ticket IN('$shipment_ticket')";
        $records = $this->_db->getAllRecords($sql);
        return $records;
    }

    public

    function findNotCollectedShipmentCountByLoadIdentity($load_identity)
    {
        $sql = "SELECT COUNT(1) AS shipment_count FROM " . DB_PREFIX . "shipment WHERE instaDispatch_loadIdentity='$load_identity' AND current_status<>'D' AND shipment_service_type='P'";
        return $this->_db->getOneRecord($sql);
    }

    public

    function findCardedCollectedShipmentCountByLoadIdentity($load_identity)
    {
        $sql = "SELECT COUNT(1) AS shipment_count FROM " . DB_PREFIX . "shipment WHERE instaDispatch_loadIdentity='$load_identity' AND current_status='Ca' AND shipment_service_type='P'";
        return $this->_db->getOneRecord($sql);
    }

    public

    function findCollectedShipmentCountByLoadIdentity($load_identity)
    {
        $sql = "SELECT COUNT(1) AS shipment_count FROM " . DB_PREFIX . "shipment WHERE instaDispatch_loadIdentity='$load_identity' AND current_status='D' AND shipment_service_type='P'";
        return $this->_db->getOneRecord($sql);
    }

    public

    function findAllCollectionShipmentCountByLoadIdentity($load_identity)
    {
        $sql = "SELECT COUNT(1) AS shipment_count FROM " . DB_PREFIX . "shipment WHERE instaDispatch_loadIdentity='$load_identity' AND shipment_service_type='P'";
        return $this->_db->getOneRecord($sql);
    }

    public

    function findNotDeliveredShipmentCountByLoadIdentity($load_identity)
    {
        $sql = "SELECT COUNT(1) AS shipment_count FROM " . DB_PREFIX . "shipment WHERE instaDispatch_loadIdentity='$load_identity' AND current_status='C' AND shipment_service_type='D'";
        return $this->_db->getOneRecord($sql);
    }

    public

    function findCardedDeliveryShipmentCountByLoadIdentity($load_identity)
    {
        $sql = "SELECT COUNT(1) AS shipment_count FROM " . DB_PREFIX . "shipment WHERE instaDispatch_loadIdentity='$load_identity' AND current_status='Ca' AND shipment_service_type='D'";
        return $this->_db->getOneRecord($sql);
    }

    public

    function findDeliveredShipmentCountByLoadIdentity($load_identity)
    {
        $sql = "SELECT COUNT(1) AS shipment_count FROM " . DB_PREFIX . "shipment WHERE instaDispatch_loadIdentity='$load_identity' AND current_status='D' AND shipment_service_type='D'";
        return $this->_db->getOneRecord($sql);
    }

    public

    function findAllDeliveryShipmentCountByLoadIdentity($load_identity)
    {
        $sql = "SELECT COUNT(1) AS shipment_count FROM " . DB_PREFIX . "shipment WHERE instaDispatch_loadIdentity='$load_identity' AND shipment_service_type='D'";
        return $this->_db->getOneRecord($sql);
    }

    public

    function findTrackingHistoryCount($shipment_ticket, $load_identity, $code)
    {
        $sql = "SELECT COUNT(1) AS code_count FROM " . DB_PREFIX . "shipment_tracking WHERE load_identity='$load_identity' AND shipment_ticket='$shipment_ticket' AND code='$code'";
        return $this->_db->getOneRecord($sql);
    }

    public

    function saveTrackingHistory($param)
    {   
        return $this->_db->save("shipment_tracking", $param);

        /*return $this->_db->save("shipment_tracking", array(
            "shipment_ticket" => $param["shipment_ticket"],
            "load_identity" => $param["load_identity"],
            "code" => $param["code"],
            "load_type" => $param["load_type"],
            "service_type" => $param["service_type"]         
        ));*/
    }

    public

    function findAllTrackingCode($load_identity)
    {
        $sql = "SELECT code AS tracking_code FROM " . DB_PREFIX . "shipment_tracking WHERE load_identity='$load_identity'";
        return $this->_db->getAllRecords($sql);
    }

    public

    function saveTrackingcode($data)
    {
        return $this->_db->update("shipment_service", array("tracking_code"=>$data["code"],"status"=>$data["code"]), "load_identity='".$data["load_identity"]."'");
    }

    public

    function findTrackingCodeCountByLoadTypeAndTrackingCode($code, $load_identity)
    {
        $sql = "SELECT COUNT(1) AS tracking_code_count FROM " . DB_PREFIX . "shipment_tracking WHERE load_identity='$load_identity' AND code='$code'";
        return $this->_db->getOneRecord($sql);
    }

    public

    function findTrackingDetail($status_detail, $datetime)
    {
        $sql = "SELECT COUNT(1) AS tracking_code_count FROM " . DB_PREFIX . "tracking_detail WHERE status_detail='$status_detail' AND datetime='$datetime'";
        return $this->_db->getOneRecord($sql);
    }

    public

    function saveTrackingDetail($param)
    {
        return $this->_db->save("tracking_detail", $param);
    }

    public

    function saveTrackingCarrierDetail($param)
    {
        return $this->_db->save("tracking_carrier_detail", $param);
    }

    public

    function findTrackingCarrierDetail($tracking_id)
    {
        $sql = "SELECT COUNT(1) AS num_count FROM " . DB_PREFIX . "tracking_carrier_detail WHERE tracking_id='$tracking_id'";
        return $this->_db->getOneRecord($sql);
    }

    public

    function updateTrackingCarrierDetail($param, $tracking_id)
    {
        return $this->_db->update("tracking_carrier_detail", $param, "tracking_id='$tracking_id'");
    }

    /*public

    function updateTracking($param, $tracking_id)
    {
        return $this->_db->update("shipment_tracking", $param, "tracking_id='$tracking_id'");
    }*/

    public

    function saveTracking($param)
    {
        return $this->_db->save("shipment_tracking", $param);
    }

    public

    function findTrackingById($load_identity, $code, $tracking_id, $tracking_code, $carrier, $origin)
    {
        $sql = "SELECT COUNT(1) AS num_count FROM " . DB_PREFIX . "shipment_tracking WHERE load_identity='$load_identity' AND code='$code' AND tracking_id='$tracking_id' AND tracking_code='$tracking_code' AND carrier='$carrier' AND origin='$origin'";
        return $this->_db->getOneRecord($sql);
    }

    public

    function findLoadIdentityByTrackingNo($tracking_code)
    {
        $sql = "SELECT load_identity AS load_identity FROM " . DB_PREFIX . "shipment_service WHERE label_tracking_number='$tracking_code'";
        return $this->_db->getOneRecord($sql);
    }

    public

    function updateTracking($load_identity, $tracking_code)
    {
        return $this->_db->update("shipment_service", array(
            "tracking_code" => $tracking_code,
            "status" => $tracking_code
        ), "load_identity='$load_identity'");
    }

    public

    function findTrackingInfoByCode($code)
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "shipments_master WHERE code='$code'";
        return $this->_db->getRowRecord($sql);
    }

    public

    function findShipmentlByLoadIdentity($load_identity)
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "shipment WHERE instaDispatch_loadIdentity='$load_identity' ORDER BY FIELD (shipment_service_type, 'P','D')";
        return $this->_db->getAllRecords($sql);
    }

    public

    function deleteTrackingByLoadIdentityAndCode($param)
    {
        return $this->_db->delete("DELETE FROM " . DB_PREFIX . "shipment_tracking WHERE shipment_ticket ='" . $param["shipment_ticket"] . "' AND load_identity='" . $param["load_identity"] . "' AND code = '" . $param["code"] . "'");
    }

    public

    function findCollectionSuccessCountByLoadIdentity($load_identity){
        $sql = "SELECT COUNT(1) AS num_rows FROM " . DB_PREFIX . "shipment_tracking AS STT where load_identity='$load_identity' AND code='COLLECTIONSUCCESS'";
        return $this->_db->getRowRecord($sql);
    }

    public

    function findTrackingHistory($param)
    {
        return $this->_db->getRowRecord("SELECT create_date AS create_date FROM " . DB_PREFIX . "shipment_tracking WHERE shipment_ticket ='" . $param["shipment_ticket"] . "' AND load_identity='" . $param["load_identity"] . "' AND code = '" . $param["code"] . "'");
    }
}