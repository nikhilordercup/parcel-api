<?php

class DashboardApi
{
    private $_db;
    private $_app;
    private $_requestParams;

    private function __construct($app)
    {
        $this->_app = $app;
        $this->_requestParams = json_decode($this->_app->request->getBody());
    }


    public static function dashboardRoutes($app)
    {

        $app->post('/carrierShipments', function () use ($app) {
            $r = json_decode($app->request->getBody());
            $obj = new Dashboard($r);
            $responce = $obj->getCarrierShipment($r);
            echoResponse(200, $responce);
        });

    }



}