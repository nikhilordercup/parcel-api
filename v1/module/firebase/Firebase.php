<?php
class Firebase{

    private $_shipment_route_id;

    private $_driver_id;

    private $_shipment_type = null;

    protected $_get_drop_from = "route";

    protected $_shipment_tickets = array();

    public function __construct($params)
    {
        if(isset($params['shipment_route_id'])){
            $this->_setRouteId($params['shipment_route_id']);
        }

        if(isset($params['driver_id'])){
            $this->_setDriverId($params['driver_id']);
        }

        $this->modelObj = new Firebase_Model_Rest();

        $this->commonObj = new Common();

        return $this;
    }

    protected function _setRouteId($v)
    {
        $this->_shipment_route_id = $v;
    }

    protected function _setDriverId($v)
    {
        $this->_driver_id = $v;
    }

    protected function _getRouteId()
    {
        return $this->_shipment_route_id;
    }

    protected function _getDriverId()
    {
        return $this->_driver_id;
    }

    protected function _getShipmentTickets()
    {
        return $this->_shipment_tickets;
    }

    private function _formate_date($input, $pattern)
    {
        if(date('Y', strtotime($input))!=1970)
            return date($pattern, strtotime($input));
        else
            return "1970-01-01 00:00:00";
    }

    private function _getRouteDrop($param, $bool = true)
    {
        return $this->commonObj->getDropName($param, $bool);
    }

    public function withdrawShipmentFromRoute()
	{
        $this->_shipment_type = "withdraw_shipment";
		$shipmentDrops = $this->_getDropOfCurrentRoute();

        $tempData = $this->_getAssignedShipmentDrops($shipmentDrops);
		return $tempData;
    }

    protected function _assignedShipmentOfRoute()
    {
        $this->_shipment_type = "assigned_shipment";
        $shipmentDrops = $this->_getDropOfCurrentRoute();
		return $shipmentDrops;
    }

    private function _getShipmentDrop()
    {
        if($this->_get_drop_from=="route"){
            $shipments = $this->modelObj->getShipmentDrop($this->_getRouteId());
        }
        elseif($this->_get_drop_from=="shipment_ticket"){
            $alltickets = implode("','",$this->_getShipmentTickets());
            $shipments = $this->modelObj->getShipmentDropByShipmentTicket($this->_getRouteId(),$alltickets);
        }
        elseif($this->_get_drop_from=="shipment_ticket_after_carded"){
            $alltickets = implode("','",$this->_getShipmentTickets());
            $shipments = $this->modelObj->getShipmentDropByShipmentTicketAfterCarded($this->_getRouteId(),$alltickets);
        }
        return $shipments;
    }

