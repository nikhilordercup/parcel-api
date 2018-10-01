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

    public function getAllCountry() {
        return $this->_db->getAllRecords("SELECT short_name, alpha2_code, alpha3_code, numeric_code FROM " . DB_PREFIX . "countries");
    }

    public function getZoneData($carrierId) {
        return $this->_db->getAllRecords("SELECT name FROM " . DB_PREFIX . "zone_info WHERE carrier_id=$carrierId ");
    }

    public function getRateTypes() {
        return $this->_db->getAllRecords("SELECT name FROM " . DB_PREFIX . "rate_types");
    }

    public function getServiceData($carrierId) {
        return $this->_db->getAllRecords("SELECT service_name,service_code FROM " . DB_PREFIX . "courier_vs_services");
    }

    public function getRateData($carrierId) {
        $sql = "SELECT  CS.service_name ,RT.name as rate_type, FZ.name as from_zone, "
                . "TZ.name as to_zone, RI.start_unit, RI.end_unit, RI.rate, "
                . "RI.additional_cost,  RI.additional_base_unit, RUT.name "
                . "FROM `icargo_rate_info` AS RI LEFT JOIN icargo_courier_vs_services AS CS "
                . "ON RI.service_id=CS.id LEFT JOIN icargo_rate_types AS RT "
                . "ON RI.rate_type_id  = RT.id LEFT JOIN icargo_zone_info AS FZ "
                . "ON RI.from_zone_id = FZ.id LEFT JOIN icargo_zone_info AS TZ "
                . "ON RI.to_zone_id = TZ.id LEFT JOIN icargo_rate_units AS RUT "
                . "ON RI.rate_unit_id=RUT.id WHERE RI.carrier_id=$carrierId ";
        return $this->_db->getAllRecords($sql);
    }

    public function getRateUnits() {
        return $this->_db->getAllRecords("SELECT name,abb FROM " . DB_PREFIX . "rate_units");
    }

    public function getZoneDefinations($carrierId) {
        $sql = "SELECT ZI.name,ZD.city, ZD.post_code, ZD.country,"
                . " ZD.flow_type, ZD.volume_base, ZD.level FROM "
                . DB_PREFIX . "zone_details AS ZD LEFT JOIN " . DB_PREFIX
                . "zone_info AS ZI ON ZD.zone_id=ZI.id "
                . "WHERE ZI.carrier_id=$carrierId ";
        return $this->_db->getAllRecords($sql);
    }

