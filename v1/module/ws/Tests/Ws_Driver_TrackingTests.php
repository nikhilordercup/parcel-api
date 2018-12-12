<?php
/**
 * Created by PhpStorm.
 * User: nishant
 * Date: 14/06/18
 * Time: 3:49 PM
 */

use PHPUnit\Framework\TestCase;
require '../Ws_Driver_Tracking.php';

class Ws_Driver_TrackingTests extends TestCase
{
    private static $_driverTracking = NULL;

    protected function setUp()
    {
        $this->_driverTracking = new Ws_Driver_Tracking();
    }
 
    protected function tearDown()
    {
        $this->_driverTracking = NULL;
    }
 
    public function testSaveTracking()
    {
        $this->_driverTracking->__set("driver_id", "231");
        $this->_driverTracking->__set("route_id", "231");
        $this->_driverTracking->__set("latitude", "18.000935488");
        $this->_driverTracking->__set("longitude", "-0.7652728273");
        $this->_driverTracking->__set("for", "ROUTEACCEPT");
        $this->_driverTracking->__set("status", "1");
        $this->_driverTracking->__set("warehouse_id", "92");
        $this->_driverTracking->__set("company_id", "10");
        $this->_driverTracking->__set("drivercode", "Nishant");

        $result = $this->_driverTracking->saveTracking();

        $this->assertEquals("success", $result);
    }
}