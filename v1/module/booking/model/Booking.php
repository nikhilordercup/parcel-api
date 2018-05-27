<?php

class Booking_Model_Booking
{

    public static $_db = NULL;

    public

    function __construct(){
        if (self::$_db == NULL) {
            self::$_db = new DbHandler();
        }
        $this->_db = self::$_db;
    }

    /**
     * Start transaction
     */
    public

    function startTransaction(){
        $this->_db->startTransaction();
    }

    /**
     * Start transaction
     */
    public

    function commitTransaction(){
        $this->_db->commitTransaction();
    }

    /**
     * Start transaction
     */
    public

    function rollBackTransaction(){
        $this->_db->rollBackTransaction();
    }

    public

    function getCustomerCarrierAccount($company_id, $customer_id){
        //$sql = "SELECT CT.id AS carrier_id, CT.name, CCT.account_number, CCT.username AS username, CCT.password AS password FROM " .  DB_PREFIX . "courier_vs_company_vs_customer AS CCCT INNER JOIN " . DB_PREFIX . "courier AS CT ON CCCT.courier_id = CT.id INNER JOIN " . DB_PREFIX . "courier_vs_company AS CCT ON CCT.company_id = CCCT.company_id WHERE CCCT.customer_id = '$customer_id' AND CCCT.status = 1 AND CCT.status = 1 AND CT.status=1;";
        $sql = "SELECT CT.description as description, CT.icon AS icon, CT.id AS carrier_id, CT.name, CT.code AS carrier_code, CCT.account_number, CCT.username AS username, CCT.password AS password, CCT.is_internal AS internal, pickup AS pickup, pickup_surcharge AS pickup_surcharge, collection_start_at AS collection_start_at, collection_end_at AS collection_end_at FROM " . DB_PREFIX . "courier_vs_company_vs_customer AS CCCT INNER JOIN " . DB_PREFIX . "courier_vs_company AS CCT ON CCT.company_id = CCCT.company_id AND CCT.courier_id = CCCT.courier_id INNER JOIN " . DB_PREFIX . "courier AS CT ON CCCT.courier_id = CT.id WHERE CCCT.customer_id = '$customer_id' AND CCCT.company_id = '$company_id' AND CCCT.status = 1 AND CT.status=1 AND CCT.is_internal='0'";
        return $this->_db->getAllRecords($sql);
    }

    public

    function getCustomerCarrierServices($customer_id, $carrier_id){
        $sql = "SELECT CST.service_code, CST.service_name
        FROM " . DB_PREFIX . "company_vs_customer_vs_services AS CCST INNER JOIN " . DB_PREFIX . "courier_vs_services_vs_company AS CSCT ON CCST.id = CSCT.service_id INNER JOIN " . DB_PREFIX . "courier_vs_services AS CST ON CST.id = CSCT.service_id WHERE CSCT.status= 1 AND CCST.status= 1 AND CST.status = 1 AND CST.courier_id= '$carrier_id' AND CCST.company_customer_id='$customer_id'";
        return $this->_db->getAllRecords($sql);
    }

    public

    function getCarrierInfo($customer_id){
        $sql = "SELECT CCT.id AS carrier_id, CCT.name, CCT.icon, CCT.description, CCT.code FROM " . DB_PREFIX . "courier_vs_company_vs_customer AS CCCT INNER JOIN " . DB_PREFIX . "courier AS CCT ON CCT.id = CCCT.courier_id WHERE CCCT.customer_id='$customer_id'  AND CCCT.status=1";
        return $this->_db->getAllRecords($sql);
    }

    public

    function checkCustomerAccountStatus($customer_id){
        $sql = "SELECT status FROM " . DB_PREFIX . "users WHERE id = '$customer_id' AND status=1";
        return $this->_db->getRowRecord($sql);
    }

    public

    function getAddressBySearchStringAndCustomerId($customer_id, $search_string){
        $record = $this->_db->getRowRecord("SELECT version_id AS version_id FROM " . DB_PREFIX . "address_book WHERE `customer_id` = '$customer_id' AND `search_string` LIKE '$search_string'");
        return $record['address_id'];
    }

    private

    function _testShipmentTicket($shipment_ticket){
        $record = $this->_db->getOneRecord("SELECT COUNT(1) AS exist FROM " . DB_PREFIX . "shipment WHERE shipment_ticket = '" . $shipment_ticket . "'");
        if ($record['exist'] > 0)
            return true;
        else
            return false;
    }

    private

    function _testParcelTicket($parcel_ticket){
        $record = $this->_db->getOneRecord("SELECT COUNT(1) AS exist FROM " . DB_PREFIX . "shipments_parcel WHERE parcel_ticket = '" . $parcel_ticket . "'");
        if ($record['exist'] > 0)
            return true;
        else
            return false;
    }

    public

    function generateTicketNo($company_id){
        $record = $this->_db->getRowRecord("SELECT (shipment_end_number + 1) AS shipment_ticket_no, shipment_ticket_prefix AS shipment_ticket_prefix FROM " . DB_PREFIX . "configuration WHERE company_id = " . $company_id);
        if ($record) {
            $ticket_number = $record['shipment_ticket_prefix'] . str_pad($record['shipment_ticket_no'], 6, 0, STR_PAD_LEFT);

            //$check_digit = $this->generateCheckDigit($ticket_number);

            //$ticket_number = "$ticket_number$check_digit";

            $this->_db->updateData("UPDATE " . DB_PREFIX . "configuration SET shipment_end_number = shipment_end_number + 1 WHERE company_id = " . $company_id);

            if ($this->_testShipmentTicket($ticket_number)) {
                $this->generateTicketNo($company_id);
            }
            return $ticket_number;
        } else {
            return false;
        }
    }

