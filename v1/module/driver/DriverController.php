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
class DriverController extends Icargo {

    private $_user_id;
    protected $_parentObj;
    
    private $_db;
    private $_app;
    private $_requestParams;
    private $_table='user_grid_states';
    /**
     * Driver Controller constructor.
     */
    private function __construct($app) {
        $this->_db = new DbHandler();
        $this->_app = $app;
        $this->_requestParams=json_decode($this->_app->request->getBody());
    }
    /**
     * Fetch All Driver List
     */
    public function getAllDrivers(){
        
    }
    /**
     * Fetch All Active Drivers
     */
    public function getActiveDrivers(){
        
    }
    /**
     * Fetch all assigned routes for active drivers.
     */
    public function getActiveDriverRoutes(){
        
    }
    /**
     * Fetch all routes information according to date and grouped with assigned driver.
     */
    public function getDayRoutes(){
        
    }

}
