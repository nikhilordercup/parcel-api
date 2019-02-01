<?php
require_once "../v1/module/report/model/Route_Model.php";

class Report extends Icargo
{
    private $_user_id;
    protected $_parentObj;
    public $driverShipmentInfo = array();
    public $reportLists = array();
    public $dailyDropRate = 0;

    public $averageTimePerDrop = 0;

    public $revenuePricePerJob = .95;

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
        $meter = (int)$meter;
        return round($meter * 0.0006213712, 2);
    }
    private function _getTimeInHourMinutes($sec)
    {
        $sec = (int)$sec;
        return gmdate("H:i", $sec);
    }
    private function _getJobCountByType($type, $driver_id)
    {
        $items = $this->modelObj->findDriverShipmentBetweenDate($this->param->start_date, $this->param->end_date, $this->param->company_id, $driver_id, $type);
        $loadIdentity = array();
        foreach ($items as $item) {
            $loadIdentity[$item["load_identity"]] = $item["load_identity"];
        }
        return count($loadIdentity);
    }
    private function _getServiceTypeName()
    {
        if ($this->param->service_type == 'lastmile') {
            $service_type = 'Vendor';
        } elseif ($this->param->service_type == 'sameday') {
            $service_type = 'SAME';
        } else {
            $service_type = 'NEXT';
        }
        return $service_type;
    }

    private function _allDropinfo($load_identity)
    {
        $items = $this->modelObj->findAllDropInfo($load_identity);
        $dropList = array();
        foreach($items as $item){
            $dropName = $this->commonObj->getDropName(array("postcode"=>$item["postcode"], "address_1"=>$item["address_1"]));
            if(!in_array($dropName, $dropList)){
                array_push($dropList, $dropName);
            }
        }
        return array("drop_count" => count($dropList), "shipment_count" => count($items));
    }

    private function _findSamedayRevenue($job_lists, $driver_drop_count, $driver_shipment_count)
    {
        $priceData = array();
        $tempAllDropCount = 0;
        $tempAllShipmentCount = 0;
        $tempDriverDropCount = 0;
        $tempDriverShipmentCount = 0;

        $tempDriverCarrierPriceDrop = 0;
        $tempDriverCustomerPriceDrop = 0;
        $tempDriverCarrierPriceShipment = 0;
        $tempDriverCustomerPriceShipment = 0;

        foreach($job_lists as $loadIdentity => $item){
            $loadPrice = $this->modelObj->findSamedayRevenue($loadIdentity);

            $dropData = $this->_allDropinfo($loadIdentity);

            $allDropCount = $dropData["drop_count"];
            $allShipmentCount = $dropData["shipment_count"];

            $driverDropCount = count($item["drop_list"]);

            $driverShipmentCount = count($item["shipment_list"]);

            $carrierPrice = number_format($loadPrice["carrier_price"], 2);
            $customerPrice = number_format($loadPrice["customer_price"], 2);

            $driverCarrierPricePerDrop = number_format($carrierPrice / $allDropCount, 2);
            $driverCustomerPricePerDrop = number_format($customerPrice / $allDropCount, 2);

            $driverCarrierPricePerShipment = number_format($carrierPrice / $allShipmentCount, 2);
            $driverCustomerPricePerShipment = number_format($customerPrice / $allShipmentCount, 2);

            $driverCarrierPriceDrop = number_format(($driverCarrierPricePerDrop * $driverDropCount), 2);
            $driverCustomerPriceDrop = number_format(($driverCustomerPricePerDrop * $driverDropCount), 2);

            $driverCarrierPriceShipment = number_format(($driverCarrierPricePerShipment * $driverShipmentCount), 2);
            $driverCustomerPriceShipment = number_format(($driverCustomerPricePerShipment * $driverShipmentCount), 2);


            $tempDriverCarrierPriceDrop += $driverCarrierPriceDrop;
            $tempDriverCustomerPriceDrop += $driverCustomerPriceDrop;

            $tempDriverCarrierPriceShipment += $driverCarrierPriceShipment;
            $tempDriverCustomerPriceShipment += $driverCustomerPriceShipment;

            $tempAllDropCount += $allDropCount;
            $tempAllShipmentCount += $allShipmentCount;

            $tempDriverDropCount += $driverDropCount;
            $tempDriverShipmentCount += $driverShipmentCount;

            $priceData["cost_breakdown"][$loadIdentity] = array(
              "driver_carrier_price_per_drop" => $driverCarrierPricePerDrop,
              "driver_customer_price_per_drop" => $driverCustomerPricePerDrop,

              "driver_carrier_price_per_shipment" => $driverCarrierPricePerShipment,
              "driver_customer_price_per_shipment" => $driverCustomerPricePerShipment,

              "driver_carrier_price_all_drop" => $driverCarrierPriceDrop,
              "driver_customer_price_all_drop" => $driverCustomerPriceDrop,

              "driver_carrier_price_all_shipment" => $driverCarrierPriceShipment,
              "driver_customer_price_all_shipment" => $driverCustomerPriceShipment,

              "carrier_price" => $carrierPrice,
              "customer_price" => $customerPrice,

              "all_drop_count" => $allDropCount,
              "driver_drop_count" => $driverDropCount,

              "all_shipment_count" => $allShipmentCount,
              "driver_shipment_count" => $driverShipmentCount
            );
        }

        $priceData["driver_carrier_price_drop"] = number_format($tempDriverCarrierPriceDrop, 2);
        $priceData["driver_customer_price_drop"] = number_format($tempDriverCustomerPriceDrop, 2);
        $priceData["driver_carrier_price_shipment"] = number_format($tempDriverCarrierPriceShipment, 2);
        $priceData["driver_customer_price_shipment"] = number_format($tempDriverCustomerPriceShipment, 2);

        $priceData["driver_carrier_price_drop"] = $tempDriverCarrierPriceDrop;
        $priceData["driver_customer_price_drop"] = $tempDriverCustomerPriceDrop;
        $priceData["driver_carrier_price_shipment"] = $tempDriverCarrierPriceShipment;
        $priceData["driver_customer_price_shipment"] = $tempDriverCustomerPriceShipment;

        $priceData["all_drop_count"] = $tempAllDropCount;
        $priceData["all_shipment_count"] = $tempAllShipmentCount;

        $priceData["driver_drop_count"] = $tempDriverDropCount;
        $priceData["driver_shipment_count"] = $tempDriverShipmentCount;

        return $priceData;
    }

    private function _findRevenuePrice($job_lists, $driver_drop_count, $driver_shipment_count, $driver_id)
    {
        $revenuePrice = 0;
        if ($this->_getServiceTypeName() == "SAME") {
            $revenuePrice = $this->_findSamedayRevenue($job_lists, $driver_drop_count, $driver_shipment_count);
            
            $revenuePrice = array(
                "driver_carrier_price_drop" => $revenuePrice["driver_carrier_price_drop"],
                "driver_customer_price_drop" => $revenuePrice["driver_customer_price_drop"],

                "driver_carrier_price_shipment" => $revenuePrice["driver_carrier_price_shipment"],
                "driver_customer_price_shipment" => $revenuePrice["driver_customer_price_shipment"],

                "total_shipment_count" => $revenuePrice["all_shipment_count"],
                "total_drop_count" => $revenuePrice["all_drop_count"],

                "driver_shipment_count" => $revenuePrice["driver_shipment_count"],
                "driver_drop_count" => $revenuePrice["driver_drop_count"],

                "cost_breakdown" => $revenuePrice["cost_breakdown"]
            );
        } else {
            $job_count = count($job_lists);
            $revenuePrice = $this->revenuePricePerJob * $job_count;
            $revenuePrice = array(
                "driver_carrier_price_drop" => $revenuePrice ,
                "driver_customer_price_drop" => $revenuePrice,

                "driver_carrier_price_shipment" => $revenuePrice,
                "driver_customer_price_shipment" => $revenuePrice,

                "total_shipment_count" => $job_count,
                "total_drop_count" => $job_count,

                "driver_shipment_count" => $job_count,
                "driver_drop_count" => $job_count,
                "cost_breakdown" => array()

            );
        }
        return $revenuePrice;
    }
    private function _findDriverDeliveredDrop($shipmentRouteList, $loadIdentityStr, $driverId)
    {
        $dropInfo = $this->modelObj->findDriverDropInfo($shipmentRouteList, $loadIdentityStr, $driverId);
        $dropList = array();
        $dropCount = 0;
        foreach ($dropInfo as $item) {
            if(!is_array($dropList["load_drop_data"][$item["load_identity"]])){
                $dropList["load_drop_data"][$item["load_identity"]] = array();
            }
            if(!in_array($item["shipment_ticket"], $dropList["load_drop_data"][$item["load_identity"]])){
                $dropList["load_drop_data"][$item["load_identity"]][] = $item["shipment_ticket"];
                $dropList["drop_list"][] = $item["shipment_ticket"];
                $dropCount++;
            }
        }
        $dropList["drop_count"] = $dropCount;
        return $dropList;
    }

    private function _findDriverTimeInfo()
    {
        $driverRouteData = $this->_findDriverInfo($this->_getServiceTypeName());

        $items = array();
        if (isset($driverRouteData["driver_data"])) {
            foreach ($driverRouteData["driver_data"] as $driver_id => $item) {

                $jobLists = array_keys($item["job_lists"]);
                $routeList = implode("','", $item["route_lists"]);

                $loadIdentityStr = implode("','", $jobLists);

                $driverDropCount = $item["drop_count"];
                $driverShipmentCount = $item["shipment_count"];

                $routeInfo = $this->modelObj->findDriverTimeInfoByShipmentRouteId($routeList);
                $driverName = $this->modelObj->findDriverNameById($driver_id);
                //$dropInfo = $this->_findDriverDeliveredDrop($routeList, $loadIdentityStr, $driver_id);

                //$allDropInfo = $this->_findAllDropInfo($loadIdentityStr);
                $items[$driver_id]["driver_name"] = $driverName["driver_name"];
                $items[$driver_id]["driver_id"] = $driver_id;
                $items[$driver_id]["time_taken"] = (int)$routeInfo["time_taken"];
                $items[$driver_id]["total_job_count"] = count($jobLists);
                $items[$driver_id]["total_route_count"] = count($item["route_lists"]);
                $items[$driver_id]["driver_drop_count"] = $driverDropCount;
                $items[$driver_id]["driver_shipment_count"] = $driverShipmentCount;
                //$items[$driver_id]["total_drop_count"] = count($allDropInfo);
                $items[$driver_id]["shipment_route_id"] = $item["route_lists"];
                $items[$driver_id]["job_lists"] = $item["job_lists"];
                //$items[$driver_id]["drop_lists"] = $item["job_lists"];//$dropInfo;
            }
        }
        return $items;
    }
    private function _findDriverInfo($type)
    {
        if ($type == 'Vendor') {
            $shipmentInfo = $this->modelObj->findDriverTimeInfoForLastMile($this->param->start_date, $this->param->end_date, $type, $this->param->company_id);
        } else {
            $shipmentInfo = $this->modelObj->findDriverTimeInfo($this->param->start_date, $this->param->end_date, $type, $this->param->company_id);
        }

        $driverRouteData = array();

        foreach ($shipmentInfo as $item) {
            $dropName = $this->commonObj->getDropName(array("postcode"=>$item["postcode"], "address_1"=>$item["address_1"]));

            if(!isset($driverRouteData["driver_data"][$item["driver_id"]])){
                $driverRouteData["driver_data"][$item["driver_id"]] = array();
                $driverRouteData["driver_data"][$item["driver_id"]]["job_lists"] = array();
                $driverRouteData["driver_data"][$item["driver_id"]]["route_lists"] = array();
                $driverRouteData["driver_data"][$item["driver_id"]]["drop_count"] = 0;
                $driverRouteData["driver_data"][$item["driver_id"]]["shipment_count"] = 0;
            }
            if(!array_key_exists($item["load_identity"], $driverRouteData["driver_data"][$item["driver_id"]]["job_lists"])){
                $driverRouteData["driver_data"][$item["driver_id"]]["job_lists"][$item["load_identity"]] = array();
                $driverRouteData["driver_data"][$item["driver_id"]]["job_lists"][$item["load_identity"]]["shipment_list"] = array();
                $driverRouteData["driver_data"][$item["driver_id"]]["job_lists"][$item["load_identity"]]["drop_list"] = array();
            }
            if(!in_array($item["shipment_ticket"], $driverRouteData["driver_data"][$item["driver_id"]]["job_lists"][$item["load_identity"]]["shipment_list"])){
                array_push($driverRouteData["driver_data"][$item["driver_id"]]["job_lists"][$item["load_identity"]]["shipment_list"], $item["shipment_ticket"]);
                $driverRouteData["driver_data"][$item["driver_id"]]["shipment_list"][$item["shipment_ticket"]] = $item["shipment_ticket"];
            }

            if(!in_array($dropName, $driverRouteData["driver_data"][$item["driver_id"]]["job_lists"][$item["load_identity"]]["drop_list"])){
                array_push($driverRouteData["driver_data"][$item["driver_id"]]["job_lists"][$item["load_identity"]]["drop_list"], $dropName);
                $driverRouteData["driver_data"][$item["driver_id"]]["drop_list"][$dropName] = $dropName;
            }

            if(!in_array($item["shipment_route_id"], $driverRouteData["driver_data"][$item["driver_id"]]["route_lists"])){
                array_push($driverRouteData["driver_data"][$item["driver_id"]]["route_lists"], $item["shipment_route_id"]);
            }

            $driverRouteData["driver_data"][$item["driver_id"]]["drop_count"] = count($driverRouteData["driver_data"][$item["driver_id"]]["drop_list"]);
            $driverRouteData["driver_data"][$item["driver_id"]]["shipment_count"] = count($driverRouteData["driver_data"][$item["driver_id"]]["shipment_list"]);
            $driverRouteData["all_job_lists"][$item["driver_id"]][$item["load_identity"]] = $item["load_identity"];
        }
        return $driverRouteData;
    }
    private function _findDaysDiff()
    {
        $earlier = new DateTime($this->param->start_date);
        $later = new DateTime($this->param->end_date);
        return $later->diff($earlier)->format("%a") + 1;
    }
    private function _findAllTransitDistance($load_identity_str)
    {
        $this->totalDistanceMeter = 0;
        $items = $this->modelObj->findTransitDistanceByLoadIdentity($load_identity_str);
        $transitDistance = array();
        foreach ($items as $item) {
            array_push($transitDistance, $item["transit_distance"]);
        }
        if (count($transitDistance) > 0) {
            $this->totalDistanceMeter = array_sum($transitDistance);
        }
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
        if ($time > 0) {
            $this->totalTime = $time;
        }
    }
    private function _findAverageSpeedMiles()
    {
        $avg_speed = 0;
        if ($this->totalTime > 0) {
            $avg_speed = $this->totalDistanceMeter / $this->totalTime;
        }
        $this->averageSpeed = $this->_getMeterToMiles($avg_speed);
    }
    private function _findDailyDropRate($item)
    {
        if (isset($item["total_job_count"]) and $item["total_job_count"] > 0) {
            $this->dailyDropRate = round($item["total_drop_count"] / $item["total_job_count"], 2);
        } else {
            $this->dailyDropRate = 0.00;
        }
    }
    private function _findAverageTimePerDrop($item)
    {
        if (isset($item["total_job_count"]) and $item["total_job_count"] > 0) {
            $this->averageTimePerDrop = round($this->totalTime / $item["total_job_count"], 2);
        } else {
            $this->averageTimePerDrop = 0.00;
        }
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
        if (isset($samedayJobsData["all_job_lists"])) {
            foreach ($samedayJobsData["all_job_lists"] as $key => $item) {
                $samedayJobCount+= count($item);
            }
        }
        $nextdayJobCount = 0;
        if (isset($nextdayJobsData["all_job_lists"])) {
            foreach ($nextdayJobsData["all_job_lists"] as $key => $item) {
                $nextdayJobCount+= count($item);
            }
        }
        $vendorJobCount = 0;
        if (isset($vendorJobsData["all_job_lists"])) {
            foreach ($vendorJobsData["all_job_lists"] as $key => $item) {
                $vendorJobCount+= count($item);
            }
        }
        return array(
            "sameday_job_count" => $samedayJobCount,
            "nextday_job_count" => $nextdayJobCount,
            "lastmile_job_count" => $vendorJobCount
        );
    }
    private function _generateDerReport()
    {
        $driverTimeInfo = $this->_findDriverTimeInfo();

        $noOfDays = $this->_findDaysDiff();
        $allActiveReportByCompanyId = $this->_findAllActiveReportByCompanyId();
        $reportData = array();
        $jobCountInfo = array(
            "nextday_job_count" => array() ,
            "sameday_job_count" => array() ,
            "lastmile_job_count" => array()
        );
        foreach ($driverTimeInfo as $driver_id => $item) {
            $load_identity_str = implode("','", array_keys($item["job_lists"]));
            $this->_findTotalTime($item["time_taken"]);
            $this->_findTotalMiles($load_identity_str);
            $this->_findAverageSpeedMiles();
            //$this->_findDailyDropRate($item);
            $this->_findAverageTimePerDrop($item);
            $totalJobCount = $item["total_job_count"];
            $driverDropCount = $item["driver_drop_count"];
            $driverShipmentCount = $item["driver_shipment_count"];


            $price = $this->_findRevenuePrice($item["job_lists"], $driverDropCount, $driverShipmentCount, $driver_id);

            $totalDropCount = $price["total_drop_count"];

            $this->_findDailyDropRate(array("total_drop_count"=>$totalDropCount, "total_job_count"=>$totalJobCount));

            $reportData[$driver_id]["no_of_jobs"] = $item["total_job_count"];
            $reportData[$driver_id]["no_of_drops"] = $driverDropCount;
            $reportData[$driver_id]["total_time_taken"] = $item["time_taken"];
            $reportData[$driver_id]['driver_name'] = $item["driver_name"];
            $reportData[$driver_id]['total_distance_meter'] = ($this->param->service_type == "sameday") ? number_format($this->totalDistanceMeter, 2) : "N/A";
            $reportData[$driver_id]['total_distance_miles'] = ($this->param->service_type == "sameday") ? number_format($this->totalDistanceMiles, 2) : "N/A";
            $reportData[$driver_id]['start_date'] = $this->libObj->date_format($this->param->start_date);
            $reportData[$driver_id]['end_date'] = $this->libObj->date_format($this->param->end_date);
            $reportData[$driver_id]['average_speed'] = ($this->param->service_type == "sameday") ? number_format($this->averageSpeed, 2) : "N/A";
            $reportData[$driver_id]['daily_drop_rate'] = number_format($this->dailyDropRate, 2);
            $reportData[$driver_id]['average_time_per_drop'] = $this->_getTimeInHourMinutes($this->averageTimePerDrop);
            $reportData[$driver_id]['time_taken_in_hr_min'] = $this->_getTimeInHourMinutes($this->totalTime);





            $reportData[$driver_id]['driver_carrier_price_drop'] = number_format(round($price["driver_carrier_price_drop"], 2), 2);
            $reportData[$driver_id]['driver_customer_price_drop'] = number_format(round($price["driver_customer_price_drop"], 2), 2);
            $reportData[$driver_id]['driver_carrier_price_shipment'] = number_format(round($price["driver_carrier_price_shipment"], 2), 2);
            $reportData[$driver_id]['driver_customer_price_shipment'] = number_format(round($price["driver_customer_price_shipment"], 2), 2);
            $reportData[$driver_id]['no_of_days'] = $noOfDays;
        }
        $allJobCount = $this->_findDriverAllShipmentCount($driverTimeInfo);

        return array(
            "status" => "success",
            "nextday_route_count" => $allJobCount["nextday_job_count"],
            "sameday_route_count" => $allJobCount["sameday_job_count"],
            "lastmile_route_count" => $allJobCount["lastmile_job_count"],
            "report_data" => array_values($reportData) ,
            "report_list" => $this->reportLists
        );
    }
    public function generateReport($param)
    {
        $this->param = $param;
        $this->param->start_date = date("Y-m-d", strtotime($this->param->start_date));
        $this->param->end_date = date("Y-m-d", strtotime($this->param->end_date));
        if ($param->report_type == "DER") {
            return $this->_generateDerReport();
        }
    }
    public function downloadReportCsv($param)
    {
        $basePath = Library::_getInstance()->base_path();
        $downloadPath = Library::_getInstance()->get_api_url();
        $folder = "output";
        $reportData = array();
        foreach ($param->data as $item) {
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
                $item->driver_carrier_price_drop,
                $item->driver_customer_price_drop
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
        foreach ($reportData as $row) {
            fputcsv($file, $row);
        }
        return array(
            "status" => "success",
            "message" => "csv generated successfully",
            "file_path" => "{$downloadPath}{$folder}/{$fileName}"
        );
    }
}
