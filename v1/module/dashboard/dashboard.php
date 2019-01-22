<?php

class Dashboard extends Icargo
{

    public function __construct($data = array())
    {
        parent::__construct(array("email" => $data->email, "access_token" => $data->access_token));
    }

    public function getCarrierShipment($dataval = array())
    {

        $customer_id = $dataval->customer_id;
        $whereClause = $this->shipmentFilters($dataval);
        $sql = "SELECT carrier_code, customer_id, instaDispatch_loadGroupTypeCode, COUNT(*) as shipment_count, shipment_service_type FROM " . DB_PREFIX . "shipment WHERE 
                    $whereClause customer_id = $customer_id AND shipment_service_type = 'P' GROUP BY carrier_code";
        $result = $this->db->getAllRecords($sql);
        return array("status" => "success", "data" => $result);
    }

    public function shipmentFilters($data = array())
    {
        $whereClause = '';
        $shipment_type = strtoupper($data->shipment_type);
        $filter_type = strtoupper($data->filter);
        if ($shipment_type == "ALL" && $filter_type == "ALL") {
            $whereClause .= '';
        } elseif ($data->filter == "customrange") {

            $filter = (($shipment_type == "ALL") ? '' : "AND instaDispatch_loadGroupTypeCode = '$shipment_type'");
            $startDate = $data->custom_sdate;
            $endDate = $data->custom_edate;
            $whereClause .= "shipment_create_date BETWEEN '$startDate 00:00:00' AND '$endDate 00:00:00' $filter AND";
        } else {

           $filterDate = $this->dashboardFilter($data);
           $jsonObj = json_decode($filterDate);
           $startDate = $jsonObj->start_date;
           $endDate = $jsonObj->end_date;
           $shipment_type_fliter = (($shipment_type == "ALL") ? "shipment_create_date BETWEEN '$startDate 00:00:00' AND '$endDate 00:00:00' AND": (($filter_type == "ALL")  ? "instaDispatch_loadGroupTypeCode = '$shipment_type' AND" : "shipment_create_date BETWEEN '$startDate 00:00:00' AND '$endDate 00:00:00' AND instaDispatch_loadGroupTypeCode = '$shipment_type' AND"));
           $whereClause .= " $shipment_type_fliter ";
        }


        return $whereClause;
    }

    public function dashboardFilter($data)
    {

        $startDate = '';
        $endDate = '';
        /* Today  tomorrow  This week  Last week  This Month  Last Month This quarter Last Quarter Custom Range */
        $curDate = date('Y-m-d');
        if ($data->filter == 'today') {
            $startDate = $endDate = $curDate;
        } else if ($data->filter == 'tomorrow') {
            //$startDate = $endDate = date('Y-m-d', strtotime($curDate, '+1D'));
            $startDate = $endDate = date('m-d-Y', strtotime($curDate . "+1 days"));
        } else if ($data->filter == 'tweek') {
            $d = strtotime("today");
            $start_week = strtotime("last sunday midnight", $d);
            $end_week = strtotime("next saturday", $d);
            $startDate = date("Y-m-d", $start_week);
            $endDate = date("Y-m-d", $end_week);
        } else if ($data->filter == 'lweek') {
            $previous_week = strtotime("-1 week +1 day");

            $start_week = strtotime("last sunday midnight", $previous_week);
            $end_week = strtotime("next saturday", $start_week);

            $startDate = date("Y-m-d", $start_week);
            $endDate = date("Y-m-d", $end_week);

            //echo $start_week.' '.$end_week ;
        } else if ($data->filter == 'tmonth') {
            $startDate = date("Y-m-01");
            $endDate = date("Y-m-d");
        } else if ($data->filter == 'lmonth') {
            $startDate = date('Y-m-d', strtotime('first day of last month'));
            $endDate = date('Y-m-d', strtotime('last day of last month'));
        } else if ($data->filter == 'tquarter') {
            $current_month = date('m');
            $current_year = date('Y');
            if ($current_month >= 1 && $current_month <= 3) {
                $startDate = date('Y-m-d', strtotime('1-January-' . $current_year));  // timestamp or 1-Januray 12:00:00 AM
                $endDate = date('Y-m-d', strtotime('1-April-' . $current_year));  // timestamp or 1-April 12:00:00 AM means end of 31 March
            } else if ($current_month >= 4 && $current_month <= 6) {
                $startDate = date('Y-m-d', strtotime('1-April-' . $current_year));  // timestamp or 1-April 12:00:00 AM
                $endDate = date('Y-m-d', strtotime('1-July-' . $current_year));  // timestamp or 1-July 12:00:00 AM means end of 30 June
            } else if ($current_month >= 7 && $current_month <= 9) {
                $startDate = date('Y-m-d', strtotime('1-July-' . $current_year));  // timestamp or 1-July 12:00:00 AM
                $endDate = date('Y-m-d', strtotime('1-October-' . $current_year));  // timestamp or 1-October 12:00:00 AM means end of 30 September
            } else if ($current_month >= 10 && $current_month <= 12) {
                $startDate = date('Y-m-d', strtotime('1-October-' . $current_year));  // timestamp or 1-October 12:00:00 AM
                $endDate = date('Y-m-d', strtotime('1-January-' . ($current_year + 1)));  // timestamp or 1-January Next year 12:00:00 AM means end of 31 December this year
            }
        } else if ($data->filter == 'lquarter') {
            $current_month = date('m');
            $current_year = date('Y');

            if ($current_month >= 1 && $current_month <= 3) {
                $startDate = date('Y-m-d', strtotime('1-October-' . ($current_year - 1)));  // timestamp or 1-October Last Year 12:00:00 AM
                $endDate = date('Y-m-d', strtotime('1-January-' . $current_year));  // // timestamp or 1-January  12:00:00 AM means end of 31 December Last year
            } else if ($current_month >= 4 && $current_month <= 6) {
                $startDate = date('Y-m-d', strtotime('1-January-' . $current_year));  // timestamp or 1-Januray 12:00:00 AM
                $endDate = date('Y-m-d', strtotime('1-April-' . $current_year));  // timestamp or 1-April 12:00:00 AM means end of 31 March
            } else if ($current_month >= 7 && $current_month <= 9) {
                $startDate = date('Y-m-d', strtotime('1-April-' . $current_year));  // timestamp or 1-April 12:00:00 AM
                $endDate = date('Y-m-d', strtotime('1-July-' . $current_year));  // timestamp or 1-July 12:00:00 AM means end of 30 June
            } else if ($current_month >= 10 && $current_month <= 12) {
                $startDate = date('Y-m-d', strtotime('1-July-' . $current_year));  // timestamp or 1-July 12:00:00 AM
                $endDate = date('Y-m-d', strtotime('1-October-' . $current_year));  // timestamp or 1-October 12:00:00 AM means end of 30 September
            }
        }
        return json_encode(array('start_date' => $startDate, 'end_date' => $endDate));
    }


}