    private function _getDropOfCurrentRoute()
	{
		$temp  = array();
        $temp1 = array();
        $shipments = $this->_getShipmentDrop();//$this->modelObj->getShipmentDrop($this->_getRouteId());

        foreach($shipments as $key => $shipment){
            $drop = $this->_getRouteDrop(array("postcode"=>$shipment['shipment_postcode'],"address_1"=>$shipment['shipment_address1']), true);
            $companyId = $shipment["company_id"];

            $drop = str_replace('/','', $drop);

            if(!isset($temp[$drop]))
            {
                $temp[$drop] = $drop;
                $temp[$drop] = array();
                $temp1[$drop]= array();
            }

            if(!isset($temp[$drop]['totshipment']))
                $temp[$drop]['totshipment'] = 0;

            if(!isset($temp[$drop]['totweight']))
                $temp[$drop]['totweight'] = 0;

            if(!isset($temp[$drop]['totvolume']))
                $temp[$drop]['totvolume'] = 0;

            if(!isset($temp[$drop]['totparcel']))
                $temp[$drop]['totparcel'] = 0;

            if(!isset($temp[$drop]['estimated_time']))
                $temp[$drop]['estimated_time'] = 0;

            if(!isset($temp[$drop]['distance_miles']))
                $temp[$drop]['distance_miles'] = 0;

            if(!isset($temp[$drop]['shipment_id']))
                $temp[$drop]['shipment_id'] = '';

            if(!isset($temp[$drop]['tickets']))
                $temp[$drop]['tickets'] = '';

            if(!isset($temp[$drop]['isrecives']))
                $temp[$drop]['isrecives'] = '';

            if(!isset($temp[$drop]['dockets']))
                $temp[$drop]['dockets'] = '';

            $temp1[$drop]['shipment_id'][$key] = $shipment['shipment_id'];
            $temp1[$drop]['isrecives'][$key]   = $shipment['is_receivedinwarehouse'];
            $temp1[$drop]['dockets'][$key]     = $shipment['instaDispatch_docketNumber'];
            $temp1[$drop]['tickets'][$key]     = $shipment['shipment_ticket'];


            array_push($this->_shipment_tickets, $shipment['shipment_ticket']);

            $temp[$drop]['totweight']                       = $temp[$drop]['totweight'] + $shipment['shipment_total_weight'];
            $temp[$drop]['totvolume']                       = $temp[$drop]['totvolume'] + $shipment['shipment_total_volume'];
            $temp[$drop]['totparcel']                       = $temp[$drop]['totparcel'] + $shipment['shipment_total_item'];
            $temp[$drop]['estimated_time']                  = $temp[$drop]['estimated_time'] + strtotime($shipment['estimatedtime']);
            $temp[$drop]['distance_miles']                  = $temp[$drop]['distance_miles'] + $shipment['distancemiles'];

            $temp[$drop]['shipment_id']                     = implode(',', $temp1[$drop]['shipment_id']);
            $temp[$drop]['isrecives']                       = implode(',', $temp1[$drop]['isrecives']);
            $temp[$drop]['dockets']                         = implode(',', $temp1[$drop]['dockets']);
            $temp[$drop]['tickets']                         = implode(';', $temp1[$drop]['tickets']);
            $temp[$drop]['postcode']                        = $shipment['shipment_postcode'];

            $temp[$drop]['shipment_address1']               = $shipment['shipment_address1'];
            $temp[$drop]['shipment_address2']               = $shipment['shipment_address2'];
            $temp[$drop]['shipment_address3']               = "";//$shipment['shipment_address3'];
            $temp[$drop]['expected_service_time']           = $this->_formate_date($shipment['shipment_required_service_starttime'],'H:i').' - '.$this->_formate_date($shipment['shipment_required_service_endtime'],'H:i');
            $temp[$drop]['service_starttime']               = $shipment['shipment_required_service_starttime'];

            $temp[$drop]['service_endtime']                 = $shipment['shipment_required_service_endtime'];
            $temp[$drop]['instaDispatch_LoadGroupTypeCode'] = $shipment['instaDispatch_loadGroupTypeCode'];
            $temp[$drop]['instaDispatch_objectIdentity']    = $shipment['instaDispatch_objectIdentity'];
            $temp[$drop]['shipment_service_type']           = $shipment['shipment_service_type'];
            $temp[$drop]['shipment_customer_country']       = $shipment['shipment_customer_country'];

            $temp[$drop]['is_receivedinwarehouse']          = $shipment['is_receivedinwarehouse'];
            $temp[$drop]['warehousereceived_date']          = $this->_formate_date($shipment['warehousereceived_date'], 'Y-m-d H:i:s');
            $temp[$drop]['driver_pickuptime']               = $this->_formate_date($shipment['driver_pickuptime'], 'Y-m-d H:i:s');
            $temp[$drop]['is_driverpickupfromwarehouse']    = $shipment['is_driverpickupfromwarehouse'];
            $temp[$drop]['route_id']                        = $shipment['shipment_routed_id'];

            $temp[$drop]['shipment_latlong']                = $shipment['shipment_latlong'];
            $temp[$drop]['shipment_latitude']               = $shipment['shipment_latitude'];
            $temp[$drop]['shipment_longitude']              = $shipment['shipment_longitude'];
            $temp[$drop]['icargo_execution_order']          = $shipment['icargo_execution_order'];
            $temp[$drop]['drops']                           = $this->_getRouteDrop(array("postcode"=>$shipment['shipment_postcode'],"address_1"=>$shipment['shipment_address1']), false);

            $temp[$drop]['totshipment']                     = ++$temp[$drop]['totshipment'];
            $temp[$drop]['disputedate']                     = $this->_formate_date($shipment['disputedate'], 'Y-m-d');
            $temp[$drop]['driver_pickuptime']               = $this->_formate_date($shipment['driver_pickuptime'], 'Y-m-d H:i:s');
            $temp[$drop]['warehousereceived_date']          = $this->_formate_date($shipment['warehousereceived_date'], 'Y-m-d H:i:s');
            $temp[$drop]['shipment_company_name']           = $this->_findCompanyName($companyId);
        }
        unset($temp1);
        array_multisort(
            array_column($temp, 'drops'), SORT_DESC,
            array_column($temp, 'icargo_execution_order'), SORT_DESC,
            $temp
        );
        return $temp;
	}

