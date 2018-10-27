<?php
class Booking_Model_Booking

{
    public static $_dbObj = NULL;

    public

    function __construct()
    {
        if (self::$_dbObj == NULL)
        {
            self::$_dbObj = new DbHandler();
        }

        $this->_db = self::$_dbObj;
    }

    /**
     * Start transaction
     */
    public

    function startTransaction()
    {
        $this->_db->startTransaction();
    }

    /**
     * Start transaction
     */
    public

    function commitTransaction()
    {
        $this->_db->commitTransaction();
    }

    /**
     * Start transaction
     */
    public

    function rollBackTransaction()
    {
        $this->_db->rollBackTransaction();
    }

    public

    function getCustomerCarrierServices($customer_id, $carrier_id, $account_number, $flowType)
    {
        $sql = "SELECT CST.service_code, CST.service_name, CSFT.service_id FROM `" . DB_PREFIX . "courier_vs_services_vs_company` AS CSCT INNER JOIN `" . DB_PREFIX . "company_vs_customer_vs_services` AS CCST ON CSCT.service_id=CCST.service_id INNER JOIN `" . DB_PREFIX . "courier_vs_services` AS CST ON CST.id=CSCT.service_id INNER JOIN `" . DB_PREFIX . "courier_vs_company` AS CCT ON CCT.company_id=CCST.company_id AND CCT.account_number='$account_number' INNER JOIN `" . DB_PREFIX . "service_flow_type` AS CSFT ON CST.id=CSFT.service_id WHERE CSCT.status= 1 AND CCST.status=1 AND CST.status = 1 AND CSCT.courier_id='$carrier_id' AND CCST.company_customer_id='$customer_id' AND CCST.courier_id='$carrier_id'  AND CSFT.flow_type='$flowType'  AND CSFT.account_number = '$account_number' AND CST.service_type='NEXTDAY' ORDER BY `CSFT`.`service_id` ASC";
        return $this->_db->getAllRecords($sql);
    }

    public

    function getCarrierInfo($customer_id)
    {
        $sql = "SELECT CCT.id AS carrier_id, CCT.name, CCT.icon, CCT.description, CCT.code FROM " . DB_PREFIX . "courier_vs_company_vs_customer AS CCCT INNER JOIN " . DB_PREFIX . "courier AS CCT ON CCT.id = CCCT.courier_id WHERE CCCT.customer_id='$customer_id'  AND CCCT.status=1";
        return $this->_db->getAllRecords($sql);
    }

    public

    function checkCustomerAccountStatus($customer_id)
    {
        $sql = "SELECT status FROM " . DB_PREFIX . "users WHERE id = '$customer_id' AND status=1";
        return $this->_db->getRowRecord($sql);
    }

    public

    function getAddressBySearchStringAndCustomerId($customer_id, $search_string)
    {
        $record = $this->_db->getRowRecord("SELECT version_id AS version_id,id as address_id FROM " . DB_PREFIX . "address_book WHERE `customer_id` = '$customer_id' AND `search_string` LIKE '$search_string'");
        return $record;
    }

    private

    function _testShipmentTicket($shipment_ticket)
    {
        $record = $this->_db->getOneRecord("SELECT COUNT(1) AS exist FROM " . DB_PREFIX . "shipment WHERE shipment_ticket = '" . $shipment_ticket . "'");
        if ($record['exist'] > 0) return true;
        else return false;
    }

    private
    function _testParcelTicket($parcel_ticket)
    {
        $record = $this->_db->getOneRecord("SELECT COUNT(1) AS exist FROM " . DB_PREFIX . "shipments_parcel WHERE parcel_ticket = '" . $parcel_ticket . "'");
        if ($record['exist'] > 0) return true;
        else return false;
    }

    public

