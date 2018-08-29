<?php
/**
 * Created by PhpStorm.
 * User: nishant
 * Date: 14/06/18
 * Time: 3:49 PM
 */
//declare(strict_types=1);

require_once "model/rest.php";
class Ws_Driver_Tracking
{
    public $driver_id = 0;
    public $route_id = 0;
    public $latitude = 0;
    public $longitude = 0;
    public $code = NULL;

    public $status = 0;
    public $warehouse_id = 0;
    public $company_id = 0;
    public $event_time = 0;
    public $source = NULL;

    public $model_rest = NULL;

    private static $_driverTracking = NULL;
    private $_db = NULL;

    public function __construct(){
        $this->model_rest = new Ws_Model_Rest();
    }

    public static function _getInstance(){
        if(self::$_driverTracking==NULL){
            self::$_driverTracking = new Ws_Driver_Tracking();
        }
        return self::$_driverTracking;
    }

    public function __set($property, $value){
        if(property_exists($this, $property)){
            $this->$property = $value;
            return $this; 
        }else{
            throw new Exception('Can\'t set property ' . $property);
        }
    }

    public function __get($property){
        if(property_exists($this, $property)){
            return $this->$property;
        }else{
            throw new Exception('Can\'t get property ' . $property);
        }
    }

   // public function saveDriverTracking() : string{
    public function saveDriverTracking(){
        //try{
            $status = $this->model_rest->saveDriverTracking(array(
                "driver_id"     => $this->__get("driver_id"),
                "route_id"      => $this->__get("route_id"),
                "latitude"      => $this->__get("latitude"),
                "longitude"     => $this->__get("longitude"),
                "code"          => $this->__get("code"),
                "status"        => $this->__get("status"),
                "warehouse_id"  => $this->__get("warehouse_id"),
                "company_id"    => $this->__get("company_id"),
                "event_time"    => date("Y-m-d H:i", strtotime("now")),
                "source"        => $this->__get("source")   
            ));
            return "success";
        //}catch(Exception $e){
            return "error";
        //}
    }
}