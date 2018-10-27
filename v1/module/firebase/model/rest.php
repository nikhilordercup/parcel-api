<?php
class Firebase_Model_Rest
{
    
    public function __construct()
    {
        $this->db = new DbHandler();    
    }
    
	 public function recoverAssignedRouteOfDriverByRouteId($driver_id, $shipment_route_id)
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "shipment_route AS SRT WHERE SRT.driver_id ='$driver_id' AND SRT.shipment_route_id ='$shipment_route_id' ORDER BY FIELD(is_current, 'Y','N')";
		$record = $this->db->getRowRecord($sql);
        return $record;
    }
	
    public function getAssignedRouteOfDriverByRouteId($driver_id, $shipment_route_id)
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "shipment_route AS SRT WHERE SRT.status = 1 AND SRT.is_active  = 'Y' AND SRT.driver_accepted  = '0' AND SRT.driver_id ='$driver_id' AND SRT.shipment_route_id ='$shipment_route_id' ORDER BY FIELD(is_current, 'Y','N')";
        $record = $this->db->getRowRecord($sql);
        return $record;
    }
    
    public function getShipmentCustomerDetailByShipTicket($shipment_route_id,$driver_id,$shipment_tickets)
    {
        //$sql = "SELECT ST.*, ABT.postcode AS shipment_postcode, ABT.address_line1 AS shipment_address1, ABT.address_line2 AS shipment_address2, ABT.city AS shipment_customer_city, ABT.country AS shipment_customer_country, ABT.iso_code AS shipment_country_code FROM " . DB_PREFIX . "shipment AS ST INNER JOIN " . DB_PREFIX . "address_book AS ABT ON ABT.id=ST.address_id WHERE ST.shipment_routed_id = '$shipment_route_id' AND ST.assigned_driver = '$driver_id' AND ST.shipment_ticket IN('$shipment_tickets') ORDER BY shipment_highest_length DESC, shipment_highest_width DESC, shipment_highest_height DESC,   shipment_highest_weight DESC";
        $sql = "SELECT ST.* FROM " . DB_PREFIX . "shipment AS ST WHERE ST.shipment_routed_id = '$shipment_route_id' AND ST.assigned_driver = '$driver_id' AND ST.shipment_ticket IN('$shipment_tickets') ORDER BY shipment_highest_length DESC, shipment_highest_width DESC, shipment_highest_height DESC,   shipment_highest_weight DESC";
		$records = $this->db->getAllRecords($sql);
        return $records;
    }
    
    public function getAllParcelOfRoute($shipment_route_id,$shipment_ticket)
    {
        $sql = "SELECT SPT.* FROM " . DB_PREFIX . "shipment AS ST INNER JOIN " . DB_PREFIX . "shipments_parcel AS SPT ON SPT.shipment_ticket=ST.shipment_ticket WHERE ST.shipment_routed_id = '$shipment_route_id' AND ST.current_status = 'O' AND SPT.shipment_ticket IN('$shipment_ticket')";
        $records = $this->db->getAllRecords($sql);
        return $records;
		
    }
    
    public function getUserFirebaseProfile($userId)
	{
        $sql = "SELECT uid AS firebase_id FROM " . DB_PREFIX . "users AS UT WHERE UT.id = '$userId'";
		$record = $this->db->getOneRecord($sql);
		return $record;	
	}
    
    public function getWarehouseDetail($warehouse_id)
    {
        $sql = "SELECT name, phone, email, address_1, address_2, postcode, city, latitude, longitude FROM " . DB_PREFIX . "warehouse WHERE id = '$warehouse_id'";
        $record = $this->db->getRowRecord($sql);
        return $record;
    }
    
    public function getUserByEmail($email)
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "users AS UT WHERE email = '$email'";
        $record = $this->db->getRowRecord($sql);
        return $record;
    }
    
    public function getUserById($user_id)
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "users AS UT WHERE id = '$user_id'";
        $record = $this->db->getRowRecord($sql);
        return $record;
    }
    
    public function getShipmentDrop($shipment_routed_id)
    {
		
		/**************address will be fetched from shipment table (comment added by kavita 13feb2018)**************/
        $sql = "SELECT CA.shipment_id, CA.shipment_total_weight, CA.shipment_total_volume, CA.shipment_ticket, CA.is_receivedinwarehouse, CA.instaDispatch_docketNumber, 
        CA.shipment_total_item, CA.shipment_postcode, CA.shipment_address1, CA.shipment_address2, CA.shipment_address3, CA.shipment_required_service_starttime, 
        CA.shipment_required_service_endtime, CA.instaDispatch_loadGroupTypeCode, CA.instaDispatch_objectIdentity, CA.shipment_service_type, CA.instaDispatch_LoadGroupTypeCode, 
        CA.shipment_customer_country, CA.is_receivedinwarehouse, CA.warehousereceived_date, CA.is_driverpickupfromwarehouse, CA.driver_pickuptime, CA.estimatedtime, 
        CA.distancemiles, CA.shipment_routed_id, CA.shipment_latitude, CA.shipment_longitude, CA.shipment_latlong, CA.icargo_execution_order, CA.disputedate, DS.driver_id 
        FROM " . DB_PREFIX . "shipment AS CA INNER JOIN " . DB_PREFIX . "driver_shipment AS DS ON CA.shipment_ticket = DS.shipment_ticket 
        WHERE CA.shipment_routed_id = '$shipment_routed_id' AND CA.current_status  = 'O' AND DS.shipment_status  = 'N' AND
        (DS.shipment_accepted='Pending' OR DS.shipment_accepted='YES')";

        $records = $this->db->getAllRecords($sql);
        return $records;
    }
    
    public function getShipmentDropByShipmentTicket($shipment_routed_id,$ticket)
    {
        $sql = "SELECT CA.shipment_id, CA.shipment_total_weight, CA.shipment_total_volume, CA.shipment_ticket, CA.is_receivedinwarehouse, CA.instaDispatch_docketNumber, 
        CA.shipment_total_item, CA.shipment_postcode, CA.shipment_address1, CA.shipment_address2, CA.shipment_address3, CA.shipment_required_service_starttime, 
        CA.shipment_required_service_endtime, CA.instaDispatch_loadGroupTypeCode, CA.instaDispatch_objectIdentity, CA.shipment_service_type, CA.instaDispatch_LoadGroupTypeCode, 
        CA.shipment_customer_country, CA.is_receivedinwarehouse, CA.warehousereceived_date, CA.is_driverpickupfromwarehouse, CA.driver_pickuptime, CA.estimatedtime, 
        CA.distancemiles, CA.shipment_routed_id, CA.shipment_latitude, CA.shipment_longitude, CA.shipment_latlong, CA.icargo_execution_order, CA.disputedate, CA.assigned_driver AS driver_id 
        FROM " . DB_PREFIX . "shipment AS CA
        WHERE CA.shipment_routed_id = '$shipment_routed_id'  AND  CA.shipment_ticket IN('$ticket') AND CA.current_status  = 'O'";

        $records = $this->db->getAllRecords($sql);
        return $records;
    }
    
    public function getShipmentDropByShipmentTicketAfterCarded($shipment_routed_id,$ticket)
    {

        $sql = "SELECT CA.shipment_id, CA.shipment_total_weight, CA.shipment_total_volume, CA.shipment_ticket, CA.is_receivedinwarehouse, CA.instaDispatch_docketNumber, 
        CA.shipment_total_item, CA.shipment_postcode, CA.shipment_address1, CA.shipment_address2, CA.shipment_address3, CA.shipment_required_service_starttime, 
        CA.shipment_required_service_endtime, CA.instaDispatch_loadGroupTypeCode, CA.instaDispatch_objectIdentity, CA.shipment_service_type, CA.instaDispatch_LoadGroupTypeCode, 
        CA.shipment_customer_country, CA.is_receivedinwarehouse, CA.warehousereceived_date, CA.is_driverpickupfromwarehouse, CA.driver_pickuptime, CA.estimatedtime, 
        CA.distancemiles, CA.shipment_routed_id, CA.shipment_latitude, CA.shipment_longitude, CA.shipment_latlong, CA.icargo_execution_order, CA.disputedate, CA.assigned_driver AS driver_id 
        FROM " . DB_PREFIX . "shipment AS CA 
        WHERE CA.shipment_routed_id = '$shipment_routed_id'  AND  CA.shipment_ticket IN('$ticket')";

        $records = $this->db->getAllRecords($sql);
        return $records;
    }

    public function getShipmentRouteByShipmentRouteId($shipment_route_id)
    {
        $sql = "SELECT UT.uid AS uid, RT.shipment_route_id, RT.firebase_id AS firebase_id FROM " . DB_PREFIX . "shipment_route RT INNER JOIN " . DB_PREFIX . "users AS UT ON RT.driver_id = UT.id WHERE RT.shipment_route_id = $shipment_route_id";
        $record = $this->db->getRowRecord($sql);
        return $record;
    }

    public function getFirebaseIdByShipmentRouteId($shipment_route_id)
    {
        $sql = "SELECT RT.firebase_id AS firebase_id FROM " . DB_PREFIX . "shipment_route RT WHERE RT.shipment_route_id = $shipment_route_id";
        $record = $this->db->getRowRecord($sql);
        return $record;
    }

    public function getJobCountByShipmentRouteId($shipment_route_id){
        $sql = "SELECT COUNT(1) AS job_count FROM " . DB_PREFIX . "shipment RT WHERE RT.shipment_routed_id = $shipment_route_id AND RT.current_status IN ('O', 'Ca')";
        $record = $this->db->getRowRecord($sql);
        return $record;
    }
}