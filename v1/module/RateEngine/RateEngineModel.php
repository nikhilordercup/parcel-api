<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace v1\module\RateEngine;
/**
 * Description of RateEngineModel
 *
 * @author perce
 */
class RateEngineModel
{

    private $_db;

    //put your code here
    public function __construct()
    {
        $this->_db = new \DbHandler();
    }

    public function getAllCountry()
    {
        return $this->_db->getAllRecords("SELECT short_name, alpha2_code, alpha3_code, numeric_code FROM " . DB_PREFIX . "countries");
    }

    public function getZoneData($carrierId)
    {
        return $this->_db->getAllRecords("SELECT name FROM " . DB_PREFIX . "zone_info WHERE carrier_id=$carrierId ");
    }

    public function getRateTypes()
    {
        return $this->_db->getAllRecords("SELECT name FROM " . DB_PREFIX . "rate_types");
    }

    public function getServiceData($carrierId)
    {
        return $this->_db->getAllRecords("SELECT service_name,service_code FROM " . DB_PREFIX . "courier_vs_services WHERE courier_id=$carrierId");
    }

    public function getRateData($carrierId, $accounts)
    {
        if ($accounts != "") {
            $sql = "SELECT  CS.service_name ,RT.name as rate_type, FZ.name as from_zone, "
                . "TZ.name as to_zone, RI.start_unit, RI.end_unit, RI.rate, "
                . "RI.additional_cost,  RI.additional_base_unit, RUT.name, CVC.account_number  "
                . "FROM `icargo_rate_info` AS RI LEFT JOIN icargo_courier_vs_services AS CS "
                . "ON RI.service_id=CS.id LEFT JOIN icargo_rate_types AS RT "
                . "ON RI.rate_type_id  = RT.id LEFT JOIN icargo_zone_info AS FZ "
                . "ON RI.from_zone_id = FZ.id LEFT JOIN icargo_zone_info AS TZ "
                . "ON RI.to_zone_id = TZ.id LEFT JOIN icargo_rate_units AS RUT "
                . "ON RI.rate_unit_id=RUT.id LEFT JOIN icargo_courier_vs_company AS CVC "
                . "ON RI.account_id=CVC.id WHERE RI.carrier_id=$carrierId AND "
                . "RI.account_id IN ($accounts) ";
        }
//        exit($sql);
        return $this->_db->getAllRecords($sql);
    }

    public function getRateUnits()
    {
        return $this->_db->getAllRecords("SELECT name,abb FROM " . DB_PREFIX . "rate_units");
    }

    public function getZoneDefinations($carrierId)
    {
        $sql = "SELECT ZI.name,ZD.city, ZD.post_code, ZD.country,"
            . " ZD.flow_type, ZD.volume_base, ZD.level FROM "
            . DB_PREFIX . "zone_details AS ZD LEFT JOIN " . DB_PREFIX
            . "zone_info AS ZI ON ZD.zone_id=ZI.id "
            . "WHERE ZI.carrier_id=$carrierId ";
        return $this->_db->getAllRecords($sql);
    }

    public function updateZoneByName($oldName, $name, $carrierId)
    {
        $sql = "UPDATE " . DB_PREFIX . "zone_info SET name='$name' WHERE name='$oldName'  AND carrier_id=$carrierId";
        $this->_db->updateData($sql);
    }

    public function addZone($name, $carrierId)
    {
        return $this->_db->save("zone_info", ['name' => $name, 'carrier_id' => $carrierId]);
    }

    public function addZoneDetails($zoneInfo = [])
    {
        return $this->_db->save('zone_details', $zoneInfo);
    }

    public function deleteZoneDetails($carrierId)
    {
        $sql = "DELETE FROM " . DB_PREFIX . "zone_details WHERE zone_id IN ("
            . "SELECT id FROM " . DB_PREFIX . "zone_info "
            . "WHERE  carrier_id=$carrierId)";
        return $this->_db->delete($sql);
    }

