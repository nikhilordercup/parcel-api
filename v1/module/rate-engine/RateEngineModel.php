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
        return $this->_db->getAllRecords("SELECT service_name,service_code FROM " . DB_PREFIX . "courier_vs_services WHERE courier_id=$carrierId");
    }

    public function getRateData($carrierId, $accounts) {
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
        $query = "SELECT * FROM " . DB_PREFIX . "zone_info  "
                . "WHERE name='$name' AND carrier_id=$carrierId ";
        try {
            return $this->_db->getOneRecord($query);
        } catch (Exception $ex) {
            echo $query;
            exit($ex->getMessage());
        }
    }

    public function getUnitByName($name) {
        $query = "SELECT R.* FROM " . DB_PREFIX . "rate_units As R "
                . "WHERE R.name='$name' ";
        return $this->_db->getOneRecord($query);
    }

    public function addNewRate($carrierId, $rates) {
        foreach ($rates as $k => $r) {
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

    public function searchUkPost($rec, $zip, $surcharge = false) {
        if ($zip == trim($zip) && strpos($zip, ' ') == false) {
            $zip = substr_replace($zip, ' ', -3, -3);
        }
        foreach ($rec as $r) {
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

    public function getServices($courierId = 0, $companyId = 0) {
        if ($courierId > 0 && $companyId > 0) {
            $query = "SELECT DISTINCT(CSC.service_id),CS.service_name "
                    . "FROM " . DB_PREFIX . "courier_vs_services_vs_company  AS CSC "
                    . "LEFT JOIN " . DB_PREFIX . "courier_vs_company AS CC "
                    . "ON CC.id=CSC.courier_id "
                    . "LEFT JOIN " . DB_PREFIX . "courier_vs_services AS CS "
                    . "ON CS.id=CSC.service_id "
                    . "WHERE CC.courier_id=$courierId AND CSC.company_id=$companyId ";
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

    public function getCompanyAccounts($courierId, $companyId) {
        $query = "SELECT DISTINCT * FROM " . DB_PREFIX . "courier_vs_company WHERE courier_id=$courierId "
                . "AND company_id=$companyId";
        return $this->_db->getAllRecords($query);
    }

    public function addSurcharge($data, $carrierId, $services, $accounts, $surcharege) {
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

    public function fetchSurcharge($carrierId, $services, $accounts, $surcharege) {
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

    public function deleteSurcharge($id) {
        return $this->_db->delete("DELETE FROM " . DB_PREFIX . "surcharges WHERE id=$id");
    }

    public function deleteIfExist($carrierId, $accountId, $serviceId, $surchargeId) {
        return $this->_db->delete("DELETE FROM " . DB_PREFIX . "surcharges WHERE carrier_id=$carrierId "
                        . "AND account_id=$accountId AND service_id=$serviceId AND surcharge=$surchargeId");
    }

    public function getSurchargeByRate() {
        
    }

}
