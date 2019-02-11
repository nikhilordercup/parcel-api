<?php
class Route_Model{
    public function __construct(){
        $this->_db = new DbHandler();
    }

    public function findDriverTimeInfo($start_date, $end_date, $service_type, $company_id){
        $sql = "SELECT `t2`.`shipment_ticket`,`t2`.`instaDispatch_loadIdentity` AS `load_identity`, `t2`.`assigned_driver` AS `driver_id`, `t2`.`shipment_routed_id` AS `shipment_route_id`, `shipment_postcode` AS `postcode`, `shipment_address1` AS `address_1` FROM " . DB_PREFIX . "shipment AS t2 WHERE t2.actual_given_service_date BETWEEN '$start_date' AND '$end_date' AND t2.company_id='$company_id' AND instaDispatch_loadGroupTypeCode='$service_type' AND assigned_driver<>0";
        return $this->_db->getAllRecords($sql);
    }

    public function findDriverTimeInfoForLastMile($start_date, $end_date, $service_type, $company_id){
        $sql = "SELECT `t2`.`shipment_ticket`,`t2`.`instaDispatch_loadIdentity` AS `load_identity`, `t2`.`assigned_driver` AS `driver_id`, `t2`.`shipment_routed_id` AS `shipment_route_id`, `shipment_postcode` AS `postcode`, `shipment_address1` AS `address_1` FROM " . DB_PREFIX . "shipment AS t2 WHERE t2.actual_given_service_date BETWEEN '$start_date' AND '$end_date' AND t2.company_id='$company_id' AND instaDispatch_loadGroupTypeCode='$service_type' AND assigned_driver<>0";
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

    public function findDriverShipmentBetweenDate($start_date, $end_date, $company_id, $driver_id, $type){
        $sql = "SELECT DISTINCT(instaDispatch_loadIdentity) AS load_identity, shipment_address1 AS shipment_address1, shipment_postcode AS shipment_postcode FROM " . DB_PREFIX . "shipment WHERE company_id='$company_id' AND assigned_driver='$driver_id' AND actual_given_service_date BETWEEN '$start_date' AND '$end_date' AND current_status='D' AND instaDispatch_loadGroupTypeCode='$type'";
        return $this->_db->getAllRecords($sql);
    }

    public function findAllActiveReportByCompanyId($company_id, $type){
        $type = 'sameday';
        $sql = "SELECT t1.id as report_id, t1.name AS report_name, t1.code FROM " . DB_PREFIX . "report_master AS t1 WHERE t1.company_id = '$company_id' AND t1.status = 1 AND t1.type='$type'";
        return $this->_db->getAllRecords($sql);
    }

    public function findSamedayRevenue($load_identity_str){
        $sql = "SELECT SUM(SPT.price) AS customer_price, SUM(SPT.baseprice) AS carrier_price  FROM ". DB_PREFIX . "shipment_price AS SPT INNER JOIN ". DB_PREFIX . "shipment_service AS SST ON SST.price_version=SPT.version AND  SST.load_identity=SPT.load_identity WHERE api_key!='taxes' AND SST.load_identity LIKE '$load_identity_str'";
        return $this->_db->getRowRecord($sql);
    }

    public function findAllDriverShipmentBetweenDate($start_date, $end_date, $company_id, $driver_id, $type){
        $sql = "SELECT shipment_address1 AS shipment_address1, shipment_postcode AS shipment_postcode, instaDispatch_loadIdentity AS load_identity FROM " . DB_PREFIX . "shipment WHERE company_id='$company_id' AND assigned_driver IN($driver_id) AND actual_given_service_date BETWEEN '$start_date' AND '$end_date' AND current_status='D' AND instaDispatch_loadGroupTypeCode='$type'";
        return $this->_db->getAllRecords($sql);
    }

    public function findDriverTimeInfoByShipmentRouteId($route_id){
        $sql = "SELECT SUM(t1.time_taken) AS time_taken FROM " . DB_PREFIX . "driver_time_tracking AS t1 WHERE t1.shipment_route_id IN('$route_id')";
        return $this->_db->getRowRecord($sql);
    }

    public function findDriverDropInfo($shipment_route_list, $load_identity_str, $driver_id){
        $sql = "SELECT `t1`.`shipment_ticket` AS `shipment_ticket`, `t1`.`instaDispatch_loadIdentity` AS `load_identity` FROM " . DB_PREFIX . "shipment AS t1 WHERE t1.shipment_routed_id IN('$shipment_route_list') AND t1.instaDispatch_loadIdentity IN ('$load_identity_str') AND assigned_driver = '$driver_id' AND current_status = 'D'";
        return $this->_db->getAllRecords($sql);
    }

    public function findAllDropInfo($load_identity_str){
        //$sql = "SELECT COUNT(1) AS num_count FROM " . DB_PREFIX . "shipment AS t1 WHERE t1.instaDispatch_loadIdentity LIKE '$load_identity_str'";
        $sql = "SELECT `shipment_ticket` AS `shipment_ticket`, `shipment_postcode` AS `postcode`, `shipment_address1` AS `address_1` FROM " . DB_PREFIX . "shipment AS t1 WHERE t1.instaDispatch_loadIdentity LIKE '$load_identity_str'";
        return $this->_db->getAllRecords($sql);
    }

    public function findAllDropByLoadIdentity($load_identity_str){
        $sql = "SELECT shipment_ticket AS shipment_ticket FROM " . DB_PREFIX . "shipment AS t1 WHERE t1.instaDispatch_loadIdentity IN ('$load_identity_str') AND current_status = 'D'";
        return $this->_db->getAllRecords($sql);
    }
}
?>