    function generateTicketNo($company_id)
    {
        $record = $this->_db->getRowRecord("SELECT (shipment_end_number + 1) AS shipment_ticket_no, shipment_ticket_prefix AS shipment_ticket_prefix FROM " . DB_PREFIX . "configuration WHERE company_id = " . $company_id);
        if ($record)
        {
            $ticket_number = $record['shipment_ticket_prefix'] . str_pad($record['shipment_ticket_no'], 6, 0, STR_PAD_LEFT);

            // $check_digit = $this->generateCheckDigit($ticket_number);
            // $ticket_number = "$ticket_number$check_digit";

            $this->_db->updateData("UPDATE " . DB_PREFIX . "configuration SET shipment_end_number = shipment_end_number + 1 WHERE company_id = " . $company_id);
            if ($this->_testShipmentTicket($ticket_number))
            {
                $this->generateTicketNo($company_id);
            }

            return $ticket_number;
        }
        else
        {
            return false;
        }
    }

    public

    function generateParcelTicketNumber($company_id)
    {
        $record = $this->_db->getRowRecord("SELECT (parcel_end_number + 1) AS ticket_no, shipment_ticket_prefix AS shipment_ticket_prefix FROM " . DB_PREFIX . "configuration WHERE company_id = " . $company_id);
        $ticket_number = $record['shipment_ticket_prefix'] . str_pad($record['ticket_no'], 6, 0, STR_PAD_LEFT);

        // update ticket number

        $this->_db->updateData("UPDATE " . DB_PREFIX . "configuration SET parcel_end_number = parcel_end_number + 1 WHERE company_id = " . $company_id);
        if ($this->_testParcelTicket($ticket_number))
        {
            $this->generateParcelTicketNumber();
        }

        return $ticket_number;
    }

    public

    function getCompanyCode($company_id)
    {
        $record = $this->_db->getRowRecord("SELECT code AS code FROM " . DB_PREFIX . "user_code WHERE id = '$company_id'");
        return $record['code'];
    }

    public

    function getCustomerWarehouseIdByCustomerId($company_id, $customer_id)
    {
        $record = $this->_db->getRowRecord("SELECT CUT.warehouse_id FROM " . DB_PREFIX . "company_users AS CUT INNER JOIN " . DB_PREFIX . "users AS UT ON UT.id = CUT.user_id WHERE CUT.user_id='$customer_id' AND CUT.company_id='$company_id'");
        return $record;
    }

    public

    function getDefaultCollectionAddress($customer_id)
    {
        $sql = "SELECT ABT.id AS address_id,ABT.address_line1 AS street1, ABT.company_name AS business_name, ABT.city AS city, ABT.postcode AS zip, ABT.country AS country_name FROM " . DB_PREFIX . "address_book AS ABT INNER JOIN " . DB_PREFIX . "user_address AS UAT ON ABT.id=UAT.address_id WHERE UAT.user_id='$customer_id' AND UAT.default_address='Y' AND ABT.customer_id='$customer_id'";
        return $this->_db->getRowRecord($sql);
    }

    public

    function getInternalCarrier($company_id)
    {
        $sql = "SELECT CT.description, CT.icon AS icon, CT.id AS carrier_id, CT.name, CT.code AS carrier_code, CCT.account_number, CCT.pickup, CCT.pickup_surcharge, CCT.collection_start_at, CCT.collection_end_at, CCT.is_internal AS internal, CCT.username, CCT.password FROM " . DB_PREFIX . "courier_vs_company AS CCT INNER JOIN " . DB_PREFIX . "courier AS CT ON CCT.courier_id=CT.id WHERE CCT.company_id='$company_id' AND CCT.status='1' AND CCT.is_internal=1";
        return $this->_db->getRowRecord($sql);
    }

    public

    function getCourierOperationalArea($company_id)
    {
        $sql = "SELECT t1.id, t1.route_id, t1.postcode FROM " . DB_PREFIX . "route_postcode AS t1 WHERE t1.company_id = '$company_id' AND t1.status=1";
        return $this->_db->getAllRecords($sql);
    }

    public

    function getCustomerDefaultCollectionAddress($customer_id)
    {
        $sql = "SELECT ABT.address_line1 AS street1, ABT.company_name AS business_name, ABT.city AS city, ABT.postcode AS zip, ABT.country AS country_name FROM " . DB_PREFIX . "address_book AS ABT INNER JOIN " . DB_PREFIX . "user_address AS UAT on ABT.id=UAT.address_id WHERE UAT.user_id='$customer_id' AND UAT.default_address='Y'";
        return $this->_db->getRowRecord($sql);
    }

