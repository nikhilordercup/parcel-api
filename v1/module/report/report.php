<?php
class Report extends Icargo{
	private $_user_id;
	protected $_parentObj;
	
	private function _setUserId($v){
		$this->_user_id = $v;
	}
	
	private function _getUserId(){
		return $this->_user_id;
	}
	
	public function __construct($data){
		$this->_parentObj = parent::__construct(array("email"=>$data->email, "access_token"=>$data->access_token));
	}
	
	public function getAllActiveReportByCompanyId($param){
		$data = $this->_parentObj->db->getAllRecords("SELECT t1.id as report_id,t1.name AS report_name,t1.code FROM ".DB_PREFIX."report_master AS t1 WHERE t1.company_id = ".$param->company_id." AND t1.status = 1");
		return array("status"=>"success","data"=>$data);
	}
	
	public function getAllActiveReportsByServiceType($param){
		$data = $this->_parentObj->db->getAllRecords("SELECT t1.id as report_id,t1.name AS report_name,t1.code FROM ".DB_PREFIX."report_master AS t1 WHERE t1.company_id = ".$param->company_id." AND t1.type = '".$param->service_type."' AND t1.status = 1");
		return array("status"=>"success","data"=>$data);
	}
	
	
	public function generateReport($param){
		//driver name,start date, end date,total time on job, total drops,number of jobs,days,total miles,daily drop rate,average speed,average time per drop
		$reportData = array();

		$difference = date_diff(date_create($param->startDate),date_create($param->endDate));
		$no_of_days = $difference->format("%a");
		
		$driverTimeData = $this->_parentObj->db->getAllRecords("SELECT * FROM " . DB_PREFIX . "driver_time_tracking WHERE create_date BETWEEN '".$param->startDate."' AND '".$param->endDate."'");	
		foreach($driverTimeData as $key=>$value){
			if(!isset($reportData[$value['driver_id']])){
				$reportData[$value['driver_id']] = array("time_taken"=>0,"total_distance_meter"=>0);
			}
			$reportData[$value['driver_id']]['time_taken'] = $reportData[$value['driver_id']]['time_taken'] + $value['time_taken'];
			
			$reportData[$value['driver_id']]['shipment_route_id'][] = $value['shipment_route_id'];
		}
		
		
		foreach($reportData as $driverId=>$item){
			$shipment_route_id = implode(',',array_unique($item['shipment_route_id']));
			$driverName = $this->_parentObj->db->getRowRecord("SELECT name FROM " . DB_PREFIX . "users WHERE id = $driverId");
			$shipmentId = $this->_parentObj->db->getAllRecords("SELECT shipment_id FROM " . DB_PREFIX . "shipment WHERE shipment_routed_id IN($shipment_route_id)");
	        foreach($shipmentId as $id){
				$totalMiles = $this->_parentObj->db->getRowRecord("SELECT SUM(transit_distance) as total_transit_distance FROM " . DB_PREFIX . "shipment_service WHERE shipment_id = ".$id['shipment_id']."");
				$reportData[$driverId]['total_distance_meter'] = $reportData[$driverId]['total_distance_meter'] + $totalMiles['total_transit_distance'];
			}
			
			$reportData[$driverId]['total_distance_miles'] = $reportData[$driverId]['total_distance_meter'] / 1609.344;
			
			$reportData[$driverId]['driver_name'] = $driverName['name'];
			
			$dropJobData = $this->getDropJobData($driverId,$param->startDate,$param->endDate);
			$reportData[$driverId]['no_of_drops'] = $dropJobData['no_of_drops'];
			$reportData[$driverId]['no_of_jobs'] = $dropJobData['no_of_jobs'];
			$reportData[$driverId]['no_of_days'] = $no_of_days;
			$reportData[$driverId]['daily_drop_rate'] = $reportData[$driverId]['no_of_drops'] / $no_of_days;
			$reportData[$driverId]['average_speed'] = $reportData[$driverId]['total_distance_meter'] / $reportData[$value['driver_id']]['time_taken'];
			$reportData[$driverId]['average_time_per_drop'] = $reportData[$value['driver_id']]['time_taken'] / $reportData[$driverId]['no_of_drops'];
		}
		return $reportData;
	}
	
	public function getDropJobData($driverId,$startDate,$endDate){
		$commonObj = new Common();
		$result = array();
		$startDate = $startDate.' 00:00:00';
		$endDate = $endDate.' 00:00:00';
		$dropJobData = $this->_parentObj->db->getAllRecords("SELECT shipment_id,shipment_create_date,assigned_driver,shipment_postcode as postcode,shipment_address1 FROM " . DB_PREFIX . "shipment WHERE assigned_driver = ".$driverId." AND shipment_create_date BETWEEN '".$startDate."' AND '".$endDate."'");
		
		foreach($dropJobData as $item){
			$dropName = $commonObj->getDropName($item);
			if(!isset($result[$dropName])){
				$result[$dropName] = array();
			}
			$result[$dropName] = $item;
		}

		$jobs = count($dropJobData);
		$drops = count($result);
		
		return array("no_of_drops"=>$drops,"no_of_jobs"=>$jobs);
	}
	
	public function downloadReportCsv($param){
		$reportData = array();
		$data = '[{"driver_name":"Nishant","start_date":"04-21-2018","end_date":"04-28-2018","total_time_on_job":67,"total_drops":3,"total_jobs":4,"days":"7","total_miles":4.02151435616,"daily_drop_rate":0.42857142857143,"average_speed":96.597014925373,"average_time_per_drop":22.333333333333,"details":""},{"driver_name":"Nishant","start_date":"04-21-2018","end_date":"04-28-2018","total_time_on_job":67,"total_drops":3,"total_jobs":4,"days":"7","total_miles":4.02151435616,"daily_drop_rate":0.42857142857143,"average_speed":96.597014925373,"average_time_per_drop":22.333333333333,"details":""},{"driver_name":"Nishant","start_date":"04-21-2018","end_date":"04-28-2018","total_time_on_job":67,"total_drops":3,"total_jobs":4,"days":"7","total_miles":4.02151435616,"daily_drop_rate":0.42857142857143,"average_speed":96.597014925373,"average_time_per_drop":22.333333333333,"details":""}]';
		$csvData = json_decode($data);
        foreach($csvData as $item){
			array_push($reportData,array($item->driver_name,$item->start_date,$item->end_date,$item->total_time_on_job,$item->total_drops,$item->total_jobs,$item->days,$item->total_miles,$item->daily_drop_rate,$item->average_speed,$item->average_time_per_drop));
		}

		// output headers so that the file is downloaded rather than displayed
		header('Content-type: text/csv');
		header('Content-Disposition: attachment; filename="demo.csv"');
		 
		// do not cache the file
		header('Pragma: no-cache');
		header('Expires: 0');
		$path = dirname(dirname(dirname(dirname(dirname(__FILE__)))))."\icargo\output\.".time().".csv";
		//echo $path;die;
		 //echo dirname(dirname(dirname(dirname(dirname(__FILE__)))));die;
		// create a file pointer connected to the output stream
		$file = fopen($path, 'w');

		// send the column headers
		fputcsv($file, array('Driver Name','Start Date','End Date','Total Time On Job','Total Drops','Total Jobs','Days','Total Miles','Daily Drop Rate','Average Speed','Average Time Per Drop'));
		// output each row of the data
		foreach ($reportData as $row)
		{

			fputcsv($file, $row);
		}
		 
		exit();
	}
}
?>