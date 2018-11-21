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
}
?>