    public

    function findPriceNextVersionNo($load_identity)
    {
        $record = $this->_db->getRowRecord("SELECT `price_version` + 1 AS version_no FROM " . DB_PREFIX . "shipment_service WHERE `load_identity` = '$load_identity'");
        if (!$record) return 1;
        else return $record['version_no'];
    }

    public

    function saveAddress($data)
    {
        $address_id = $this->_db->save("address_book", $data);
        return $address_id;
    }

    public

    function saveShipment($data)
    {
        $shipment_id = $this->_db->save("shipment", $data);
        return $shipment_id;
    }

    public

    function saveParcel($data)
    {
        $parcel_id = $this->_db->save("shipments_parcel", $data);
        return $parcel_id;
    }

    public

    function saveShipmentService($data)
    {
        $id = $this->_db->save("shipment_service", $data);
        return $id;
    }

    public

    function saveItemService($data)
    {
        $id = $this->_db->save("shipment_items", $data);
        return $id;
    }

    public

    function saveShipmentPrice($data)
    {
        $id = $this->_db->save("shipment_price", $data);
        return $id;
    }

    public

    function saveShipmentAttribute($data)
    {
        $id = $this->_db->save("shipment_attributes", $data);
        return $id;
    }

    public

    function saveShipmentCollection($data)
    {
        $id = $this->_db->save("shipment_collection", $data);
        return $id;
    }

    public

    function getParcelDimesionByShipmentId($shipment_id)
    {
        $sql = "SELECT parcel_weight, parcel_height, parcel_length, parcel_width FROM " . DB_PREFIX . "shipments_parcel AS SPT WHERE SPT.shipment_id='$shipment_id'";
        return $this->_db->getAllRecords($sql);
    }

    public

    function saveShipmentDimension($data, $shipment_id)
    {
        return $this->_db->update("shipment", $data, "shipment_id='$shipment_id'");
    }

    public

    function isCarrierInternal($carrier_code, $company_id)
    {

        // NEED TO FIX currently not using

        return $this->_db->getRowRecord("SELECT is_internal FROM " . DB_PREFIX . "courier_vs_company AS CCT INNER JOIN " . DB_PREFIX . "courier AS CT ON CT.id=CCT.courier_id WHERE CT.code='$carrier_code' AND CCT.company_id='$company_id'");
    }

    public

    function getCompanyCarrier($company_id)
    {
        $sql = "SELECT CT.description as description, CT.icon AS icon, CCT.courier_id AS carrier_id, CT.name, CT.code AS carrier_code, CCT.username AS username, CCT.password AS password, CCT.is_internal AS internal, pickup AS pickup, pickup_surcharge AS pickup_surcharge, collection_start_at AS collection_start_at, collection_end_at AS collection_end_at, CCT.id AS account_id FROM " . DB_PREFIX . "courier_vs_company AS CCT INNER JOIN " . DB_PREFIX . "courier AS CT ON CCT.courier_id = CT.id WHERE CT.status=1 AND CCT.status=1 AND CCT.company_id='$company_id' AND CCT.is_internal='0'";
        return $this->_db->getAllRecords($sql);
    }

    public

    function getCustomerCarrierAccountByAccountId($company_id, $customer_id, $carrier_acccount)
    {
        $sql = "SELECT CCCT.company_courier_account_id AS account_id, CCCT.courier_id AS carrier_id, CCCT.account_number FROM  `icargo_courier_vs_company_vs_customer` AS CCCT WHERE CCCT.customer_id='$customer_id' AND CCCT.company_id = '$company_id' AND CCCT.status = 1 AND CCCT.company_courier_account_id IN($carrier_acccount)";
        return $this->_db->getAllRecords($sql);
    }

    public

    function getShipmentDataByLoadIdentity($loadIdentity)
    {

        $sql = "SELECT ST.*, IC.alpha2_code FROM " . DB_PREFIX . "shipment AS ST LEFT JOIN " . DB_PREFIX . "countries AS IC ON IC.alpha3_code = ST.shipment_country_code WHERE ST.instaDispatch_loadIdentity='$loadIdentity' AND instaDispatch_loadGroupTypeCode='NEXT'";
        return $this->_db->getAllRecords($sql);
    }

