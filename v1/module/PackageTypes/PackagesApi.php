<?php

namespace v1\module\PackageTypes;
class PackagesApi
{
    private $_db;
    private $_app;
    private $_requestParams;

    private function __construct($app)
    {
        $this->_app = $app;
        $this->_requestParams = json_decode($this->_app->request->getBody());
    }

    public static function packageTypesRoutes($app)
    {
        $app->post('/getAllPackageTypesByUID', function () use ($app) {
            $r = json_decode($app->request->getBody());
            $obj = new PackageTypes($r);
            $responce = $obj->getAllPackageTypesByUID($r);
            echoResponse(200, $responce);
        });

        $app->post('/getPackageDataByID', function () use ($app) {
            $r = json_decode($app->request->getBody());
            $obj = new PackageTypes($r);
            $responceData = $obj->getPackageTypeByID($r);
            echoResponse(200, $responceData);
        });

        $app->post('/updateUserPackage', function () use ($app){
            $r = json_decode($app->request->getBody());
            $obj = new PackageTypes($r);
            $res = $obj->updateUserPackage($r);
            echoResponse(200, $res);
        });

        $app->post('/deletePackageByID', function () use ($app){
            $r = json_decode($app->request->getBody());
            $obj = new PackageTypes($r);
            $delResponce = $obj->deletePackageByID($r);
            echoResponse(200, $delResponce);
        });
    }

}