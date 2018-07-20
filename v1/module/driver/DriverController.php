<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of DriverController
 *
 * @author Mandeep Singh Nain
 */
class DriverController{

    protected $_parentObj;
    private $_db;
    private $_app;
    private $_requestParams;
    private $_table = 'user_grid_states';

    /**
     * Driver Controller constructor.
     */
    private function __construct($app) {
        $this->_db = new DbHandler();
        $this->_app = $app;
        $this->_requestParams = json_decode($this->_app->request->getBody());
    }
    /**
     * Register routes for driver data
     * @param type $app
     */
    public static function initRoutes($app) {        
        
        $app->post('/fetchDayRoutes', function() use ($app) { 
            $self = new DriverController($app);
            $r = json_decode($app->request->getBody());	
            verifyRequiredParams(array('access_token'), $r);
            $data=$self->getDayRoutes( $r->company_id, $r->route_date);
            echoResponse(200, array('result'=>'success','message'=> json_encode($data)));
        });
       
    }

    /**
     * Fetch All Driver List
     */
    public function getAllDrivers($companyId) {
        $sql = "SELECT U.* FROM `" . DB_PREFIX . "users` AS U JOIN " . DB_PREFIX . "company_users  AS CU ON U.id=CU.user_id 
WHERE  user_level=4 AND CU.company_id=$companyId";
    }

    /**
     * Fetch All Active Drivers
     */
    public function getActiveDrivers($companyId) {
        $sql = "SELECT U.* FROM `" . DB_PREFIX . "users` AS U JOIN " . DB_PREFIX . "company_users  AS CU ON U.id=CU.user_id 
WHERE device_token_id IS NOT NULL AND user_level=4 AND CU.company_id=$companyId";
    }

    /**
     * Fetch all assigned routes for active drivers.
     */
    public function getActiveDriverRoutes() {
        
    }

    /**
     * Fetch all routes information according to date and grouped with assigned driver.
     */
    public function getDayRoutes($companyId,$date) {
        $sql="SELECT shipment_route_id, assign_start_time FROM `".DB_PREFIX."shipment_route` "
                . "WHERE company_id=$companyId  AND service_date LIKE '$date%' AND driver_id > 0";
//        $sql="SELECT * FROM `".DB_PREFIX."shipment_route` WHERE company_id=$companyId  AND driver_id > 0";
        $shipmentSql="SELECT MAX(estimatedtime), shipment_routed_id,  FROM `".DB_PREFIX."shipment` "
                . "WHERE shipment_routed_id IN ("
                . "SELECT shipment_route_id FROM `".DB_PREFIX."shipment_route` WHERE "
                . "company_id=$companyId  AND service_date LIKE '$date%')"
                . " GROUP BY shipment_routed_id";
        $rec =$this->_db->getAllRecords($sql);
        $shipments=$this->_db->getAllRecords($shipmentSql);
        $result=array();
        foreach($rec as $k=>$r){
            $index=0;
            foreach ($shipments as $s){
                if($r['shipment_route_id']==$s['shipment_routed_id']){
                    $rec[$k]['shipments'][$index]=$s;
                    $index++;
                }                
            }
        }
        return $rec;
    }

}
