<?php
class Report extends Icargo
    {
    private $_user_id;
    protected $_parentObj;
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
        }
		private function _getMeterToMiles($meter)
		    {
				    $meter = (int) $meter;
						return round($meter * 0.0006213712, 2);
		    }
		private function _getTimeInHourMinutes($sec)
				{
						$sec = (int) $sec;
				    return gmdate("H:i:s", $sec);
				}
    public function getAllActiveReportByCompanyId($param)
        {
        $data = $this->_parentObj->db->getAllRecords("SELECT t1.id as report_id,t1.name AS report_name,t1.code FROM " . DB_PREFIX . "report_master AS t1 WHERE t1.company_id = " . $param->company_id . " AND t1.status = 1");
        return array(
            "status" => "success",
            "data" => $data
        );
        }
    public function getAllActiveReportsByServiceType($param)
        {
        $data = $this->_parentObj->db->getAllRecords("SELECT t1.id as report_id,t1.name AS report_name,t1.code FROM " . DB_PREFIX . "report_master AS t1 WHERE t1.company_id = " . $param->company_id . " AND t1.type = '" . $param->service_type . "' AND t1.status = 1");
        return array(
            "status" => "success",
            "data" => $data
        );
        }
    public function generateReport($param)
        {
        // driver name,start date, end date,total time on job, total drops,number of jobs,days,total miles,daily drop rate,average speed,average time per drop
        $reportData = array();
        if ($param->startDate == $param->endDate)
            {
            $difference = 0;
            $no_of_days = 1;
            }
          else
            {
            $difference = date_diff(date_create($param->startDate) , date_create($param->endDate));
            $no_of_days = $difference->format("%a");
            }
        if ($param->service_type == 'lastmile')
            {
            $param->service_type = 'Vendor';
            }
        elseif ($param->service_type == 'sameday')
            {
            $param->service_type = 'Same';
            }
          else
            {
            $param->service_type = 'Next';
            }

				$driverTimeData = $this->_parentObj->db->getAllRecords("SELECT DISTINCT(t1.id),t1.* FROM " . DB_PREFIX . "driver_time_tracking as t1 INNER JOIN " . DB_PREFIX . "shipment as t2 ON t2.shipment_routed_id=t1.shipment_route_id WHERE create_date BETWEEN '" . $param->startDate . "' AND '" . $param->endDate . "' AND t2.instaDispatch_loadGroupTypeName='" . $param->service_type . "' AND t2.company_id=" . $param->company_id . "");
        if (count($driverTimeData > 0))
            {
            foreach($driverTimeData as $key => $value)
                {
                if (!isset($reportData[$value['driver_id']]))
                    {
                    $reportData[$value['driver_id']] = array(
                        "time_taken" => 0,
                        "total_distance_meter" => 0,
												"total_distance_miles" => 0
                    );
                    }
                $reportData[$value['driver_id']]['time_taken'] = $reportData[$value['driver_id']]['time_taken'] + $value['time_taken'];
                $reportData[$value['driver_id']]['shipment_route_id'][] = $value['shipment_route_id'];
								$reportData[$value['driver_id']]['time_taken_in_hr_min'] = $this->_getTimeInHourMinutes($reportData[$value['driver_id']]['time_taken']);

                }
            foreach($reportData as $driverId => $item)
                {
                $shipment_route_id = implode(',', array_unique($item['shipment_route_id']));
                $driverName = $this->_parentObj->db->getRowRecord("SELECT name FROM " . DB_PREFIX . "users WHERE id = $driverId");
                $loadIdentity = $this->_parentObj->db->getRowRecord("SELECT instaDispatch_loadIdentity as load_identity FROM " . DB_PREFIX . "shipment WHERE shipment_routed_id IN($shipment_route_id)");
                $totalMiles = $this->_parentObj->db->getRowRecord("SELECT SUM(transit_distance) as total_transit_distance FROM " . DB_PREFIX . "shipment_service WHERE load_identity = '" . $loadIdentity['load_identity'] . "'");

                $reportData[$driverId]['total_distance_meter'] = $reportData[$driverId]['total_distance_meter'] + $totalMiles['total_transit_distance'];
								$reportData[$driverId]['total_distance_miles'] = $reportData[$driverId]['total_distance_miles'] + $this->_getMeterToMiles($totalMiles['total_transit_distance']);
                $reportData[$driverId]['driver_name'] = $driverName['name'];
                $dropJobData = $this->getDropJobData($driverId, $param->startDate, $param->endDate, $param->service_type);
                if ($dropJobData['no_of_drops'] != 0 AND $dropJobData['no_of_jobs'] != 0)
                    {
                    $reportData[$driverId]['no_of_drops'] = $dropJobData['no_of_drops'];
                    $reportData[$driverId]['no_of_jobs'] = $dropJobData['no_of_jobs'];
                    $reportData[$driverId]['no_of_days'] = $no_of_days;
                    }
                  else
                    {
                    $reportData[$driverId]['no_of_drops'] = $dropJobData['no_of_drops'];
                    $reportData[$driverId]['no_of_jobs'] = $dropJobData['no_of_jobs'];
                    $reportData[$driverId]['no_of_days'] = $no_of_days;
                    }
                }
            if (count($reportData > 0))
                {
                return array(
                    "status" => "success",
                    "reportData" => $reportData
                );
                }
              else
                {
                return array(
                    "status" => "success",
                    "reportData" => array()
                );
                }
            }
          else
            {
            return array(
                "status" => "error",
                "reportData" => array()
            );
            }
        }
    public function getDropJobData($driverId, $startDate, $endDate, $service_type)

        {
        $commonObj = new Common();
        $result = array();
        $drops = 0;
        $jobs = 0;
        $dropJobData = $this->_parentObj->db->getAllRecords("SELECT shipment_id,shipment_routed_id,shipment_create_date,assigned_driver,shipment_postcode as postcode,shipment_address1 FROM " . DB_PREFIX . "shipment WHERE assigned_driver = " . $driverId . " AND shipment_assigned_service_date BETWEEN '" . $startDate . "' AND '" . $endDate . "' AND instaDispatch_loadGroupTypeName='" . $service_type . "'");
        foreach($dropJobData as $item)
            {
            $dropName = $commonObj->getDropName($item);
            if (!isset($result[$item['shipment_routed_id']][$dropName]))
                {
                $result[$item['shipment_routed_id']][$dropName] = array();
                }
            $result[$item['shipment_routed_id']][$dropName] = $item;
            }
        foreach($result as $item)
            {
            $drops+= count($item);
            }
        $jobs = count($dropJobData);
        return array(
            "no_of_drops" => $drops,
            "no_of_jobs" => $jobs
        );
        }
    public function downloadReportCsv($param)
        {
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
        // output headers so that the file is downloaded rather than displayed
        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename="demo.csv"');
        // do not cache the file
        header('Pragma: no-cache');
        header('Expires: 0');
        $fileName = time() . ".csv";
        $path = dirname(dirname(dirname(dirname(__FILE__)))) . '/output/' . $fileName;
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
            "file_path" => "http://api.instadispatch.com/dev/output/" . $fileName
        );
        }
    }
?>