    public

    function getDeliveryShipmentData($loadIdentity)
    {
        $sql = "SELECT * FROM  " . DB_PREFIX . "shipment AS ST WHERE ST.instaDispatch_loadIdentity='$loadIdentity' AND instaDispatch_loadGroupTypeCode='NEXT' AND shipment_service_type='D'";
        return $this->_db->getRowRecord($sql);
    }

    public

    function getPackageDataByLoadIdentity($loadIdentity)
    {
        $sql = "SELECT * FROM  " . DB_PREFIX . "shipments_parcel AS PT WHERE PT.instaDispatch_loadIdentity='$loadIdentity' AND parcel_type='D'";
        return $this->_db->getAllRecords($sql);
    }

    public

    function getCredentialDataByLoadIdentity($carrierAccountNumber, $loadIdentity)
    {

        $sql = "SELECT username,password,token,authentication_token,authentication_token_created_at FROM " . DB_PREFIX . "courier_vs_company AS CCT WHERE CCT.account_number='$carrierAccountNumber'";
        $credentailData = $this->_db->getRowRecord($sql);
        return $credentailData;
    }

    public

    function getServiceDataByLoadIdentity($loadIdentity)
    {
        $sql = "SELECT SST.service_name,SST.currency,CST.service_code FROM " . DB_PREFIX . "shipment_service AS SST INNER JOIN " . DB_PREFIX . "courier_vs_services AS CST ON SST.service_name = CST.service_name WHERE SST.load_identity='$loadIdentity'";
        return $this->_db->getRowRecord($sql);
    }

    public

    function getLabelByLoadIdentity($loadIdentity)
    {
        $sql = "SELECT SST.label_file_pdf, SST.label_json, SST.load_identity, CR.code as carrier_code FROM " . DB_PREFIX . "shipment_service AS SST INNER JOIN icargo_courier CR ON SST.carrier=CR.id WHERE SST.load_identity IN('$loadIdentity')";
        return $this->_db->getAllRecords($sql);
    }

    public

    function saveLabelDataByLoadIdentity($labelArr, $loadIdentity)
    {
        return $this->_db->update("shipment_service", $labelArr, "load_identity='" . $loadIdentity . "'");
    }

    public

    function getBookingStatusByLoadidentity($loadIdentity)
    {
        $sql = "SELECT status as booking_status FROM " . DB_PREFIX . "shipment_service AS SST WHERE SST.load_identity='$loadIdentity'";
        return $this->_db->getRowRecord($sql);
    }

    public

    function deleteBookingDataByLoadIdentity($loadIdentity)
    {
        $service_id = $this->_db->getRowRecord("SELECT id FROM " . DB_PREFIX . "shipment_service AS SST WHERE SST.load_identity='$loadIdentity'");

        // delete from shipment table

        $deleteShipment = $this->_db->delete("DELETE FROM " . DB_PREFIX . "shipment WHERE instaDispatch_loadIdentity='$loadIdentity'");
        if ($deleteShipment)
        {

            // delete from shipment parcel

            $deleteShipmentParcel = $this->_db->delete("DELETE FROM " . DB_PREFIX . "shipments_parcel WHERE instaDispatch_loadIdentity='$loadIdentity'");

            // delete from shipment price table

            $deleteShipmentPrice = $this->_db->delete("DELETE FROM " . DB_PREFIX . "shipment_price WHERE load_identity='$loadIdentity'");

            // delete from shipment attribute table

            $deleteShipmentAttributes = $this->_db->delete("DELETE FROM " . DB_PREFIX . "shipment_attributes WHERE load_identity='$loadIdentity'");

            // delete from shipment collection table

            $deleteShipmentCollection = $this->_db->delete("DELETE FROM " . DB_PREFIX . "shipment_collection WHERE service_id=" . $service_id['id'] . "");

            // delete from shipment service table

            $deleteShipmentService = $this->_db->delete("DELETE FROM " . DB_PREFIX . "shipment_service WHERE load_identity='$loadIdentity'");
            return array(
                "status" => "success",
                "message" => "shipment deleted successfully"
            );
        }
        else
        {
            return array(
                "status" => "error",
                "message" => "error while deleting shipment"
            );
        }
    }