    protected function _getFirebaseProfile()
    {
        return $this->modelObj->getUserFirebaseProfile($this->_getDriverId());
    }

    protected function _getAssignedShipmentDrops($shipmentDrops)
	{
		$total_parcel = 0;
		$route_all_shipments_tickets = array();
		$drop_count = count($shipmentDrops);

        if($drop_count > 0) {
			$time_remaining = array();
			$drops = array();
			foreach($shipmentDrops as $key => $drop) {

                if($this->_shipment_type == "withdraw_shipment"){
                    $this->_setDriverId($this->_getDriverId());
				    $this->_setRouteId($drop['route_id']);
                }

		        $service_start_timestamp = strtotime($drop['service_starttime']);
				$service_end_timestamp = strtotime($drop['service_endtime']);

				$seconds = $service_end_timestamp - $service_start_timestamp;
				$hours = floor($seconds / 3600);
				$minutes = $hours * 60;
				$total_parcel += $drop['totparcel'];
				array_push($route_all_shipments_tickets,$drop['tickets']);

				$shipment_tickets = str_replace(";","','",$drop['tickets']);

                $consignee_info = $this->modelObj->getShipmentCustomerDetailByShipTicket($this->_getRouteId(),$this->_getDriverId(),$shipment_tickets);

				$shipData = array();
				foreach($consignee_info as $value){
				    $shipData[$value['shipment_ticket']] = $value;
                    $parcelsdata = $this->modelObj->getAllParcelOfRoute($this->_getRouteId(),$shipment_tickets);
				    $parcelData  = array();
				    foreach($parcelsdata as $value){
					    $value['scan'] = array();
				        $parcelData[$value['parcel_ticket']] = $value;
				    }
				    $shipData[$value['shipment_ticket']]['parcels'] = $parcelData;
				}
				$drops[$drop['drops']] = $drop;
				$drops[$drop['drops']]['shipments'] = $shipData;
				//$drops[$drop['drops']]['firebase_profile'] = $this->_getFirebaseProfile();
			}
			return array('shipments_drops'=>$drops,'firebase_profile'=>$this->_getFirebaseProfile());
		} else {
			//unset($routeRecords[$key]);
		}
	}

    protected function _getRouteDetailByShipmentRouteId()
    {
        return $this->modelObj->getShipmentRouteByShipmentRouteId($this->_getRouteId());
    }

    protected function _getFirebaseIdByShipmentRouteId()
    {
        return $this->modelObj->getFirebaseIdByShipmentRouteId($this->_getRouteId());
    }

    protected function _getJobCountByShipmentRouteId()
    {
        return $this->modelObj->getJobCountByShipmentRouteId($this->_getRouteId());
    }

    private function _findCompanyName($company_id){
        $data = $this->modelObj->getUserById($company_id);
        return $data["name"];
    }
}
?>