    public function fetchCarrierByAccountNumber($number)
    {
        $query = "SELECT * FROM " . DB_PREFIX . "courier_vs_company WHERE account_number='$number'";
        return $this->_db->getOneRecord($query);
    }

    public function searchZone($address, $carrierId)
    {
        if(strlen($address->country)==3){
            $t=$this->iso3Toiso2($address->country);
            $address->country=$t['alpha2_code'];
        }
        if(!isset($address->city)){
            $address->city="";
        }
        if ($address->country == 'GB') {
            if ($address->zip == trim($address->zip) && strpos($address->zip, ' ') == false) {
                $address->zip = substr_replace($address->zip, ' ', -3, -3);
            };
            $query = "SELECT ZD.*,ZF.name,ZF.carrier_id FROM " . DB_PREFIX . "zone_details AS ZD"
                . " LEFT JOIN " . DB_PREFIX . "zone_info AS ZF ON ZF.id=ZD.zone_id WHERE "
                . " (ZD.city = '" . $address->city . "' "
                . "OR INSTR('" . $address->zip . "',ZD.post_code) "
                . "OR INSTR(ZD.post_code , '" . $address->zip . "') "
                . "OR ZD.country='" . $address->country . "') AND ZF.carrier_id=" . $carrierId;
            $rec = $this->_db->getAllRecords($query);
            $city = [];
            $country=[];
            $zip = $this->searchUkPost($rec, $address->zip);
            if (empty($zip)) {
                foreach ($rec as $r) {
                    if ($r['level']=='City' && $r['city'] == $address->city) {
                        $city = $r;
                    }elseif ($r['level']=='Country' && $r['country']==$address->country){
                        $country=$r;
                    }
                }
            }
        } else {
            $query = "SELECT ZD.*,ZF.name,ZF.carrier_id FROM " . DB_PREFIX . "zone_details AS ZD"
                . " LEFT JOIN " . DB_PREFIX . "zone_info AS ZF ON ZF.id=ZD.zone_id WHERE "
                . " (ZD.city = '" . $address->city . "' "
                . "OR ZD.post_code = '$address->zip'  "
                . "OR ZD.country='" . $address->country . "') AND ZF.carrier_id=" . $carrierId;
            $rec = $this->_db->getAllRecords($query);
            $city = [];
            $country=[];
            $zip = [];

            foreach ($rec as $r) {
                if ($r['level']=='Post Code' && $r['post_code'] == $address->zip) {
                    $zip = $r;
                } else if ($r['level']=='City' && $r['city'] == $address->city) {
                    $city = $r;
                }elseif ($r['level']=='Country' && $r['country']==$address->country){
                    $country=$r;
                }
            }
        }
        if (!count($rec)) {
            return $rec;
        }
        if (count($zip)) {
            return $zip;
        } else if (count($city)) {
            return $city;
        } else {
            return $country;
        }
    }

    public function searchPriceForZone($carrierId, $fromZone, $toZone)
    {
        $query = "SELECT R.*,C.name as carrier_name,S.service_name,S.service_code,RT.name as rate_type"
            . ",RU.name as rate_unit, R.account_id, CVC.account_number FROM " . DB_PREFIX . "rate_info AS R "
            . "LEFT JOIN  " . DB_PREFIX . "courier_vs_services AS S ON R.service_id=S.id "
            . "LEFT JOIN " . DB_PREFIX . "courier AS C ON R.carrier_id=C.id "
            . "LEFT JOIN " . DB_PREFIX . "rate_types AS RT ON R.rate_type_id=RT.id "
            . "LEFT JOIN " . DB_PREFIX . "courier_vs_company AS CVC ON R.account_id=CVC.id "
            . "LEFT JOIN " . DB_PREFIX . "rate_units AS RU ON R.rate_unit_id=RU.id WHERE "
            . "R.carrier_id=$carrierId AND R.from_zone_id=$fromZone "
            . "AND R.to_zone_id=$toZone ";
        return $this->_db->getAllRecords($query);
    }