    public

    function updateBookingStatus($statusArr, $loadIdentity)
    {
        return $this->_db->update("shipment_service", $statusArr, "load_identity='" . $loadIdentity . "'");
    }

    public

    function getAutoPrintStatusByCustomerId($customerId)
    {
        $sql = "SELECT auto_label_print as auto_label_print FROM " . DB_PREFIX . "customer_info AS CI WHERE CI.user_id=" . $customerId . "";
        return $this->_db->getRowRecord($sql);
    }

    // get collection start time by carrier code,address_id and customer_id

    /*public

    function getCollectionStartTime($addressId, $customerId, $carrierCode)
    {
        $sql = "SELECT collection_start_time as collection_start_time,collection_end_time as collection_end_time FROM " . DB_PREFIX . "address_carrier_time AS ACT WHERE ACT.address_id=" . $addressId . " AND ACT.customer_id=" . $customerId . " AND ACT.carrier_code='" . $carrierCode . "'";
        $record = $this->_db->getRowRecord($sql);
        return $record;
    }*/

    public

    function checkCarrierCollectionStartTime($companyId, $carrierId, $accountNo, $userName, $password, $collectionTime)
    {
        $sql = "SELECT COUNT(1) AS num_count FROM " . DB_PREFIX . "courier_vs_company AS CCT WHERE CCT.company_id='$companyId' AND CCT.account_number='$accountNo' AND CCT.username='$userName' AND CCT.password='$password' AND CCT.courier_id='$carrierId' AND ('$collectionTime' >= `collection_start_at` AND '$collectionTime' <= `collection_end_at`)";
        $record = $this->_db->getRowRecord($sql);
        return $record;
    }

    public

    function checkCollectionTime($addressId, $customerId, $carrierCode, $collectionTime)
    {
        $sql = "SELECT collection_start_time AS collection_start_time, collection_end_time AS collection_end_time FROM " . DB_PREFIX . "address_carrier_time AS ACT WHERE ACT.address_id=" . $addressId . " AND ACT.customer_id=" . $customerId . " AND ACT.carrier_code='" . $carrierCode . "' AND '$collectionTime' >= `collection_start_time` AND '$collectionTime' <= `collection_end_time`";
        $record = $this->_db->getRowRecord($sql);
        return $record;
    }

    public

    function findCarrierCollectionStartTime($companyId, $carrierId, $accountNo, $userName, $password)
    {
        $sql = "SELECT collection_start_at AS collection_start_time, collection_end_at AS collection_end_time FROM " . DB_PREFIX . "courier_vs_company AS CCT WHERE CCT.company_id='$companyId' AND CCT.account_number='$accountNo' AND CCT.username='$userName' AND CCT.password='$password' AND CCT.courier_id='$carrierId'";
        $record = $this->_db->getRowRecord($sql);
        return $record;
    }

    public

    function checkPackageSpecificService($company_id, $package_code, $carrier_code)
    {
        $sql = "SELECT PST.service_code as service_code,CST.service_name as service_name FROM " . DB_PREFIX . "package_service AS PST INNER JOIN " . DB_PREFIX . "courier_vs_services AS CST ON PST.service_code = CST.service_code WHERE PST.package_code='$package_code' AND PST.carrier_code='$carrier_code' AND PST.company_id=$company_id";
        return $this->_db->getAllRecords($sql);
    }

    public

    function getCustomerInfo($user_id)
    {
        return $this->_db->getRowRecord("SELECT quote_expiry_days FROM " . DB_PREFIX . "customer_info AS CIT WHERE CIT.user_id='$user_id'");
    }

    public

    function getUserInfo($user_id)
    {
        return $this->_db->getRowRecord("SELECT email FROM " . DB_PREFIX . "users AS UT WHERE UT.id='$user_id'");
    }

    public

