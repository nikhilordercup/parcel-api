<?php
class Route_Model_Complete{
	public static $_db = null;
	
	public static function getInstance(){
		if(self::$_db==null){
			self::$_db = new DbHandler();
		}
		return self::$_db;
	}
	
	public function save($param){
		//$status = $this->getInstance()->update('shipment_route', array("is_pause"=>0,"is_current"=>"N","completed_date"=>date('Y-m-d h:i:s')),"shipment_route_id=".$param['shipment_route_id']);
		$status = $this->getInstance()->updateData("UPDATE `".DB_PREFIX."shipment_route` SET `is_pause`=0, `is_current`='N', `completed_date`=NOW() WHERE `shipment_route_id`=".$param['shipment_route_id']);
		if($status){
			return array("status"=>true,"message"=>"Complete route status saved");
		}
		else{
			return array("status"=>false,"message"=>"Complete route status not saved");
		}
	}
}
?>