    public function getServiceByName($name)
    {
        $query = "SELECT CS.*,C.name FROM " . DB_PREFIX . "courier_vs_services As CS "
            . "LEFT JOIN " . DB_PREFIX . "courier AS C ON CS.courier_id=C.id "
            . "WHERE CS.service_name='$name' ";
        return $this->_db->getOneRecord($query);
    }

    public function getRateTypeByName($name)
    {
        $query = "SELECT R.* FROM " . DB_PREFIX . "rate_types As R "
            . "WHERE R.name='$name' ";
        return $this->_db->getOneRecord($query);
    }

    public function getZoneByName($carrierId, $name)
    {
        $query = "SELECT * FROM " . DB_PREFIX . "zone_info  "
            . "WHERE name='$name' AND carrier_id=$carrierId ";
        try {
            return $this->_db->getOneRecord($query);
        } catch (Exception $ex) {
            echo $query;
            exit($ex->getMessage());
        }
    }

    public function getUnitByName($name)
    {
        $query = "SELECT R.* FROM " . DB_PREFIX . "rate_units As R "
            . "WHERE R.name='$name' ";
        return $this->_db->getOneRecord($query);
    }

    public function addNewRate($carrierId, $rates)
    {
        $lc=[];
        foreach ($rates as $k => $r) {
            if(!isset($lc[$r['account_number']])) {
                $account = $this->_db->getOneRecord("SELECT id FROM " . DB_PREFIX . "courier_vs_company WHERE "
                    . " courier_id=$carrierId AND account_number=" . $r['account_number']);

                if (!$account) {
                    return [
                        'error' => true,
                        'message' => 'Account number not found:' . $r['account_number']
                    ];
                }
                $deleteQuery = "DELETE FROM " . DB_PREFIX . "rate_info "
                    . "WHERE  carrier_id=$carrierId AND account_id=" . $account['id'];
                $this->_db->delete($deleteQuery);
                $lc[$r['account_number']]=$account;
            }else{
                $account=$lc[$r['account_number']];
            }
                $rates[$k]['account_id'] = $account['id'];

        }
        $this->_db->startTransaction();
        foreach ($rates as $r) {
            unset($r['account_number']);
            $this->_db->save('rate_info', $r);
        }
        $this->_db->commitTransaction();
        return [
            'error' => FALSE,
            'message' => 'success'
        ];
    }

    public function searchUkPost($rec, $zip, $surcharge = false)
    {
        if ($zip == trim($zip) && strpos($zip, ' ') == false) {
            $zip = substr_replace($zip, ' ', -3, -3);
        }
        foreach ($rec as $r) {
            if(isset($r['level']) && $r['level']!='Post Code'){
                continue;
            }
            for ($i = strlen($zip); $i >= 2; $i--) {
                if ($surcharge) {
                    if (trim($r) == substr($zip, 0, $i)) {
                        return $r;
                    }
                } else if ($r['post_code'] == substr($zip, 0, $i)) {
                    return $r;
                }
            }
        }
        return [];
    }

    public function getServices($courierId = 0, $companyId = 0)
    {
        if ($courierId > 0 && $companyId > 0) {
            $query = "SELECT DISTINCT(CS.id),CS.service_name 
FROM ".DB_PREFIX."courier_vs_services AS CS  
LEFT JOIN ".DB_PREFIX."courier_vs_services_vs_company AS CSC ON CS.id=CSC.service_id 
LEFT JOIN ".DB_PREFIX."courier_vs_company AS CC ON CC.courier_id=CSC.courier_id 
WHERE CS.courier_id=$courierId AND CSC.company_id=$companyId";
        } else if ($courierId > 0 && $companyId == 0) {
            $query = "SELECT DISTINCT  * FROM " . DB_PREFIX . "courier_vs_services_vs_company "
                . "WHERE courier_id=$courierId  ";
        } else if ($courierId == 0 && $companyId > 0) {
            $query = "SELECT DISTINCT  * FROM " . DB_PREFIX . "courier_vs_services_vs_company "
                . "WHERE  company_id=$companyId ";
        } else if ($courierId == 0 && $companyId == 0) {
            $query = "SELECT DISTINCT  * FROM " . DB_PREFIX . "courier_vs_services_vs_company ";
        }
        return $this->_db->getAllRecords($query);
    }

