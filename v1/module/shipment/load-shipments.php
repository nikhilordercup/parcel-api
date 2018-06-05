<?php
class loadShipment extends Library
{
    public $data = array();
    public $warehouse_id = 0;
    public $company_id = 0;
    public $access_token = null;
    public $shipment_type = null;
    public $drop_type = array();
	public $service_type = array();
    public $user_id = 0;
    public $collectionShip =  array();
    public $routetype = null;
	private $_execution_order = 'shipment_executionOrder';
    public function __construct($param)
    {
        $this->db = new DbHandler();
		$this->common_obj = new Common();
        if (isset($param['company_id'])) {
            $this->company_id = $param['company_id'];
        }
        if (isset($param['warehouse_id'])) {
            $this->warehouse_id = $param['warehouse_id'];
        }
        if (isset($param['shipment_ticket'])) {
            $this->shipment_ticket = $param['shipment_ticket'];
        }
        if (isset($param['access_token'])) {
            $this->access_token = $param['access_token'];
        }
        if (isset($param['shipment_type'])) {
            $this->shipment_type = $param['shipment_type'];
        }
        if (isset($param['driver_id'])) {
            $this->driver_id = $param['driver_id'];
        }
        if (isset($param['disputeid'])) {
            $this->disputeid = $param['disputeid'];
        }
        if (isset($param['assign_time'])) {
            $this->assign_time = $param['assign_time'];
        }
        if(isset($param['email'])) {
            $this->email = $param['email'];
        }
        if (isset($param['shipment_route_id'])) {
            $this->shipment_route_id = $param['shipment_route_id'];
        }	
	    if (isset($param['route_name'])) {
            $this->route_name = $param['route_name'];
        }
        if (isset($param['user_id'])) {
            $this->user_id = $param['user_id'];
        }
        if (isset($param['routetype'])) {
            $this->routetype = strtoupper($param['routetype']);
        }

        if (isset($param['start_date'])) {
            $this->startDate = date("Y-m-d H:i:s", strtotime($param['start_date']));
        }

        if (isset($param['end_date'])) {
            $this->endDate = date("Y-m-d H:i:s", strtotime($param['end_date']));
        }

        $this->drop_type = array(
            "vendor_d" => "N/D",
            "vendor_c" => "N/C",
            "next_p" => "N/C",
            "next_d" => "N/D",
            "same_p" => "S/C",
            "same_d" => "S/D"
            
        );
		
		$this->service_type = array(
			'p' => 'Collection',
			'd' => 'Delivery',
			'b' => 'Both'
		);
    }
    public function testCompanyConfiguration()
    {
        $record = $this->db->getRowRecord("SELECT configuration_json AS configuration_json FROM " . DB_PREFIX . "configuration AS s WHERE company_id = " . $this->company_id);
		
        if ($record) {
            $configuration                      = json_decode($record['configuration_json']);
            $this->drop_count                   = $configuration->maximum_allow_drop;
            $this->buffer_time                  = $configuration->buffer_time;
            $this->buffer_time_of_drop          = $configuration->maxbuffertimeperdrop;
            $this->buffer_time_of_each_shipment = $configuration->maxbuffertimepershipment;
            $this->shipment_attempt_conf        = array(
                'regularattemptconf' => $configuration->regularattempt,
                'phoneattemptconf' => $configuration->phonetypeattempt
            );
            $data                               = array(
                'status' => true,
                'message' => 'Configuration found'
            );
        }
        else {
            $data = array(
                'status' => false,
                'message' => 'Configuration not found. Please set the configuration first.'
            );
        }
        return $data;
    }
    private function _get_shipment_data_by_docket_no($docket)
    {
        $records = $this->db->getAllRecords("SELECT $this->_execution_order AS shipment_order, instaDispatch_docketNumber  AS docket_number, shipment_postcode, shipment_service_type, distancemiles, estimatedtime, waitingtime, loadingtime, shipment_instruction,shipment_required_service_starttime, shipment_required_service_endtime FROM " . DB_PREFIX . "shipment AS s WHERE  s.instaDispatch_docketNumber = '$docket' ORDER BY shipment_order");
        return $records;
    }
    private function _get_parcel_data_by_shipment_id($shipment_id)
    {
        $records = $this->db->getAllRecords("SELECT instaDispatch_pieceIdentity, parcel_weight, parcel_width, parcel_height, parcel_length FROM " . DB_PREFIX . "shipments_parcel AS s WHERE  s.shipment_id = '$shipment_id'");
        return $records;
    }

    private function _get_shipment_service_type_by_load_identity($load_identity)
    {
        $record = $this->db->getRowRecord("SELECT s.*, u.name AS customer_name, u.address_1 AS customer_address_line1, u.address_2 AS customer_address_line2 FROM " . DB_PREFIX . "shipment_service AS s INNER JOIN " . DB_PREFIX . "users AS u ON s.customer_id = u.id WHERE  s.load_identity = '$load_identity'");
        return $record;

    }

    private function _get_next_day_data()
    {
        $records = $this->db->getAllRecords("SELECT * FROM " . DB_PREFIX . "shipment AS s WHERE s.current_status = 'C' AND s.instaDispatch_loadGroupTypeCode != 'SAME' AND s.company_id = ' $this->company_id ' AND s.warehouse_id = ' $this->warehouse_id' AND is_internal=1");
        //$records = $this->db->getAllRecords("SELECT s.*, ABT.address_line1 AS shipment_address1, ABT.address_line2 AS shipment_address2, ABT.postcode AS shipment_postcode, ABT.city AS shipment_customer_city, ABT.country AS shipment_customer_country, ABT.iso_code AS shipment_country_code FROM " . DB_PREFIX . "shipment AS s INNER JOIN " . DB_PREFIX . "address_book AS ABT ON ABT.id=s.address_id WHERE s.current_status = 'C' AND s.instaDispatch_loadGroupTypeCode != 'SAME' AND s.company_id = " . $this->company_id." AND s.warehouse_id = " . $this->warehouse_id);
        
		$counter = 0;
        $temp    = array();
        $data = array();
        foreach ($records as $key => $record) {
            $load_group_type_code = strtolower($record["instaDispatch_loadGroupTypeCode"]);
            if ($record["shipment_service_type"] == "D") {
                $service_type = "Delivery";
            }
            else if ($record["shipment_service_type"] == "P") {
                $service_type = "Collection";
            }
            if ($load_group_type_code == "vendor") {
                $type = "Retail";
            }
            else if ($load_group_type_code == "next") {
                $type = "Next Day";
            }
            else if ($load_group_type_code == "phone") {
                $type = "Phone";
            }
            //suppressing type variable temporary
            $type = $load_group_type_code;
            
            if($load_group_type_code =="phone" || $load_group_type_code =="vendor"){
                $type = "retail";
            }
            
            
            if ($record["shipment_service_type"] == "P") {
                $temp[$record["shipment_postcode"]][]                  = $record["shipment_ticket"];
                $data[$record["shipment_postcode"]]["shipment_ticket"] = implode(',', $temp[$record["shipment_postcode"]]); //$record["shipment_ticket"];
                $data[$record["shipment_postcode"]]["docket_no"]       = $record["instaDispatch_docketNumber"];
                $data[$record["shipment_postcode"]]["reference_no"]    = $record["instaDispatch_loadIdentity"];
                $data[$record["shipment_postcode"]]["service_date"]    = $record["shipment_required_service_date"];
                $data[$record["shipment_postcode"]]["service_time"]    = $record["shipment_required_service_starttime"];
                $data[$record["shipment_postcode"]]["weight"]          = $record["shipment_total_weight"];
                $data[$record["shipment_postcode"]]["postcode"]        = $record["shipment_postcode"];
                $data[$record["shipment_postcode"]]["shipment_address"] = $record["shipment_address1"];
                $data[$record["shipment_postcode"]]["attempt"]         = $record["shipment_total_attempt"];
                $data[$record["shipment_postcode"]]["in_warehouse"]    = $record["is_receivedinwarehouse"];
                $data[$record["shipment_postcode"]]["type"]            = $type;
                $data[$record["shipment_postcode"]]["parcels"]         = $this->_get_parcel_data_by_shipment_id($record["shipment_id"]);
                $data[$record["shipment_postcode"]]["action"]          = "<a>Detail</a>";
                $data[$record["shipment_postcode"]]["count"]           = (isset($data[$record["shipment_postcode"]]["count"])) ? ++$data[$record["shipment_postcode"]]["count"] : 1;
                $data[$record["shipment_postcode"]]["service_type"]    = "$service_type(" . $data[$record["shipment_postcode"]]["count"] . ")";
            }
            else {
                $temp[$key][]                  = $record["shipment_ticket"];
                $data[$key]["shipment_ticket"] = implode(',', $temp[$key]);
                $data[$key]["docket_no"]       = $record["instaDispatch_docketNumber"];
                $data[$key]["reference_no"]    = $record["instaDispatch_loadIdentity"];
                $data[$key]["service_date"]    = $record["shipment_required_service_date"];
                $data[$key]["service_time"]    = $record["shipment_required_service_starttime"];
                $data[$key]["weight"]          = $record["shipment_total_weight"];
                $data[$key]["postcode"]        = $record["shipment_postcode"];
                $data[$key]["shipment_address"] = $record["shipment_address1"];
                $data[$key]["attempt"]         = $record["shipment_total_attempt"];
                $data[$key]["in_warehouse"]    = $record["is_receivedinwarehouse"];
                $data[$key]["type"]            = $type;
                $data[$key]["parcels"]         = $this->_get_parcel_data_by_shipment_id($record["shipment_id"]);
                $data[$key]["action"]          = "<a>Detail</a>";
                $data[$key]["count"]           = (isset($data[$key]["count"])) ? ++$data[$key]["count"] : 1;
                $data[$key]["service_type"]    = "$service_type(" . $data[$key]["count"] . ")";
            }
        }
        return array_values($data);
    }
    public function shipments()
    {
        if (strtolower($this->shipment_type) == 'next') {
            return $this->_get_next_day_data();
        }
        else if (strtolower($this->shipment_type) == 'same') {
            return $this->_get_same_day_data();
        }
        
        else if (strtolower($this->shipment_type) == 'return') {
            return $this->_get_return_data();
        }
        else if (strtolower($this->shipment_type) == 'disputed') {
            return $this->_get_disputed_data();
        }
        else if (strtolower($this->shipment_type) == 'sameday_customer') {
            return $this->_get_customer_same_day_data();
        }
        
    }

    private 

