<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ExcelReader
 *
 * @author perce
 */
class ExcelReader {

    private $_exceReader;

    /**
     *
     * @var \PhpOffice\PhpSpreadsheet\Spreadsheet 
     */
    private $_spredSheet;
    private $_sheetData = [];

    /**
     *
     * @var RateEngineModel
     */
    private $_reateEngineModel;

    //put your code here
    function __construct() {
        $this->_exceReader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx;
        $this->_reateEngineModel = new RateEngineModel();
    }

    public function getHeader() {
        $this->_sheetData[0];
    }

    public function getData() {
        $local = $this->_sheetData;
        unset($local[0]);
        return $local;
    }

    public function changeSheetByName($name) {
        $this->_spredSheet->setActiveSheetIndexByName($name);
        return $this;
    }

    public function loadData() {
        $this->_sheetData = $this->_spredSheet
                ->getActiveSheet()
                ->toArray();
        return $this;
    }

    public function readZones( $carrierId) {
        $data = $this->changeSheetByName("Zone")
                ->loadData()
                ->getData();

        $queryData = [];
        foreach ($data as $i => $d) {
            if (trim($d[0]) == "") {
                break;
            }
            $queryData[$i - 1]['name'] = $d[0];
            $queryData[$i - 1]['newName'] = $d[1];
            $queryData[$i - 1]['action'] = $d[2];
            if ($d[2] == 'New') {
                $this->_reateEngineModel->addZone($d[0],  $carrierId);
            } elseif ($d[2] == 'Update') {
                $this->_reateEngineModel->updateZoneByName($d[0], $d[1],  $carrierId);
            }
        }
        exit;
    }

    public function readZoneDefinations( $carrierId) {
        $data = $this->changeSheetByName("Zone Definations")
                ->loadData()
                ->getData();
        $queryData = [];
        $this->_reateEngineModel->deleteZoneDetails( $carrierId);
        foreach ($data as $i => $d) {
            if (trim($d[0]) == '' || strlen($d[3])>2) {
                break;
            }
            $zone = $this->_reateEngineModel->getZoneByName( $carrierId, $d[0]);
            $queryData[$i - 1]['zone_id'] = $zone['id'];
            $queryData[$i - 1]['city'] = $d[1];
            $queryData[$i - 1]['post_code'] = $d[2];
            $queryData[$i - 1]['country'] = $d[3];
            $queryData[$i - 1]['level'] = $d[6];
            $queryData[$i - 1]['flow_type'] = $d[4];
            $queryData[$i - 1]['volume_base'] = $d[5];
            $this->_reateEngineModel->addZoneDetails($queryData[$i-1]);
        }
    }

    public function readRateDetails( $carrierId) {
        $rateTypes = $this->_reateEngineModel->getRateTypes();
        $data = $this->changeSheetByName("Rate Details")
                ->loadData()
                ->getData();
        $queryData = [];
        $error = [];
        
        foreach ($data as $i => $d) {
            if(trim($d[0])==""){
                return $queryData;
            }
            $service = $this->_reateEngineModel->getServiceByName($d[0]);
            if (!$service) {
                $error = [
                    'error_type' => 'Service',
                    'error_message' => 'Service Not Found'
                ];
                break;
            }
            
            $rateTypes = $this->_reateEngineModel->getRateTypeByName($d[1]);
            if (!$rateTypes) {
                $error = [
                    'error_type' => 'Rate Type',
                    'error_message' => 'Invalid rate type, Please enter a valid rate type.'
                ];
                break;
            }
            $fromZone = $this->_reateEngineModel->getZoneByName( $carrierId, $d[2]);
            if (!$fromZone) {
                $error = [
                    'error_type' => 'Zone',
                    'error_message' => 'Zone not found.'
                ];
                break;
            }
            $toZone = $this->_reateEngineModel->getZoneByName( $carrierId, $d[3]);
            if (!$toZone) {
                $error = [
                    'error_type' => 'Zone',
                    'error_message' => 'Zone not found.'
                ];
                break;
            }
            $unit = $this->_reateEngineModel->getUnitByName($d[9]);
            if (!$unit) {
                $error = [
                    'error_type' => 'Unit',
                    'error_message' => 'Invalid package unit.'
                ];
                break;
            }
            $queryData[$i - 1]['carrier_id'] = $service['courier_id'];
            $queryData[$i - 1]['service_id'] = $service['id'];
            $queryData[$i - 1]['rate_type_id'] = $rateTypes['id'];
            $queryData[$i - 1]['from_zone_id'] = $fromZone['id'];
            $queryData[$i - 1]['to_zone_id'] = $toZone['id'];
            $queryData[$i - 1]['start_unit'] = $d[4];
            $queryData[$i - 1]['end_unit'] = $d[5];
            $queryData[$i - 1]['rate'] = $d[6];
            $queryData[$i - 1]['additional_cost'] = $d[7];
            $queryData[$i - 1]['additional_base_unit'] = $d[8];
            $queryData[$i - 1]['rate_unit_id'] = $unit['id'];
            $queryData[$i-1]['account_number']=$d[10];
        }
        if(count($error)){
           return ['error'=>$error]; 
        }else{
            return $queryData;
        }
    }

    public function loadExcelFromPost() {
        $this->_spredSheet = $this->_exceReader->load($_FILES['excel']['tmp_name']);
        return $this;
    }

    public function filterAndProcessData() {
        if ($this->_spredSheet->sheetNameExists('Zone')) {
            $this->readZones();
        } else if ($this->_spredSheet->sheetNameExists('Zone Definations')) {
            $this->readZoneDefinations();
        } else if ($this->_spredSheet->sheetNameExists('Rate Details')) {
            $this->readRateDetails();
        } else if ($this->_spredSheet->sheetNameExists('Service List')) {
            //No need to update
        } else if ($this->_spredSheet->sheetNameExists('Countries')) {
            //Always will be constant from database
        } else if ($this->_spredSheet->sheetNameExists('Rate Types')) {
            //No need to update
        } else if ($this->_spredSheet->sheetNameExists('Rate Units')) {
            //No need to update
        }
    }

}