    public

    function generateParcelTicketNumber($company_id){
        $record = $this->_db->getRowRecord("SELECT (parcel_end_number + 1) AS ticket_no, shipment_ticket_prefix AS shipment_ticket_prefix FROM " . DB_PREFIX . "configuration WHERE company_id = " . $company_id);

        $ticket_number = $record['shipment_ticket_prefix'] . str_pad($record['ticket_no'], 6, 0, STR_PAD_LEFT);

        //$check_digit = $this->generateCheckDigit($ticket_number);

        //$ticket_number = "$ticket_number$check_digit";

        // update ticket number
        $this->_db->updateData("UPDATE " . DB_PREFIX . "configuration SET parcel_end_number = parcel_end_number + 1 WHERE company_id = " . company_id);

        if ($this->_testParcelTicket($ticket_number)) {
            $this->generateParcelTicketNumber();
        }
        return $ticket_number;
    }

    public

    function getCompanyCode($company_id){
        $record = $this->_db->getRowRecord("SELECT code AS code FROM " . DB_PREFIX . "user_code WHERE id = '$company_id'");
        return $record['code'];
    }

    public

    function getCustomerWarehouseIdByCustomerId($company_id, $customer_id){
        $record = $this->_db->getRowRecord("SELECT CUT.warehouse_id FROM " . DB_PREFIX . "company_users AS CUT INNER JOIN " . DB_PREFIX . "users AS UT ON UT.id = CUT.user_id WHERE CUT.user_id='$customer_id' AND CUT.company_id='$company_id'");
        return $record;
    }

    public

    function getDefaultCollectionAddress($customer_id){
        $sql = "SELECT ABT.address_line1 AS street1, ABT.company_name AS business_name, ABT.city AS city, ABT.postcode AS zip, ABT.country AS country_name FROM " . DB_PREFIX . "address_book AS ABT INNER JOIN " . DB_PREFIX . "user_address AS UAT ON ABT.id=UAT.address_id WHERE UAT.user_id='$customer_id' AND UAT.default_address='Y' AND ABT.customer_id='$customer_id'";
        return $this->_db->getRowRecord($sql);
    }

    public

    function getInternalCarrier($company_id){
        $sql = "SELECT CT.icon AS icon, CT.id AS carrier_id, CT.name, CT.code AS carrier_code, CCT.pickup, CCT.pickup_surcharge, CCT.collection_start_at, CCT.collection_end_at, CCT.is_internal AS internal FROM " . DB_PREFIX . "courier_vs_company AS CCT INNER JOIN " . DB_PREFIX . "courier AS CT ON CCT.courier_id=CT.id WHERE CCT.company_id='$company_id' AND CCT.status='1'";
        return $this->_db->getRowRecord($sql);
    }

    public

    function getCourierOperationalArea($company_id){
        $sql = "SELECT t1.id, t1.route_id, t1.postcode FROM " . DB_PREFIX . "route_postcode AS t1 WHERE t1.company_id = '$company_id' AND t1.status=1";
        return $this->_db->getAllRecords($sql);
    }

    public

    function getCustomerDefaultCollectionAddress($customer_id){
        $sql = "SELECT ABT.address_line1 AS street1, ABT.company_name AS business_name, ABT.city AS city, ABT.postcode AS zip, ABT.country AS country_name FROM " . DB_PREFIX . "address_book AS ABT INNER JOIN " . DB_PREFIX . "user_address AS UAT on ABT.id=UAT.address_id WHERE UAT.user_id='$customer_id' AND UAT.default_address='Y'";
        return $this->_db->getRowRecord($sql);
    }

    public

    function findPriceNextVersionNo($load_identity){
        $record = $this->_db->getRowRecord("SELECT `price_version` + 1 AS version_no FROM " . DB_PREFIX . "shipment_service WHERE `load_identity` = '$load_identity'");
        if(!$record)
            return 1;
        else
            return $record['version_no'];
    }

    public

    function saveAddress($data){
        $address_id = $this->_db->save("address_book", $data);
        return $address_id;
    }

    public

    function saveShipment($data){
        $shipment_id = $this->_db->save("shipment", $data);
        return $shipment_id;
    }

    public

    function saveParcel($data){
        $parcel_id = $this->_db->save("shipments_parcel", $data);
        return $parcel_id;
    }

    public

    function saveShipmentService($data){
        $id = $this->_db->save("shipment_service", $data);
        return $id;
    }

    public

    function saveShipmentPrice($data){
        $id = $this->_db->save("shipment_price", $data);
        return $id;
    }

    public

    function saveShipmentAttribute($data){
        $id = $this->_db->save("shipment_attributes", $data);
        return $id;
    }

    public

    function saveShipmentCollection($data){
        $id = $this->_db->save("shipment_collection", $data);
        return $id;
    }
}
?>