    public function getCompanyAccounts($courierId, $companyId)
    {
        $query = "SELECT DISTINCT * FROM " . DB_PREFIX . "courier_vs_company WHERE courier_id=$courierId "
            . "AND company_id=$companyId";
        return $this->_db->getAllRecords($query);
    }

    public function addSurcharge($data, $carrierId, $services, $accounts, $surcharege)
    {
        foreach ($accounts as $a) {
            foreach ($services as $s) {
                $this->deleteIfExist($carrierId, $a, $s, $surcharege);
                $t = [
                    'carrier_id' => $carrierId,
                    'service_id' => $s,
                    'account_id' => $a,
                    'surcharge' => $surcharege,
                    'surcharge_rules' => json_encode($data)
                ];
                $this->_db->save('surcharges', $t);
            }
        }
    }

    public function fetchSurcharge($carrierId, $services, $accounts, $surcharege)
    {
        $query = "SELECT S.id, C.name AS carrierName,CS.service_name AS serviceName, A.account_number AS accountNumber,"
            . " S.surcharge, S.surcharge_rules AS surchargeRule, CS.service_code as serviceCode "
            . "FROM " . DB_PREFIX . "surcharges AS S "
            . "LEFT JOIN " . DB_PREFIX . "courier AS C ON S.carrier_id=C.id "
            . "LEFT JOIN " . DB_PREFIX . "courier_vs_services AS CS ON S.service_id=CS.id "
            . "LEFT JOIN " . DB_PREFIX . "courier_vs_company AS A ON S.account_id=A.id "
            . "WHERE carrier_id=$carrierId AND account_id IN (" . implode(',', $accounts) . ") ";
        if ($services && count($services)) {
            $query .= " AND service_id IN (" . implode(',', $services) . ") ";
        }
        if ($surcharege) {
            $query .= " AND surcharge=$surcharege";
        }
        return $this->_db->getAllRecords($query);
    }

    public function deleteSurcharge($id)
    {
        return $this->_db->delete("DELETE FROM " . DB_PREFIX . "surcharges WHERE id=$id");
    }

    public function deleteIfExist($carrierId, $accountId, $serviceId, $surchargeId)
    {
        return $this->_db->delete("DELETE FROM " . DB_PREFIX . "surcharges WHERE carrier_id=$carrierId "
            . "AND account_id=$accountId AND service_id=$serviceId AND surcharge=$surchargeId");
    }

