<?php
namespace v1\module\RateEngine;
use v1\module\RateEngine\ExcelBuilder;
use v1\module\RateEngine\ExcelReader;
use v1\module\RateEngine\RateEngineModel;
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
     * @var \v1\module\RateEngine\ExcelBuilder
     */
    private $_excelBuilder;
    private $_excelReader;
    private $_rateEngineModel;
    /**
     * @var RateEngineController
     */
    private static $_rateEngine;

    //put your code here
    private function __construct() {
        $this->_excelBuilder = new ExcelBuilder();
        $this->_rateEngineModel = new RateEngineModel();
        $this->_excelReader = new ExcelReader;
    }

    private static function createInstance() {
        if (!self::$_rateEngine)
            self::$_rateEngine = new RateEngineController;
    }

    /**
     * @param $app \Slim\Slim
     */
    public static function initRoutes($app) {
        $app->post('/getCsv', function () use ($app) {
            $carrierId = $app->request->post('carrier_id');
            $flag = $app->request->post('file_type');
            $accounts = $app->request->post('account_id');

            self::createInstance();
            self::$_rateEngine->cleanExcel();
            switch ($flag) {
                case 'zone': {
                        self::$_rateEngine->addZoneData($carrierId);
                        break;
                    }
                case 'zone-details': {
                        self::$_rateEngine->addZoneDefinations($carrierId);
                        break;
                    }
                case 'rate-details': {
                        self::$_rateEngine->addRateDetails($carrierId, $accounts);
                        break;
                    }
                case 'services': {
                        self::$_rateEngine->addServiceData($carrierId);
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
                        self::$_rateEngine->addRateUnitData();
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
        $app->post('/rate-engine/get-services-accounts', function() use ($app) {
            $r = json_decode($app->request->getBody());
            self::createInstance();
            self::$_rateEngine->getServiceAndAccounts($r);
        });
        $app->post("/saveSurcharge", function() use ($app) {
            $r = json_decode($app->request->getBody());
            self::createInstance();
            self::$_rateEngine->saveSurcharge($r);
            echoResponse(200, []);
        });

        $app->post("/getSurcharge", function() use ($app) {
            $r = json_decode($app->request->getBody());
            self::createInstance();
            $data = self::$_rateEngine->getSurcharge($r);
            echoResponse(200, $data);
        });

        $app->post("/deleteSurcharge", function() use ($app) {
            $r = json_decode($app->request->getBody());
            self::createInstance();
            $data = self::$_rateEngine->deleteSurcharge($r);
            echoResponse(200, $data);
        });
        $app->post("/rate-engine/save-carrier", function() use ($app) {
            $r = json_decode($app->request->getBody());
            self::createInstance();
            self::$_rateEngine->saveCarrierInfo($r);
        });
        $app->post("/rate-engine/save-service", function() use ($app) {
            $r = json_decode($app->request->getBody());
            self::createInstance();
            self::$_rateEngine->saveServiceInfo($r);
        });
        $app->post("/rate-engine/save-service-options", function() use ($app) {
            $r = json_decode($app->request->getBody());
            self::createInstance();
            self::$_rateEngine->perpareAndSaveServiceOption($r);
        });
        $app->post("/rate-engine/get-carriers", function() use ($app) {
            json_decode($app->request->getBody());
            self::createInstance();
            self::$_rateEngine->fetchAllCarriers();
        });
        $app->post("/rate-engine/get-services", function() use ($app) {
            $r = json_decode($app->request->getBody());
            self::createInstance();
            self::$_rateEngine->fetchAllServices($r);
        });
        $app->post("/rate-engine/get-service-options", function() use ($app) {
            $r = json_decode($app->request->getBody());
            self::createInstance();
            self::$_rateEngine->getServiceOption($r->id);
        });
        $app->post("/rate-engine/delete-carrier-or-service", function() use ($app) {
            $r = json_decode($app->request->getBody());
            self::createInstance();
            self::$_rateEngine->deleteCarrierOrService($r);
        });
        $app->post("/rate-engine/save-tax-details", function() use ($app) {
            $r = json_decode($app->request->getBody());
            self::createInstance();
//            $rec=[
//                'tax_type'=>$r->tax_type,
//                'tax_factor'=>$r->tax_factor,
//                'tax_factor_value'=>$r->tax_factor_value,
//            ];
            $data=self::$_rateEngine->_rateEngineModel->saveTaxDetails($r->country_id,$r);
            echoResponse(200,$data);
        });
        $app->post("/rate-engine/get-tax-details", function() use ($app) {
            $r = json_decode($app->request->getBody());
            self::createInstance();
            $data=self::$_rateEngine->_rateEngineModel->getTaxDetails($r->country_id);
            echoResponse(200,$data);
        });
    }

    public function getServiceAndAccounts($request) {
        $companyId = $request->company_id;
        $courierId = $request->courier_id;
        $accounts = $this->_rateEngineModel->getCompanyAccounts($courierId, $companyId);
        $services = $this->_rateEngineModel->getServices($courierId, $companyId);
        $data = [
            'services' => $services,
            'account' => $accounts
        ];
        echoResponse(200, $data);
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

    public function addZoneData($carrierId) {
        $zoneHeader = ['Zone Name', 'Update Name', 'Action'];
        $zones = $this->_rateEngineModel
                ->getZoneData($carrierId);
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

    public function addServiceData($carrierId) {
        $rateTypeHeader = ['Service Name', 'Service Code'];
        $this->_excelBuilder->addSheet("Service List")
                ->changeSheetByName("Service List")
                ->addHeader($rateTypeHeader)
                ->addData($this->_rateEngineModel
                        ->getServiceData($carrierId));
        return $this;
    }

    public function addRateDetails($carrierId, $accounts) {
        $rateDetailsHeader = ['Service', 'Rate Type', 'From Zone', 'To Zone', 'Start Unit',
            'End Unit', 'Rate', 'Additional Cost', 'Additional Base Unit', 'Unit', 'Account'];
        $unitList = $this->getRateUnitList();
        $rateTypes = $this->getRateTypeList();
        $data = $this->_rateEngineModel
                ->getRateData($carrierId, $accounts);
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

    public function addZoneDefinations($carrierId) {
        $zoneDefinationHeader = ['Zone', 'City', 'Post Code', 'Alpha 2', 'Flow Type',
            "Volume Base", 'Level'];
        $levels = 'City,Post Code,Country';
        $flowTypes = 'Domestic,International';
        $zoneDefination = $this->_rateEngineModel->getZoneDefinations($carrierId);
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
                            ->readZones($_POST['carrierId']);
                    break;
                }
            case 'zoneDetailsExcel': {
                    $this->_excelReader->loadExcelFromPost()
                            ->readZoneDefinations($_POST['carrierId']);
                    break;
                }
            case 'rateExcel': {
                    $d = $this->_excelReader->loadExcelFromPost()
                            ->readRateDetails($_POST['carrierId']);

                    if (isset($d['error_type'])) {
                        echo json_encode($d);
                    } else {
                        $this->_rateEngineModel->addNewRate($_POST['carrierId'], $d);
                    }
                    break;
                }
        }
    }

    public function saveSurcharge($d) {
        $carrierId = $d->carrier;
        $services = $d->services;
        $accounts = $d->accounts;
        $surcharege = $d->surcharge;
        $this->_rateEngineModel->addSurcharge($d, $carrierId, $services, $accounts, $surcharege);
    }

    public function getSurcharge($d) {
        $carrierId = $d->carrier??NULL;
        $services = $d->services ?? NULL;
        $accounts = $d->accounts ?? [];
        $surcharege = $d->surcharge ?? NULL;
        if (!count($accounts)) {
            return [];
        }
        return $this->_rateEngineModel
                        ->fetchSurcharge($carrierId, $services, $accounts, $surcharege);
    }
    public function deleteSurcharge($d){
        return $this->_rateEngineModel->deleteSurcharge($d->id);
    }
    public function saveCarrierInfo($request){
	if(isset($request->id)){
            $data=[
                'name'=>$request->name,
                'code'=>$request->code,
                'icon'=>"",
                'description'=>$request->desc,
            ];
            $this->_rateEngineModel->updateCarrier($request->id,$data);
            $id=$request->id;
        }else {
        $id=$this->_rateEngineModel
            ->addCarrier($request->name,$request->code,$request->desc,
                "",$request->company_id);
	}
        if($id){
            echoResponse(200,['success'=>true]);
        }else{
            echoResponse(200,['success'=>false]);
        }

    }
    public function saveServiceInfo($request){
        $id=$this->_rateEngineModel
            ->addService($request->name,$request->code,$request->desc,
                $request->service_type,$request->carrier_id,
                "",$request->company_id);
        if($id){
            echoResponse(200,['success'=>true]);
        }else{
            echoResponse(200,['success'=>false]);
        }
    }
    public function perpareAndSaveServiceOption($request){
        $data=[
            'service_id'=>$request->service_id,
            'residential'=>(int)$request->residential??0,
            'am_delivery'=>(int)$request->am_delivery??0,
            'saturday_delivery'=>(int)$request->saturday_delivery??0,
            'duitable'=>(int)$request->duitable??0,
            'hold_at_location'=>(int)$request->hold_at_location??0,
            'holiday_delivery'=>(int)$request->holiday_delivery??0,
            'length'=>$request->length??'',
            'width'=>$request->width??'',
            'height'=>$request->height??'',
            'dimension_unit'=>$request->dimension_unit??'',
            'girth'=>(int)$request->girth??0,
            'service_type'=>$request->service_type??'',
            'service_level'=>$request->service_level??'',
            'barcode_value'=>$request->barcode_value??'',
            'max_waiting_time'=>$request->max_waiting_time??'',
            'time_unit'=>$request->time_unit??'',
            'change_from_base'=>(int)$request->change_from_base??0,
            'min_weigth'=>$request->min_weigth??'',
            'max_weight'=>$request->max_weight??'',
            'min_box_weight'=>$request->min_box_weight??'',
            'max_box_weight'=>$request->max_box_weight??'',
            'weight_unit'=>$request->weight_unit??'',
            'max_box_count'=>$request->max_box_count??'',
            'max_transit_days'=>(int)$request->max_transit_days??0,
            'min_transit_days'=>(int)$request->min_transit_days??0,
            'service_time'=>$request->service_time??'',
            'weight_per'=>$request->weight_per??'',
            'updated_at'=>$request->updated_at??date('Y-m-d H:i:s'),
            'status'=>1
        ];
        $id=$this->_rateEngineModel->addServiceOption($data);
        if($id){
            echoResponse(200,['success'=>true]);
        }else{
            echoResponse(200,['success'=>false]);
        }
    }
    public function fetchAllCarriers(){
        $data=$this->_rateEngineModel->getAllCarriers();
        echoResponse(200,$data);
    }
    public function fetchAllServices($request){
        $data=$this->_rateEngineModel->getAllServicesByCarrier($request->carrier_id);
        echoResponse(200,$data);
    }

    public function deleteCarrierOrService($r){
        if($r->entity_type=='carrier'){
            $this->_rateEngineModel->updateCarrier($r->id,['status'=>0]);
        }elseif ($r->entity_type=='service'){
            $this->_rateEngineModel->updateService($r->id,['status'=>0]);
        }
        echoResponse(200,['success'=>true]);
    }
    public function getServiceOption($serviceId){
        $rec=$this->_rateEngineModel->getServiceOption($serviceId);
        if(!$rec)$rec=[];
        $rec=(array)$rec;
        $data=[
            'service_id'=>$serviceId,
            'residential'=>(bool)($rec['residential']??'0'),
            'am_delivery'=>(bool)($rec['am_delivery']??'0'),
            'saturday_delivery'=>(bool)($rec['saturday_delivery']??'0'),
            'duitable'=>(bool)($rec['duitable']??'0'),
            'hold_at_location'=>(bool)($rec['hold_at_location']??'0'),
            'holiday_delivery'=>(bool)($rec['holiday_delivery']??'0'),
            'length'=>$rec['length']??'',
            'width'=>$rec['width']??'',
            'height'=>$rec['height']??'',
            'dimension_unit'=>$rec['dimension_unit']??'',
            'girth'=>(bool)($rec['girth']??'0'),
            'service_type'=>$rec['service_type']??'',
            'service_level'=>$rec['service_level']??'',
            'barcode_value'=>$rec['barcode_value']??'',
            'max_waiting_time'=>$rec['max_waiting_time']??'',
            'time_unit'=>$rec['time_unit']??'',
            'change_from_base'=>(bool)($rec['change_from_base']??'0'),
            'min_weigth'=>$rec['min_weigth']??'',
            'max_weight'=>$rec['max_weight']??'',
            'min_box_weight'=>$rec['min_box_weight']??'',
            'max_box_weight'=>$rec['max_box_weight']??'',
            'weight_unit'=>$rec['weight_unit']??'',
            'max_box_count'=>$rec['max_box_count']??'',
            'max_transit_days'=>$rec['max_transit_days']??'',
            'min_transit_days'=>$rec['min_transit_days']??'',
            'service_time'=>$rec['service_time']??'',
            'weight_per'=>$rec['weight_per']??''
        ];
        echoResponse(200,$data);
    }
    public function saveImageToFile($imageBase64,$fileName){
        $imageBase64 = substr($imageBase64, 1+strrpos($imageBase64, ','));
        file_put_contents($fileName, base64_decode($imageBase64));
    }
}