//    public function getZoneByName($companyId, $carrierId, $name) {
//        $sql = "SELECT * FROM " . DB_PREFIX . "zone_info WHERE name='$oldName' AND company_id=$companyId AND carrier_id=$carrierId";
//        return $this->_db->getOneRecord($sql);
//    }

    public function updateZoneByName($oldName, $name, $carrierId) {
        $sql = "UPDATE " . DB_PREFIX . "zone_info SET name='$name' WHERE name='$oldName'  AND carrier_id=$carrierId";
        $this->_db->updateData($sql);
    }

    public function addZone($name, $carrierId) {
        return $this->_db->save("zone_info", ['name' => $name, 'carrier_id' => $carrierId]);
    }

    public function addZoneDetails($zoneInfo = []) {
        return $this->_db->save('zone_details', $zoneInfo);
    }

    public function deleteZoneDetails($carrierId) {
        $sql = "DELETE FROM " . DB_PREFIX . "zone_details WHERE zone_id IN ("
                . "SELECT id FROM " . DB_PREFIX . "zone_info "
                . "WHERE  carrier_id=$carrierId)";
        return $this->_db->delete($sql);
    }

    public function fetchCarrierByAccountNumber($number) {
        $query = "SELECT * FROM " . DB_PREFIX . "courier_vs_company WHERE account_number='$number'";
        return $this->_db->getOneRecord($query);
    }

    public function searchZone($address, $carrierId) {
        if ($address->country == 'GB') {
            $query = "SELECT ZD.*,ZF.name,ZF.carrier_id FROM " . DB_PREFIX . "zone_details AS ZD"
                    . " LEFT JOIN " . DB_PREFIX . "zone_info AS ZF ON ZF.id=ZD.zone_id WHERE "
                    . " (ZD.city = '" . $address->city . "' "
                    . "OR INSTR('" . $address->zip . "',ZD.post_code) "
                    . "OR INSTR(ZD.post_code , '" . $address->zip . "') "
                    . "OR ZD.country='" . $address->country . "') AND ZF.carrier_id=" . $carrierId;
            $rec = $this->_db->getAllRecords($query);
            $city = [];
            $zip = $this->searchUkPost($rec, $address->zip);
            if (empty($zip)) {
                foreach ($rec as $r) {
                    if ($r['city'] == $address->city) {
                        $city = $r;
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
            $zip = [];

            foreach ($rec as $r) {
                if ($r['post_code'] == $address->zip) {
                    $zip = $r;
                } else if ($r['city'] == $address->city) {
                    $city = $r;
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
            return [];
        }
    }

    public function searchPriceForZone($carrierId, $fromZone, $toZone) {
        $query = "SELECT R.*,C.name as carrier_name,S.service_name,RT.name as rate_type"
                . ",RU.name as rate_unit FROM " . DB_PREFIX . "rate_info AS R "
                . "LEFT JOIN  " . DB_PREFIX . "courier_vs_services AS S ON R.service_id=S.id "
                . "LEFT JOIN " . DB_PREFIX . "courier AS C ON R.carrier_id=C.id "
                . "LEFT JOIN " . DB_PREFIX . "rate_types AS RT ON R.rate_type_id=RT.id "
                . "LEFT JOIN " . DB_PREFIX . "rate_units AS RU ON R.rate_unit_id=RU.id WHERE "
                . "R.carrier_id=$carrierId AND R.from_zone_id=$fromZone "
                . "AND R.to_zone_id=$toZone ";
        return $this->_db->getAllRecords($query);
    }

    public function getServiceByName($name) {
        $query = "SELECT CS.*,C.name FROM " . DB_PREFIX . "courier_vs_services As CS "
                . "LEFT JOIN " . DB_PREFIX . "courier AS C ON CS.courier_id=C.id "
                . "WHERE CS.service_name='$name' ";
        return $this->_db->getOneRecord($query);
    }

    public function getRateTypeByName($name) {
        $query = "SELECT R.* FROM " . DB_PREFIX . "rate_types As R "
                . "WHERE R.name='$name' ";
        return $this->_db->getOneRecord($query);
    }

    public function getZoneByName($carrierId, $name) {
        $query = "SELECT Z.* FROM " . DB_PREFIX . "zone_info AS Z "
                . "WHERE Z.name='$name' AND Z.carrier_id=$carrierId ";
        //print_r($query);
        return $this->_db->getOneRecord($query);
    }

    public function getUnitByName($name) {
        $query = "SELECT R.* FROM " . DB_PREFIX . "rate_units As R "
                . "WHERE R.name='$name' ";
        return $this->_db->getOneRecord($query);
    }

    public function addNewRate($carrierId, $rates) {
        $deleteQuery = "DELETE FROM " . DB_PREFIX . "rate_info WHERE  carrier_id=$carrierId";
        $this->_db->delete($deleteQuery);
        $this->_db->startTransaction();
        foreach ($rates as $r) {
            $this->_db->save('rate_info', $r);
        }
        $this->_db->commitTransaction();
    }

    public function searchUkPost($rec, $zip) {
        for ($i = strlen($zip); $i >= 2; $i--) {
            foreach ($rec as $r) {
                echo $r['post_code'] . '=' . substr($zip, 0, $i) . '****';
                if ($r['post_code'] == substr($zip, 0, $i)) {
                    return $r;
                }
            }
        }
        return [];
    }

}
