<?php
class Load_Assign_Model extends Shipment_Model
    {

    public static $obj = NULL;

    public

    static function _getInstance()
        {
        if(self::$obj == NULL){
            self::$obj = new Load_Assign_Model();
        }
        return self::$obj;
        }

    public

    function getDriverAssignedVehicle($driverId)
        {
        return $this->_get_driver_assigned_vehicle($driverId);
        }

    public

    function checkAllShipmentInWarehouse($shipment_str)
        {
        $sql = "SELECT COUNT(1) AS exist FROM " . DB_PREFIX . "shipment WHERE current_status = 'O' AND is_receivedinwarehouse = 'NO' AND shipment_service_type = 'D' AND shipment_ticket IN ('$shipment_str')";
        return $this->db->getOneRecord($sql);
        }

    public

    function getAllTicketsByRoute($route_id)
        {
        $sql = "SELECT temp_shipment_ticket AS shipment_ticket,job_type FROM " . DB_PREFIX . "temp_routes_shipment WHERE temp_route_id = " . $route_id . " ORDER BY drag_temp_route_id";
        return $this->db->getAllRecords($sql);
        }
    
    public

    function getRouteDetails($shipment_str, $access_token)
        {
        $sql = "SELECT t1.load_identity,t1.shipment_type,t1.drag_temp_route_id, t1.execution_order, t1.drop_execution_order, t1.temp_shipment_ticket, t1.distancemiles, t1.estimatedtime, t2.route_id, t2.route_name, t2.is_optimized, t2.optimized_type, t2.last_optimized_time, t2.route_type FROM " . DB_PREFIX . "temp_routes_shipment AS t1 ";
        $sql.= "LEFT JOIN " . DB_PREFIX . "temp_routes AS t2 ON t1.drag_temp_route_id = t2.temp_route_id ";
        $sql.= "WHERE t1.temp_shipment_ticket IN('$shipment_str') AND t1.session_id = '$access_token ' ORDER BY t1.execution_order";
        return $this->db->getAllRecords($sql);
        }
        
    public

    function getShipmentDetails($shipment_id)
        {
        $sql = "SELECT * FROM " . DB_PREFIX . "shipment WHERE shipment_id = $shipment_id";
        return $this->db->getRowRecord($sql);
        }
        
    public

    function getShipmentAddress($addressid)
        {
        $sql = "SELECT * FROM " . DB_PREFIX . "address_book WHERE id = $addressid";
        return $this->db->getRowRecord($sql);
        }

    public

    function deleteTempRoutes($route_id)
        {
        return $this->db->delete("DELETE FROM " . DB_PREFIX . "temp_routes WHERE temp_route_id = '$route_id'");
        }

    public

    function getExecutionOrder($temp_route_id)
        {
        $sql = "SELECT drop_name, drop_execution_order FROM `" . DB_PREFIX . "temp_routes_shipment` WHERE temp_route_id = $temp_route_id GROUP BY drop_name ORDER BY drop_execution_order";
        $sql = "SELECT drop_name, drop_execution_order FROM `" . DB_PREFIX . "temp_routes_shipment` WHERE temp_route_id = $temp_route_id ORDER BY drop_execution_order";
       
        $records = $this->db->getAllRecords($sql);
        return $records;
        }
    
    public

    function getNotAssignCollectionjob($loadidentity)
        {
        $sql = "SELECT * FROM " . DB_PREFIX . "shipment WHERE instaDispatch_loadIdentity = '$loadidentity'  AND shipment_service_type = 'P' AND current_status = 'C'";
        return $this->db->getRowRecord($sql);
        }

    public

    function getCollectionjobDetails($loadidentity)
        {
        $sql = "SELECT * FROM " . DB_PREFIX . "shipment WHERE instaDispatch_loadIdentity = '$loadidentity'  AND shipment_service_type = 'P'";
        return $this->db->getRowRecord($sql);
        }
    public

    function checkNotPendingDeliveryjob($loadidentity)
        {
        $sql = "SELECT * FROM " . DB_PREFIX . "shipment WHERE instaDispatch_loadIdentity = '$loadidentity'  AND shipment_service_type = 'D' AND current_status = 'C'";
        return $this->db->getAllRecords($sql);
        }
    public

    function getTempRouteDetail($route_id)
        {
        $sql = "SELECT route_type FROM " . DB_PREFIX . "temp_routes WHERE temp_route_id = '$route_id'";
        $data =  $this->db->getRowRecord($sql);
         return $data['route_type'];
        }

    public

    function saveRouteFirebaseId($shipment_route_id, $firebase_id){
        return $this->db->update("shipment_route", array("firebase_id"=>$firebase_id), "shipment_route_id = $shipment_route_id");
    }
}
?>