    function _prepare_customer_sameday_data($records){
        $data    = array();
        $temp    = array();
        foreach ($records as $key => $record) {
            $temp[$record['instaDispatch_loadIdentity']]['reference_no']     = $record["instaDispatch_loadIdentity"];
            //$temp[$record['instaDispatch_loadIdentity']]['distance_miles'][] = ($record["transit_distance"]=="") ? 0 : $record["transit_distance"] ;
            //$temp[$record['instaDispatch_loadIdentity']]['estimated_time'][] = ($record["transit_time"]=="") ? 0 : $record["transit_time"];
            $temp[$record['instaDispatch_loadIdentity']]["service_date"]     = $record["shipment_required_service_date"];


            $temp[$record['instaDispatch_loadIdentity']]["shipments"][] = array(
                "docket_number"               =>$record["docket_number"], 
                "shipment_postcode"           =>$record["postcode"],
                "shipment_service_type"       =>$this->service_type[strtolower($record["shipment_service_type"])],
                "waiting_time"                =>date("i", strtotime($record["waiting_time"]))." min",
                "loading_time"                =>date("i", strtotime($record["loading_time"]))." min",
                "shipment_instruction"        =>$record["shipment_instruction"],
                "shipment_service_start_time" =>$record["shipment_required_service_start_time"],
                "shipment_service_end_time"   =>$record["shipment_required_service_end_time"],
                "shipment_order"              =>$record["shipment_order"],
                "shipment_ticket"             =>$record["shipment_ticket"],
                "instaDispatch_loadIdentity"  =>$record['instaDispatch_loadIdentity'],
                "customer_name"               =>$record["shipment_customer_name"],
                "customer_email"              =>$record["shipment_customer_email"],
                "customer_phone"              =>$record["shipment_customer_phone"],
                "address_line1"               =>$record["address_line1"],
                "address_line2"               =>$record["address_line2"],
                "city"                        =>$record["city"],
                "state"                       =>$record["state"],
                "country"                     =>$record["country"]
            );
        }
        $counter = 0;

        foreach($temp as $key => $record)
        {
            $service_opted = $this->_get_shipment_service_type_by_load_identity($key);

            $start_time                        = $this->time_format($record['shipments'][0]['shipment_service_start_time']);
            $end_time                          = $this->time_format($record['shipments'][0]['shipment_service_end_time']);
            $data[$counter]['collection_time'] = "$start_time - $end_time";     
            $data[$counter]['shipment_ticket'] = implode(',', array_column($record['shipments'],"shipment_ticket"));
            $data[$counter]["reference_no"]    = $record["reference_no"];

            $data[$counter]['miles']           = $service_opted["transit_distance_text"];
            $data[$counter]['time']            = $service_opted["transit_time_text"];

            $data[$counter]['service_date']    = $this->date_format($record["service_date"]);
            $end_shipment                      = end($record['shipments']);
            $data[$counter]['start_postcode']  = $record['shipments'][0]['shipment_postcode'];
            $data[$counter]['end_postcode']    = $end_shipment['shipment_postcode'];
            $data[$counter]['drops']           = count($record['shipments']);
            $data[$counter]['shipments']       = $record['shipments'];
            $data[$counter]['service_name']    = $service_opted['service_name'];
			
			$data[$counter]['quote_amount']    = $service_opted['total_price'];

            /*$data[$counter]['customer_name']   = $service_opted['customer_name'];

            $data[$counter]['customer_address_line1']   = $service_opted['customer_address_line1'];
            $data[$counter]['customer_address_line2']   = $service_opted['customer_address_line2'];

            $data[$counter]['shipment_latitude']   = $service_opted['latitude'];
            $data[$counter]['shipment_longitude']   = $service_opted['longitude'];*/

            $counter++;
        }
        return $data;
    }

    private 

    function _prepare_sameday_data($records){
        $data    = array();
        $temp    = array();
        foreach ($records as $key => $record) {
            $temp[$record['instaDispatch_loadIdentity']]['reference_no']     = $record["instaDispatch_loadIdentity"];
            //$temp[$record['instaDispatch_loadIdentity']]['distance_miles'][] = ($record["transit_distance"]=="") ? 0 : $record["transit_distance"] ;
            //$temp[$record['instaDispatch_loadIdentity']]['estimated_time'][] = ($record["transit_time"]=="") ? 0 : $record["transit_time"];
            $temp[$record['instaDispatch_loadIdentity']]["service_date"]     = $record["shipment_required_service_date"];

            $temp[$record['instaDispatch_loadIdentity']]["shipments"][] = array(
                "docket_number"               =>$record["docket_number"], 
                "shipment_postcode"           =>$record["postcode"],
                "shipment_service_type"       =>$this->service_type[strtolower($record["shipment_service_type"])],
                "waiting_time"                =>date("i", strtotime($record["waiting_time"]))." min",
                "loading_time"                =>date("i", strtotime($record["loading_time"]))." min",
                "shipment_instruction"        =>$record["shipment_instruction"],
                "shipment_service_start_time" =>$record["shipment_required_service_start_time"],
                "shipment_service_end_time"   =>$record["shipment_required_service_end_time"],
                "shipment_order"              =>$record["shipment_order"],
                "shipment_ticket"             =>$record["shipment_ticket"],
                "instaDispatch_loadIdentity"  =>$record['instaDispatch_loadIdentity'],
                "shipment_address_line1"      =>$record['address_line1'],
                "shipment_address_line2"      =>$record['address_line2'],
                "shipment_latitude"           =>$record['latitude'],
                "shipment_longitude"          =>$record['longitude'],
                "icargo_execution_order"      =>$record['icargo_execution_order'],
                "drop_name"                   =>$this->common_obj->getDropName(array("postcode"=>$record['postcode'],"address_1"=>$record['address_line1']))
            );
        }
        $counter = 0;

        foreach($temp as $key => $record)
        {
            $service_opted = $this->_get_shipment_service_type_by_load_identity($key);


            $start_time                        = $this->time_format($record['shipments'][0]['shipment_service_start_time']);
            $end_time                          = $this->time_format($record['shipments'][0]['shipment_service_end_time']);

            $data[$counter]['collection_time'] = "$start_time - $end_time";     
            $data[$counter]['shipment_ticket'] = implode(',', array_column($record['shipments'],"shipment_ticket"));
            $data[$counter]["reference_no"]    = $record["reference_no"];

            $data[$counter]['miles']           = $service_opted["transit_distance_text"];
            $data[$counter]['time']            = $service_opted["transit_time_text"];

            $data[$counter]['service_date']    = $this->date_format($record["service_date"]);
            $end_shipment                      = end($record['shipments']);
            $data[$counter]['start_postcode']  = $record['shipments'][0]['shipment_postcode'];
            $data[$counter]['end_postcode']    = $end_shipment['shipment_postcode'];
            $data[$counter]['drops']           = count($record['shipments']);

            $data[$counter]['customer_name']   = $service_opted['customer_name'];

            $data[$counter]['customer_address_line1']   = $service_opted['customer_address_line1'];
            $data[$counter]['customer_address_line2']   = $service_opted['customer_address_line2'];

            $data[$counter]['shipments']       = $record['shipments'];
            $data[$counter]['service_name']    = $service_opted['service_name'];
            $counter++;
        }
        return $data;
    }

