<?php
    class Common{
        
        public function __construct()
        {
            static $inst = null;
            if ($inst === null) {
                $inst = new DbHandler();
            }
            $this->db = $inst;
        }
        
        public function getDropName($param, $bool=false)
        {
            /*if($bool)
                $drop = preg_replace('/#|\$|\/|\[|\]|\./','',$param['postcode']." ".$param['address_1']);
            else
                $drop = $param['postcode']." ".$param['address_1'];
            return $drop;*/
            return $param['postcode'];
        }
        
        public function getShipmentParcelStatusDetail($ticket) 
        {
            //$sql = "SELECT T2.parcel_ticket, T2.instaDispatch_pieceIdentity, T2.instaDispatch_loadIdentity AS instaDispatch_loadIdentity_parcel FROM `" . DB_PREFIX . "shipment` AS T1 LEFT JOIN `" . DB_PREFIX . "shipments_parcel` AS T2 ON T2.shipment_ticket = T1.shipment_ticket WHERE `T1`.`shipment_ticket` = '$ticket'";
            $sql = "SELECT T2.parcel_ticket, T2.instaDispatch_pieceIdentity, T2.instaDispatch_loadIdentity AS instaDispatch_loadIdentity_parcel FROM `" . DB_PREFIX . "shipments_parcel` AS T2 WHERE `T2`.`shipment_ticket` = '$ticket'";
            $records = $this->db->getAllRecords($sql);
            return $records;
        }



        public function getShipmentStatusDetail($ticket)
        {
            $sql = "SELECT T2.instaDispatch_loadIdentity AS instaDispatch_loadIdentity, shipment_latitude AS shipment_latitude, shipment_longitude AS shipment_longitude FROM `" . DB_PREFIX . "shipment` AS T2 WHERE `T2`.`shipment_ticket` = '$ticket'";
            $record = $this->db->getRowRecord($sql);
            return $record;
        }

        public function addShipmentlifeHistory($tickets, $action, $driver_id, $route_id, $company_id, $warehouse_id, $action_code, $action_taken_by)
        {
            $tickets = str_replace('"','',$tickets);
            $all_parcel_details = $this->getShipmentParcelStatusDetail($tickets);
            if (count($all_parcel_details) > 0) {
                foreach ($all_parcel_details as $shipdetails) {

                    $parcel_ticket = isset($shipdetails['parcel_ticket']) ? $shipdetails['parcel_ticket'] : '';

                    $piece_identity = isset($shipdetails['instaDispatch_pieceIdentity']) ? $shipdetails['instaDispatch_pieceIdentity'] : '';

                    $loadIdentity_parcel = isset($shipdetails['instaDispatch_loadIdentity_parcel']) ? $shipdetails['instaDispatch_loadIdentity_parcel'] : $shipdetails['instaDispatch_loadIdentity'];

                    $shipmentHistoryData = array();
                    $shipmentHistoryData['shipment_ticket'] = $tickets;
                    $shipmentHistoryData['parcel_ticket'] = $parcel_ticket;
                    $shipmentHistoryData['instaDispatch_pieceIdentity'] = $piece_identity;
                    $shipmentHistoryData['instaDispatch_loadIdentity'] = $loadIdentity_parcel;
                    $shipmentHistoryData['create_date'] = date("Y-m-d");
                    $shipmentHistoryData['create_time'] = date("H:m:s");
                    $shipmentHistoryData['actions'] = $action;
                    $shipmentHistoryData['internel_action_code'] = $action_code;
                    $shipmentHistoryData['driver_id'] = $driver_id;
                    $shipmentHistoryData['route_id'] = $route_id;
                    $shipmentHistoryData['action_taken_by'] = $action_taken_by;
                    $shipmentHistoryData['warehouse_id'] = $warehouse_id;
                    $shipmentHistoryData['company_id'] = $company_id;
                    $shipmentHistoryData['lattitude'] = 0.000000000;
                    $shipmentHistoryData['longitude'] = 0.000000000;
                    $this->db->save('shipment_life_history', $shipmentHistoryData);
                }
            }else {
                    $loadIdentity = $this->getShipmentStatusDetail($tickets);
                    $shipmentHistoryData = array();
                    $shipmentHistoryData['shipment_ticket'] = $tickets;
                    $shipmentHistoryData['parcel_ticket'] = "";
                    $shipmentHistoryData['instaDispatch_pieceIdentity'] = "";
                    $shipmentHistoryData['instaDispatch_loadIdentity'] = $loadIdentity["instaDispatch_loadIdentity"];
                    $shipmentHistoryData['create_date'] = date("Y-m-d");
                    $shipmentHistoryData['create_time'] = date("H:m:s");
                    $shipmentHistoryData['actions'] = $action;
                    $shipmentHistoryData['internel_action_code'] = $action_code;
                    $shipmentHistoryData['driver_id'] = $driver_id;
                    $shipmentHistoryData['route_id'] = $route_id;
                    $shipmentHistoryData['action_taken_by'] = $action_taken_by;
                    $shipmentHistoryData['warehouse_id'] = $warehouse_id;
                    $shipmentHistoryData['company_id'] = $company_id;

                    $shipmentHistoryData['lattitude'] = $loadIdentity["shipment_latitude"];
                    $shipmentHistoryData['longitude'] = $loadIdentity["shipment_longitude"];
                    $this->db->save('shipment_life_history', $shipmentHistoryData);
            }
            return true;
        }
		
		public function getAddressBookSearchString($arr)
        {
           return $arr->address_1.$arr->address_2.$arr->postcode.$arr->city.$arr->state.$arr->country;
        }

        public function countryList(){
            $sql = "SELECT * FROM `" . DB_PREFIX . "countries`";
            $records = $this->db->getAllRecords($sql);
            return $records;
        }
    }
?>