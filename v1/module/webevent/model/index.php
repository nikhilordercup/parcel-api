<?php
class Webevent_Model_Index{
    public static $_modelObj = null;
    public static $_db = null;

    public static function getInstance(){
        if(self::$_modelObj==null){
            self::$_modelObj = new Webevent_Model_Index();
        }
        return self::$_modelObj;
    }

    public static function getDbInstance(){
        if(self::$_db==null){
            self::$_db = new DbHandler();
        }
        return self::$_db;
    }

    public function saveEvent($param){
        $status = $this->getInstance()->getDbInstance("UPDATE `".DB_PREFIX."shipment_route` SET `event_code`='".$param["event_code"]."' WHERE `shipment_route_id`=".$param['shipment_route_id']);
        if($status){
            return array("status"=>true,"message"=>"Event saved successfully");
        }
        else{
            return array("status"=>false,"message"=>"Event not saved");
        }
    }

    public function getDriverActiveRouteDetail($param){
        $sql = "SELECT shipment_route_id, route_name FROM `".DB_PREFIX."shipment_route` AS SRT WHERE SRT.driver_id='".$param["driver_id"]."' AND `is_active`='Y' AND `is_current`='Y'";
        return $this->getDbInstance()->getAllRecords($sql);
    }
}
?>