    private function _get_same_day_data()
    { //LEFT JOIN " . DB_PREFIX . "shipment_service AS t ON t.shipment_id=s.shipment_id  , t.transit_distance_text, t.transit_time_text
        /*$records = $this->db->getAllRecords("SELECT b.postcode, s.shipment_id, shipment_instruction, shipment_postcode, shipment_service_type, waitingtime AS waiting_time,
        loadingtime AS loading_time, $this->_execution_order AS shipment_order,instaDispatch_loadGroupTypeCode, instaDispatch_loadIdentity,
        instaDispatch_docketNumber AS docket_number, shipment_ticket, instaDispatch_jobIdentity, shipment_required_service_date, 
        shipment_required_service_starttime AS shipment_required_service_start_time, shipment_required_service_endtime AS shipment_required_service_end_time, 
        distancemiles AS distance_miles, estimatedtime AS estimated_time, b.address_line1, b.address_line2, b.latitude, b.longitude, s.icargo_execution_order
        FROM " . DB_PREFIX . "shipment AS s
        INNER JOIN " . DB_PREFIX . "address_book AS b on s.address_id=b.id WHERE s.current_status = 'C' AND 
        s.instaDispatch_loadGroupTypeCode = 'SAME' AND s.company_id = ".$this->company_id." AND s.warehouse_id = '$this->warehouse_id' ORDER BY FIELD(\"shipment_service_type\",\"P\",\"D\")");*/
        $sql = "SELECT s.shipment_postcode as postcode, s.shipment_id, shipment_instruction, shipment_postcode, shipment_service_type, waitingtime AS waiting_time, 
        loadingtime AS loading_time, $this->_execution_order AS shipment_order,instaDispatch_loadGroupTypeCode, instaDispatch_loadIdentity,
        instaDispatch_docketNumber AS docket_number, shipment_ticket, instaDispatch_jobIdentity, shipment_required_service_date, 
        shipment_required_service_starttime AS shipment_required_service_start_time, shipment_required_service_endtime AS shipment_required_service_end_time, 
        distancemiles AS distance_miles, estimatedtime AS estimated_time, s.shipment_address1 as address_line1, s.shipment_address1 as address_line2, s.shipment_latitude as latitude, s.shipment_longitude as longitude, s.icargo_execution_order
        FROM " . DB_PREFIX . "shipment AS s
        WHERE s.current_status = 'C' AND 
        s.instaDispatch_loadGroupTypeCode = 'SAME' AND s.company_id = ".$this->company_id." AND s.warehouse_id = '$this->warehouse_id' AND s.shipment_create_date BETWEEN '$this->startDate' AND '$this->endDate' ORDER BY FIELD(\"shipment_service_type\",\"P\",\"D\")";

        $records = $this->db->getAllRecords($sql);
        $data = $this->_prepare_sameday_data($records);

        return $data;
    }

    private function _get_customer_same_day_data()
    {//LEFT JOIN " . DB_PREFIX . "shipment_service AS t ON t.shipment_id=s.shipment_id , t.transit_distance, t.transit_time 
        $sqlOld = "SELECT b.postcode,b.address_line1,b.address_line2,b.city,b.state,b.country,s.shipment_customer_name,s.shipment_customer_email,s.shipment_customer_phone,s.shipment_id, shipment_instruction, shipment_postcode, shipment_service_type, waitingtime AS waiting_time, 
        loadingtime AS loading_time, $this->_execution_order AS shipment_order,instaDispatch_loadGroupTypeCode, instaDispatch_loadIdentity,
        instaDispatch_docketNumber AS docket_number, shipment_ticket, instaDispatch_jobIdentity, shipment_required_service_date, 
        shipment_required_service_starttime AS shipment_required_service_start_time, shipment_required_service_endtime AS shipment_required_service_end_time, 
        distancemiles AS distance_miles, estimatedtime AS estimated_time
        FROM " . DB_PREFIX . "shipment AS s LEFT JOIN " . DB_PREFIX . "shipment_service AS t ON t.load_identity=s.instaDispatch_loadIdentity INNER JOIN " . DB_PREFIX . "address_book AS b on s.address_id=b.id WHERE s.current_status = 'C' AND 
        s.instaDispatch_loadGroupTypeCode = 'SAME' AND s.company_id = ".$this->company_id." AND s.warehouse_id = '$this->warehouse_id' AND t.customer_id='$this->user_id' ORDER BY FIELD(\"shipment_service_type\",\"P\",\"D\")";

        /*$sql = "SELECT s.shipment_postcode as postcode,s.shipment_address1 as address_line1,b.shipment_address2 as address_line2,s.shipment_customer_city as city,b.state,s.shipment_customer_country as country,s.shipment_customer_name,s.shipment_customer_email,s.shipment_customer_phone,s.shipment_id, shipment_instruction, shipment_postcode, shipment_service_type, waitingtime AS waiting_time, 
        loadingtime AS loading_time, $this->_execution_order AS shipment_order,instaDispatch_loadGroupTypeCode, instaDispatch_loadIdentity,
        instaDispatch_docketNumber AS docket_number, shipment_ticket, instaDispatch_jobIdentity, shipment_required_service_date, 
        shipment_required_service_starttime AS shipment_required_service_start_time, shipment_required_service_endtime AS shipment_required_service_end_time, 
        distancemiles AS distance_miles, estimatedtime AS estimated_time
        FROM " . DB_PREFIX . "shipment AS s LEFT JOIN " . DB_PREFIX . "shipment_service AS t ON t.load_identity=s.instaDispatch_loadIdentity WHERE s.current_status = 'C' AND 
        s.instaDispatch_loadGroupTypeCode = 'SAME' AND s.company_id = ".$this->company_id." AND s.warehouse_id = '$this->warehouse_id' AND t.customer_id='$this->user_id' ORDER BY FIELD(\"shipment_service_type\",\"P\",\"D\")";*/

        $sql = "SELECT s.shipment_postcode as postcode,s.shipment_address1 as address_line1,s.shipment_address2 as address_line2,s.shipment_customer_city as city,s.shipment_county as state,s.shipment_customer_country as country,s.shipment_customer_name,s.shipment_customer_email,s.shipment_customer_phone,s.shipment_id, shipment_instruction, shipment_postcode, shipment_service_type, waitingtime AS waiting_time, 
        loadingtime AS loading_time, $this->_execution_order AS shipment_order,instaDispatch_loadGroupTypeCode, instaDispatch_loadIdentity,
        instaDispatch_docketNumber AS docket_number, shipment_ticket, instaDispatch_jobIdentity, shipment_required_service_date, 
        shipment_required_service_starttime AS shipment_required_service_start_time, shipment_required_service_endtime AS shipment_required_service_end_time, 
        distancemiles AS distance_miles, estimatedtime AS estimated_time
        FROM " . DB_PREFIX . "shipment AS s LEFT JOIN " . DB_PREFIX . "shipment_service AS t ON t.load_identity=s.instaDispatch_loadIdentity WHERE s.current_status = 'C' AND 
        s.instaDispatch_loadGroupTypeCode = 'SAME' AND s.company_id = ".$this->company_id." AND s.warehouse_id = '$this->warehouse_id' AND t.customer_id='$this->user_id' ORDER BY FIELD(\"shipment_service_type\",\"P\",\"D\")";

        $records = $this->db->getAllRecords($sql);
        $data = $this->_prepare_customer_sameday_data($records);
        return $data;
    }

    private function _get_return_data()
    {
       $records = $this->db->getAllRecords("SELECT * FROM " . DB_PREFIX . "shipment AS s WHERE s.current_status = 'Rit'  AND s.warehouse_id = " . $this->warehouse_id);
        $counter = 0;
        $temp    = array();
        $data = array();
        foreach ($records as $key => $record) {
            $load_group_type_code = strtolower($record["instaDispatch_loadGroupTypeCode"]);
            if ($record["shipment_service_type"] == "D") {
                $service_type = "Delivery";
            }
            else if ($record["shipment_service_type"] == "P") {
                $service_type = "Collection";
            }
            if ($load_group_type_code == "vendor") {
                $type = "Retail";
            }
            else if ($load_group_type_code == "next") {
                $type = "Next Day";
            }
            else if ($load_group_type_code == "phone") {
                $type = "Phone";
            }
            //suppressing type variable temporary
            $type = $load_group_type_code;
            
            if($load_group_type_code =="phone" || $load_group_type_code =="vendor"){
                $type = "retail";
            }

                $data[$key]["shipment_ticket"] = $record["shipment_ticket"];
                //$data[$key]["shipment_ticket"] = implode(',', $temp[$key]);
                $data[$key]["docket_no"]       = $record["instaDispatch_docketNumber"];
                $data[$key]["reference_no"]    = $record["instaDispatch_loadIdentity"];
                $data[$key]["service_date"]    = $record["shipment_required_service_date"];
                $data[$key]["service_time"]    = $record["shipment_required_service_starttime"];
                $data[$key]["weight"]          = $record["shipment_total_weight"];
                $data[$key]["postcode"]        = $record["shipment_postcode"];
                $data[$key]["attempt"]         = $record["shipment_total_attempt"];
                $data[$key]["in_warehouse"]    = $record["is_receivedinwarehouse"];
                $data[$key]["type"]            = $type;
                $data[$key]["parcels"]         = $this->_get_parcel_data_by_shipment_id($record["shipment_id"]);
                $data[$key]["action"]          = "<a>Detail</a>";
                $data[$key]["count"]           = (isset($data[$key]["count"])) ? ++$data[$key]["count"] : 1;
                $data[$key]["service_type"]    = "$service_type(" . $data[$key]["count"] . ")";
            /*}*/
        }
        $aoColumn = array(
            "Docket No",
            "Reference No",
            "Service Type",
            "Service Date",
            "Service Time",
            "Weight",
            "Postcode",
            "Attempt",
            "In Warehouse",
            "Type",
            "Action"
        );
        return array(
            "aoColumn" => $aoColumn,
            "aaData" => array_values($data)
        );   
    }
    private function _get_disputed_data()
    {
       $records = $this->db->getAllRecords("SELECT * FROM " . DB_PREFIX . "shipment AS s WHERE s.current_status = 'Dis'  AND s.warehouse_id = " . $this->warehouse_id);
        $counter = 0;
        $temp    = array();
        $data = array();
        foreach ($records as $key => $record) {
            $load_group_type_code = strtolower($record["instaDispatch_loadGroupTypeCode"]);
            if ($record["shipment_service_type"] == "D") {
                $service_type = "Delivery";
            }
            else if ($record["shipment_service_type"] == "P") {
                $service_type = "Collection";
            }
            if ($load_group_type_code == "vendor") {
                $type = "Retail";
            }
            else if ($load_group_type_code == "next") {
                $type = "Next Day";
            }
            else if ($load_group_type_code == "phone") {
                $type = "Phone";
            }
            //suppressing type variable temporary
            $type = $load_group_type_code;
            
            if($load_group_type_code =="phone" || $load_group_type_code =="vendor"){
                $type = "retail";
            }

                $data[$key]["shipment_ticket"] = $record["shipment_ticket"];
                //$data[$key]["shipment_ticket"] = implode(',', $temp[$key]);
                $data[$key]["docket_no"]       = $record["instaDispatch_docketNumber"];
                $data[$key]["reference_no"]    = $record["instaDispatch_loadIdentity"];
                $data[$key]["service_date"]    = $record["shipment_required_service_date"];
                $data[$key]["service_time"]    = $record["shipment_required_service_starttime"];
                $data[$key]["weight"]          = $record["shipment_total_weight"];
                $data[$key]["postcode"]        = $record["shipment_postcode"];
                $data[$key]["attempt"]         = $record["shipment_total_attempt"];
                $data[$key]["in_warehouse"]    = $record["is_receivedinwarehouse"];
                $data[$key]["type"]            = $type;
                $data[$key]["parcels"]         = $this->_get_parcel_data_by_shipment_id($record["shipment_id"]);
                $data[$key]["action"]          = "<a>Detail</a>";
                $data[$key]["count"]           = (isset($data[$key]["count"])) ? ++$data[$key]["count"] : 1;
                $data[$key]["service_type"]    = "$service_type(" . $data[$key]["count"] . ")";
            /*}*/
        }
        $aoColumn = array(
            "Docket No",
            "Reference No",
            "Service Type",
            "Service Date",
            "Service Time",
            "Weight",
            "Postcode",
            "Attempt",
            "In Warehouse",
            "Type",
            "Action"
        );
        return array(
            "aoColumn" => $aoColumn,
            "aaData" => array_values($data)
        );   
    }
    public function shipmentStatus()
    {
        $data                  = array();
        $shipment_type         = array();
        $shipment_day_services = array();
        $records               = $this->db->getAllRecords("SELECT shipment_postcode, shipment_customer_country, instaDispatch_loadGroupTypeCode, shipment_service_type, is_receivedinwarehouse, instaDispatch_objectIdentity, shipment_ticket FROM " . DB_PREFIX . "shipment AS s WHERE s.shipment_ticket IN('$this->shipment_ticket') AND s.current_status = 'C'");
        /***********address data will be fetched by shipment table(comment added by kavita 13feb2018)**************/
		//$records = $this->db->getAllRecords("SELECT ABT.postcode AS shipment_postcode, ABT.country AS shipment_customer_country, instaDispatch_loadGroupTypeCode, shipment_service_type, is_receivedinwarehouse, instaDispatch_objectIdentity, shipment_ticket FROM " . DB_PREFIX . "shipment AS s INNER JOIN " . DB_PREFIX . "address_book AS ABT ON ABT.id=s.address_id WHERE s.shipment_ticket IN('$this->shipment_ticket') AND s.current_status = 'C'");
        foreach ($records as $key => $value) {
            $shipment_type[] = $value['instaDispatch_loadGroupTypeCode'];
            if (($value['instaDispatch_loadGroupTypeCode'] == 'NEXT') && ($value['shipment_service_type'] == 'D') && ($value['is_receivedinwarehouse'] == 'NO')) {
                $shipment_day_services[] = $value['shipment_ticket'];
            }
        }
        $shipment_type = array_values(array_unique($shipment_type));
        if (count($shipment_type) > 1) {
            if (in_array('SAME', $shipment_type)) {
                $data = array(
                    'status' => false,
                    'message' => 'You can not process Same day with Other'
                );
            }
            else {
                $count = count($shipment_day_services);
                if ($count > 0) {
                    $data = array(
                        'status' => false,
                        'message' => 'Total ' . $count . ' Next Day Shipment that has not been collected yet. Do you want to exclude ?',
                        'data' => $shipment_day_services
                    );
                }
                else {
                    $data = array(
                        'status' => true,
                        'message' => 'All selected shipments are in warehouse.',
                        'line' => 195
                    );
                }
            }
        }
        elseif (count($shipment_type) == 1) {
            if ($shipment_type[0] == 'NEXT') {
                $count = count($shipment_day_services);
                if ($count > 0) {
                    $data = array(
                        'status' => false,
                        'message' => 'Total ' . $count . ' next Day Shipment select who not collect yet.',
                        'data' => $shipment_day_services
                    );
                }
                else {
                    $data = array(
                        'status' => true,
                        'message' => 'All selected shipments are in warehouse.',
                        'line' => 213
                    );
                }
            }
            if ($shipment_type[0] == 'Vendor') {
                $data = array(
                    'status' => true,
                    'message' => 'All selected shipments are in warehouse.',
                    'line' => 221
                );
            }
            if ($shipment_type[0] == 'SAME') {
                $data = array(
                    'status' => true,
                    'message' => 'All selected shipments are in warehouse.',
                    'line' => 230
                );
            }
        }
        return $data;
    }
    public function shipmentStatusSameDay()
    {
        $data                  = array();
        $shipment_type         = array();
        $shipment_day_services = array();
        $records               = $this->db->getAllRecords("SELECT shipment_postcode, shipment_customer_country, instaDispatch_loadGroupTypeCode, shipment_service_type, is_receivedinwarehouse, instaDispatch_objectIdentity, shipment_ticket FROM " . DB_PREFIX . "shipment AS s WHERE s.shipment_ticket IN('$this->shipment_ticket') AND s.current_status = 'C'");
        foreach ($records as $key => $value) {
            $shipment_type[] = $value['instaDispatch_loadGroupTypeCode'];
            $shipment_day_services[] = $value['shipment_ticket'];    
        }
        $shipment_type = array_values(array_unique($shipment_type));
        if (count($shipment_type) == 1) {
            if ($shipment_type[0] == 'SAME') {
                $count = count($shipment_day_services);
                if ($count > 0) {
                    $data = array(
                        'status' => true,
                        'message' => 'Total ' . $count . ' same Day Shipment select.',
                        'data' => $shipment_day_services
                    );
                }  
            }else {
                    $data = array(
                        'status' => true,
                        'message' => 'You  can not selected other than SAME day.',
                        'line' => 631
                    );
        }
          }else {
                    $data = array(
                        'status' => true,
                        'message' => 'You  can not selected more than one type of shipment here.',
                        'line' => 631
                    );
        }
        return $data;
    }
    
    
    
        
    private function _search_in_array($array)
    {
        array_multisort(array_map('strlen', $array), $array);
        $data = array_pop($array);
        return $data;
    }
    private function _get_shipment_route_by_key($needle, $column)
    {
        $data                 = array();
        $this->shipment_route = array();
        $cols                 = array_column($this->route_postcode, $column);
        $key                  = array_search($needle, $cols);
        
        if (!$key) {
            $temp = array();
            foreach ($cols as $key => $col) {
                if (stristr($needle, $col))
                    array_push($temp, $key);
            }
            if (count($temp) > 0)
                $data = $this->route_postcode[$this->_search_in_array($temp)];
        }
        else
            $data = $this->route_postcode[$key];

        $this->shipment_route = $data;
        if (isset($data['route_id'])) {
            return $data['route_id'];
        }
        else {
            return 0;
        }
    }
    private function _clean_temporary_data_by_session_id()
    {
        $this->db->delete("DELETE FROM " . DB_PREFIX . "temp_routes_shipment WHERE session_id = '" . $this->access_token . "' AND company_id = '" . $this->company_id . "'");
        $this->db->delete("DELETE FROM " . DB_PREFIX . "temp_routes WHERE session_id = '" . $this->access_token . "' AND company_id = '" . $this->company_id . "'");
    }
    public function requestRoutesData()
    {
        $ticketids            = $this->shipment_ticket;
		$this->drops          = array();
        $data                 = array();
        $data['routes']       = array();
        $this->route_postcode = $this->db->getAllRecords("SELECT t1.*, t2.name AS route_name FROM " . DB_PREFIX . "route_postcode AS t1 INNER JOIN " . DB_PREFIX . "routes AS t2 ON t1.route_id = t2.id WHERE t1.company_id = " . $this->company_id . " AND t1.warehouse_id = " . $this->warehouse_id);
        $records              = $this->db->getAllRecords("SELECT shipment_id, shipment_ticket, shipment_postcode, shipment_address1, instaDispatch_loadGroupTypeCode, shipment_service_type,instaDispatch_loadIdentity, instaDispatch_docketNumber, ".$this->_execution_order." AS shipment_executionOrder, shipment_customer_country FROM " . DB_PREFIX . "shipment WHERE shipment_ticket IN('$this->shipment_ticket') AND current_status = 'C' ORDER BY shipment_service_type");
        //$records = $this->db->getAllRecords("SELECT ST.shipment_id, ST.shipment_ticket, ABT.postcode AS shipment_postcode, ABT.address_line1 AS shipment_address1 , ABT.country AS shipment_customer_country, ST.instaDispatch_loadGroupTypeCode, ST.shipment_service_type, ST.instaDispatch_docketNumber, ".$this->_execution_order." AS shipment_executionOrder FROM " . DB_PREFIX . "shipment AS ST INNER JOIN " . DB_PREFIX . "address_book AS ABT ON ABT.id=ST.address_id WHERE shipment_ticket IN('$this->shipment_ticket') AND current_status = 'C' ORDER BY shipment_service_type");
        /*foreach ($records as $key => $values) {
            $routeId                                                                                                                     = $this->_get_shipment_route_by_key($values['shipment_postcode'], 'postcode');
            $values['routeId']                                                                                                           = $routeId;
            $data[$values['instaDispatch_loadGroupTypeCode']][$values['shipment_service_type']][$values['instaDispatch_docketNumber']][] = $values;
        }*/

        foreach ($records as $key => $value) {
            if ((strtolower($value['instaDispatch_loadGroupTypeCode']) == 'vendor') || (strtolower($value['instaDispatch_loadGroupTypeCode']) == 'phone')) {
                $routeId                    = $this->_get_shipment_route_by_key($value['shipment_postcode'], 'postcode');
                $data['routes'][$routeId][] = $value;
            }
            if (((strtolower($value['instaDispatch_loadGroupTypeCode']) == 'same') && (strtolower($value['shipment_service_type']) == 'p')) || ((strtolower($value['instaDispatch_loadGroupTypeCode']) == 'next') && (strtolower($value['shipment_service_type']) == 'p'))) {
                $routeId                    = $this->_get_shipment_route_by_key($value['shipment_postcode'], 'postcode');
                $data['routes'][$routeId][] = $value;
            }
            if ((strtolower($value['instaDispatch_loadGroupTypeCode']) == 'same') && (strtolower($value['shipment_service_type']) == 'd')) {
                if (array_key_exists($value['instaDispatch_docketNumber'], $data['SAME']['P'])) {
                    $existedRouteId                    = $data['SAME']['P'][$value['instaDispatch_docketNumber']]['0']['routeId'];
                    $data['routes'][$existedRouteId][] = $value;
                }
                else {
                    $routeId                    = $this->_get_shipment_route_by_key($value['shipment_postcode'], 'postcode');
                    $data['routes'][$routeId][] = $value;
                }
            }
            if ((strtolower($value['instaDispatch_loadGroupTypeCode']) == 'next') && (strtolower($value['shipment_service_type']) == 'd')) {
                if (isset($data['NEXT']['P']) and array_key_exists($value['instaDispatch_docketNumber'], $data['NEXT']['P'])) {
                    $existedRouteId                    = $data['NEXT']['P'][$value['instaDispatch_docketNumber']]['0']['routeId'];
                    $data['routes'][$existedRouteId][] = $value;
                }
                else {
                    $routeId                    = $this->_get_shipment_route_by_key($value['shipment_postcode'], 'postcode');
                    $data['routes'][$routeId][] = $value;
                }
            }
        }

        $this->_clean_temporary_data_by_session_id();
        foreach ($data['routes'] as $key => $valueinner)
            $this->_add_temp_routes($key, $valueinner,'NONSAMEDAY');
		
		$this->_update_execution_order();
    }
    
    public function requestRoutesSamedayData()
    {
        $ticketids            = $this->shipment_ticket;
		$this->drops          = array();
        $data                 = array();
        $data['routes']       = array();
        $this->route_postcode = $this->db->getAllRecords("SELECT t1.*, t2.name AS route_name FROM " . DB_PREFIX . "route_postcode AS t1 INNER JOIN " . DB_PREFIX . "routes AS t2 ON t1.route_id = t2.id WHERE t1.company_id = " . $this->company_id . " AND t1.warehouse_id = " . $this->warehouse_id);
        $records              = $this->db->getAllRecords("SELECT shipment_id, shipment_ticket, shipment_postcode, shipment_address1, instaDispatch_loadGroupTypeCode, shipment_service_type, instaDispatch_loadIdentity,instaDispatch_docketNumber, ".$this->_execution_order." AS shipment_executionOrder, shipment_customer_country FROM " . DB_PREFIX . "shipment WHERE shipment_ticket IN('$this->shipment_ticket') AND current_status = 'C' ORDER BY shipment_service_type");
        foreach ($records as $key => $value) {
            $routeId                    = $this->_get_shipment_route_by_key($value['shipment_postcode'], 'postcode');
            $data['routes'][$routeId][] = $value;
        }
        $this->_clean_temporary_data_by_session_id();
        foreach ($data['routes'] as $key => $valueinner)
            $this->_add_temp_routes($key, $valueinner,'SAMEDAY');
		
		$this->_update_execution_order();
    }
    
	private function _update_execution_order()
	{
		$sql = "SELECT group_concat(`shipment_id`) as `shipment_id`, `drop_name`, `temp_route_id` FROM `" . DB_PREFIX . "temp_routes_shipment` group by drop_name, temp_route_id ORDER BY drop_name";
		$records = $this->db->getAllRecords($sql);
		foreach($records as $record){
			if(!isset($this->drops[$record['temp_route_id']][$record['drop_name']]['shipments'])){
				$this->drops[$record['temp_route_id']][$record['drop_name']]['shipments'] = array();
			}
			$this->drops[$record['temp_route_id']][$record['drop_name']]['shipments'][] =  $record['shipment_id'];  
		}
		foreach($this->drops as $route_id => $drop){
			$execution_order = 1;
			foreach($drop as $shipment){
				$sql = "UPDATE `" . DB_PREFIX . "temp_routes_shipment` SET drop_execution_order = $execution_order WHERE temp_route_id = $route_id AND company_id = $this->company_id AND shipment_id IN(".$shipment['shipments'][0].")";			
				$this->db->updateData($sql);
				$execution_order++;
			}
		}
	}



    //commented by nishant. address data is not in shipment table
    /*private function _get_all_drops_by_session()
    {
        $sql = "SELECT `SH`.`temp_route_id`, `SH`.`drag_temp_route_id`, `SH`.`execution_order`, `SH`.`distancemiles`, `SH`.`estimatedtime`, `R2`.`custom_route`, `R2`.`route_id`, `R2`.`session_id`, ";
        $sql .= "`R2`.`route_name`, CONCAT(route_name,SH.temp_route_id) AS `route_name_display`, `R2`.`is_optimized`, `R2`.`optimized_type`, `R2`.`last_optimized_time`, ";
        $sql .= "CONCAT(shipment_postcode,' ',shipment_address1) AS `drops`, SUM(shipment_total_weight) AS `totweight`, SUM(shipment_total_volume) AS `totvolume`, GROUP_CONCAT(shipment_ticket) AS `shipment_ticket`, GROUP_CONCAT(temp_ship_id) AS `shipment_id`,";
        $sql .= "GROUP_CONCAT(instaDispatch_docketNumber) AS `dockets`, SUM(shipment_total_item) AS `totparcel`, COUNT(1) AS `totshipment`, GROUP_CONCAT(is_receivedinwarehouse) AS `isrecives`, ";
        $sql .= "`CA`.`shipment_postcode` AS `postcode`, `CA`.`shipment_latlong`, `CA`.`shipment_latitude`, `CA`.`shipment_longitude`, `CA`.`shipment_customer_country`, `CA`.`instaDispatch_loadGroupTypeCode`, `CA`.`shipment_service_type`, `CA`.`icargo_execution_order`, `R2`.`warehouse_id` ";
        $sql .= "FROM `" . DB_PREFIX . "temp_routes_shipment` AS `SH` INNER JOIN `" . DB_PREFIX . "temp_routes` AS `R2` ON SH.drag_temp_route_id = R2.temp_route_id ";
        $sql .= "INNER JOIN `" . DB_PREFIX . "shipment` AS `CA` ON SH.shipment_id = CA.shipment_id ";
        $sql .= "WHERE (SH.session_id = '" . $this->access_token . "') AND (CA.current_status = 'C') GROUP BY `SH`.`temp_route_id`, `drops` ORDER BY `SH`.`execution_order` ASC, `drops` ASC";
        $records = $this->db->getAllRecords($sql);
        return $records;
    }*/

    /*private function _get_all_drops_by_session()
    {
        $sql = "SELECT `SH`.`temp_route_id`, `SH`.`drag_temp_route_id`, `SH`.`execution_order`, `SH`.`distancemiles`, `SH`.`estimatedtime`, `R2`.`custom_route`, `R2`.`route_id`, `R2`.`session_id`, ";
        $sql .= "`R2`.`route_name`, CONCAT(route_name,SH.temp_route_id) AS `route_name_display`, `R2`.`is_optimized`, `R2`.`optimized_type`, `R2`.`last_optimized_time`, ";
        $sql .= "CONCAT(ABT.postcode AS shipment_postcode,' ',ABT. address_line1 AS shipment_address1) AS `drops`, SUM(shipment_total_weight) AS `totweight`, SUM(shipment_total_volume) AS `totvolume`, GROUP_CONCAT(shipment_ticket) AS `shipment_ticket`, GROUP_CONCAT(temp_ship_id) AS `shipment_id`,";
        $sql .= "GROUP_CONCAT(instaDispatch_docketNumber) AS `dockets`, SUM(shipment_total_item) AS `totparcel`, COUNT(1) AS `totshipment`, GROUP_CONCAT(is_receivedinwarehouse) AS `isrecives`, ";
        $sql .= "`ABT`.`postcode` AS `postcode`, `ABT`.`` AS `shipment_latlong`, `ABT`.`longitude` AS `shipment_longitude`, `ABT`.`country` AS `shipment_customer_country`, `CA`.`instaDispatch_loadGroupTypeCode`, `CA`.`shipment_service_type`, `CA`.`icargo_execution_order`, `R2`.`warehouse_id` ";
        $sql .= "FROM `" . DB_PREFIX . "temp_routes_shipment` AS `SH` INNER JOIN `" . DB_PREFIX . "temp_routes` AS `R2` ON SH.drag_temp_route_id = R2.temp_route_id ";
        $sql .= "INNER JOIN `" . DB_PREFIX . "shipment` AS `CA` ON SH.shipment_id = CA.shipment_id ";
        $sql .= "INNER JOIN `" . DB_PREFIX . "address_book` AS `ABT` ON CA.address_id = ABT.id ";
        $sql .= "WHERE (SH.session_id = '" . $this->access_token . "') AND (CA.current_status = 'C') GROUP BY `SH`.`temp_route_id`, `drops` ORDER BY `SH`.`execution_order` ASC, `drops` ASC";
        $records = $this->db->getAllRecords($sql);
        return $records;
    }*/

    private function _get_active_driver()
    {
        $sql     = "SELECT t1.id AS driver_id, t1.name AS driver_name FROM " . DB_PREFIX . "users AS t1 INNER JOIN " . DB_PREFIX . "company_users AS t2 ON t1.id = t2.user_id WHERE t1.user_level = 4 AND t1.status = 1 AND t2.company_id = " . $this->company_id . " ORDER BY driver_name";
        $records = $this->db->getAllRecords($sql);
        return $records;
    }
    public function loadPreparedRoute()
    {
		$prepared_route = $this->_load_prepared_route();
		$data           = array(
            'prepared_route' => $prepared_route,
            'active_drivers' => $this->_get_active_driver(),
			'warehouse_data' => $this->_get_warehouse_data()
        );
        return $data;
    }
	private function _get_warehouse_data()
	{
		$sql = "SELECT * FROM `" . DB_PREFIX . "warehouse` WHERE id = $this->warehouse_id";
		return $this->db->getRowRecord($sql);
	}
    private function _get_load_type($key)
    {
        $key = strtolower($key);
        return $this->drop_type[$key];
    }
	private function _get_service_type($key)
    {
        $key = strtolower($key);
        return $this->service_type[$key];
    }
	private function _date_format($date, $time= false)
	{
		if($time)
			return date("Y-M-d H:i:s", strtotime($date));
		else
			return date("Y-M-d", strtotime($date));
	}
	private function _load_prepared_route()
	{
		/*$sql = "SELECT `SH`.`temp_route_id`, `SH`.`drag_temp_route_id`, `SH`.`drop_execution_order`, `SH`.`distancemiles`, `SH`.`estimatedtime`, `R2`.`custom_route`, `R2`.`route_id`, `R2`.`session_id`, ";
        $sql .= "`R2`.`route_name`, CONCAT(route_name,SH.temp_route_id) AS `route_name_display`, `R2`.`is_optimized`, `R2`.`optimized_type`, `R2`.`last_optimized_time`, `SH`.drop_name, ";
        $sql .= "shipment_postcode, shipment_address1 , shipment_total_weight AS `shipment_weight`, shipment_total_volume AS `shipment_volume`, shipment_ticket AS `shipment_ticket`, `CA`.shipment_id AS `shipment_id`,";
        $sql .= "instaDispatch_docketNumber AS `docket_number`, is_receivedinwarehouse AS `is_receive_in_warehouse`, `CA`.shipment_total_item, `CA`.shipment_create_date, ";
        $sql .= "`CA`.`shipment_postcode` AS `postcode`, `CA`.`shipment_latlong`, `CA`.`shipment_latitude`, `CA`.`shipment_longitude`, `CA`.`shipment_customer_country`, `CA`.`instaDispatch_loadGroupTypeCode`, `CA`.`shipment_service_type`, `CA`.`icargo_execution_order`, `R2`.`warehouse_id`, `CA`.error_flag, `CA`.shipment_required_service_date AS service_date, `CA`.is_receivedinwarehouse, `CA`.shipment_total_attempt ";
        $sql .= "FROM `" . DB_PREFIX . "temp_routes_shipment` AS `SH` INNER JOIN `" . DB_PREFIX . "temp_routes` AS `R2` ON SH.temp_route_id = R2.temp_route_id ";
        $sql .= "INNER JOIN `" . DB_PREFIX . "shipment` AS `CA` ON SH.shipment_id = CA.shipment_id ";
        $sql .= "WHERE (SH.session_id = '" . $this->access_token . "') AND (CA.current_status = 'C')";*/

        //commented by nishant. address book table is now separated
		
		//address record will be fetched from shipments table only(comment added by kavita 13feb2018)
		$sql = "SELECT `R2`.`route_type`,`SH`.`shipment_type`,`SH`.`job_type`,`R2`.`temp_route_id`, `SH`.`drag_temp_route_id`, `SH`.`drop_execution_order`, `SH`.`distancemiles`, `SH`.`estimatedtime`, `R2`.`custom_route`, `R2`.`route_id`, `R2`.`session_id`, ";
        $sql .= "`R2`.`route_name`, CONCAT(route_name,SH.temp_route_id) AS `route_name_display`, `R2`.`is_optimized`, `R2`.`optimized_type`, `R2`.`last_optimized_time`, `SH`.drop_name, ";
        $sql .= "shipment_postcode, shipment_address1 , shipment_total_weight AS `shipment_weight`, shipment_total_volume AS `shipment_volume`, shipment_ticket AS `shipment_ticket`, `CA`.shipment_id AS `shipment_id`,";
        $sql .= "instaDispatch_docketNumber AS `docket_number`, is_receivedinwarehouse AS `is_receive_in_warehouse`, `CA`.shipment_total_item, `CA`.shipment_create_date, ";
        $sql .= "`CA`.`shipment_postcode` AS `postcode`, `CA`.`shipment_latlong`, `CA`.`shipment_latitude`, `CA`.`shipment_longitude`, `CA`.`shipment_customer_country`, `CA`.`instaDispatch_loadGroupTypeCode`, `CA`.`shipment_service_type`, `CA`.`icargo_execution_order`, `R2`.`warehouse_id`, `CA`.error_flag, `CA`.shipment_required_service_date AS service_date, `CA`.is_receivedinwarehouse, `CA`.shipment_total_attempt ";
        $sql .= "FROM `" . DB_PREFIX . "temp_routes` AS `R2` LEFT JOIN `" . DB_PREFIX . "temp_routes_shipment` AS `SH` ON R2.temp_route_id = SH.temp_route_id ";
        $sql .= "LEFT JOIN `" . DB_PREFIX . "shipment` AS `CA` ON SH.shipment_id = CA.shipment_id ";
        $sql .= "WHERE (R2.session_id = '" . $this->access_token . "')";
		
       /*  $sql = "SELECT `R2`.`temp_route_id`, `SH`.`drag_temp_route_id`, `SH`.`drop_execution_order`, `SH`.`distancemiles`, `SH`.`estimatedtime`, `R2`.`custom_route`, `R2`.`route_id`, `R2`.`session_id`, ";
        $sql .= "`R2`.`route_name`, CONCAT(route_name,SH.temp_route_id) AS `route_name_display`, `R2`.`is_optimized`, `R2`.`optimized_type`, `R2`.`last_optimized_time`, `SH`.drop_name, ";
        $sql .= "ABT.postcode AS shipment_postcode, ABT.address_line1 AS shipment_address1 , shipment_total_weight AS `shipment_weight`, shipment_total_volume AS `shipment_volume`, shipment_ticket AS `shipment_ticket`, `CA`.shipment_id AS `shipment_id`,";
        $sql .= "instaDispatch_docketNumber AS `docket_number`, is_receivedinwarehouse AS `is_receive_in_warehouse`, `CA`.shipment_total_item, `CA`.shipment_create_date, ";
        $sql .= "`ABT`.`postcode` AS `postcode`,`ABT`.`latitude` AS `shipment_latitude`, `ABT`.`longitude` AS `shipment_longitude`, `ABT`.`country` AS `shipment_customer_country`, `CA`.`instaDispatch_loadGroupTypeCode`, `CA`.`shipment_service_type`, `CA`.`icargo_execution_order`, `R2`.`warehouse_id`, `CA`.error_flag, `CA`.shipment_required_service_date AS service_date, `CA`.is_receivedinwarehouse, `CA`.shipment_total_attempt ";
        $sql .= "FROM `" . DB_PREFIX . "temp_routes` AS `R2` LEFT JOIN `" . DB_PREFIX . "temp_routes_shipment` AS `SH` ON R2.temp_route_id = SH.temp_route_id ";
        $sql .= "LEFT JOIN `" . DB_PREFIX . "shipment` AS `CA` ON SH.shipment_id = CA.shipment_id ";
        $sql .= "LEFT JOIN `" . DB_PREFIX . "address_book` AS `ABT` ON ABT.id = CA.address_id "; 
        $sql .= "WHERE (R2.session_id = '" . $this->access_token . "')";*/

        $getAllDropsBySession = $this->db->getAllRecords($sql);
		$containerarray       = array(); 
		foreach($getAllDropsBySession as $keysdrop => $valuedrop){
			
			$drop = $valuedrop['drop_name'];
			$containerarray[$valuedrop['temp_route_id']][$drop]['last_optimized_time'] = $this->_date_format($valuedrop['last_optimized_time'], true);
			$containerarray[$valuedrop['temp_route_id']][$drop]['route_name']      = $valuedrop['route_name'];
            $containerarray[$valuedrop['temp_route_id']][$drop]['postcode']        = $valuedrop['postcode'];
			
			if($valuedrop['instaDispatch_loadGroupTypeCode']!='' AND $valuedrop['shipment_service_type']!=''){
				$load_type = $valuedrop['instaDispatch_loadGroupTypeCode'].'_'.$valuedrop['shipment_service_type'];
				$load_group_type                                                       = $this->_get_load_type($load_type);
				$service_type                                                          = $this->_get_service_type($valuedrop['shipment_service_type']);
				$containerarray[$valuedrop['temp_route_id']][$drop]['shipments'][]     = array('shipment_type'=>$valuedrop['shipment_type'],'shipment_volume'=>$valuedrop['shipment_volume'],'shipment_weight'=>$valuedrop['shipment_weight'],'shipment_id'=>$valuedrop['shipment_id'],'ticket'=>$valuedrop['shipment_ticket'],'error_flag'=>$valuedrop['error_flag'],'shipment_total_item'=>$valuedrop['shipment_total_item'],'docket_number'=>$valuedrop['docket_number'],'type'=>$service_type,'postcode'=>$valuedrop['postcode'],'service_date'=>$this->_date_format($valuedrop['service_date']),'warehouse_status'=>$valuedrop['is_receivedinwarehouse'],'total_attempt'=>$valuedrop['shipment_total_attempt'],
				'create_date'=>$this->_date_format($valuedrop['shipment_create_date'])); 
						
			
			$shipment_count = count($containerarray[$valuedrop['temp_route_id']][$drop]['shipments']);
			$containerarray[$valuedrop['temp_route_id']][$drop]['shipment_count']  = $shipment_count;
			$containerarray[$valuedrop['temp_route_id']][$drop]['drop_type']       = $load_group_type;
			}
			
			$containerarray[$valuedrop['temp_route_id']][$drop]['temp_route_id']   = $valuedrop['temp_route_id'];
			$containerarray[$valuedrop['temp_route_id']][$drop]['route_type']      = $valuedrop['route_type'];
			$containerarray[$valuedrop['temp_route_id']][$drop]['latitude']        = $valuedrop['shipment_latitude'];
			$containerarray[$valuedrop['temp_route_id']][$drop]['longitude']       = $valuedrop['shipment_longitude'];

            $containerarray[$valuedrop['temp_route_id']][$drop]['shipment_latlong'] = $valuedrop['shipment_latitude'].','.$valuedrop['shipment_longitude'];
			

            $containerarray[$valuedrop['temp_route_id']][$drop]['execution_order'] = $valuedrop['drop_execution_order'];
			$containerarray[$valuedrop['temp_route_id']][$drop]['warehouse_id']    = $valuedrop['warehouse_id'];
			
			//$containerarray[$valuedrop['temp_route_id']][$drop]['drop_error']  = 0;
			
			if(!isset($containerarray[$valuedrop['temp_route_id']][$drop]['drop_error']) || $containerarray[$valuedrop['temp_route_id']][$drop]['drop_error']!=1){
				$containerarray[$valuedrop['temp_route_id']][$drop]['drop_error']  = $valuedrop['error_flag'];
			}
			
			
			$tdrop                                                                 = ($valuedrop['estimatedtime'] != '') ? $valuedrop['estimatedtime'] + ceil((($shipment_count * $this->buffer_time_of_each_shipment) + $this->buffer_time_of_drop) / 60) : 0;
			$containerarray[$valuedrop['temp_route_id']][$drop]['eta']             = $tdrop;
			//$containerarray[$valuedrop['temp_route_id']][$drop]['drop_type']       = $load_group_type;	
		}
		//ksort($containerarray);
        return $containerarray;
	}
    //Roopesh
	public function addRouteBox(){
        
       
		$getLastCustomRoute = $this->db->getOneRecord("SELECT temp_route_id,route_name FROM `icargo_temp_routes` where custom_route = 'Y' AND session_id = '".$this->access_token."' ORDER BY `icargo_temp_routes`.`temp_route_id` DESC");
		if($getLastCustomRoute!=''){
			$customRoute = explode('_',$getLastCustomRoute['route_name']);
			$routeName = $customRoute[1] + 1; 
			$routeName = 'Custom_'.$routeName;
			$insertData =  $this->db->save("temp_routes",array('custom_route'=>'Y','route_id'=>0,'route_name'=>$routeName,'session_id'=>$this->access_token,'status'=>1,'company_id'=>$this->company_id,'warehouse_id'=>$this->warehouse_id,'route_type'=>$this->routetype));
		}else{
			$insertData =  $this->db->save("temp_routes",array('custom_route'=>'Y','route_id'=>0,'route_name'=>'Custom_0','session_id'=>$this->access_token,'status'=>1,'company_id'=>$this->company_id,'warehouse_id'=>$this->warehouse_id,'route_type'=>$this->routetype));
		}
        $this->addRouteBoxShipment($insertData);
		if($insertData!= NULL){
			$response["status"] = "success";
			$response["message"] = "Route Box added successfully";
		}else{
			$response["status"] = "error";
			$response["message"] = "Failed to add route box. Please try again";
		}
		return $response;
	}
	
	public function removeRouteBox($routeId){
		$deleteData =  $this->db->delete("DELETE FROM `icargo_temp_routes` WHERE `icargo_temp_routes`.`temp_route_id` = $routeId");
		if($deleteData!= NULL){
			$deleteTempShipment =  $this->db->delete("DELETE FROM `icargo_temp_routes_shipment` WHERE `icargo_temp_routes_shipment`.`temp_route_id` = $routeId");
			if($deleteTempShipment!= NULL){
				$response["status"] = "success";
				$response["message"] = "Route Box removed successfully";
			}else{
				$response["status"] = "error";
				$response["message"] = "Failed to remove route box. Please try again";
			}
		}else{
			$response["status"] = "error";
			$response["message"] = "Failed to remove route box. Please try again";
		}
		return $response;
	}
	
	private function _add_temp_routes($roughtkey, $drops,$routeType)
    {  
        foreach ($drops as $key => $row) {
            $mid[$key] = $row['instaDispatch_docketNumber'];
        }
        array_multisort($mid, SORT_DESC, $drops);
        $count = 0;
        if (count($drops) > 0) {
            $temprouteId = ($count == 0) ? '' : $this->_create_temp_route($roughtkey,$routeType);
            $routeDrop   = ($roughtkey == 0) ? $this->drop_count : $this->_get_allowed_drop_of_route($roughtkey);
            $loopcounter = 0;
            foreach ($drops as $keys => $valData) {
                $shipment_service_type = strtolower($valData['shipment_service_type']);
                if (strtolower($valData['instaDispatch_loadGroupTypeCode']) != 'same') {
					if (($count % ($routeDrop)) == 0) {
                        $temprouteId = $this->_create_temp_route($roughtkey,$routeType);
                        $count       = 0;
                    }
                    $executionorder = $count + 1;
                }
                else {
                    if ($shipment_service_type == 'p') {
                        $executionorder = 1;
                        $this->collectionShip = $valData;
                    }
                    else {
                        $executionorder = $valData['shipment_executionOrder'] + 1;
                    }
                    if ($count == 0) {
                        $temprouteId = $this->_create_temp_route($roughtkey,$routeType);
                        $count       = 0;
                        if($shipment_service_type != 'p'){
                           $this->collectionShip['shipment_ticket'] = $this->collectionShip['shipment_ticket'].'V'.rand(45,230);
                           $this->_add_temp_shipment($this->collectionShip, $temprouteId, $executionorder);  
                        }
                    }
                    if ($loopcounter > 0) {
                        if ($shipment_service_type == 'p') {
                            $temprouteId = $this->_create_temp_route($roughtkey,$routeType);
                            $count       = 0;
                        }
                    }
                }
                $this->_add_temp_shipment($valData, $temprouteId, $executionorder);
                $count++;
                $loopcounter++;
            }
        }
    }
	private function _get_drop($data)
	{ 
		return $this->common_obj->getDropName(array("postcode"=>$data['shipment_postcode'],"address_1"=>$data['shipment_address1']),true);
		//return $data['shipment_postcode'].' '.$data['shipment_address1'];
	}
    private function _add_temp_shipment($valData, $tempRid, $ordernum)
    {
       
		$drop                            = $this->_get_drop($valData);
		$dataArr                         = array();
		$dataArr['drop_name']            = $drop;
		$dataArr['shipment_id']          = $valData['shipment_id'];
		$dataArr['temp_route_id']        = $tempRid;
		$dataArr['temp_shipment_ticket'] = $valData['shipment_ticket'];//$valDa;
        $dataArr['shipment_type']        = $valData['shipment_service_type'];//$valDa;
        $dataArr['job_type']             = $valData['instaDispatch_loadGroupTypeCode'];//$valDa;
        $dataArr['load_identity']        = $valData['instaDispatch_loadIdentity'];//$valDa;
        $dataArr['session_id']           = $this->access_token;
		$dataArr['drag_temp_route_id']   = $tempRid;
		$dataArr['execution_order']      = $ordernum;
        $dataArr['drop_execution_order'] = $ordernum;
        $dataArr['distancemiles']        = 0;
        $dataArr['estimatedtime']        = 0;
		$dataArr['status']               = 1;
		$dataArr['company_id']           = $this->company_id;
		$this->db->save("temp_routes_shipment", $dataArr);
    }
    
    private function _get_route_name($roughtkey)
    {
        $route_name = "";
        
        foreach($this->route_postcode as $route_postcode)
        {
            if($route_postcode['route_id']==$roughtkey)
            {
                $route_name = $route_postcode['route_name'];
                break;
            }
        }
        return $route_name;
    }
    
    private function _create_temp_route($roughtkey,$routeType)
    {
        
        $route_name = $this->_get_route_name($roughtkey);
        
        $routeArray                 = array();
        $routeArray['custom_route'] = ($roughtkey == 0) ? 'Y' : 'N';
        $routeArray['route_id']     = $roughtkey;
        $routeArray['route_name']   = ($roughtkey == 0) ? 'Custom ' : $route_name;//$this->shipment_route['route_name'];
        $routeArray['route_name']   = $this->_check_route_name_exist_in_temp($routeArray['route_name'], '1');
        $routeArray['session_id']   = $this->access_token;
        $routeArray['status']       = 1;
        $routeArray['last_optimized_time']       = date('Y-m-d');
        $routeArray['company_id']   = $this->company_id;
		$routeArray['warehouse_id'] = $this->warehouse_id;
        $routeArray['route_type']   = $routeType;
        
        /*if($roughtkey==2){
            

        }*/
        
        return $this->db->save("temp_routes", $routeArray);
    }
    private function _get_allowed_drop_of_route($rid)
    {
        $record = $this->db->getOneRecord("SELECT allowed_drops AS allowed_drops FROM " . DB_PREFIX . "routes WHERE id = " . $rid);
        return $record['allowed_drops'];
    }
    private function _check_route_name_exist_in_temp($routeName, $s)
    {
        for ($i = 0; $i < 45; $i++) {
            $sql    = "SELECT COUNT(1) AS exist FROM " . DB_PREFIX . "temp_routes WHERE route_name = '" . $routeName . '_' . $i . "' AND session_id = '" . $this->access_token . "'";
            $record = $this->db->getOneRecord($sql);
            if ($record['exist']) {
            }
            else {
                $routeName = $routeName . '_' . $i;
                break;
            }
        }
        return $routeName;
    }
    public function sameDayDriverAssign($data)
    {
        require_once dirname(dirname(__FILE__))."/firebase/route-assign.php";
        require_once dirname(dirname(__FILE__))."/firebase/model/rest.php";
        
        $common_obj = new Common();
        $shipment_tickets    = explode(',', $this->shipment_ticket);
        $vehicleIdofDriver = $this->_get_driver_data_by_id($this->driver_id);

        if ((count($shipment_tickets) > 0) && ($this->driver_id != '') && (is_numeric($this->driver_id))) {
			$timestamp = (isset($this->assign_time)) ? strtotime($this->assign_time) : strtotime(date('Y-m-d H:i:s'));
			$assign_time = date('H:i:s',$timestamp);
			$assign_date = date('Y-m-d',$timestamp);
            // Create a Route
            $insertData = array(
                'route_id' => '0',
                'custom_route'      => 'Y',
                'route_name'        => $this->route_name,//'custom_1.' . rand(1, 50),
                'driver_id'         => $this->driver_id,
                'create_date'       => date("Y-m-d"),
                'assign_start_time' => date("h:i:s", $timestamp),//str_replace(' ', '', $this->assign_time . ':00'),
                'is_active'         => 'Y',
                'is_optimized'      => 'NO',
                'optimized_type'    => 'N',
                'status'            => '1',
                'company_id'        => $this->company_id,
                'warehouse_id'      => $this->warehouse_id
            );
            
            $shipment_routed_id    = $this->db->save('shipment_route', $insertData);
            // Assign Route to Driver
            foreach ($shipment_tickets as $shipment_ticket) {
                $shipdata      = $this->_get_shipment_detail_sameday($shipment_ticket);
               
                $insertData    = array(
                    'shipment_ticket'   => $shipment_ticket,
                    'driver_id'         => $this->driver_id,
                    'vehicle_id'        => $vehicleIdofDriver['vehicle_id'],
                    'shipment_route_id' => $shipment_routed_id,
                    'assigned_date'     => date("Y-m-d"),
                    'assigned_time'     => date("H:m:s"),
                    'create_date'       => date("Y-m-d"),
                    'execution_order'   => $shipdata['shipment_executionOrder'],
                    'distancemiles'     => $shipdata['distancemiles'],
                    'estimatedtime'     => $shipdata['estimatedtime']
                );
                $columnNames   = array(
                    'shipment_ticket',
                    'driver_id',
                    'vehicle_id',
                    'shipment_route_id',
                    'assigned_date',
                    'assigned_time',
                    'create_date',
                    'execution_order',
                    'distancemiles',
                    'estimatedtime'
                );
                $drshipid      = $this->db->save('driver_shipment', $insertData);
                // Update Cargo shipment
                
                $status        = $this->db->updateData("UPDATE " . DB_PREFIX . "shipment SET shipment_assigned_service_date = '".$assign_date."',shipment_assigned_service_time = '".$assign_time."', is_driver_assigned = 1,company_id='$this->company_id',warehouse_id='$this->warehouse_id',shipment_assigned_service_time='" . date("H:m:s") . "',is_shipment_routed='1',assigned_driver='$this->driver_id',assigned_vehicle='" . $vehicleIdofDriver['vehicle_id'] . "',current_status='O',icargo_execution_order='" . $shipdata['shipment_executionOrder'] . "',distancemiles='" . $shipdata['distancemiles'] . "',estimatedtime='" . $shipdata['estimatedtime'] . "',shipment_routed_id='$shipment_routed_id' WHERE shipment_ticket = '$shipment_ticket'");

                $historyOfShip = $common_obj->addShipmentlifeHistory($shipment_ticket, 'Assign to Driver', $this->driver_id, $shipment_routed_id, $this->company_id, $this->warehouse_id, "ASSIGNTODRIVER", 'controller');
            }
            if($historyOfShip) {
                $firebaseObj = new Firebase_Route_Assign(array("driver_id"=>$this->driver_id,"route_id"=>$shipment_routed_id,"warehouse_id"=>$this->warehouse_id,"email"=>$this->email,"company_id"=>$this->company_id));
                
                return array(
                    'status' => true,
                    'message' => 'Requested Route has been Assigned to driver',
                    'post_data' => $firebaseObj->getCurrentAssignedRouteData()
                );
            }
        }
        else {
            return array(
                'status' => false,
                'message' => 'An error occured while assigning route to driver'
            );
        }
    }
    public function addShipmentlifeHistory($tickets, $action, $driverid, $routeid, $actionCode, $companyid)
    {
        $allShipParceldetails = $this->_get_shipment_parcel_status_details($tickets);
        if (count($allShipParceldetails) > 0) {
            foreach ($allShipParceldetails as $shipdetails) {
                $insertData    = array(
                    'shipment_ticket' => $tickets,
                    'company_id' => $companyid,
                    'parcel_ticket' => isset($shipdetails['parcel_ticket']) ? $shipdetails['parcel_ticket'] : '',
                    'instaDispatch_pieceIdentity' => isset($shipdetails['instaDispatch_pieceIdentity']) ? $shipdetails['instaDispatch_pieceIdentity'] : '',
                    'instaDispatch_loadIdentity' => isset($shipdetails['instaDispatch_loadIdentity_parcel']) ? $shipdetails['instaDispatch_loadIdentity_parcel'] : $shipdetails['instaDispatch_loadIdentity'],
                    'create_date' => date("Y-m-d"),
                    'actions' => $action,
                    'internel_action_code' => $actionCode,
                    'driver_id' => isset($driverid) ? $driverid : '',
                    'route_id' => isset($routeid) ? $routeid : '',
                    'action_taken_by' => 'controller',
                    'create_time'=>date('H:m:s')
                );
                $columnNames   = array(
                    'shipment_ticket',
                    'company_id',
                    'parcel_ticket',
                    'instaDispatch_pieceIdentity',
                    'instaDispatch_loadIdentity',
                    'create_date',
                    'actions',
                    'internel_action_code',
                    'driver_id',
                    'route_id',
                    'action_taken_by',
                    'create_time'
                );
                $historyOfShip = $this->db->insertIntoTable($insertData, $columnNames, DB_PREFIX . 'shipment_life_history');
            }
        }
        return true;
    }
    private function _get_driver_data_by_id($driverid)
    {
        $sql     = "SELECT * FROM " . DB_PREFIX . "driver_vehicle AS t1 WHERE t1.driver_id = $driverid";
        $records = $this->db->getRowRecord($sql);
        return $records;
    }
    private function _get_shipment_detail_sameday($ticket)
    {
        $sql     = "SELECT * FROM " . DB_PREFIX . "shipment AS t1 WHERE t1.shipment_ticket = '$ticket'";
        $records = $this->db->getRowRecord($sql);
        return $records;
    }
    private function _get_shipment_parcel_status_details($ticket)
    {
        $sql    = "SELECT t1.*,t2.parcel_ticket,t2.instaDispatch_pieceIdentity,t2.instaDispatch_loadIdentity AS instaDispatch_loadIdentity_parcel,t3.name,t4.route_name FROM " . DB_PREFIX . "shipment AS t1 LEFT JOIN " . DB_PREFIX . "shipments_parcel AS t2 ON t1.shipment_ticket = t2.shipment_ticket LEFT JOIN " . DB_PREFIX . "users AS t3 ON t1.assigned_driver = t3.id LEFT JOIN " . DB_PREFIX . "shipment_route AS t4 ON t1.shipment_routed_id = t4.shipment_route_id WHERE t1.shipment_ticket = '$ticket'";
        $result = $this->db->getAllRecords($sql);
        return $result;
    }
    public function getActiveMoveToDisputeActions()
    {
        $sql    = "SELECT * FROM " . DB_PREFIX . "dispute_actions AS t1 WHERE t1.status = 1";
        $result = $this->db->getAllRecords($sql);
        return $result;
    }
    public function moveToDispute()
    {
        $jsonData    = explode(',', $this->shipment_ticket);
        $disputeid   = $this->disputeid;
        $companyid   = $this->company_id;
        $warehouseid = $this->warehouse_id;
        $ticketids   = '"' . implode('","', $jsonData) . '"';
        
        
        
        $reqDate = date('Y-m-d');
        
        $status      = $this->db->updateData("UPDATE " . DB_PREFIX . "shipment SET current_status = 'Dis',disputeid= $disputeid,disputedate='$reqDate',company_id='" . $companyid . "',warehouse_id='" . $warehouseid . "' WHERE shipment_ticket IN(" . $ticketids . ")");
        if ($status) {
            foreach ($jsonData as $ticket) {
                $this->addShipmentlifeHistory($ticket, 'Move to Disputed #' . $disputeid, 0, 0, 'DISPUTED', $companyid);
            }
            return array(
                'status' => true,
                'message' => 'Selected shipments has been moved to disputed shipment'
            );
        }
        else {
            return array(
                'status' => true,
                'message' => 'Error while moving shipments to disputed shipments'
            );
        }
    }
	
	
	public function getAllPendingJobCount(){
		//$sql = 'SELECT count(*) as pending_job_count FROM `icargo_shipment` as t1 WHERE t1.company_id = "'.$this->company_id.'" AND t1.warehouse_id = "'.$this->warehouse_id.'" AND t1.current_status="C"';
		$sql = 'SELECT t1.* FROM `icargo_shipment` as t1 WHERE t1.company_id = "'.$this->company_id.'" AND t1.warehouse_id = "'.$this->warehouse_id.'" AND t1.current_status="C"';
		$records = $this->db->getAllRecords($sql);
		$sameDayCount = 0;
		$nextDayCount = 0;
		$countArr = array();
		foreach($records as $record){
			if(strtolower($record['instaDispatch_loadGroupTypeCode'])=='same')
				$sameDayCount++;
			else
				$nextDayCount++;
		}
		$countArr = array('nextdaycount'=>$nextDayCount,'samedaycount'=>$sameDayCount);
		return $countArr;
	}

    public function getRouteNameByRouteId($route_id){
        $sql = "SELECT route_name FROM " . DB_PREFIX . "shipment_route WHERE shipment_route_id = $route_id";
        return $this->db->getRowRecord($sql);
    }

    public function getRunsheetData($routeId){
        $sql = "SELECT R1.icargo_execution_order, R1.instaDispatch_docketNumber,ABT.postcode AS shipment_postcode,ABT.address_line1 AS address_line1,ABT.address_line2 AS address_line2,R1.shipment_total_item,R1.shipment_required_service_date,R1.shipment_required_service_endtime,R1.shipment_required_service_starttime,R1.shipment_customer_name AS consignee_name 
        FROM " . DB_PREFIX ."shipment AS `R1`
        INNER JOIN " . DB_PREFIX . "address_book AS ABT ON ABT.id=R1.address_id WHERE R1.shipment_routed_id = $routeId";
        $records = $this->db->getAllRecords($sql);
        $routeData = $this->getRouteNameByRouteId($routeId);
        return array("route_name"=>$routeData["route_name"],"run_sheet_data"=>$records);
    }

	public function getRunsheetDataBKP($routeId){
		$sql = 'SELECT R1.*,R2.`route_name` FROM `icargo_shipment` AS `R1` LEFT JOIN `icargo_shipment_route` AS R2 ON R1.shipment_routed_id = R2.shipment_route_id WHERE R1.shipment_routed_id = "'.$routeId.'"';
		$records = $this->db->getAllRecords($sql);
		return $records;
	}
    
	public function getSearchData($serchParam){
		$sql = 'SELECT t1.*,t2.route_name as route_name,t3.name as driver_name FROM `icargo_shipment` as t1 LEFT JOIN `icargo_shipment_route` AS t2 ON t1.shipment_routed_id = t2.shipment_route_id LEFT JOIN `icargo_users` AS t3 ON t2.driver_id = t3.id WHERE t1.company_id = "'.$this->company_id.'" AND (t1.`shipment_postcode` LIKE "%'.$serchParam.'%" OR t1.`instaDispatch_docketNumber` LIKE "%'.$serchParam.'%" OR t1.`shipment_ticket` LIKE "%'.$serchParam.'%")';
		$records = $this->db->getAllRecords($sql);
		//return $records;	
        $temp    = array();
        $data = array();
        foreach ($records as $key => $record) {
            $load_group_type_code = strtolower($record["instaDispatch_loadGroupTypeCode"]);
            if ($record["shipment_service_type"] == "D") {
                $service_type = "Delivery";
            }
            else if ($record["shipment_service_type"] == "P") {
                $service_type = "Collection";
            }
            if ($load_group_type_code == "vendor") {
                $type = "Retail";
            }
            else if ($load_group_type_code == "next") {
                $type = "Next Day";
            }
            else if ($load_group_type_code == "phone") {
                $type = "Phone";
            }
            //suppressing type variable temporary
            $type = $load_group_type_code;
            
            if($load_group_type_code =="phone" || $load_group_type_code =="vendor"){
                $type = "retail";
            }
			   
               $data[$key]["docket_no"]       = $record["instaDispatch_docketNumber"];
			   $data[$key]["reference_no"]    = $record["instaDispatch_loadIdentity"];
			   $data[$key]["service_type"]    = $service_type;//"$service_type(" . $data[$key]["count"] . ")";
			   $data[$key]["service_date"]    = $record["shipment_required_service_date"];
			   $data[$key]["address_1"]       = $record["shipment_address1"];
			   $data[$key]["postcode"]        = $record["shipment_postcode"];
			   $data[$key]["shipment_ticket"] = $record["shipment_ticket"];
			   $data[$key]["route_name"]      = ($record["route_name"]=='') ? 'N/A': $record["route_name"];
			   $data[$key]["driver_name"]     = ($record["driver_name"]=='') ? 'N/A': $record["driver_name"];
			   $data[$key]["action"]          = "<a>Detail</a>";
            /*if ($record["shipment_service_type"] == "P") {
                $temp[$record["shipment_postcode"]][]                  = $record["shipment_ticket"];
                $data[$record["shipment_postcode"]]["shipment_ticket"] = implode(',', $temp[$record["shipment_postcode"]]); //$record["shipment_ticket"];
                $data[$record["shipment_postcode"]]["docket_no"]       = $record["instaDispatch_docketNumber"];
                $data[$record["shipment_postcode"]]["reference_no"]    = $record["instaDispatch_loadIdentity"];
                $data[$record["shipment_postcode"]]["service_date"]    = $record["shipment_required_service_date"];
                $data[$record["shipment_postcode"]]["service_time"]    = $record["shipment_required_service_starttime"];
                $data[$record["shipment_postcode"]]["weight"]          = $record["shipment_total_weight"];
                $data[$record["shipment_postcode"]]["postcode"]        = $record["shipment_postcode"];
                $data[$record["shipment_postcode"]]["attempt"]         = $record["shipment_total_attempt"];
                $data[$record["shipment_postcode"]]["in_warehouse"]    = $record["is_receivedinwarehouse"];
                $data[$record["shipment_postcode"]]["type"]            = $type;
                $data[$record["shipment_postcode"]]["parcels"]         = $this->_get_parcel_data_by_shipment_id($record["shipment_id"]);
                $data[$record["shipment_postcode"]]["action"]          = "<a>Detail</a>";
                $data[$record["shipment_postcode"]]["count"]           = (isset($data[$record["shipment_postcode"]]["count"])) ? ++$data[$record["shipment_postcode"]]["count"] : 1;
                $data[$record["shipment_postcode"]]["service_type"]    = "$service_type(" . $data[$record["shipment_postcode"]]["count"] . ")";
            }
            else {
                $temp[$key][]                  = $record["shipment_ticket"];
                $data[$key]["shipment_ticket"] = implode(',', $temp[$key]);
                $data[$key]["docket_no"]       = $record["instaDispatch_docketNumber"];
                $data[$key]["reference_no"]    = $record["instaDispatch_loadIdentity"];
                $data[$key]["service_date"]    = $record["shipment_required_service_date"];
                $data[$key]["service_time"]    = $record["shipment_required_service_starttime"];
                $data[$key]["weight"]          = $record["shipment_total_weight"];
                $data[$key]["postcode"]        = $record["shipment_postcode"];
                $data[$key]["attempt"]         = $record["shipment_total_attempt"];
                $data[$key]["in_warehouse"]    = $record["is_receivedinwarehouse"];
                $data[$key]["type"]            = $type;
                $data[$key]["parcels"]         = $this->_get_parcel_data_by_shipment_id($record["shipment_id"]);
                $data[$key]["action"]          = "<a>Detail</a>";
                $data[$key]["count"]           = (isset($data[$key]["count"])) ? ++$data[$key]["count"] : 1;
                $data[$key]["service_type"]    = "$service_type(" . $data[$key]["count"] . ")";
            }*/
        }
        $aoColumn = array("Docket No","Reference No","Service Type","Service Date","Address 1","Postcode","Shipment Ticket","Route Name","Driver Name","Action");
        return array("aoColumn" => $aoColumn,"aaData" => array_values($data));
	}
    
    public function addRouteBoxShipment($temprouteId){
        if($this->routetype=='SAMEDAY'){
        $date = date('Y-m-d');
        $sql     = "SELECT t1.* FROM " . DB_PREFIX . "temp_routes_shipment AS t1  WHERE t1.session_id = '$this->access_token' AND CAST(t1.create_date AS DATE) = '$date' AND t1.company_id = '$this->company_id' AND t1.shipment_type = 'P' AND t1.job_type = 'SAME'";
        $collectionship = $this->db->getRowRecord($sql);
        $valData =  array(); 
        $valData['shipment_ticket'] = $collectionship['temp_shipment_ticket'].'V'.rand(45,230);
        $valData['shipment_id'] = $collectionship['shipment_id'];
        $valData['shipment_postcode'] = $collectionship['drop_name'];
        $valData['shipment_address1'] = '';
        $valData['instaDispatch_loadGroupTypeCode'] = 'SAME';
        $valData['instaDispatch_loadIdentity'] = $collectionship['load_identity'];
        $valData['shipment_service_type'] = 'P'; 
        $this->_add_temp_shipment($valData, $temprouteId,1);
      } 
    }
}
?>