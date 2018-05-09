<?php
class Ws_Model_Rest
{
    public function __construct()
    {
        $this->db = new DbHandler();
    }
    
    public function save($table, $data)
    {
        try{
            return $this->db->save($table, $data);
        }catch(Exception $e){

        }
    }
    
    public function update($table, $data, $condition)
    {
        try{
            return $this->db->update($table, $data, $condition);
        }catch(Exception $e){

        }
    }
    
    public function get_shipment_ticket_by_shipment_route_id($shipment_route_id)
    {
        $sql = "SELECT shipment_id, shipment_ticket FROM " . DB_PREFIX ."shipment WHERE shipment_routed_id = '$shipment_route_id'";
        $records = $this->db->getAllRecords($sql);
        return $records;
    }
    
    public function get_user_by_email($email)
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "users WHERE email = '$email'";
        $record = $this->db->getRowRecord($sql);
        return $record;
    }
    
    public function get_user_by_id($id)
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "users WHERE id = '$id'";
        $record = $this->db->getRowRecord($sql);
        return $record;
    }
    
    public function get_route_by_shipment_route_id($shipment_route_id)
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "shipment_route WHERE shipment_route_id = '$shipment_route_id'";
        $record = $this->db->getRowRecord($sql);
        return $record;
    }
    
    public function get_shipment_details_by_ticket($shipment_ticket)
    {
        $sql = "SELECT * FROM " . DB_PREFIX ."shipment WHERE shipment_ticket IN('$shipment_ticket') AND is_driver_accept  = 'Pending' AND current_status  = 'O'";
        $records = $this->db->getAllRecords($sql);
        return $records;
    }
    
    public function get_driver_company_warehouse($driver_id)
    {
        $sql = "SELECT company_id, warehouse_id FROM " . DB_PREFIX . "company_users WHERE user_id = '$driver_id' ";
        $record = $this->db->getRowRecord($sql);
        return $record;
    }
    
    public function get_assigned_vehicle_for_shipment($shipment_route_id)
    {
        $sql = "SELECT vehicle_id FROM " . DB_PREFIX . "driver_shipment WHERE shipment_route_id = '$shipment_route_id'";
        $record = $this->db->getOneRecord($sql);
        return $record;
    }
    
    public function check_shipment_accepted_by_driver_by_ticket($ticket, $driver_id, $routeid, $shipmentType = null)
    {
        //$subConditionForNextDay = 1;//($shipmentType == 'P') ? "1" : "R1.is_driverpickupfromwarehouse  = 'YES'";
        $sql = "SELECT COUNT(1) AS exist FROM " . DB_PREFIX . "shipment WHERE shipment_ticket = '$ticket' AND shipment_routed_id = '$routeid' AND assigned_driver = '$driver_id' AND is_driver_accept = 'YES'";
        $record = $this->db->getOneRecord($sql);
        return $record;
    }
    
    public function get_accepted_shipment_details_by_ticket($ticket)
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "shipment WHERE shipment_ticket = '$ticket' AND is_driver_accept = 'YES' AND (current_status = 'O' OR current_status = 'Ca')";
        $record = $this->db->getRowRecord($sql);
        return $record;
    }
    
    
    public function more_shipment_exist_in_this_route_for_driver_from_operation($driver_id,$route_id)
    {
        $sql = "SELECT COUNT(1) AS count FROM " . DB_PREFIX . "driver_shipment as s1 left join " . DB_PREFIX . "shipment as s2 on s1.shipment_ticket = s2.shipment_ticket 
        WHERE s1.driver_id = '$driver_id' AND s1.shipment_route_id = '$route_id' AND s1.is_driveraction_complete = 'N' AND s2.is_receivedinwarehouse = 'YES'";
	    $record = $this->db->getOneRecord($sql);
        return $record;
    }
    
    public function more_shipment_exist_in_this_route_for_driver_from_operation_count($driver_id,$route_id)
    {
        $sql = "SELECT COUNT(1) AS count FROM " . DB_PREFIX . "driver_shipment as s1 left join " . DB_PREFIX . "shipment as s2 on s1.shipment_ticket = s2.shipment_ticket 
        WHERE s1.driver_id = '$driver_id' AND s1.shipment_route_id = '$route_id' AND s1.is_driveraction_complete = 'N' AND s2.is_receivedinwarehouse = 'YES'";
	    $record = $this->db->getOneRecord($sql);
        return $record['count'];
    }
    
    public function get_accepted_shipment_details_by_ticket_after_update($ticket)
    {
        
        $sql = "SELECT * FROM " . DB_PREFIX . "shipment WHERE shipment_ticket = '$ticket' AND is_driver_accept = 'YES' AND current_status = 'D'";
        $record = $this->db->getRowRecord($sql);
        return $record;
    }
    
    public function get_only_shipment_details_by_ticket($ticket)
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "shipment WHERE shipment_ticket = '$ticket'";
        $record = $this->db->getRowRecord($sql);
        return $record;
    }
    
    public function get_parcel_details_by_parcel_ticket($parcel_ticket)
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "shipments_parcel WHERE parcel_ticket = '$parcel_ticket'";
        $record = $this->db->getRowRecord($sql);
        return $record;
    }
    
    public function driver_route_exist_by_route_id($driver_id,$shipment_route_id)
    {
        $sql = "SELECT COUNT(1) AS exist FROM " . DB_PREFIX . "shipment_route AS RT WHERE RT.driver_id = '$driver_id' AND RT.is_active = 'Y' AND RT.shipment_route_id = '$shipment_route_id' ORDER BY RT.shipment_route_id";
        $record = $this->db->getOneRecord($sql);
        return $record;
    }
    
    public function check_load_identity_assign_to_driver($ticket, $driver_id, $shipment_route_id)
    {
        $sql = "SELECT COUNT(1) AS exist FROM " . DB_PREFIX . "driver_shipment AS RT WHERE RT.driver_id = '$driver_id' AND RT.shipment_ticket = '$ticket' AND RT.shipment_route_id = '$shipment_route_id' ORDER BY RT.shipment_route_id";
        $record = $this->db->getOneRecord($sql);
        return $record;
    }
    
    public function check_load_identity_exist($ticket)
    {
        $sql = "SELECT COUNT(1) AS exist FROM " . DB_PREFIX . "shipment WHERE shipment_ticket = '$ticket'";
        $record = $this->db->getOneRecord($sql);
        return $record;
    }
    
    public function shipment_exist_by_ticket_driver($ticket, $assigned_driver, $shipment_routed_id)
    {
        $sql = "SELECT COUNT(1) AS exist FROM " . DB_PREFIX . "shipment WHERE shipment_ticket = '$ticket' AND shipment_routed_id = '$shipment_routed_id' AND assigned_driver = '$assigned_driver' AND is_driver_accept = 'YES' AND (current_status = 'O' OR current_status = 'Ca')";
        $record = $this->db->getOneRecord($sql);
        return $record;
    }
    
    public function get_shipment_parcel_status_details($ticket)
    {
        $sql = "SELECT ST.*, PT.parcel_ticket, PT.instaDispatch_pieceIdentity, PT.instaDispatch_loadIdentity AS instaDispatch_loadIdentity_parcel FROM " . DB_PREFIX . "shipment AS ST LEFT JOIN " . DB_PREFIX . "shipments_parcel AS PT ON PT.shipment_ticket=ST.shipment_ticket WHERE ST.shipment_ticket = '$ticket'";
        $records = $this->db->getAllRecords($sql);
        return $records;
    }

    public function get_available_shipment_for_service_by_shipment_route_id($shipment_routed_id)
    {
        $sql = "SELECT shipment_ticket AS shipment_ticket FROM " . DB_PREFIX . "shipment WHERE shipment_routed_id = '$shipment_routed_id' AND is_driver_accept = 'YES' AND (current_status != 'D' OR current_status != 'Ca')";
        $record = $this->db->getAllRecords($sql);
        return $record;
    }

    public function get_shipment_details_by_shipment_ticket($shipment_ticket)
    {
        $sql = "SELECT * FROM " . DB_PREFIX ."shipment WHERE shipment_ticket IN('$shipment_ticket')";
        $records = $this->db->getRowRecord($sql);
        return $records;
    }
}
?>