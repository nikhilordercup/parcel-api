<?php
class Shipment_Model
    {
    public static $_modelObj = NULL;
    public static $_db = NULL;

    public

    function __construct()
        {
        if (self::$_db == NULL)
            {
            self::$_db = new DbHandler();
            }
        $this->db = self::$_db;
        }

    public static

    function getInstanse()
        {
        if (self::$_modelObj == NULL)
            {
            self::$_modelObj = new Shipment_Model();
            }

        return self::$_modelObj;
        }

    public

    function startTransaction(){
        $this->db->startTransaction();
    }

    public

    function commitTransaction(){
        $this->db->commitTransaction();
    }

    public

    function rollBackTransaction(){
        $this->db->rollBackTransaction();
    }

    public

    function addContent($table_name, $data)
        {
        return $this->db->save($table_name, $data);
        }

    public

    function editContent($table_name, $data, $condition)
        {
        return $this->db->update($table_name, $data, $condition);
        }

    public

    function deleteContent($sql)
        {
        return $this->db->query($sql);
        }

    public

    function getAffectedRows()
        {
        return $this->db->getAffectedRows();
        }

    public

    function getAssignedShipmentData($componyId, $whareHouseId, $routeId)
        {
        $record = array();
        $sqldata = 'R1.shipment_latitude, R1.shipment_longitude,R1.instaDispatch_docketNumber as docket_no,R1.shipment_assigned_service_date as service_date,
                R1.instaDispatch_loadGroupTypeCode as shipment_type,R1.current_status,R1.instaDispatch_loadIdentity as reference_no,
                R1.shipment_total_attempt as attempt,R1.shipment_address1 AS address1,R1.icargo_execution_order as execution_order,
                R1.shipment_assigned_service_time as service_time,R1.shipment_total_weight as weight,R1.shipment_ticket as shipment_ticket,
                R1.shipment_service_type as service_type,R1.is_receivedinwarehouse as in_warehouse,R1.shipment_postcode as postcode,
                R1.shipment_customer_name as consignee_name';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment AS R1
            
             WHERE R1.shipment_routed_id  = '" . $routeId . "' 
             AND R1.warehouse_id  = '" . $whareHouseId . "' 
             AND R1.company_id  = '" . $componyId . "' 
             AND (R1.is_driver_accept  = 'Pending' OR R1.is_driver_accept  = 'YES')  
             AND (R1.current_status  = 'O' OR R1.current_status  = 'D' OR R1.current_status  = 'Ca' OR R1.current_status  = 'S'  )";
        
        $record = $this->db->getAllRecords($sql);
        return $record;
        }

    public

    function getAssignedShipmentDataByTicket($componyId, $whareHouseId, $routeId, $ticket)
        {
        $record = array();
        $sqldata = 'CA.instaDispatch_docketNumber as docket_no,CA.shipment_assigned_service_date as service_date,
                CA.instaDispatch_loadGroupTypeCode as shipment_type,CA.current_status,CA.instaDispatch_loadIdentity as reference_no,CA.shipment_total_attempt as attempt,
                CA.shipment_assigned_service_time as service_time,CA.shipment_total_weight as weight,CA.shipment_ticket as shipment_ticket,
                CA.shipment_service_type as service_type,CA.is_receivedinwarehouse as in_warehouse,CA.shipment_postcode as postcode, CA.shipment_address1 AS address1, CA.shipment_customer_name AS consignee_name, CA.icargo_execution_order AS execution_order, CA.current_status';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "driver_shipment AS R1
             INNER JOIN " . DB_PREFIX . "shipment AS CA  ON R1.shipment_ticket = CA.shipment_ticket
             WHERE R1.shipment_route_id  = '" . $routeId . "' 
             AND CA.warehouse_id  = '" . $whareHouseId . "' 
             AND CA.company_id  = '" . $componyId . "' 
             AND CA.shipment_ticket  = '" . $ticket . "' 
             AND (R1.shipment_accepted  = 'Pending' OR R1.shipment_accepted  = 'YES')  
             AND (CA.current_status  = 'O' OR CA.current_status  = 'D' OR CA.current_status  = 'Ca'  )";
        $record = $this->db->getAllRecords($sql);
        return $record;
        }

    public

    function getUnAssignedShipmentData($componyId, $whareHouseId, $routeId)
        {
        $record = array();
        $sqldata = 'CA.instaDispatch_docketNumber as docket_no,CA.shipment_assigned_service_date as service_date,
                CA.instaDispatch_loadGroupTypeCode as shipment_type,CA.current_status,CA.instaDispatch_loadIdentity as reference_no,CA.shipment_total_attempt as attempt,CA.shipment_address1 AS address1,CA.icargo_execution_order as execution_order,
                CA.shipment_assigned_service_time as service_time,CA.shipment_total_weight as weight,CA.shipment_ticket as shipment_ticket,
                CA.shipment_service_type as service_type,CA.is_receivedinwarehouse as in_warehouse,CA.shipment_postcode as postcode,CA.shipment_customer_name as consignee_name';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment AS CA
             WHERE CA.shipment_routed_id  = '" . $routeId . "' 
             AND CA.warehouse_id  = '" . $whareHouseId . "' 
             AND CA.company_id  = '" . $componyId . "' 
             AND (CA.is_shipment_routed  = '1')  
             AND CA.current_status  = 'S'";
        $record = $this->db->getAllRecords($sql);
        return $record;
        }

    public

    function getShipmentParcels($ticket)
        {
        $record = array();
        $sqldata = 'CP.parcel_id,CP.parcel_ticket,CP.instaDispatch_pieceIdentity as Consignment,CP.parcel_weight as Weight,
                   CP.parcel_height as Height,CP.parcel_length as Length,CP.parcel_width as Width,CP.shipment_ticket as shipment_ticket';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipments_parcel AS CP
                WHERE CP.shipment_ticket  IN(" . $ticket . ")";
        $record = $this->db->getAllRecords($sql);
        return $record;
        }

    public

    function getAssignRouteShipmentDetails($company_id)
        {
        $sqldata = "t1.customer_id, t1.`shipment_longitude`,t1.`shipment_latitude`,t1.shipment_customer_name,t1.shipment_address1,t1.icargo_execution_order,t1.`current_status`,
        t1.`shipment_id`,t1.shipment_address1,t1.`estimatedtime` AS estimated_time, t1.`distancemiles` AS distance_miles,t1.`current_status`,t1.`instaDispatch_loadGroupTypeCode`,t1.shipment_customer_country,t1.shipment_customer_city,t1.`shipment_address3`, t2.`name` AS driver_name, t1.`assigned_driver` AS assigned_driver_id,t1.`shipment_service_type`,t1.`assigned_driver`,t1.shipment_postcode,t1.`shipment_routed_id`,t1.`company_id`,t1.`warehouse_id`,t1.`shipment_ticket`, 'Assigned'";
		$sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment AS t1
               INNER JOIN " . DB_PREFIX . "users AS t2  ON t2.id = t1.assigned_driver
               WHERE t1.is_driver_assigned = 1 AND t1.`current_status`='O' AND t1.company_id = '" . $company_id . "' ORDER BY t1.shipment_executionOrder";
        $record = $this->db->getAllRecords($sql);
        return $record;
        }

    public

    function getAssignRouteShipmentDetailsByShipmentRouteId($company_id, $shipment_route_id, $driver_id)
        {
        $sqldata = "t1.shipment_customer_name,t1.customer_id,t1.`shipment_id`,t1.shipment_address1,t1.shipment_address2,t1.`estimatedtime` AS estimated_time, t1.`distancemiles` AS distance_miles,t1.`current_status`,t1.`instaDispatch_loadGroupTypeCode`,t1.shipment_customer_country,t1.shipment_customer_city,t1.`shipment_address3`, t2.`name` AS driver_name, t1.`assigned_driver` AS assigned_driver_id,t1.`shipment_service_type`,t1.`assigned_driver`,t1.shipment_postcode,t1.`shipment_routed_id`,t1.`company_id`,t1.`warehouse_id`,t1.`shipment_ticket`,'Assigned',t1.shipment_latitude,t1.shipment_longitude,t1.`icargo_execution_order`,t1.`shipment_total_item`,t1.`shipment_customer_name` AS consignee_name";
		$sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment AS t1
               INNER JOIN " . DB_PREFIX . "users AS t2  ON t2.id = t1.assigned_driver
               WHERE t1.is_driver_assigned = 1 AND (t1.`current_status`='O' OR t1.`current_status`='D' OR t1.`current_status`='Ca') AND t1.company_id = '" . $company_id . "' AND t1.shipment_routed_id = '$shipment_route_id' AND assigned_driver = '$driver_id' ORDER BY t1.shipment_executionOrder";
        $record = $this->db->getAllRecords($sql);
        return $record;
        }

    public function getActiveRoute($company_id){
        $sql = "SELECT * FROM " . DB_PREFIX . "shipment_route WHERE company_id='$company_id' AND is_active='Y' AND driver_id > 0";
        $records = $this->db->getAllRecords($sql);
        return $records;
    }

    public

    function getCompletedRouteShipmentDetailsByShipmentRouteId($company_id)
        {
        $record = array();
        $sqldata = "t1.`shipment_address1`,t1.`estimatedtime` AS estimated_time, t1.`distancemiles` AS distance_miles,t1.`current_status`,t1.`instaDispatch_loadGroupTypeCode`,t1.`shipment_customer_country`,t1.`shipment_customer_city`,t1.`shipment_address3`, t1.`assigned_driver` AS assigned_driver_id,t1.`shipment_service_type`,t1.`assigned_driver`,t1.`shipment_postcode`,
        t1.`shipment_routed_id`,t1.`company_id`,t1.`warehouse_id`,t1.`shipment_ticket`,'Assigned',t1.`shipment_latitude`,
        t1.`shipment_longitude`,t1.`icargo_execution_order`,t1.`shipment_total_item`";
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment AS t1
               
                WHERE current_status = 'D' AND is_driver_assigned = '1' AND shipment_routed_id!=0 AND company_id = '" . $company_id . "' ORDER BY `shipment_routed_id`,`shipment_executionOrder`";;
        $record = $this->db->getAllRecords($sql);
        return $record;
        }

    public

    function getUnAssignShipmentDetails($company_id)
        {
        $record = array();
        $sqldata = "t1.customer_id,t1.shipment_address1 AS `shipment_address1`,t1.`estimatedtime` AS estimated_time, t1.`distancemiles` AS distance_miles,t1.`current_status`,t1.`instaDispatch_loadGroupTypeCode`,t1.shipment_customer_country AS `shipment_customer_country`,t1.shipment_customer_city AS `shipment_customer_city`,t1.`shipment_address3`, t1.`assigned_driver` AS assigned_driver_id,t1.`shipment_service_type`,t1.`assigned_driver`,t1.shipment_postcode AS `shipment_postcode`,
        t1.`shipment_routed_id`,t1.`company_id`,t1.`warehouse_id`,t1.`shipment_ticket`,'Assigned',t1.shipment_latitude AS `shipment_latitude`,t1.shipment_longitude AS `shipment_longitude`,t1.`icargo_execution_order`,t1.`shipment_total_item`";
		$sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment AS t1
                WHERE current_status = 'S' AND is_driver_assigned = '0' AND shipment_routed_id!=0 AND company_id = '" . $company_id . "' ORDER BY `shipment_routed_id`,`shipment_executionOrder`";;

        $record = $this->db->getAllRecords($sql);
        return $record;
        }

    public

    function _get_assigned_route_detail($shipment_route_id)
        {
        $record = array();
        $sqldata = 'R4.instaDispatch_loadGroupTypeCode,R1.*,R2.name,R2.uid';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment_route  as R1
        INNER JOIN " . DB_PREFIX . "users AS R2  ON R1.driver_id = R2.id
        INNER JOIN " . DB_PREFIX . "driver_vehicle AS R3  ON R3.driver_id = R1.driver_id
        INNER JOIN " . DB_PREFIX . "shipment as R4 on R4.shipment_routed_id=R1.shipment_route_id
        WHERE shipment_route_id = $shipment_route_id GROUP BY R4.instaDispatch_loadGroupTypeCode";
        $record = $this->db->getRowRecord($sql);

        if(count($record)>0){
            $vehicle = $this->getVehicleIdByDriverId($record["driver_id"]);
            $record["vehicle_id"] = $vehicle["vehicle_id"];
        }
        return $record;
        }

    public

    function getShipmentStatusDetails($ticket)
        {
        $record = array();
        $sqldata = 'R1.*,R2.name,R3.route_name';
		$sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment AS R1
                LEFT JOIN " . DB_PREFIX . "users AS R2  ON R1.assigned_driver = R2.id
                LEFT JOIN " . DB_PREFIX . "shipment_route AS R3  ON R1.shipment_routed_id = R3.shipment_route_id
                WHERE R1.shipment_ticket IN(" . $ticket . ")";
		$record = $this->db->getRowRecord($sql);
        return $record;
        }

    public

    function getAllShipmentDetailsByTicket($ticket)
        {
        $record = array();
        $sqldata = 'R1.*';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment AS R1
        WHERE R1.shipment_ticket IN(" . $ticket . ")";
        $record = $this->db->getAllRecords($sql);
        return $record;
        }

    public

    function getShipmentParcelStatusDetails($ticket)
        {
        $sqldata = 'R1.*,R0.parcel_ticket,R0.instaDispatch_pieceIdentity,R0.instaDispatch_loadIdentity as instaDispatch_loadIdentity_parcel,
                   R2.name,R3.route_name';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment AS R1
                LEFT JOIN " . DB_PREFIX . "shipments_parcel AS R0  ON R0.shipment_ticket = R1.shipment_ticket
                LEFT JOIN " . DB_PREFIX . "users AS R2 ON R1.assigned_driver = R2.id 
                LEFT JOIN " . DB_PREFIX . "shipment_route AS R3  ON R1.shipment_routed_id = R3.shipment_route_id
                WHERE R1.shipment_ticket  IN( " . $ticket . ") AND R2.user_level= 4";
        $record = $this->db->getAllRecords($sql);
        return $record;
        }

    public

    function getCheckAllshipmentinWareHouse($ticket)
        {
        $sql = "SELECT count(1) as exist FROM " . DB_PREFIX . "shipment AS R1 WHERE R1.shipment_ticket IN(" . $ticket . ") AND R1.current_status = 'O' AND is_receivedinwarehouse = 'NO'";
        $record = $this->db->getOneRecord($sql);
        return $record['exist'];
        }

    public

    function getOperationalShipmentDetails($ticket)
        {
        $record = array();
        $sqldata = 'R1.*';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment AS R1
               WHERE R1.shipment_ticket IN(" . $ticket . ") AND (R1.current_status = 'O' OR R1.current_status  = 'Ca') 
               AND is_receivedinwarehouse = 'YES'";
        $record = $this->db->getAllRecords($sql);
        return $record;
        }

    public

    function getDriverRunsheetExportData($driverId, $routeid)
        {
        $record = array();
        $sqldata = 'R1.shipment_accepted,R1.assigned_date,
                   R2.name,
                   R3.route_name,
                   CA.shipment_ticket,CA.instaDispatch_objectIdentity,CA.instaDispatch_docketNumber,CA.shipment_total_item,
                   CA.shipment_required_service_date,CA.shipment_required_service_starttime,CA.shipment_required_service_endtime,
                   CA.shipment_postcode';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "driver_shipment AS R1
                    
                    LEFT JOIN " . DB_PREFIX . "users AS R2 ON R1.driver_id = R2.id 
                    LEFT JOIN " . DB_PREFIX . "shipment_route AS R3  ON R1.shipment_route_id = R3.shipment_route_id
                    LEFT JOIN " . DB_PREFIX . "shipment AS CA ON R1.shipment_ticket = CA.shipment_ticket
                    WHERE R1.driver_id = " . $driverId . "  
                    AND R1.shipment_route_id = " . $routeid . " 
                    AND  R1.shipment_accepted  != 'Release'  
                    AND CA.is_receivedinwarehouse = 'YES'  
                    AND (CA.current_status = 'O' || CA.current_status  = 'Ca') 
                    ORDER BY R1.execution_order";
        $record = $this->db->getAllRecords($sql);
        return $record;
        }

    public

    function getShipmentAdditionalDetailsOneRow($ticket)
        {
        $record = array();
        $sqldata = 'GROUP_CONCAT(keydata,":",valuedata) as data';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipments_additionalinfo AS R1
                WHERE R1.ticket IN(" . $ticket . ")
                group by R1.ticket order by R1.id";
        $record = $this->db->getOneRecord($sql);
        return $record['data'];
        }

    public

    function moreShipExistinThisRouteforDriverFromOperation($driver, $routeId)
        {
        $sql = "SELECT COUNT(1) as num FROM " . DB_PREFIX . "driver_shipment AS R1
                LEFT JOIN " . DB_PREFIX . "shipment AS R2  ON R1.shipment_ticket = R2.shipment_ticket
                WHERE R1.shipment_route_id = " . $routeId . " 
                AND R1.driver_id = " . $driver . " 
                AND R1.is_driveraction_complete = 'N'";
        $record = $this->db->getOneRecord($sql);
        return $record['num'];
        }

    public

    function moreShipExistinThisRouteforDriverFromOperationWithWarehouse($driver, $routeId)
        {
        $sql = "SELECT count(1) as num FROM " . DB_PREFIX . "driver_shipment AS R1
                LEFT JOIN " . DB_PREFIX . "shipment AS R2  ON R1.shipment_ticket = R2.shipment_ticket
                WHERE R1.shipment_route_id = " . $routeId . " 
                AND R1.driver_id = " . $driver . " 
                AND R1.is_driveraction_complete = 'N'
                AND R2.is_receivedinwarehouse = 'YES'";
        $record = $this->db->getOneRecord($sql);
        return $record['num'];
        }

    public

    function getMoveToOtherRouteAcions($companyId)
        {
        $record = array();
        $sqldata = 'R1.shipment_route_id,R1.route_name,R2.name,R2.id as driverid';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment_route AS R1
                LEFT JOIN " . DB_PREFIX . "users AS R2  ON R1.driver_id = R2.id
                WHERE R1.is_active  = 'Y' AND R1.company_id = " . $companyId . " ";
        $record = $this->db->getAllRecords($sql);
        return $record;
        }

    public

    function getShipmentDetails($ticket)
        {
        $record = array();
        $sqldata = 'R1.shipment_ticket,R1.shipment_service_type,R1.shipment_total_attempt,R1.current_status,R1.is_shipment_routed,R1.shipment_routed_id,R1.is_driver_assigned,R1.is_driver_accept,R1.assigned_driver,R1.assigned_vehicle,R1.last_history_id,R2.execution_order,R2.distancemiles,R2.estimatedtime,R1.shipment_postcode,R1.shipment_address1,R1.shipment_address2,R1.shipment_address3';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment AS R1 
        LEFT JOIN " . DB_PREFIX . "driver_shipment AS R2  ON R1.shipment_ticket = R2.shipment_ticket WHERE R1.shipment_ticket IN('$ticket')";
        $record = $this->db->getAllRecords($sql);
        return $record;
        }

    public

    function getActiveDrivers($company_id)
        {
        $record = array();
        $sqldata = 't1.id AS driver_id, t1.name AS driver_name';
        $sql = "SELECT  " . $sqldata . " FROM " . DB_PREFIX . "users AS t1 INNER JOIN " . DB_PREFIX . "company_users AS t2 ON t1.id = t2.user_id WHERE t1.user_level = 4 AND t1.status = 1 AND t2.company_id = " . $company_id . " ORDER BY driver_name";
        $records = $this->db->getAllRecords($sql);
        return $records;
        }

    public

    function _get_driver_assigned_vehicle($driverId)
        {
        $record = array();
        $sql = "SELECT vehicle_id FROM " . DB_PREFIX . "driver_vehicle WHERE driver_id = " . $driverId;
        $records = $this->db->getRowRecord($sql);
        return $records;
        }

    public

    function _get_all_tickets_by_route($routeId)
        {
        $record = array();
        $sql = "SELECT shipment_ticket,icargo_execution_order as execution_order,distancemiles,estimatedtime FROM " . DB_PREFIX . "shipment WHERE shipment_routed_id = " . $routeId;
        $records = $this->db->getAllRecords($sql);
        return $records;
        }

    public

    function getAllowedFailActionsforController($company_id)
        {
        $record = array();
        $sqldata = 'R1.status_code,R1.status_name';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment_status as R1 WHERE  R1.is_show  = '1' AND R1.status_code  != 'DELIVERED' AND R1.status  = '1'";
        $records = $this->db->getAllRecords($sql);
        return $records;
        }

    public

    function getDriverCommentByTicket($ticket)
        {
        $record = array();
        $sqldata = 'SH.driver_comment';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment AS SH
                WHERE SH.shipment_ticket = '" . $ticket . "'";
        $record = $this->db->getOneRecord($sql);
        return $record['driver_comment'];
        }

    public

    function getconfigurationData($companyId)
        {
        $sql = "SELECT R1.configuration_json FROM " . DB_PREFIX . "configuration AS R1
                WHERE R1.company_id = '" . $companyId . "'";
        $record = $this->db->getOneRecord($sql);
        return $record['configuration_json'];
        }

    public

    function moreShipExistinThisSavedRoute($routeId)
        {
        $sql = "SELECT count(1) as num FROM " . DB_PREFIX . "shipment AS SH
                WHERE SH.shipment_routed_id = " . $routeId . "
                AND SH.current_status = 'S'";
        $record = $this->db->getOneRecord($sql);
        return $record['num'];
        }

    public

    function getShipmentCountsDb($company_id, $warehouse_id)
        {
        $record = array();
        $sqldata = 'SP.instaDispatch_loadGroupTypeCode as shiptype,count(1) as num';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment AS SP
                WHERE SP.company_id = " . $company_id . "
                AND SP.warehouse_id = " . $warehouse_id . "
                AND SP.current_status = 'C'
                GROUP BY SP.instaDispatch_loadGroupTypeCode";
        $record = $this->db->getAllRecords($sql);
        return $record;
        }

    public

    function getAllParceldataByTicket($ticket)
        {
        $record = array();
        $sqldata = 'CP.shipment_ticket,CP.parcel_ticket,CP.parcel_weight,CP.parcel_height,CP.parcel_length,CP.parcel_width,
                CP.parcel_type,CP.create_date,CP.dataof,CP.instaDispatch_pieceIdentity';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipments_parcel AS CP
                WHERE CP.shipment_ticket = '" . $ticket . "'";
        $record = $this->db->getAllRecords($sql);
        return $record;
        }

    public

    function getAcceptRejectsShipmentStatusHistory($ticket)
        {
        $record = array();
        $sqldata = 'R1.*,R2.name';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "driver_accept_reject_history AS R1
                LEFT JOIN " . DB_PREFIX . "users AS R2  ON R1.driver_id = R2.id
                WHERE R1.shipment_ticket = '" . $ticket . "'";
        $record = $this->db->getAllRecords($sql);
        return $record;
        }

    public

    function getShipmentCurrentStatusAndDriverId($ticket)
        {
        $record = array();
        $sqldata = 'R1.current_status,R1.assigned_driver';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment AS R1
                WHERE R1.shipment_ticket = '" . $ticket . "'";
        $record = $this->db->getRowRecord($sql);
        return $record;
        }

    public

    function getShipmentLifeCycleHistory($ticket)
        {
        $record = array();
        $sqldata = 'R1.*,R2.name,R3.route_name';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment_life_history AS R1
                LEFT JOIN " . DB_PREFIX . "users AS R2  ON R1.driver_id = R2.id
                LEFT JOIN " . DB_PREFIX . "shipment_route AS R3  ON R1.route_id = R3.shipment_route_id
                WHERE R1.shipment_ticket = '" . $ticket . "'
                ORDER BY R1.his_id";
        $record = $this->db->getAllRecords($sql);
        return $record;
        }

    public

    function getExistingPodData($ticket)
        {
        $record = array();
        $sqldata = 'R1.*';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipments_pod AS R1
                 WHERE R1.shipment_ticket = '" . $ticket . "'";
        $record = $this->db->getAllRecords($sql);
        return $record;
        }

    public

    function getShipmentAdditionalDetails($ticket)
        {
        $record = array();
        $sqldata = 'R1.keydata,R1.valuedata';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipments_additionalinfo AS R1
                  WHERE R1.ticket = '" . $ticket . "'
                  ORDER BY R1.id";
        $record = $this->db->getAllRecords($sql);
        return $record;
        }

    public

    function getShipmentDetailsByReference($refNo)
        {
        $sql = "SELECT CA.* FROM " . DB_PREFIX . "shipment AS CA
              WHERE CA.instaDispatch_jobIdentity = '" . $refNo . "'
              AND CA.shipment_service_type = 'P'
              ORDER BY CA.shipment_id Desc";
        $record = $this->db->getAllRecords($sql);
        return $record;
        }

    public

    function getShipmentStatusHistory($historyId)
        {
        $record = array();
        $sqldata = 'R1.*,R2.name';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment_history AS R1
                    LEFT JOIN " . DB_PREFIX . "users AS R2  ON R1.driver_id = R2.id
                    WHERE R1.shipment_history_id = '" . $historyId . "'";
        $record = $this->db->getRowRecord($sql);
        return $record;
        }

    public

    function getCompletedRouteShipmentDetailsByShipmentRouteIdAndSearchDate($company_id, $search_date, $warehouse_id)
        {
        $sql = "SELECT RT.route_name, RT.assign_start_time, RT.shipment_route_id, UT.name AS assigned_driver, RT.completed_date AS service_date FROM " . DB_PREFIX . "shipment_route AS RT 
        INNER JOIN " . DB_PREFIX . "users AS UT ON UT.id=RT.driver_id 
        WHERE DATE_FORMAT(RT.completed_date,\"%Y-%m-%d\") = '$search_date' AND company_id = '$company_id' AND warehouse_id = '$warehouse_id'";
        $record = $this->db->getAllRecords($sql);
        return $record;
        }

    public

    function getShipmentsByShipmentRouteId($shipment_route_id)
        {
        $sql = "SELECT ST.customer_id,ST.shipment_postcode AS postcode, ST.shipment_address1 AS address_line1, ST.shipment_latitude AS latitude, ST.shipment_longitude AS longitude, ST.shipment_id, ST.instaDispatch_loadGroupTypeCode, ST.shipment_ticket, ST.`icargo_execution_order`, ST.`shipment_service_type` FROM " . DB_PREFIX . "shipment AS ST
               WHERE ST.shipment_routed_id = '$shipment_route_id' ORDER BY shipment_executionOrder";	   
        $records = $this->db->getAllRecords($sql);
        return $records;
        }

    public

    function getParcelCountByShipmentId($shipment_id)
        {
        $sql = "SELECT COUNT(1) AS parcel_count FROM " . DB_PREFIX . "shipments_parcel AS PT WHERE shipment_id IN($shipment_id)";
        $records = $this->db->getRowRecord($sql);
        return $records;
        }

    public

    function getShipmentRouteByShipmentRouteId($shipment_route_id)
        {
        $sql = "SELECT * FROM " . DB_PREFIX . "shipment_route AS RT WHERE shipment_route_id = $shipment_route_id";
        $records = $this->db->getRowRecord($sql);
        return $records;
        }

    public

    function getLastDropExecutionOrderOfRoute($shipment_route_id)
        {
        //$sql = "SELECT execution_order FROM " . DB_PREFIX . "driver_shipment AS RT WHERE shipment_route_id = $shipment_route_id ORDER BY execution_order DESC";
        $sql = "SELECT icargo_execution_order AS execution_order FROM " . DB_PREFIX . "shipment AS ST WHERE ST.shipment_routed_id = $shipment_route_id ORDER BY icargo_execution_order DESC";
        $record = $this->db->getOneRecord($sql);
        return $record;
        }

    public

    function getShipmentDetailsByShipmentTicket($ticket)
        {
        
	$sql = "SELECT R1.shipment_ticket,R1.shipment_service_type,R1.shipment_total_attempt,R1.current_status,R1.is_shipment_routed,R1.shipment_routed_id,R1.is_driver_assigned,R1.is_driver_accept,R1.assigned_driver,R1.assigned_vehicle,R1.last_history_id,R1.distancemiles,R1.estimatedtime,R1.shipment_postcode AS shipment_postcode,R1.shipment_address1 AS shipment_address1,R1.shipment_address2 AS shipment_address2,R1.shipment_address3 FROM " . DB_PREFIX . "shipment AS R1 WHERE R1.shipment_ticket IN('$ticket')";
        $record = $this->db->getAllRecords($sql);
        return $record;
        }

    public

    function setWarehouseStatusYesByShipmentTicketAndRouteId($shipment_ticket, $shipment_route_id){
        return $this->db->update("shipment", array("is_receivedinwarehouse"=>"YES"), "shipment_ticket IN ('$shipment_ticket') AND shipment_routed_id = $shipment_route_id AND shipment_service_type = 'D'");
    }

    public

    function getShipmentTracking($loadIdentity){
        $sql = "SELECT `ST`.`shipment_ticket`, `ST`.`shipment_instruction`,`ST`.`assigned_driver` AS `driver_id`,`ST`.`shipment_executionOrder` AS `execution_order`,`ST`.`warehouse_id` AS `warehouse_id`,`SLHT`.`internel_action_code` AS `status_code`,`ST`.`shipment_postcode` AS `postcode`, `ST`.`shipment_address1` AS `address_line1` ,`ST`.`shipment_latitude` AS `latitude`, `ST`.`shipment_longitude` AS `longitude`, date_format(`SLHT`.`create_date`, \"%b/%d/%Y\") as `create_date`, date_format(`SLHT`.`create_time`, \"%H:%i\") AS `create_time` FROM `" . DB_PREFIX . "shipment` AS `ST` INNER JOIN `" . DB_PREFIX . "shipment_life_history` AS `SLHT` ON `ST`.`shipment_ticket` = `SLHT`.`shipment_ticket` WHERE `SLHT`.`instaDispatch_loadIdentity`='$loadIdentity' AND (`SLHT`.`internel_action_code` = 'COLLECTIONSUCCESS' OR `SLHT`.`internel_action_code` = 'DELIVERYSUCCESS')";
        $records = $this->db->getAllRecords($sql);
        return $records;
    }

    public

    function getWarehouseInfo($warehouseId){
        $sql = "SELECT `WT`.`latitude` AS `latitude`,`WT`.`longitude` AS `longitude` FROM `" . DB_PREFIX . "warehouse` AS `WT` WHERE `WT`.`id`='$warehouseId'";
        $record = $this->db->getRowRecord($sql);
        return $record;
    }

    public

    function getDriverCurrentLocation($driverId, $createDate){
        $sql = "SELECT `DT`.`latitude` AS `latitude`,`DT`.`longitude` AS `longitude` FROM `" . DB_PREFIX . "api_driver_tracking` AS `DT` WHERE `DT`.`driver_id`='$driverId' AND DATE_FORMAT(`DT`.`create_date`, \"%Y-%m-%d\")='$createDate'";
        $record = $this->db->getRowRecord($sql);
        return $record;
    }

    public

    function getShipmentPod($shipment_ticket){
        $sql = "SELECT `value` as `pod` FROM `" . DB_PREFIX . "shipments_pod` AS `PODT` WHERE `PODT`.`shipment_ticket`='$shipment_ticket'";
        $record = $this->db->getRowRecord($sql);
        return $record;
    }

    public

    function getVehicleIdByDriverId($driver_id){
        $sql = "SELECT `vehicle_id` as `vehicle_id` FROM `" . DB_PREFIX . "driver_vehicle` AS `DVT` WHERE `DVT`.`driver_id`='$driver_id'";
        $record = $this->db->getRowRecord($sql);
        return $record;
    }

    public

    function releaseShipment($shipment_ticket){
        return $this->db->update("shipment", array(
            "is_driver_assigned"=>"0",
            "is_driver_accept"=>"Pending",
            "assigned_driver"=>"0",
            "assigned_vehicle"=>"0",
            "current_status"=>"S",
            "distancemiles"=>"0.00",
            "estimatedtime"=>"0.00"
        ), "shipment_ticket IN ('$shipment_ticket')");
    }

    public

    function releaseShipmentFromDriver($shipment_ticket){
        return $this->db->update("driver_shipment", array(
            "shipment_accepted"=>"Release",
            "is_driveraction_complete"=>"Y"
        ), "shipment_ticket IN ('$shipment_ticket')");
    }

    public

    function releaseShipmentFromRoute($shipment_route_id){
        return $this->db->update("shipment_route", array(
            "assign_start_time"=>"00:00:00",
            "actual_start_time"=>"00:00:00",
            "is_current"=>"N",
            "driver_accepted"=>"0",
            "is_route_started"=>"0",
            "driver_id"=>"0",
            "is_active"=>"Y",
            "status"=>"1"
        ), "shipment_route_id = '$shipment_route_id'");
    }

    public function saveUserCredentialInfo($param, $user_id){        
        return $this->db->update("users", array("device_token_id"=>$param["device_token_id"]), "id='$user_id'");
    }

    public

    function getCustomerById($user_id){
        $sql = "SELECT `UT`.name as `name`, uid as uid FROM `" . DB_PREFIX . "users` AS `UT` WHERE `UT`.`id` IN('$user_id')";
        $records = $this->db->getAllRecords($sql);
        return $records;
    }

    public

    function saveRoutePostId($post_id, $shipment_route_id){
        return $this->db->update("shipment_route", array("firebase_id"=>$post_id), "shipment_route_id='$shipment_route_id'");
    }

    public

    function findShipmentByShipmentTicket($shipment_ticket, $shipment_route_id){
        $sql = "SELECT `shipment_ticket` AS `shipment_ticket` FROM `" . DB_PREFIX . "shipment` AS `ST` WHERE `ST`.`shipment_ticket` IN('$shipment_ticket') AND `shipment_routed_id`='$shipment_route_id' AND (current_status='O' OR current_status='Ca')";
        $records = $this->db->getAllRecords($sql);
        return $records;
    }

    public

    function findShipmentByShipmentRouteIdAndDriverId($shipment_route_id, $driver_id)
        {
        $sql = "SELECT `shipment_ticket` AS `shipment_ticket`, current_status FROM " . DB_PREFIX . "shipment AS RT WHERE shipment_routed_id = '$shipment_route_id' AND assigned_driver='$driver_id' AND current_status!='D'";
        $records = $this->db->getAllRecords($sql);
        return $records;
        }

    public

    function findAssignedDriverAndRouteInfo($shipment_route_id)
        {
        $sql = "SELECT RT.route_name AS route_name, UT.name AS driver_name FROM " . DB_PREFIX . "shipment_route AS RT INNER JOIN " . DB_PREFIX . "users AS UT ON RT.driver_id = UT.id WHERE shipment_route_id = $shipment_route_id";
        $records = $this->db->getRowRecord($sql);
        return $records;
        }
		
	public

    function findAllUndeliveredShipmentOfRoute($routeId)
        {
        $record = array();
        $sql = "SELECT shipment_ticket,icargo_execution_order as execution_order,distancemiles,estimatedtime FROM " . DB_PREFIX . "shipment WHERE shipment_routed_id = '$routeId' AND current_status!='D'";
        $records = $this->db->getAllRecords($sql);
        return $records;
        }
		
	public

    function findShipmentCurrentStatus($shipment_ticket)
        {
        $record = array();
        $sql = "SELECT current_status AS current_status FROM " . DB_PREFIX . "shipment WHERE shipment_ticket = '$shipment_ticket'";
        $record = $this->db->getRowRecord($sql);
        return $record;
        }

    public

    function findAssignedLoadIdentityByShipmentTicket($shipment_ticket)
        {
        $record = array();
        $sql = "SELECT instaDispatch_loadIdentity AS load_identity, instaDispatch_loadGroupTypeCode AS load_type, shipment_ticket AS shipment_ticket, assigned_driver AS assigned_driver, shipment_routed_id AS shipment_route_id, company_id AS company_id, warehouse_id AS warehouse_id FROM " . DB_PREFIX . "shipment WHERE shipment_ticket IN('$shipment_ticket')";/*assigned_driver > 0 AND */
        $records = $this->db->getAllRecords($sql);
        return $records;
        }

    public

    function findNotCollectedShipmentCountByLoadIdentity($load_identity){
        $sql = "SELECT COUNT(1) AS shipment_count FROM " . DB_PREFIX . "shipment where instaDispatch_loadIdentity='$load_identity' AND current_status='C' AND shipment_service_type='P'";
        return $this->db->getOneRecord($sql);
    }

    public

    function findCardedCollectedShipmentCountByLoadIdentity($load_identity){
        $sql = "SELECT COUNT(1) AS shipment_count FROM " . DB_PREFIX . "shipment where instaDispatch_loadIdentity='$load_identity' AND current_status='Ca' AND shipment_service_type='P'";
        return $this->db->getOneRecord($sql);
    }

    public

    function findCollectedShipmentCountByLoadIdentity($load_identity){
        $sql = "SELECT COUNT(1) AS shipment_count FROM " . DB_PREFIX . "shipment where instaDispatch_loadIdentity='$load_identity' AND current_status='D' AND shipment_service_type='P'";
        return $this->db->getOneRecord($sql);
    }

    public

    function findAllCollectionShipmentCountByLoadIdentity($load_identity){
        $sql = "SELECT COUNT(1) AS shipment_count FROM " . DB_PREFIX . "shipment where instaDispatch_loadIdentity='$load_identity' AND shipment_service_type='P'";
        return $this->db->getOneRecord($sql);
    }



    public

    function findNotDeliveredShipmentCountByLoadIdentity($load_identity){
        $sql = "SELECT COUNT(1) AS shipment_count FROM " . DB_PREFIX . "shipment where instaDispatch_loadIdentity='$load_identity' AND current_status='C' AND shipment_service_type='D'";
        return $this->db->getOneRecord($sql);
    }

    public

    function findCardedDeliveryShipmentCountByLoadIdentity($load_identity){
        $sql = "SELECT COUNT(1) AS shipment_count FROM " . DB_PREFIX . "shipment where instaDispatch_loadIdentity='$load_identity' AND current_status='Ca' AND shipment_service_type='D'";
        return $this->db->getOneRecord($sql);
    }

    public

    function findDeliveredShipmentCountByLoadIdentity($load_identity){
        $sql = "SELECT COUNT(1) AS shipment_count FROM " . DB_PREFIX . "shipment where instaDispatch_loadIdentity='$load_identity' AND current_status='D' AND shipment_service_type='D'";
        return $this->db->getOneRecord($sql);
    }

    public

    function findAllDeliveryShipmentCountByLoadIdentity($load_identity){
        $sql = "SELECT COUNT(1) AS shipment_count FROM " . DB_PREFIX . "shipment where instaDispatch_loadIdentity='$load_identity' AND shipment_service_type='D'";
        return $this->db->getOneRecord($sql);
    }
}
?>