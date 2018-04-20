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
	
	public function generateReport(){
		// output headers so that the file is downloaded rather than displayed
header('Content-type: text/csv');
header('Content-Disposition: attachment; filename="demo.csv"');
 
// do not cache the file
header('Pragma: no-cache');
header('Expires: 0');
$path = dirname(dirname(dirname(dirname(dirname(__FILE__)))))."\output\.".time().".csv";
//echo $path;die;
 //echo dirname(dirname(dirname(dirname(dirname(__FILE__)))));die;
// create a file pointer connected to the output stream
$file = fopen($path, 'w');
 
// send the column headers
fputcsv($file, array('Driver Name', 'Date', 'No of Drops'));
 
// Sample data. This can be fetched from mysql too
$data = array(
array('Nishant', '22-03-2018', '4'),
array('Roopesh', '22-03-2018', '6'),
array('Perceptive', '22-03-2018', '8'),
array('Test', '22-03-2018', '10'));
 
// output each row of the data
foreach ($data as $row)
{
fputcsv($file, $row);
}
 
exit();
	}
}
?>