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

		private function _getRouteCountByType($type, $start_date, $end_date, $company_id)
				{
		    $result = $this->modelObj->findRouteCountBetweenDate($type, $start_date, $end_date, $company_id);
        if(isset($result["route_count"]))
				    return $result["route_count"];
        else
            return 0;
				}

		private function _getAllRouteCount()
				{
		    return array(
				    "nextday_route_count" => $this->_getRouteCountByType('NEXT', $this->param->start_date, $this->param->end_date, $this->param->company_id),
						"sameday_route_count" => $this->_getRouteCountByType('SAME', $this->param->start_date, $this->param->end_date, $this->param->company_id),
						"lastmile_route_count" => $this->_getRouteCountByType('Vendor', $this->param->start_date, $this->param->end_date, $this->param->company_id)
				);
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

    private function _findDriverShipmentBetweenDate($driver_id)
        {
        $items = $this->modelObj->findDriverShipmentBetweenDate($this->param->start_date, $this->param->end_date, $this->param->company_id, $driver_id);
        $totalJobs = count($items);
        $this->driverShipmentInfo = array();
        $driverShipmentInfo = array();
        foreach($items as $item){
            $dropName = $this->commonObj->getDropName(array("postcode"=>$item["shipment_postcode"], "address_1"=>$item["shipment_address1"]));
            $driverShipmentInfo[$item["load_Identity"]] = $dropName;
        }
        $this->driverShipmentInfo = array(
          "total_jobs" => $totalJobs,
          "total_drops" => count($driverShipmentInfo)
        );
        }

		private function _findDriverTimeInfo()
				{
		    $driverTimeInfo = $this->modelObj->findDriverTimeInfo($this->param->start_date, $this->param->end_date, $this->_getServiceTypeName(), $this->param->company_id);
				$items = array();
				foreach($driverTimeInfo as $item){
            $driverName = $this->modelObj->findDriverNameById($item["driver_id"]);
            $items[$item["driver_id"]]["driver_name"] = $driverName["driver_name"];
            $items[$item["driver_id"]]["driver_id"] = $item["driver_id"];
            $items[$item["driver_id"]]["time_taken"][] = $item["time_taken"];
            $items[$item["driver_id"]]["shipment_route_id"][$item["shipment_route_id"]] = $item["shipment_route_id"];
				}
				return array_values($items);
				}

		private function _findDriverInfo()
				{
		    return $this->_findDriverTimeInfo();
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

    private function _findTotalMiles($shipment_route_id)
        {
        $this->totalDistanceMiles = 0;
        $items = $this->modelObj->findLoadIdentityByShipmentTicket($shipment_route_id);
        $loadIdentity = array();
        foreach($items as $item)
            $loadIdentity[$item["load_identity"]] = $item["load_identity"];

        $loadIdentityStr = implode("','",$loadIdentity);
        $this->_findAllTransitDistance($loadIdentityStr);

        $this->totalDistanceMiles = $this->_getMeterToMiles($this->totalDistanceMeter);
        }

    private function _findTotalTime($time)
        {
        $this->totalTime = 0;
        if(count($time)>0)
            $this->totalTime = array_sum($time);
        }

    private function _findAverageSpeedMiles()
        {
        $avg_speed = $this->totalDistanceMeter / $this->totalTime;
        $this->averageSpeed = $this->_getMeterToMiles($avg_speed);
        }

    private function _findDailyDropRate()
        {
        if(isset($this->driverShipmentInfo["total_jobs"]) and $this->driverShipmentInfo["total_jobs"]>0)
            $this->dailyDropRate = round($this->driverShipmentInfo["total_drops"]/$this->driverShipmentInfo["total_jobs"], 2);
        else
            $this->dailyDropRate = 0.00;
        }

    private function _findAverageTimePerDrop()
        {
        if(isset($this->driverShipmentInfo["total_jobs"]) and $this->driverShipmentInfo["total_jobs"]>0)
            $this->averageTimePerDrop = round($this->totalTime/$this->driverShipmentInfo["total_jobs"], 2);
        else
            $this->averageTimePerDrop = 0.00;
        }

    private function _findAllActiveReportByCompanyId()
        {
        $items = $this->modelObj->findAllActiveReportByCompanyId($this->param->company_id, $this->param->service_type);
        $this->reportLists[$this->param->service_type] = $items;
        }

    private function _generateDerReport()
        {
				$routeCountInfo = $this->_getAllRouteCount();
				$driverTimeInfo = $this->_findDriverInfo();
        $noOfDays = $this->_findDaysDiff();
        $allActiveReportByCompanyId = $this->_findAllActiveReportByCompanyId();

        $reportData = array();
        for($i=0; $i<count($driverTimeInfo);$i++){
            $driverId = $driverTimeInfo[$i]['driver_id'];
            $driverName = $driverTimeInfo[$i]['driver_name'];
            $shipment_route_id = implode(",", $driverTimeInfo[$i]['shipment_route_id']);

            $this->_findTotalTime($driverTimeInfo[$i]["time_taken"]);
            $this->_findTotalMiles($shipment_route_id);
            $this->_findDriverShipmentBetweenDate($driverId);
            $this->_findAverageSpeedMiles();
            $this->_findDailyDropRate();
            $this->_findAverageTimePerDrop();

            $reportData[$driverId]["total_time_taken"] = $this->totalTime;
            $reportData[$driverId]['total_distance_meter'] = $this->totalDistanceMeter;
            $reportData[$driverId]['total_distance_miles'] = $this->totalDistanceMiles;

            $reportData[$driverId]["no_of_jobs"] = $this->driverShipmentInfo["total_jobs"];
            $reportData[$driverId]["no_of_drops"] = $this->driverShipmentInfo["total_drops"];

            $reportData[$driverId]['average_speed'] = $this->averageSpeed;
            $reportData[$driverId]['driver_name'] = $driverName;

            $reportData[$driverId]['daily_drop_rate'] = $this->dailyDropRate;
            $reportData[$driverId]['start_date'] = $this->libObj->date_format($this->param->start_date);
            $reportData[$driverId]['end_date'] = $this->libObj->date_format($this->param->end_date);

            $reportData[$driverId]['average_time_per_drop'] = $this->_getTimeInHourMinutes($this->averageTimePerDrop);
            $reportData[$driverId]['time_taken_in_hr_min'] = $this->_getTimeInHourMinutes($this->totalTime);

            $reportData[$driverId]['no_of_days'] = $noOfDays;

            $this->driverShipmentInfo = array();
            $this->dailyDropRate = 0;
            $this->averageTimePerDrop = 0;
        }

        return array(
          "status" => "success",
          "nextday_route_count" => $routeCountInfo["nextday_route_count"],
          "sameday_route_count" => $routeCountInfo["sameday_route_count"],
          "lastmile_route_count" => $routeCountInfo["lastmile_route_count"],
          "report_data" => $reportData,
          "report_list" => $this->reportLists
        );
        }

    public function generateReport($param)
        {
        $this->param = $param;
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
                $item->total_time_on_job,
                $item->total_drops,
                $item->total_jobs,
                $item->days,
                $item->total_miles,
                $item->daily_drop_rate,
                $item->average_speed,
                $item->average_time_per_drop
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
            'Average Time Per Drop'
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