    public function getEndPointByEnv($env = null)
    {
        if ($env) {
            return $this->_db->getAllRecords("SELECT * FROM " . DB_PREFIX . "service_providers 
        WHERE provider_type='ENDPOINT' AND app_env='$env'");
        } else {
            return $this->_db->getAllRecords("SELECT * FROM " . DB_PREFIX . "service_providers 
        WHERE provider_type='ENDPOINT' ");
        }
    }

    public function getProviderInfo($callType,$env,$providerType='ENDPOINT')
    {
            $query = "SELECT CSP.request_type,C.code, SP.rate_endpoint,SP.label_endpoint,SP.app_env,
EP.provider_type, EP.provider 
FROM `icargo_carrier_service_provider` AS CSP
LEFT JOIN icargo_courier AS C ON C.id=CSP.carrier_id
LEFT JOIN icargo_service_providers AS SP ON SP.id =CSP.provider_id
LEFT JOIN icargo_service_providers AS EP ON EP.id=CSP.provider_endpoint_id
WHERE EP.provider_type='$providerType' AND CSP.request_type='$callType' AND SP.app_env='$env'
";
        return $this->_db->getAllRecords($query);
    }
    public function iso3Toiso2($iso3){
        $query="SELECT alpha2_code FROM `".DB_PREFIX."countries` WHERE alpha3_code='$iso3'";
        return $this->_db->getOneRecord($query);
    }
    public function addCarrier($name,$code,$desc,$icon="",$companyId=0){
        $data=[
            'name'=>$name,
            'code'=>$code,
            'icon'=>$icon,
            'description'=>$desc,
            'is_self'=>'YES',
            'company_id'=>$companyId,
            'is_apiused'=>'YES',
            'status'=>1
        ];
        return $this->_db->save('courier',$data);
    }
    public function addService($name,$code,$desc,$serviceType,$carrierId,$icon="",$createrId=0,$flowType=""){
        $data=[
            'courier_id'=>$carrierId,
            'service_name'=>$name,
            'service_code'=>$code,
            'service_icon'=>$icon,
            'service_description'=>$desc,
            'created_by'=>$createrId,
            'status'=>1,
            'service_type'=>$serviceType,
            'flow_type'=>$flowType
        ];
        return $this->_db->save('courier_vs_services',$data);
    }
    public function updateCarrier($carrierId,$newData){
        return $this->_db->update('courier',$newData,"id=$carrierId");
    }
    public function updateService($serviceId,$newData){
        return $this->_db->update('courier_vs_services',$newData,"id=$serviceId");
    }
    public function addServiceOption($data){
        $d=$this->_db->getOneRecord("SELECT * FROM ".DB_PREFIX."service_options 
        WHERE service_id=".$data['service_id']);
        if(!$d) {
            return $this->_db->save('service_options', $data);
        }else{
            return $this->updateServiceOption($d['id'],$data);
        }
    }
    public function updateServiceOption($optionId,$newData){
        return $this->_db->update('service_options',
            $newData,"id=$optionId");
    }
    public function getAllCarriers(){
        return $this->_db->getAllRecords("SELECT * FROM ".DB_PREFIX."courier WHERE status=1");
    }
    public function getAllServicesByCarrier($carrierId){

        return $this->_db->getAllRecords("SELECT CS.*,C.company_id,C.name as carrier_name, C.is_self
        FROM ".DB_PREFIX."courier_vs_services AS CS LEFT JOIN
        ".DB_PREFIX."courier AS C ON CS.courier_id=C.id
        WHERE CS.status=1 AND CS.courier_id=$carrierId");
    }
    public function getServiceOption($serviceId){
        return $this->_db->getOneRecord("SELECT * FROM ".DB_PREFIX."service_options WHERE service_id=$serviceId");
    }
    public function getTaxDetails($countryId){
        return $this->_db->getOneRecord("SELECT * FROM ".DB_PREFIX."tax_details WHERE country_id=$countryId");
    }
    public function saveTaxDetails($countryId,$request){
        $tax=$this->getTaxDetails($countryId);
        if($tax){
            $data=[
                'tax_type'=>$request->tax_type,
                'tax_factor'=>$request->tax_factor,
                'tax_factor_value'=>$request->tax_factor_value,
                'updated_at'=>date('Y-m-d H:i:s')
                ];
            return $this->_db->update('tax_details',$data,"country_id=$countryId");
        }else{
            $data=[
                'tax_type'=>$request->tax_type,
                'tax_factor'=>$request->tax_factor,
                'tax_factor_value'=>$request->tax_factor_value,
                'country_id'=>$countryId
            ];
            return $this->_db->save('tax_details',$data);
        }
    }
    public function getTaxInfoByIso2($iso){
        $sql="SELECT T.* FROM ".DB_PREFIX."countries AS C LEFT JOIN 
                ".DB_PREFIX."tax_details AS T ON C.id=T.country_id WHERE C.alpha2_code='$iso'";
        return $this->_db->getOneRecord($sql);
    }
}
