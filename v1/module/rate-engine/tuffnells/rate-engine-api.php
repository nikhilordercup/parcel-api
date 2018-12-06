<?php

class RateengineApi
{
    private $_db;
    private $_app;
    private $_requestParams;

    private function __construct($app)
    {
        $this->_app = $app;
        $this->_requestParams = json_decode($this->_app->request->getBody());
    }


    public static function rateEngineRoutes($app)
    {
        $app->post('/saveLabels', function () use ($app) {
            $r = json_decode($app->request->getBody());
            $obj = new TuffnellsLabels($r);
            $responce = $obj->tuffnellLabelData($r);
            echoResponse(200, $responce);
        });

        $app->post('/paperManifest', function () use ($app){
            $r = json_decode($app->request->getBody());
            $obj = new TuffnellsLabels($r);
            $resp = $obj->paperManifestLabel($r);
            echoResponse(200, $resp);
        });

        $app->post('/addParentAccount', function () use ($app){
            $r = json_decode($app->request->getBody());
            $obj = new TuffnellsLabels($r);
            $res = $obj->childAccounts($r);
            echoResponse(200, $res);
        });
    }
}