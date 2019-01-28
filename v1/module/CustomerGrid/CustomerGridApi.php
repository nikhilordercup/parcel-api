<?php

namespace v1\module\CustomerGrid;

class CustomerGridApi
{
    private $_db;
    private $_app;
    private $_requestParams;

    private function __construct($app)
    {
        $this->_app = $app;
        $this->_requestParams = json_decode($this->_app->request->getBody());
    }


    public static function customerGridApi($app)
    {
        $app->post('/saveCustomerGrid', function () use ($app){
            $r = json_decode($app->request->getBody());
            $obj = new CustomerGrids($r);
            $resp = $obj->saveGridStatus($r);
            echoResponse(200, $resp);
        });
    }

}