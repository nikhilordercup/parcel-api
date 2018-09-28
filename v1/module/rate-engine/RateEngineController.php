<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'ExcelBuilder.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'ExcelReader.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'RateEngineModel.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RateEngineController
 *
 * @author perce
 */
class RateEngineController {

    /**
     *
     * @var ExcelBuilder
     */
    private $_excelBuilder;
    private $_excelReader;
    private $_rateEngineModel;
    private static $_rateEngine;

    //put your code here
    private function __construct() {
        $this->_excelBuilder = new ExcelBuilder();
        $this->_rateEngineModel = new RateEngineModel;
        $this->_excelReader = new ExcelReader;
    }

    private static function createInstance() {
        if (!self::$_rateEngine)
            self::$_rateEngine = new RateEngineController;
    }

    public static function initRoutes($app) {
        $app->get('/getCsv/:companyId/:carrierId/:flag', function($companyId, $carrierId, $flag) use ($app) {
            self::createInstance();
            self::$_rateEngine->cleanExcel();
            switch ($flag) {
                case 'zone': {
                        self::$_rateEngine->addZoneData($companyId, $carrierId);
                        break;
                    }
                case 'zone-details': {
                        self::$_rateEngine->addZoneDefinations($companyId, $carrierId);
                        break;
                    }
                case 'rate-details': {
                        self::$_rateEngine->addRateDetails($companyId, $carrierId);
                        break;
                    }
                case 'services': {
                        self::$_rateEngine->addServiceData($companyId, $carrierId);
                        break;
                    }
                case 'countries': {
                        self::$_rateEngine->addCountryData();
                        break;
                    }
                case 'rate-types': {
                        self::$_rateEngine->addRateTypeData();
                        break;
                    }
                case 'rate-units': {
                        addRateUnitData();
                        break;
                    }
            }
            self::$_rateEngine->downloadExcel($flag);
        });
        $app->post('/rate-engine/update/from-excel', function() use ($app) {
            header("Access-Control-Allow-Origin: *");
            self::createInstance();
            self::$_rateEngine->updateWithExcel();
        });
    }

    public function addCountryData() {
        $header = ['Country Name', 'Alpha 2', 'Alpha 3', 'Numaric'];
        $this->_excelBuilder
                ->addSheet("Country")
                ->changeSheetByName("Country")
                ->addHeader($header);
        $data = $this->_rateEngineModel->getAllCountry();
        $this->_excelBuilder->addData($data);
        return $this;
    }

    public function downloadExcel($fileName = "Rate-Data") {
        $this->_excelBuilder
                ->resetActiveSheet()
                ->download($fileName);
        return $this;
    }

    public function addZoneData($companyId, $carrierId) {
        $zoneHeader = ['Zone Name', 'Update Name', 'Action'];
        $zones = $this->_rateEngineModel
                ->getZoneData($companyId, $carrierId);
        $this->_excelBuilder->addSheet("Zone")
                ->changeSheetByName("Zone")
                ->addHeader($zoneHeader)
                ->addSelectOption('C', 'New,Update', count($zones))
                ->addData($zones);
        return $this;
    }

    public function addRateTypeData() {
        $rateTypeHeader = ['Rate Type'];
        $this->_excelBuilder->addSheet("Rate Type")
                ->changeSheetByName("Rate Type")
                ->addHeader($rateTypeHeader)
                ->addData($this->_rateEngineModel
                        ->getRateTypes());
        return $this;
    }

    public function addServiceData($companyId, $carrierId) {
        $rateTypeHeader = ['Service Name', 'Service Code'];
        $this->_excelBuilder->addSheet("Service List")
                ->changeSheetByName("Service List")
                ->addHeader($rateTypeHeader)
                ->addData($this->_rateEngineModel
                        ->getServiceData($companyId, $carrierId));
        return $this;
    }

    public function addRateDetails($companyId, $carrierId) {
        $rateDetailsHeader = ['Service', 'Rate Type', 'From Zone', 'To Zone', 'Start Unit',
            'End Unit', 'Rate', 'Additional Cost', 'Additional Base Unit', 'Unit'];
        $unitList = $this->getRateUnitList();
        $rateTypes = $this->getRateTypeList();
        $data = $this->_rateEngineModel
                ->getRateData($companyId, $carrierId);
        $this->_excelBuilder->addSheet("Rate Details")
                ->changeSheetByName("Rate Details")
                ->addHeader($rateDetailsHeader)
                ->addSelectOption('J', $unitList, count($data))
                ->addSelectOption('B', $rateTypes, count($data))
                ->addData($data);
        return $this;
    }

    public function addRateUnitData() {
        $rateUnitHeader = ['Unit', 'Abbrivation'];
        $this->_excelBuilder->addSheet("Rate Units")
                ->changeSheetByName("Rate Units")
                ->addHeader($rateUnitHeader)
                ->addData($this->_rateEngineModel
                        ->getRateUnits());
        return $this;
    }

    public function cleanExcel() {
        $this->_excelBuilder->removeSheetByIndex(0);
        return $this;
    }

    public function getRateUnitList() {
        $list = [];
        $rateUnits = $this->_rateEngineModel
                ->getRateUnits();
        foreach ($rateUnits as $r) {
            array_push($list, $r['name']);
        }
        return implode(',', $list);
    }

    public function getRateTypeList() {
        $list = [];
        $rateUnits = $this->_rateEngineModel
                ->getRateTypes();
        foreach ($rateUnits as $r) {
            array_push($list, $r['name']);
        }
        return implode(',', $list);
    }

    public function addZoneDefinations($companyId, $carrierId) {
        $zoneDefinationHeader = ['Zone', 'City', 'Post Code', 'Country', 'Flow Type',
            "Volume Base", 'Level'];
        $levels = 'City,Post Code,Country';
        $flowTypes = 'Domastic,International';
        $zoneDefination = $this->_rateEngineModel->getZoneDefinations($companyId, $carrierId);
//        print_r($zoneDefination);exit;
        $this->_excelBuilder->addSheet("Zone Definations")
                ->changeSheetByName("Zone Definations")
                ->addHeader($zoneDefinationHeader)                
                ->addSelectOption('G', $levels, count($zoneDefination))
                ->addSelectOption('E', $flowTypes, count($zoneDefination))
                ->addData($zoneDefination);
        return $this;
    }

    public function updateWithExcel() {
        switch ($_POST['fileType']) {
            case 'zoneExcel': {
                    $this->_excelReader->loadExcelFromPost()
                            ->readZones($_POST['company_id'], $_POST['carrierId']);
                    break;
                }
            case 'zoneDetailsExcel': {
                    $this->_excelReader->loadExcelFromPost()
                            ->readZoneDefinations($_POST['company_id'], $_POST['carrierId']);
                    break;
                }
            case 'rateExcel': {
                    $d = $this->_excelReader->loadExcelFromPost()
                            ->readRateDetails($_POST['company_id'], $_POST['carrierId']);
                    if (isset($d['error'])) {
                        echo json_encode($d);
                    }else{
                        $this->_rateEngineModel->addNewRate($_POST['company_id'], 
                                $_POST['carrierId'], $d);
                    }
                    break;
                }
        }
    }

}
