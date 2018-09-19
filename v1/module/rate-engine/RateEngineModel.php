<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RateEngineModel
 *
 * @author perce
 */
class RateEngineModel {

    private $_db;

    //put your code here
    public function __construct() {
        $this->_db = new DbHandler();
    }
    public function getAllCountry(){
        return $this->_db->getAllRecords("SELECT short_name, alpha2_code, alpha3_code, numeric_code FROM ".DB_PREFIX."countries");
    }
    public function getZoneData(){
        return $this->_db->getAllRecords("SELECT name FROM ".DB_PREFIX."zone_info");
    }
    public function getRateTypes(){
        return $this->_db->getAllRecords("SELECT name FROM ".DB_PREFIX."rate_types");
    }
    public function getServiceData(){
        return $this->_db->getAllRecords("SELECT service_name,service_code FROM ".DB_PREFIX."courier_vs_services");
    }
    public function getRateData(){
        $sql="SELECT  CS.service_name ,RT.name as rate_type, FZ.name as from_zone, "
                . "TZ.name as to_zone, RI.start_unit, RI.end_unit, RI.rate, "
                . "RI.additional_cost,  RI.additional_base_unit, RUT.name "
                . "FROM `icargo_rate_info` AS RI LEFT JOIN icargo_courier_vs_services AS CS "
                . "ON RI.service_id=CS.id LEFT JOIN icargo_rate_types AS RT "
                . "ON RI.rate_type_id  = RT.id LEFT JOIN icargo_zone_info AS FZ "
                . "ON RI.from_zone_id = FZ.id LEFT JOIN icargo_zone_info AS TZ "
                . "ON RI.to_zone_id = TZ.id LEFT JOIN icargo_rate_units AS RUT "
                . "ON RI.rate_unit_id=RUT.id";
        return $this->_db->getAllRecords($sql);
    }
    public function getRateUnits(){
        return $this->_db->getAllRecords("SELECT name,abb FROM ".DB_PREFIX."rate_units");
    }
    public function getZoneDefinations(){
        $sql="SELECT ZI.name,ZD.city, ZD.post_code, ZD.country,"
                . " ZD.flow_type, ZD.volume_base, ZD.level FROM "
                .DB_PREFIX."zone_details AS ZD LEFT JOIN ".DB_PREFIX
                . "zone_info AS ZI ON ZD.zone_id=ZI.id";
        return $this->_db->getAllRecords($sql);
    }
    public function getZoneByName($companyId,$carrierId,$name){
        $sql="SELECT * FROM ".DB_PREFIX."zone_info WHERE name='$oldName' AND company_id=$companyId AND carrier_id=$carrierId";
        return $this->_db->getOneRecord($sql);
    }
    public function updateZoneByName($oldName,$name,$companyId,$carrierId){
        $sql="UPDATE ".DB_PREFIX."zone_info SET name='$name' WHERE name='$oldName' AND company_id=$companyId AND carrier_id=$carrierId";
        $this->_db->updateData($sql);
    }
    public function addZone($name,$companyId,$carrierId){
        return $this->_db->save("zone_info", ['name'=>$name,'company_id'=>$companyId,'carrier_id'=>$carrierId]);
    }
    public function addZoneDetails($zoneInfo=[]){
       return $this->_db->save('zone_details', $zoneInfo);
    }
    public function deleteZoneDetails($companyId,$carrierId){
        $sql="DELETE FROM ".DB_PREFIX."zone_details WHERE zone_id IN ("
                . "SELECT id FROM ".DB_PREFIX."zone_info "
                . "WHERE company_id=$companyId AND carrier_id=$carrierId)";
        return $this->_db->delete($sql);
    }
    

}
