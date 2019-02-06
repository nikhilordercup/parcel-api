<?php
require_once "../v1/module/report/model/Route_Model.php";

class Report extends Icargo
    {
    private $_user_id;
    protected $_parentObj;

    var $driverShipmentInfo = array();
    var $reportLists = array();
    var $dailyDropRate = 0;
    var $averageTimePerDrop = 0;
    var $revenuePricePerJob = .95;

    private function _setUserId($v)
        {
        $this->_user_id = $v;
        }

    private function _getUserId()
        {
        return $this->_user_id;
        }

    public function __construct($data)
        {
        $this->_parentObj = parent::__construct(array(
            "email" => $data->email,
            "access_token" => $data->access_token
        ));

				$this->modelObj = new Route_Model();
        $this->commonObj = new Common();
        $this->libObj = new Library();
        }

		private function _getMeterToMiles($meter)
		    {
		    $meter = (int) $meter;
        return round($meter * 0.0006213712, 2);
		    }

		private function _getTimeInHourMinutes($sec)
				{
				$sec = (int) $sec;
		    return gmdate("H:i", $sec);
				}

		private function _getJobCountByType($type, $driver_id)
				{
        $items = $this->modelObj->findDriverShipmentBetweenDate($this->param->start_date, $this->param->end_date, $this->param->company_id, $driver_id, $type);
        $loadIdentity = array();
        foreach($items as $item){
            $loadIdentity[$item["load_identity"]] = $item["load_identity"];
        }
        return count($loadIdentity);
				}

		private function _getServiceTypeName()
				{
				if ($this->param->service_type == 'lastmile')
            $service_type = 'Vendor';
        elseif ($this->param->service_type == 'sameday')
            $service_type = 'SAME';
          else
            $service_type = 'NEXT';

				return $service_type;
				}

    private function _findSamedayRevenue($load_identity_str, $drop_count)
        {
        $item = $this->modelObj->findSamedayRevenue($load_identity_str);


        $revenuePrice = array(
            "driver_carrier_price_per_drop" => 0,
            "driver_customer_price_per_drop" => 0
        );

        if(isset($item["carrier_price"])){
            $pricePerDrop = $item["carrier_price"]/$drop_count;
            $revenuePrice["driver_carrier_price_per_drop"] = number_format($pricePerDrop, 2);
            $revenuePrice["driver_carrier_price"] = number_format($item["carrier_price"], 2);
        }

        if(isset($item["customer_price"])){
            $pricePerDrop = $item["customer_price"]/$drop_count;
            $revenuePrice["driver_customer_price_per_drop"] = number_format($pricePerDrop, 2);
            $revenuePrice["driver_customer_price"] = number_format($item["customer_price"], 2);
        }
        return $revenuePrice;
        }

    private function _findRevenuePrice($job_count, $load_identity_str, $drop_count)
        {
        $revenuePrice = 0;
        if($this->_getServiceTypeName()=="SAME"){
            $revenuePrice = $this->_findSamedayRevenue($load_identity_str, $drop_count);

            $revenuePrice = array(
                "driver_carrier_price_per_drop" => $revenuePrice["driver_carrier_price_per_drop"],
                "driver_customer_price_per_drop" => $revenuePrice["driver_customer_price_per_drop"],
                "driver_carrier_price" => $revenuePrice["driver_carrier_price"],
                "driver_customer_price" => $revenuePrice["driver_customer_price"]
            );
        }
        else{
            $revenuePrice = $this->revenuePricePerJob * $job_count;
            $revenuePrice = array(
                "driver_carrier_price_per_drop" => $this->revenuePricePerJob,
                "driver_customer_price_per_drop" => $this->revenuePricePerJob,
                "driver_carrier_price" => number_format($revenuePrice, 2),
                "driver_customer_price" => number_format($revenuePrice, 2),
            );
        }
        return $revenuePrice;
        }



        private function _findDriverDeliveredDrop($shipmentRouteList, $loadIdentityStr, $driverId){
            $dropInfo = $this->modelObj->findDriverDropInfo($shipmentRouteList, $loadIdentityStr, $driverId);

            $dropList = array();
            foreach($dropInfo as $item){
                array_push($dropList, $item["shipment_ticket"]);
            }
            return $dropList;
        }

		private function _findDriverTimeInfo()
				{
                $driverRouteData = $this->_findDriverInfo($this->_getServiceTypeName());




                $items = array();

                if(isset($driverRouteData["driver_data"])){
                    foreach($driverRouteData["driver_data"] as $driver_id => $item){



                        $shipmentRouteList = implode("','",$item["route_lists"]);

                        $loadIdentityStr = implode("','",$item["job_lists"]);


                        $routeInfo = $this->modelObj->findDriverTimeInfoByShipmentRouteId($shipmentRouteList);

                        $driverName = $this->modelObj->findDriverNameById($driver_id);

                        $dropInfo = $this->_findDriverDeliveredDrop($shipmentRouteList, $loadIdentityStr, $driver_id);

                        $items[$driver_id]["driver_name"] = $driverName["driver_name"];
                        $items[$driver_id]["driver_id"] = $driver_id;
                        $items[$driver_id]["time_taken"] = (int)$routeInfo["time_taken"];

                        $items[$driver_id]["total_job_count"] = count($item["job_lists"]);
                        $items[$driver_id]["total_route_count"] = count($item["route_lists"]);
                        $items[$driver_id]["total_drop_count"] = count($dropInfo);

                        $items[$driver_id]["shipment_route_id"] = $item["route_lists"];
                        $items[$driver_id]["job_lists"] = $item["job_lists"];
                        $items[$driver_id]["drop_lists"] = $dropInfo;
                    }
                }

                return $items;


				}

		private function _findDriverInfo($type)
				{
            if($type=='Vendor'){
                $shipmentInfo = $this->modelObj->findDriverTimeInfoForLastMile($this->param->start_date, $this->param->end_date, $type, $this->param->company_id);
            }else{
                $shipmentInfo = $this->modelObj->findDriverTimeInfo($this->param->start_date, $this->param->end_date, $type, $this->param->company_id);
            }

            $driverRouteData = array();

            foreach($shipmentInfo as $item){
                    $driverRouteData["driver_data"][$item["driver_id"]]["job_lists"][$item["load_identity"]] = $item["load_identity"];
                    $driverRouteData["driver_data"][$item["driver_id"]]["route_lists"][$item["shipment_route_id"]] = $item["shipment_route_id"];

                    $driverRouteData["all_job_lists"][$item["driver_id"]][$item["load_identity"]] = $item["load_identity"];

                }

                return $driverRouteData;
				}

    private function _findDaysDiff()
        {
        $earlier = new DateTime($this->param->start_date);
        $later = new DateTime($this->param->end_date);
        return $later->diff($earlier)->format("%a");
        }

    private function _findAllTransitDistance($load_identity_str)
        {
        $this->totalDistanceMeter = 0;
        $items = $this->modelObj->findTransitDistanceByLoadIdentity($load_identity_str);
        $transitDistance = array();

        foreach($items as $item)
            array_push($transitDistance, $item["transit_distance"]);
        if(count($transitDistance)>0)
            $this->totalDistanceMeter = array_sum($transitDistance);

        $this->totalDistanceMeter;
        }

    private function _findTotalMiles($load_identity_str)
        {
        $this->totalDistanceMiles = 0;
        $this->_findAllTransitDistance($load_identity_str);

        $this->totalDistanceMiles = $this->_getMeterToMiles($this->totalDistanceMeter);
        }

    private function _findTotalTime($time)
        {
        $this->totalTime = 0;
        if($time>0)
            $this->totalTime = $time;
        }

    private function _findAverageSpeedMiles()
        {
        $avg_speed = 0;
        if($this->totalTime>0)
            $avg_speed = $this->totalDistanceMeter / $this->totalTime;

        $this->averageSpeed = $this->_getMeterToMiles($avg_speed);
        }

    private function _findDailyDropRate($item)
        {
        if(isset($item["total_job_count"]) and $item["total_job_count"]>0)
            $this->dailyDropRate = round($item["total_drop_count"]/$item["total_job_count"], 2);
        else
            $this->dailyDropRate = 0.00;
        }

    private function _findAverageTimePerDrop()
        {
        if(isset($item["total_job_count"]) and $item["total_job_count"]>0)
            $this->averageTimePerDrop = round($this->totalTime/$item["total_job_count"], 2);
        else
            $this->averageTimePerDrop = 0.00;
        }

    private function _findAllActiveReportByCompanyId()
        {
        $items = $this->modelObj->findAllActiveReportByCompanyId($this->param->company_id, $this->param->service_type);
        $this->reportLists[$this->param->service_type] = $items;
        }

    private function _findDriverAllShipmentCount($driver_time_info)
        {

        $samedayJobsData = $this->_findDriverInfo("SAME");
        $nextdayJobsData = $this->_findDriverInfo("NEXT");
        $vendorJobsData = $this->_findDriverInfo("Vendor");


        $samedayJobCount = 0;
        if(isset($samedayJobsData["all_job_lists"])){
            foreach($samedayJobsData["all_job_lists"] as $key => $item){
                $samedayJobCount += count($item);
            }
        }

        $nextdayJobCount = 0;
        if(isset($nextdayJobsData["all_job_lists"])){
            foreach($nextdayJobsData["all_job_lists"] as $key => $item){
                $nextdayJobCount += count($item);
            }
        }

        $vendorJobCount = 0;
        if(isset($vendorJobsData["all_job_lists"])){
            foreach($vendorJobsData["all_job_lists"] as $key => $item){
                $vendorJobCount += count($item);
            }
        }

        return array(
            "sameday_job_count"  => $samedayJobCount,
            "nextday_job_count"  => $nextdayJobCount,
            "lastmile_job_count" => $vendorJobCount
        );

        }

    private function _generateDerReport()
        {
		$driverTimeInfo = $this->_findDriverTimeInfo();//$this->_findDriverInfo();

        $noOfDays = $this->_findDaysDiff();
        $allActiveReportByCompanyId = $this->_findAllActiveReportByCompanyId();

        $reportData = array();
        $jobCountInfo = array(
          "nextday_job_count" =>array(),
          "sameday_job_count" =>array(),
          "lastmile_job_count" =>array()
        );

        foreach($driverTimeInfo as $driver_id => $item){
            $load_identity_str = implode("','", $item["job_lists"]);

            $this->_findTotalTime($item["time_taken"]);
            $this->_findTotalMiles($load_identity_str);
            $this->_findAverageSpeedMiles();
            $this->_findDailyDropRate($item);
            $this->_findAverageTimePerDrop($item);

            $totalJobCount = $item["total_job_count"];
            $totalDropCount = $item["total_drop_count"];

            $reportData[$driver_id]["no_of_jobs"] = $item["total_job_count"];
            $reportData[$driver_id]["no_of_drops"] = $totalDropCount;

            $reportData[$driver_id]["total_time_taken"] = $item["time_taken"];
            $reportData[$driver_id]['driver_name'] = $item["driver_name"];

            $reportData[$driver_id]['total_distance_meter'] = ($this->param->service_type=="sameday") ? $this->totalDistanceMeter : "N/A";
            $reportData[$driver_id]['total_distance_miles'] = ($this->param->service_type=="sameday") ? $this->totalDistanceMiles : "N/A";

            $reportData[$driver_id]['start_date'] = $this->libObj->date_format($this->param->start_date);
            $reportData[$driver_id]['end_date'] = $this->libObj->date_format($this->param->end_date);

            $reportData[$driver_id]['average_speed'] = ($this->param->service_type=="sameday") ? $this->averageSpeed : "N/A";

            $reportData[$driver_id]['daily_drop_rate'] = $this->dailyDropRate;

            $reportData[$driver_id]['average_time_per_drop'] = $this->_getTimeInHourMinutes($this->averageTimePerDrop);
            $reportData[$driver_id]['time_taken_in_hr_min'] = $this->_getTimeInHourMinutes($this->totalTime);

            $price = $this->_findRevenuePrice($totalJobCount, $load_identity_str, $totalDropCount);

            $reportData[$driver_id]['driver_carrier_price_per_drop'] = round($price["driver_carrier_price_per_drop"], 2);
            $reportData[$driver_id]['driver_customer_price_per_drop'] = round($price["driver_customer_price_per_drop"], 2);

            $reportData[$driver_id]['driver_carrier_price'] = round($price["driver_carrier_price"], 2);
            $reportData[$driver_id]['driver_customer_price'] = round($price["driver_customer_price"], 2);

            $reportData[$driver_id]['no_of_days'] = $noOfDays;
        }

        $allJobCount = $this->_findDriverAllShipmentCount($driverTimeInfo);

        return array(
          "status" => "success",
          "nextday_route_count" => $allJobCount["nextday_job_count"],
          "sameday_route_count" => $allJobCount["sameday_job_count"],
          "lastmile_route_count" => $allJobCount["lastmile_job_count"],
          "report_data" => array_values($reportData),
          "report_list" => $this->reportLists
        );
        }

    public function generateReport($param)
        {
        $this->param = $param;
        $this->param->start_date = date("Y-m-d" , strtotime($this->param->start_date));
        $this->param->end_date = date("Y-m-d" , strtotime($this->param->end_date));
        if($param->report_type=="DER")
            return $this->_generateDerReport();
        }

    public function downloadReportCsv($param)
        {
        $basePath = Library::_getInstance()->base_path();
        $downloadPath = Library::_getInstance()->get_api_url();
        $folder = "output";

        $reportData = array();
        foreach($param->data as $item)
            {
            array_push($reportData, array(
                $item->driver_name,
                $item->start_date,
                $item->end_date,
                $item->time_taken_in_hr_min,
                $item->no_of_drops,
                $item->no_of_jobs,
                $item->no_of_days,
                $item->total_distance_miles,
                $item->daily_drop_rate,
                $item->average_speed,
                $item->average_time_per_drop,
                $item->driver_carrier_price,
                $item->driver_customer_price
            ));
            }
        $fileName = time() . ".csv";
        $path = "{$basePath}{$folder}/$fileName";

      // create a file pointer connected to the output stream
        $file = fopen($path, 'w');
        // send the column headers
        $headers = array(
            'Driver Name',
            'Start Date',
            'End Date',
            'Total Time On Job',
            'Total Drops',
            'Total Jobs',
            'Days',
            'Total Miles',
            'Daily Drop Rate',
            'Average Speed',
            'Average Time Per Drop',
            'Carrier Revenue',
            'Customer Revenue'
        );
        fputcsv($file, $headers);
        // output each row of the data
        foreach($reportData as $row)
            {
            fputcsv($file, $row);
            }
        return array(
            "status" => "success",
            "message" => "csv generated successfully",
            "file_path" => "{$downloadPath}{$folder}/{$fileName}"
        );
        }
    }
?>
