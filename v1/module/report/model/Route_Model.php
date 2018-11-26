<?php
class Route_Model{
    public function __construct(){
        $this->_db = new DbHandler();
    }
    public function findRouteCountBetweenDate($type, $start_date, $end_date, $company_id){
        $sql = "SELECT DISTINCT(shipment_routed_id) AS route_count FROM " . DB_PREFIX . "shipment WHERE instaDispatch_loadGroupTypeCode='$type' AND company_id='$company_id' AND shipment_create_date BETWEEN '$start_date' AND '$end_date'";
        return $this->_db->getRowRecord($sql);
    }

    public function findDriverTimeInfo($start_date, $end_date, $service_type, $company_id){
        $sql = "SELECT t1.* FROM " . DB_PREFIX . "driver_time_tracking as t1 INNER JOIN " . DB_PREFIX . "shipment as t2 ON t2.shipment_routed_id=t1.shipment_route_id WHERE create_date BETWEEN '$start_date' AND '$end_date' AND t2.instaDispatch_loadGroupTypeCode='$service_type' AND t2.company_id='$company_id'";
        return $this->_db->getAllRecords($sql);
    }

    public function findDriverNameById($id){
        $sql = "SELECT name AS driver_name FROM " . DB_PREFIX . "users WHERE id = '$id'";
        return $this->_db->getRowRecord($sql);
    }

    public function findLoadIdentityByShipmentTicket($shipment_route_id){
        $sql = "SELECT instaDispatch_loadIdentity as load_identity FROM " . DB_PREFIX . "shipment WHERE shipment_routed_id IN($shipment_route_id)";
        return $this->_db->getAllRecords($sql);
    }

    public function findTransitDistanceByLoadIdentity($load_identity_str){
        $sql = "SELECT transit_distance AS transit_distance FROM " . DB_PREFIX . "shipment_service WHERE load_identity IN('$load_identity_str')";
        return $this->_db->getAllRecords($sql);
    }

    public function findDriverShipmentBetweenDate($start_date, $end_date, $company_id, $driver_id){
        $sql = "SELECT shipment_address1 AS shipment_address1, shipment_postcode AS shipment_postcode, instaDispatch_loadIdentity AS load_Identity FROM " . DB_PREFIX . "shipment WHERE company_id='$company_id' AND assigned_driver='$driver_id' AND shipment_create_date BETWEEN '$start_date' AND '$end_date'";
        return $this->_db->getAllRecords($sql);
    }

    public function findAllActiveReportByCompanyId($company_id, $type){
        $type = 'sameday';
        $sql = "SELECT t1.id as report_id, t1.name AS report_name, t1.code FROM " . DB_PREFIX . "report_master AS t1 WHERE t1.company_id = '$company_id' AND t1.status = 1 AND t1.type='$type'";
        return $this->_db->getAllRecords($sql);
    }
}
?>
