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
    private $_sheetData=[];
    /**
     *
     * @var RateEngineModel
     */
    private $_reateEngineModel;
    //put your code here
    function __construct() {
        $this->_exceReader=new \PhpOffice\PhpSpreadsheet\Reader\Xlsx;
        //$this->_spredSheet = $this->_exceReader->load($_FILES['file']['tmp_name']);
        $this->_reateEngineModel=new RateEngineModel();
    }

    public function getHeader(){
        $this->_sheetData[0];
    }
    public function getData(){
        $local=$this->_sheetData;
        unset($local[0]);
        return $local;
    }
    public function changeSheetByName($name) {
        $this->_spredSheet->setActiveSheetIndexByName($name);
        return $this;
    }
    public function loadData(){
        $this->_sheetData=$this->_spredSheet
                ->getActiveSheet()
                ->toArray();
        return $this;
    }
    public function readZones($companyid,$carrierId){
        $data=$this->changeSheetByName("Zone")
                ->loadData()
                ->getData();
        
        $queryData=[];
        foreach ($data as $i=>$d){
            if(trim($d[0])==""){
                break;
            }
            $queryData[$i-1]['name']=$d[0];
            $queryData[$i-1]['newName']=$d[1];
            $queryData[$i-1]['action']=$d[2];
            if($d[2]=='New'){
                $this->_reateEngineModel->addZone($d[0],$companyid,$carrierId);
            }elseif ($d[2]=='Update') {
                $this->_reateEngineModel->updateZoneByName($d[0], $d[1],$companyid,$carrierId);
            }
        }
        print_r($queryData);exit;
        
    }
    public function readZoneDefinations($companyId,$carrierId){
        $data=$this->changeSheetByName("Zone Definations")
                ->loadData()
                ->getData();
        $queryData=[];
        $this->_reateEngineModel->deleteZoneDetails($companyId, $carrierId);
        foreach ($data as $i=>$d){
            if(trim($d[0])==''){
                break;
            }
            $zone=$this->_reateEngineModel->getZoneByName($companyId, $carrierId, $d[0]);
            $queryData[$i-1]['zone_id']=$zone['id'];
            $queryData[$i-1]['city']=$d[1];
            $queryData[$i-1]['post_code']=$d[2];
            $queryData[$i-1]['country']=$d[3];
            $queryData[$i-1]['level']=$d[4];
            $queryData[$i-1]['flow_type']=$d[5];
            $queryData[$i-1]['volume_base']=$d[6];
            $this->_reateEngineModel->addZoneDetails($queryData);            
        }        
    }
    public function readRateDetails(){
        $rateTypes=$this->_reateEngineModel->getRateTypes();
        $data=$this->changeSheetByName("Rate Details")
                ->loadData()
                ->getData();
        $queryData=[];
        foreach ($data as $i=>$d){
            $queryData[$i-1]['name']=$d[0];
        }
        
    }
    public function loadExcelFromPost(){
        $this->_spredSheet = $this->_exceReader->load($_FILES['excel']['tmp_name']);
        return $this;
    }
    public function filterAndProcessData(){
        if($this->_spredSheet->sheetNameExists('Zone')){
            $this->readZones();
        }else if($this->_spredSheet->sheetNameExists('Zone Definations')){
            $this->readZoneDefinations();
        }else if($this->_spredSheet->sheetNameExists('Rate Details')){
            $this->readRateDetails();
        }else if($this->_spredSheet->sheetNameExists('Service List')){
            //No need to update
        }else if($this->_spredSheet->sheetNameExists('Countries')){
            //Always will be constant from database
        }else if($this->_spredSheet->sheetNameExists('Rate Types')){
            //No need to update
        }else if($this->_spredSheet->sheetNameExists('Rate Units')){
            //No need to update
        }
    }
}