    function checkExistingPickupForShipment($customerId, $carrierId, $userId, $collectionDate, $searchString, $companyName, $contactName, $loadIdentity)
    {
        $sql = "SELECT IP.* FROM " . DB_PREFIX . "pickups AS IP WHERE IP.user_id='$userId' AND IP.customer_id='$customerId' AND carrier_id='$carrierId' AND pickup_date='$collectionDate' AND search_string='$searchString'";
        $pickUps = $this->_db->getAllRecords($sql);
        $fullMatch = false;
        $pickupId = 0;
        $allPickup = array();
        $results = array();
        if (count($pickUps))
        {
            foreach($pickUps as $pickUp)
            {
                if (strtolower($pickUp['name']) == strtolower($contactName) && strtolower($pickUp['company_name']) == strtolower($companyName))
                {
                    (!$fullMatch) ? ($allPickup = array()) : '';
                    (!$pickupId) ? ($pickupId = $pickUp['id']) : '';
                    $results = array(
                        'full_match' => '1'
                    );
                    $allPickup[] = $pickUp;
                    $fullMatch = true;
                }
                else
                if (!$fullMatch)
                {
                    $results = array(
                        'full_match' => '0'
                    );
                    $allPickup[] = $pickUp;
                }
            }

            $results['data'] = $allPickup;
            $results['search'] = 1;
            $results['shipment_id'] = $loadIdentity;
            if (count($allPickup) == 1 && $pickupId)
            {
                $results['assign'] = 1;
                $results['data'] = array();

                // $loadIdentity of collection (P - pickup)
                $this->updateShipment($pickupId, $loadIdentity);
            }
            else
            {
                $results['assign'] = 0;
            }
        }
        else
        {
            $results = array(
                'search' => 0,
                'full_match' => 0,
                'data' => $allPickup,
                'assign' => 0,
                'shipment_id' => $loadIdentity
            );
        }

        return $results;
    }

    public

    function updateShipment($pickupId, $loadIdentity)
    {
        $flag = $this->_db->updateData("UPDATE " . DB_PREFIX . "shipment SET pickup_id = '$pickupId' WHERE shipment_ticket = '$loadIdentity'");
        return $flag;
    }

    public

    function getCompanyInfo($companyId)
    {
        return $this->_db->getRowRecord("SELECT IU.* FROM " . DB_PREFIX . "users AS IU WHERE IU.id='$companyId'");
    }

    public

    function saveInfoReceived($load_identity)
    {
        $id = $this->_db->save("shipment_tracking", array(
            "load_identity" => $load_identity,
            "code" => "INFO_RECEIVED"
        ));
        return $id;
    }

    public

    function getCustomerAccountBalence($customer_id)
    {
        $sql = "SELECT available_credit FROM " . DB_PREFIX . "customer_info WHERE user_id = '$customer_id'";
        return $this->_db->getRowRecord($sql);
    }

    function getBookedShipmentsPrice($priceServiceid, $customerId)
    {
        $sql = "SELECT S.grand_total,C.customer_type,C.available_credit FROM " . DB_PREFIX . "shipment_service as S
                INNER JOIN " . DB_PREFIX . "customer_info as C on S.customer_id = C.user_id
                WHERE S.id = '$priceServiceid' AND C.user_id = '$customerId'";
        return $this->_db->getRowRecord($sql);
    }

    public

    function saveAccountHistory($data)
    {
        $his_id = $this->_db->save("accountbalancehistory", $data);
        return $his_id;
    }

    public

    function editAccountBalance($data, $condition)
    {
        $status = $this->_db->update("customer_info", $data, $condition);
        return $status;
    }

    public

    function getCarrierCode($carrier_id)
    {
        $sql = "SELECT code AS carrier_code  FROM " . DB_PREFIX . "courier WHERE id = '$carrier_id'";
        return $this->_db->getRowRecord($sql);
    }

    public

    function getBookedShipmentsCustomerInfo($customer_id)
    {
        $sql = "SELECT customer_type FROM " . DB_PREFIX . "customer_info WHERE user_id = '$customer_id'";
        return $this->_db->getRowRecord($sql);
    }
}

?>