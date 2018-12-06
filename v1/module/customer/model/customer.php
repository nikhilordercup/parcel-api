<?php
class Customer_Model
{

    public function __construct()
    {
        $this->db = new DbHandler();
    }
    public static function getInstanse()
    {
        return new Customer_Model();
    }
    public function addContent($table_name, $data)
    {
        return $this->db->save($table_name, $data);
    }
    public function editContent($table_name, $data, $condition)
    {
        return $this->db->update($table_name, $data, $condition);
    }
    public function deleteContent($sql)
    {
        return $this->db->query($sql);
    }
    public function getAffectedRows()
    {
        return $this->db->getAffectedRows();
    }

    public function startTransaction()
    {
        $this->db->startTransaction();
    }

    public function commitTransaction()
    {
        $this->db->commitTransaction();
    }
    public function rollBackTransaction()
    {
        $this->db->rollBackTransaction();
    }

    public function getAllCustomerData()
    {
        $record  = array();
        $sqldata = 't1.id,name,email,phone,address_1,address_2,city,postcode,CI.ccf,t1.status';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "users AS t1
             INNER JOIN " . DB_PREFIX . "customer_info AS CI  ON CI.user_id=t1.id WHERE user_level = 5";
        $record  = $this->db->getAllRecords($sql);
        return $record;
    }

    public function getCustomerDataByCompanyId($company_id)
    {
        $record  = array();
        $sqldata = 't1.id,name,email,phone,address_1,address_2,city,postcode,t1.access_token,CI.ccf,t1.status,CI.accountnumber,CI.available_credit as amount,CI.creditlimit as creditlimit,CI.invoicecycle,CI.customer_type';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "users AS t1
             INNER JOIN " . DB_PREFIX . "company_users AS t2  ON t2.user_id=t1.id
             INNER JOIN " . DB_PREFIX . "customer_info AS CI ON CI.user_id=t1.id
             WHERE t2.company_id  = '" . $company_id . "'
             AND t1.user_level  = '5'";
        $record  = $this->db->getAllRecords($sql);
        return $record;
    }
    public function getAssignedShipmentDataByTicket($componyId, $whareHouseId, $routeId, $ticket)
    {
        $record  = array();
        $sqldata = 'CA.instaDispatch_docketNumber as docket_no,CA.shipment_assigned_service_date as service_date,
                CA.instaDispatch_loadGroupTypeCode as shipment_type,CA.current_status,CA.instaDispatch_loadIdentity as reference_no,CA.shipment_total_attempt as attempt,
                CA.shipment_assigned_service_time as service_time,CA.shipment_total_weight as weight,CA.shipment_ticket as shipment_ticket,
                CA.shipment_service_type as service_type,CA.is_receivedinwarehouse as in_warehouse,CA.shipment_postcode as postcode';

        $sql    = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "driver_shipment AS R1
             LEFT JOIN " . DB_PREFIX . "shipment AS CA  ON R1.shipment_ticket = CA.shipment_ticket
             WHERE R1.shipment_route_id  = '" . $routeId . "'
             AND CA.warehouse_id  = '" . $whareHouseId . "'
             AND CA.company_id  = '" . $componyId . "'
             AND CA.shipment_ticket  = '" . $ticket . "'
             AND (R1.shipment_accepted  = 'Pending' OR R1.shipment_accepted  = 'YES')
             AND (CA.current_status  = 'O' OR CA.current_status  = 'D' OR CA.current_status  = 'Ca'  )";
        $record = $this->db->getAllRecords($sql);
        return $record;
    }



    public function getAllCouriersofCustomer($componyId, $customerId)
    {
        $record  = array();
        $sqldata = 't1.id AS id,t1.courier_id AS courier_id,t1.status, t1.account_number,t1.company_ccf_value as ccf,
                t1.company_surcharge_value as surcharge,t2.name,t2.code,t2.icon, t1.is_internal AS internal';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "courier_vs_company AS t1
             INNER JOIN " . DB_PREFIX . "courier AS t2 ON t1.courier_id = t2.id
             WHERE t1.company_id  = '" . $componyId . "' AND t1.status = '1'";
        $record  = $this->db->getAllRecords($sql);
        return $record;
    }
    public function getAllCouriersofCompany($componyId, $customerId, $courier_id, $company_courier_account_id)
    {
        $record  = array();
        $sqldata = 't1.*';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "courier_vs_company_vs_customer AS t1
              WHERE t1.company_id  = '" . $componyId . "'
              AND   t1.courier_id  = '" . $courier_id . "'
              AND   t1.customer_id  = '" . $customerId . "'
              AND   t1.company_courier_account_id  = '" . $company_courier_account_id . "'";	  
        $record  = $this->db->getRowRecord($sql);
        return $record;
    }
    public function checkDataExistFromCustomerAccount($company_id, $account_number, $customer_id, $courier_id)
    {
        $record  = array();
        $sqldata = 'count(1) as exist';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "courier_vs_company_vs_customer AS t1
             WHERE t1.customer_id  = '" . $customer_id . "'
             AND t1.courier_id  = '" . $courier_id . "'
             AND t1.account_number  = '" . $account_number . "'
             AND t1.company_id  = '" . $company_id . "'";
        $record  = $this->db->getOneRecord($sql);
        return $record['exist'];
    }

    public function getAllCouriersofCustomerAccount($componyId, $customerId)
    {
        $record  = array();
        $sqldata = 't1.id AS id, t1.account_number,t1.company_ccf_value as ccf,
                t1.company_surcharge_value as surcharge,t2.name,t2.code,t2.icon,t3.customer_ccf_value as customer_ccf,
                t3.customer_surcharge_value as customer_surcharge,t3.status as customer_status';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "courier_vs_company AS t1
             INNER JOIN " . DB_PREFIX . "courier AS t2 ON t1.courier_id = t2.id
             INNER JOIN " . DB_PREFIX . "courier_vs_company_vs_customer AS t3 ON (t3.account_number = t1.account_number AND t3.courier_id = t1.courier_id AND t3.company_id = t1.company_id )
             WHERE t1.company_id  = '" . $componyId . "'
             AND t3.customer_id  = '" . $customerId . "'
             AND t1.status  = '1'
             AND t3.status  = '1'";
        $record  = $this->db->getAllRecords($sql);
        return $record;
    }
    /* public function getAllCourierServicesForCustomer($company_id){
    $record = array();
    $sqldata ='L.id,L.service_id,L.courier_id, A.service_name,A.service_code,A.service_icon,A.service_description,C.name as courier_name,C.code as courier_code,L.company_service_ccf as ccf,
    L.company_service_code as custom_service_code,L.company_service_name as custom_service_name,L.status,B.account_number as account_number';
    $sql = "SELECT ".$sqldata." FROM " . DB_PREFIX."courier_vs_services_vs_company as L
    INNER JOIN ".DB_PREFIX."courier_vs_services AS A ON L.service_id = A.id
    INNER JOIN ".DB_PREFIX."courier_vs_company AS B ON B.courier_id = A.courier_id  AND B.company_id = ".$company_id."
    INNER JOIN ".DB_PREFIX."courier as C on C.id = A.courier_id
    WHERE L.company_id = ".$company_id."
    AND C.status = 1 AND B.status = 1 AND A.status = 1 AND  L.status = 1";
    $record = $this->db->getAllRecords($sql);
    return $record;
    } */

    public function getAllCourierServicesForCustomer($company_id)
    {
        $result = array();
        $sql    = "SELECT CSCT.id,CSCT.company_service_ccf AS ccf,CSCT.company_ccf_operator AS ccf_operator,CSCT.company_service_code AS custom_service_code,CSCT.company_service_name AS custom_service_name,CSCT.status,CSCT.service_id AS service_id, CST.service_name,CST.service_code,CST.service_icon,CST.service_description,CT.name as courier_name,CT.code as courier_code,CSCT.courier_id";
        $sql .= " FROM " . DB_PREFIX . "courier_vs_services_vs_company as CSCT";
        $sql .= " INNER JOIN " . DB_PREFIX . "courier_vs_services AS CST ON CSCT.service_id = CST.id";
        $sql .= " INNER JOIN " . DB_PREFIX . "courier AS CT ON CT.id = CST.courier_id";
        $sql .= " WHERE CSCT.company_id=$company_id AND CSCT.status=1";
        $data = $this->db->getAllRecords($sql);

        foreach ($data as $item) {
            $accountNo              = $this->findServiceAccountByServiceAndCourierId($item["service_id"], $item["courier_id"]);
            $key                    = $item["service_name"] . "__SEPARATOR__" . $item["service_code"] . "__SEPARATOR__" . $accountNo;
            $item["account_number"] = $accountNo;
            $result[$key]           = $item;
        }

        return $result;
    }

    public function findServiceAccountByServiceAndCourierId($service_id, $courier_id)
    {
        $sql = "SELECT CCT.account_number AS account_number";
        $sql .= " FROM `" . DB_PREFIX . "courier_vs_company` AS CCT";
        $sql .= " INNER JOIN `" . DB_PREFIX . "courier_vs_services_vs_company` AS CSCT ON CCT.id=CSCT.courier_id";
        $sql .= " WHERE CSCT.service_id='$service_id' AND CSCT.courier_id='$courier_id'";
        $record = $this->db->getRowRecord($sql);
        return $record["account_number"];
    }


    public function checkServiceExistFromCustomerAccount($service_id, $company_service_id, $courier_id, $company_id, $company_customer_id)
    {
        $record  = array();
        $sqldata = 'count(1) as exist';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "company_vs_customer_vs_services AS t1
             WHERE t1.company_customer_id  = '" . $company_customer_id . "'
             AND t1.company_id  = '" . $company_id . "'
             AND t1.courier_id  = '" . $courier_id . "'
             AND t1.company_service_id  = '" . $company_service_id . "'
             AND t1.service_id  = '" . $service_id . "'";
        $record  = $this->db->getOneRecord($sql);
        return $record['exist'];
    }
    public function getAllCourierSurchargeForCustomer($company_id)
    {
        $record  = array();
        $sqldata = 'L.id,L.surcharge_id,L.courier_id, A.surcharge_name,A.surcharge_code,A.surcharge_icon,A.surcharge_description,
                C.name as courier_name,C.code as courier_code,L.company_surcharge_surcharge as surcharge,L.company_surcharge_code as custom_surcharge_code,L.company_surcharge_name as custom_surcharge_name,L.status,B.account_number as account_number';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "courier_vs_surcharge_vs_company as L
              INNER JOIN " . DB_PREFIX . "courier_vs_surcharge AS A ON L.surcharge_id = A.id
              INNER JOIN " . DB_PREFIX . "courier_vs_company AS B ON B.courier_id = A.courier_id AND B.company_id = " . $company_id . "
              INNER JOIN " . DB_PREFIX . "courier as C on C.id = A.courier_id
              WHERE L.company_id = " . $company_id . "
              AND C.status = 1 AND B.status = 1 AND A.status = 1 AND  L.status = 1";
        $record  = $this->db->getAllRecords($sql);
        return $record;
    }


    public function checkSurchargeExistFromCustomerAccount($surcharge_id, $company_surcharge_id, $courier_id, $company_id, $company_customer_id)
    {
        $record  = array();
        $sqldata = 'count(1) as exist';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "company_vs_customer_vs_surcharge AS t1
             WHERE t1.company_customer_id  = '" . $company_customer_id . "'
             AND t1.company_id  = '" . $company_id . "'
             AND t1.courier_id  = '" . $courier_id . "'
             AND t1.company_surcharge_id  = '" . $company_surcharge_id . "'
             AND t1.surcharge_id  = '" . $surcharge_id . "'";
        $record  = $this->db->getOneRecord($sql);
        return $record['exist'];
    }

    public function getCustomerPersonalDetails($company_id, $company_customer_id)
    {
        $record  = array();
        $sqldata = 't1.id,t1.name,t1.email,t1.password,t1.phone,t1.address_1,t1.address_2,t1.city,t1.postcode,t1.state,
                t1.country,t1.status,CI.ccf_operator_service,CI.ccf_operator_surcharge,CI.ccf,CI.surcharge,CI.customer_type,
                CI.accountnumber,CI.vatnumber,CI.creditlimit,CI.available_credit as availablebalance,CI.invoicecycle,tm.name as company_name,CI.ccf_history,CI.charge_from_base,CI.tax_exempt,CI.auto_label_print,CI.round_trip,CI.driving_mode';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "users AS t1
             INNER JOIN " . DB_PREFIX . "company_users AS t2  ON t2.user_id=t1.id
             INNER JOIN " . DB_PREFIX . "customer_info AS CI ON CI.user_id=t1.id
             INNER JOIN " . DB_PREFIX . "users AS tm  ON tm.id= t2.company_id
             WHERE t2.company_id  = '" . $company_id . "'
             AND   t1.id  = '" . $company_customer_id . "'
             AND t1.user_level  = '5'";
        $record  = $this->db->getRowRecord($sql);
        return $record;
    }
    public function getCustomerPickupAddress($company_customer_id)
    {
        $record  = array();
        $sqldata = 't1.*';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "address_book AS t1
             WHERE t1.customer_id  = '" . $company_customer_id . "'
             AND t1.pickup_address  = 'Y'
             AND t1.billing_address  = 'N'";
        $record  = $this->db->getRowRecord($sql);
        return $record;
    }

    public function getCustomerBillingAddress($company_customer_id)
    {
        $record  = array();
        $sqldata = 't1.*';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "address_book AS t1
             WHERE t1.customer_id  = '" . $company_customer_id . "'
             AND t1.billing_address  = 'Y'
             AND t1.pickup_address  = 'N'";
        $record  = $this->db->getRowRecord($sql);
        return $record;
    }

    public function getAllAllowedCourierServicesofCompanyCustomer($service_id, $company_service_id, $courier_id, $company_id, $company_customer_id)
    {
        $record  = array();
        $sqldata = 't1.*';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "company_vs_customer_vs_services AS t1
             WHERE t1.company_customer_id  = '" . $company_customer_id . "'
             AND t1.company_id  = '" . $company_id . "'
             AND t1.courier_id  = '" . $courier_id . "'
             AND t1.company_service_id  = '" . $company_service_id . "'
             AND t1.service_id  = '" . $service_id . "'";
        $record  = $this->db->getRowRecord($sql);
        return $record;

    }

    public function getAllAllowedCourierSurchargeofCompanyCustomer($surcharge_id, $company_surcharge_id, $courier_id, $company_id, $company_customer_id)
    {
        $record  = array();
        $sqldata = 't1.*';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "company_vs_customer_vs_surcharge AS t1
             WHERE t1.company_customer_id  = '" . $company_customer_id . "'
             AND t1.company_id  = '" . $company_id . "'
             AND t1.courier_id  = '" . $courier_id . "'
             AND t1.company_surcharge_id  = '" . $company_surcharge_id . "'
             AND t1.surcharge_id  = '" . $surcharge_id . "'";
        $record  = $this->db->getRowRecord($sql);
        return $record;
    }

    public function checkCustomerEmailExist($company_email)
    {
        $record  = array();
        $sqldata = 'count(1) as exist';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "users AS t1
             WHERE t1.email  = '" . $company_email . "'";
        $record  = $this->db->getOneRecord($sql);
        return $record['exist'];
    }

    public function checkCustomerAccountExist($accountstr)
    {
        $record  = array();
        $sqldata = 'count(1) as exist';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "customer_info AS t1
             WHERE t1.accountnumber  = '" . $accountstr . "'";
        $record  = $this->db->getOneRecord($sql);
        return $record['exist'];
    }

    public function getCustomerCarrierDetails($data)
    {
        $record  = array();
        $sqldata = 't1.id,t1.customer_ccf_value as ccf,t1.customer_surcharge_value as surcharge ,t1.company_ccf_operator_service as ccf_operator_service,t1.company_ccf_operator_surcharge as ccf_operator_surcharge,t1.ccf_history';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "courier_vs_company_vs_customer AS t1
             WHERE t1.courier_id  = '" . $data->data->courier_id . "'
             AND t1.customer_id  = '" . $data->customer_id . "'
             AND t1.account_number  = '" . $data->data->account_number . "'
             AND t1.company_id  = '" . $data->company_id . "'";
        $record  = $this->db->getRowRecord($sql);
        return $record;
    }

    public function getCustomerServiceDetails($data)
    {
        $record  = array();
        $sqldata = 't1.company_customer_id as customer_id,t1.courier_id,t1.company_id,t1.id,t1.customer_ccf,t1.ccf_operator,t1.ccf_history';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "company_vs_customer_vs_services AS t1
             WHERE t1.courier_id  = '" . $data->data->courier_id . "'
             AND t1.company_customer_id  = '" . $data->data->customer_id . "'
             AND t1.service_id  = '" . $data->data->service_id . "'
             AND t1.company_id  = '" . $data->company_id . "'";
        $record  = $this->db->getRowRecord($sql);
        return $record;
    }

    public function getCustomerSurchargeDetails($data)
    {
        $record  = array();
        $sqldata = 't1.company_customer_id as customer_id,t1.courier_id,t1.company_id,t1.id,t1.customer_surcharge,t1.ccf_operator,t1.ccf_history';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "company_vs_customer_vs_surcharge AS t1
             WHERE t1.courier_id  = '" . $data->data->courier_id . "'
             AND t1.company_customer_id  = '" . $data->data->customer_id . "'
             AND t1.surcharge_id  = '" . $data->data->surcharge_id . "'
             AND t1.company_id  = '" . $data->company_id . "'";
        $record  = $this->db->getRowRecord($sql);
        return $record;
    }
    public function getAllUserData()
    {
        $record  = array();
        $sqldata = 't1.id,name,email,phone,address_1,address_2,city,postcode,t1.status';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "users AS t1 WHERE user_level = 6";
        $record  = $this->db->getAllRecords($sql);
        return $record;
    }
    /*public function getUserDataByCustomerId($customerId){
    $record = array();
    $sqldata ='t1.id,t1.parent_id,name,email,phone,t1.access_token,t1.status,t1.address_1,t1.address_2,t1.city,t1.postcode';
    $sql = "SELECT ".$sqldata." FROM " . DB_PREFIX . "users AS t1
    WHERE t1.parent_id  = '".$customerId."'
    AND t1.user_level  = '6' AND t1.status=1";
    $records = $this->db->getAllRecords($sql);
    return $records;
    }*/

    public function getUserDataByCustomerId($customerId, $companyId)
    {
        $record  = array();
        $sqldata = 't1.id,t1.parent_id,name,email,phone,t1.access_token,t1.status,t1.address_1,t1.address_2,t1.city,t1.postcode,t1.is_default,t1.user_level';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "users AS t1
             INNER JOIN " . DB_PREFIX . "company_users AS t2  ON t2.user_id=t1.id
             WHERE t2.company_id = '" . $companyId . "' AND t1.status=1 AND t1.parent_id  = '" . $customerId . "'
             OR t1.id  = '" . $customerId . "'";
        $records = $this->db->getAllRecords($sql);
        return $records;
    }
    public function getCustomerAddressDataByCustomerId($customerId)
    {
        $sql     = "SELECT UAT.warehouse_address AS warehouse_address, ABT.id,ABT.address_line1,ABT.address_line2,ABT.postcode,ABT.city,ABT.state,ABT.country,ABT.address_type,ABT.first_name as name,ABT.company_name FROM " . DB_PREFIX . "address_book as ABT LEFT JOIN `" . DB_PREFIX . "user_address` AS UAT ON ABT.id = UAT.address_id AND UAT.user_id=$customerId where ABT.customer_id = " . $customerId . " AND ABT.status=1";
        $records = $this->db->getAllRecords($sql);
        return $records;
    }


    public function getUserAddressDataByUserId($param)
    {
        $data = $this->db->getRowRecord("SELECT name,email,phone,parent_id FROM " . DB_PREFIX . "users as UT where id = " . $param->user_id . "");
        $data = $this->db->getAllRecords("SELECT ABT.id,ABT.address_line1,ABT.address_line2,ABT.postcode,ABT.city,ABT.state,ABT.country FROM " . DB_PREFIX . "address_book as ABT where ABT.customer_id = " . $data['parent_id'] . "");
        return $data;
    }

    public function getUserDefaultAddressId($param)
    {
        return $this->db->getRowRecord("SELECT address_id FROM " . DB_PREFIX . "user_address as UT where user_id = " . $param->user_id . "");
    }

    public function isDefaultAddressExist($param)
    {
        return $this->db->getRowRecord("select COUNT(*) as address_exist,address_id from " . DB_PREFIX . "user_address where user_id=" . $param->id . "");
    }

    public function addUser($param)
    {
        $param->password             = passwordHash::hash($param->password);
        $param->user_level           = 6;
        $param->register_in_firebase = 1;
        $param->address_1            = "";
        $param->address_2            = "";
        $param->city                 = "";
        $param->state                = "";
        $param->postcode             = "";
        $param->country              = "";
        $data                        = array(
            'parent_id' => $param->customer_id,
            'name' => $param->name,
            'contact_name' => $param->name,
            'phone' => $param->phone,
            'email' => $param->user_email,
            'password' => $param->password,
            'address_1' => $param->address_1,
            'address_2' => $param->address_2,
            'city' => $param->city,
            'postcode' => $param->postcode,
            'state' => $param->state,
            'country' => $param->country,
            'user_level' => $param->user_level,
            'uid' => $param->uid,
            'register_in_firebase' => $param->register_in_firebase
        );
        $user_id                     = $this->db->save("users", $data);

        if ($user_id != NULL) {
            $relationData     = array(
                'company_id' => $param->company_id,
                'warehouse_id' => $param->warehouse_id,
                'user_id' => $user_id
            );
            $column_names     = array(
                'company_id',
                'warehouse_id',
                'user_id'
            );
            $relationTblEntry = $this->db->insertIntoTable($relationData, $column_names, DB_PREFIX . "company_users");

            if ($relationTblEntry != NULL) {
                $data['user_id']          = $user_id;
                $data['action']           = $user_id;
                $data['address']          = $param->address_1 . ' ' . $param->address_2;
                $response["status"]       = "success";
                $response["message"]      = "User created successfully";
                $response["saved_record"] = $data;
            } else {
                $response["status"]  = "error";
                $response["message"] = "Failed to create user. Please try again";
            }
        } else {
            $response["status"]  = "error";
            $response["message"] = "Failed to create user. Please try again";
        }

        return $response;
    }

    public function addAddress($param)
    {
        $libObj      = new Library();
        $postCodeObj = new Postcode();
        $commonObj   = new Common();
        if (!isset($param->address_2))
            $param->address_2 = '';
        $isValidPostcode = $postCodeObj->validate($param->postcode);
        if ($isValidPostcode) {
            $latLngArr = $libObj->get_lat_long_by_postcode($param->postcode);
            if ($latLngArr['latitude'] != '' || $latLngArr['longitude'] != '') {
                $searchString = $commonObj->getAddressBookSearchString($param);
                $insertData   = array(
                    "phone" => $param->phone,
                    "contact_no" => $param->phone,
                    "first_name" => $param->name,
                    "name" => $param->name,
                    "email" => $param->user_email,
                    "contact_email" => $param->user_email,
                    "postcode" => $param->postcode,
                    "address_line1" => $param->address_1,
                    "address_line2" => $param->address_2,
                    "city" => $param->city,
                    "state" => $param->state,
                    "country" => $param->country,
                    "latitude" => $latLngArr['latitude'],
                    "longitude" => $latLngArr['longitude'],
                    "customer_id" => $param->customer_id,
                    "address_type" => $param->address_type,
                    "search_string" => $searchString,
                    "iso_code" => $param->alpha3_code
                );
                $column_names = array(
                    'phone',
                    'contact_no',
                    'first_name',
                    'name',
                    'email',
                    'contact_email',
                    'postcode',
                    'address_line1',
                    'address_line2',
                    'city',
                    'state',
                    'country',
                    'latitude',
                    'longitude',
                    'customer_id',
                    'address_type',
                    'search_string',
                    'iso_code'
                );
                $address_id   = $this->db->insertIntoTable($insertData, $column_names, DB_PREFIX . "address_book");
                if ($address_id != NULL) {
                    $insertData['id']             = $address_id;
                    $insertData['action']         = $address_id;
                    $insertData['address']        = $param->address_1 . ' ' . $param->address_2;
                    $insertData['contact_person'] = $param->name;
                    $response["status"]           = "success";
                    $response["message"]          = "Address added successfully";
                    $response["saved_record"]     = $insertData;

                    $this->db->save("user_address", array(
                        "user_id" => $param->customer_id,
                        "address_id" => $address_id,
                        "default_address" => "0"
                    ));
                }
            } else {
                $response["status"]  = "error";
                $response["message"] = "Failed to add address.Geo position of supplied postcode is not found please supply valid postcode.";
            }
        } else {
            $response["status"]  = "error";
            $response["message"] = "Invalid postcode";
        }
        return $response;
    }
    public function getAddressDataById($param)
    {
        return $this->db->getRowRecord("SELECT ABT.address_line1,ABT.address_line2,ABT.postcode,ABT.city,ABT.state,ABT.country,ABT.first_name as name,ABT.contact_no as phone,ABT.address_type,ABT.contact_email as email,ABT.billing_address,ABT.pickup_address,UAT.warehouse_address,UAT.default_address FROM " . DB_PREFIX . "address_book as ABT LEFT JOIN `" . DB_PREFIX . "user_address` AS UAT ON ABT.id = UAT.address_id AND UAT.user_id=" . $param->customer_id . " where ABT.id = " . $param->address_id . "");
    }

    public function deleteUserById($param)
    {
        return $this->db->updateData("UPDATE " . DB_PREFIX . "users SET status = 0 WHERE id = " . $param->user_id . "");
    }

    public function deleteAddressById($param)
    {
        return $this->db->updateData("UPDATE " . DB_PREFIX . "address_book SET status = 0 WHERE id = " . $param->address_id . "");
    }

    public function editUser($param)
    {
        return $this->db->updateData("UPDATE " . DB_PREFIX . "users SET name='" . $param->name . "',phone='" . $param->phone . "',address_1='" . $param->address_1 . "',address_2='" . $param->address_2 . "',postcode='" . $param->postcode . "',city='" . $param->city . "',state='" . $param->state . "',country='" . $param->country . "' WHERE id = " . $param->id . "");
    }

    public function editAddress($param)
    {
        return $this->db->updateData("UPDATE " . DB_PREFIX . "address_book SET search_string = '$param->search_string', first_name='" . $param->name . "',name='" . $param->name . "',contact_email='" . $param->user_email . "',email='" . $param->user_email . "',address_type='" . $param->address_type . "',contact_no='" . $param->phone . "',phone='" . $param->phone . "',address_line1='" . $param->address_1 . "',address_line2='" . $param->address_2 . "',postcode='" . $param->postcode . "',city='" . $param->city . "',state='" . $param->state . "',country='" . $param->country . "' WHERE id = " . $param->id . "");
    }

    public function setDefaultAddress($param)
    {
        /* if (isset($param->address_list->id))
            $param->address_list->address_id = $param->address_list->id; */

        $addressExist = $this->isExist("user_id='" . $param->userid . "' AND address_id=" . $param->address_list->address_id . "", "user_address");
        if ($addressExist) {
            $defaultExist = $this->isExist("user_id='" . $param->userid . "' AND default_address='Y'", "user_address");
            if (!$defaultExist) {
                return $this->db->updateData("UPDATE " . DB_PREFIX . "user_address SET default_address = 'Y' WHERE user_id = " . $param->userid . " AND address_id=" . $param->address_list->address_id . "");
            } else {
                $removeDefault = $this->db->updateData("UPDATE " . DB_PREFIX . "user_address SET default_address = 'N' WHERE user_id = " . $param->userid . "");
                if ($removeDefault) {
                    return $this->db->updateData("UPDATE " . DB_PREFIX . "user_address SET default_address = 'Y' WHERE user_id = " . $param->userid . " AND address_id=" . $param->address_list->address_id . "");
                }
            }
        } else {
            $defaultExist = $this->isExist("user_id='" . $param->userid . "' AND default_address='Y'", "user_address");
            if ($defaultExist) {
                $removeDefault = $this->db->updateData("UPDATE " . DB_PREFIX . "user_address SET default_address = 'N' WHERE user_id = " . $param->userid . "");
                if ($removeDefault) {
                    $data = array(
                        "user_id" => $param->userid,
                        "address_id" => $param->address_list->address_id,
                        "default_address" => "Y",
                        "warehouse_address" => "Y"
                    );
                    return $this->db->save('user_address', $data);
                }
            } else {
                $data = array(
                    "user_id" => $param->userid,
                    "address_id" => $param->address_list->address_id,
                    "default_address" => "Y",
                    "warehouse_address" => "Y"
                );
                return $this->db->save('user_address', $data);
            }
        }
    }

    public function searchAddressByUserId($param)
    {
        return $this->db->getRowRecord("SELECT  ABT.* FROM  " . DB_PREFIX . "address_book ABT INNER JOIN " . DB_PREFIX . "user_address AS UAT ON ABT.id=UAT.address_id WHERE UAT.user_id='" . $param->user_id . "' AND default_address='Y'");
    }

    public function checkDefaultUserExist( /*$company_id,*/ $customer_id)
    {
        return $this->db->getRowRecord("SELECT UT.id FROM  " . DB_PREFIX . "users as UT WHERE UT.is_default=1 AND (UT.parent_id=" . $customer_id . " OR UT.id=" . $customer_id . ")");
    }

    public function existAddress($condition)
    {
        $record  = array();
        $sqldata = 'count(1) as exist';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "address_book AS t1 WHERE " . $condition . "";
        $record  = $this->db->getOneRecord($sql);
        return $record['exist'];
    }

    public function isExist($condition, $tbl)
    {
        $record  = array();
        $sqldata = 'count(1) as exist';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "$tbl AS t1 WHERE " . $condition . "";
        $record  = $this->db->getOneRecord($sql);
        return $record['exist'];
    }

    public function existDefaultAddressForUser($condition)
    {
        $record  = array();
        $sqldata = 'count(1) as exist';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "user_address AS t1 WHERE " . $condition . "";
        $record  = $this->db->getOneRecord($sql);
        return $record['exist'];
    }

    public function disableCustomerWarehouseAddress($param)
    {
        return $this->db->updateData("UPDATE " . DB_PREFIX . "user_address SET warehouse_address='N' WHERE user_id = " . $param->customer_id);
    }

    public function disableCustomerWarehouseAddressByAddressId($param)
    {
        return $this->db->updateData("UPDATE " . DB_PREFIX . "user_address SET warehouse_address='N' WHERE user_id = " . $param->customer_id . " AND address_id = " . $param->address_id);
    }

    public function enableCustomerWarehouseAddress($param)
    {
        return $this->db->updateData("UPDATE " . DB_PREFIX . "user_address SET warehouse_address='Y' WHERE user_id = '" . $param->customer_id . "' AND address_id = " . $param->address_id);
    }

    public function saveCustomerWarehouseAddress($param)
    {
        $data = array(
            "user_id" => $param->customer_id,
            "address_id" => $param->address_id,
            "warehouse_address" => "Y"
        );
        return $this->db->save('user_address', $data);
    }

    public function searchCustomerAddressByAddressId($param)
    {
        $record = array();
        $sql    = "SELECT COUNT(1) AS exist FROM " . DB_PREFIX . "user_address AS UAT WHERE user_id = $param->customer_id AND address_id = $param->address_id";
        $record = $this->db->getOneRecord($sql);
        return $record['exist'];
    }

    public function disableCompanyInternalCarrier($status, $company_id)
    {

        return $this->db->updateData($sql);
    }

    public function updateCompanyInternalCarrier($status, $company_id, $carrier_id)
    {
        $record = array();

        return $this->db->updateData($sql);
    }

    public function getAddressBySearchStr($param)
    {
        $records           = array();
        $param->search_str = str_replace(' ', '', $param->search_str);
        $sql               = "SELECT UAT.default_address AS warehouse_address, ABT.id,ABT.address_line1,ABT.address_line2,ABT.postcode,ABT.city,ABT.state,ABT.country,ABT.address_type,ABT.name,ABT.company_name FROM " . DB_PREFIX . "address_book as ABT LEFT JOIN `" . DB_PREFIX . "user_address` AS UAT ON ABT.id = UAT.address_id where ABT.customer_id = " . $param->customer_id . " AND ABT.status=1 AND search_string LIKE '%" . $param->search_str . "%'";
        $records           = $this->db->getAllRecords($sql);
        if (count($records) > 0)
            return array(
                "status" => "success",
                "data" => $records
            );
        else
            return array(
                "status" => "error",
                "data" => "no record found"
            );
    }

    public function deleteCarrierData($carrier, $customer_id, $address_id)
    {
        return $this->db->delete("DELETE FROM " . DB_PREFIX . "address_carrier_time WHERE address_id = " . $address_id . " AND customer_id = " . $customer_id . " AND carrier_code = '" . $carrier . "'");
    }

    public function addCarrierData($carrier, $customer_id, $carrier_time, $address_id)
    {
        $data = array(
            "carrier_code" => $carrier,
            "address_id" => $address_id,
            "customer_id" => $customer_id,
            "collection_start_time" => $carrier_time->collection_start_time,
            "collection_end_time" => $carrier_time->collection_end_time,
            "booking_start_time" => $carrier_time->booking_start_time,
            "booking_end_time" => $carrier_time->booking_end_time
        );

        return $this->db->save("address_carrier_time", $data);
    }

    public function getCustomerAllTransactionData($customerId)
    {
        $sql     = "SELECT *  FROM " . DB_PREFIX . "accountbalancehistory as ABT  where ABT.customer_id = " . $customerId . " order by id DESC";
        $records = $this->db->getAllRecords($sql);
        return $records;
    }
    public function getCustomerAllAuthorizationData($customerId)
    {
        $sql     = "SELECT *  FROM " . DB_PREFIX . "customer_tokens as ABT  where ABT.customer_id = " . $customerId . "";
        $records = $this->db->getAllRecords($sql);
        return $records;
    }

    public function editAuthorizationStatus($param)
    {
        return $this->db->updateData("UPDATE " . DB_PREFIX . "customer_tokens SET status = '$param->status' WHERE token_id = " . $param->descid . " AND customer_id = " . $param->customer_id . "");
    }
    public function editAuthorization($param)
    {
        return $this->db->updateData("UPDATE " . DB_PREFIX . "customer_tokens SET title = '$param->title',description = '$param->description',url = '$param->url' WHERE token_id = '$param->descid'");
    }
    public function downloadAccountStatements($customerId, $from, $to, $company_id)
    {
        $from = date('Y-m-d', strtotime($from));
        $to   = date('Y-m-d', strtotime($to));

        $sql     = "SELECT *  FROM " . DB_PREFIX . "accountbalancehistory as ABT
                where ABT.customer_id = " . $customerId . "
                AND  ABT.company_id = " . $company_id . "
                AND  DATE_FORMAT(create_date,'%Y-%m-%d') BETWEEN '$from' AND '$to'
                order by id DESC";
        $records = $this->db->getAllRecords($sql);
        return $records;
    }
    public function getCustomerDetails($customerId)
    {
        $record  = array();
        $sqldata = 'CUS.billing_full_name AS customername,CUS.billing_address_1 AS customeraddress1,
                    CUS.billing_address_2 AS customeraddress2,CUS.billing_postcode AS customerpostcode,
                    CUS.billing_city AS customercity,CUS.billing_country AS customercountry,
                    CUS.billing_phone AS customerphone,CUS.accountnumber AS customeraccount,
                    CUS.vatnumber AS customervat,
                    CUS.billing_state AS customerstate,
                    CURDATE() AS create_statement_date';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "customer_info AS CUS
                where CUS.user_id = '$customerId'";
        $record  = $this->db->getRowRecord($sql);
        return $record;
    }

    public function getCompanyDetails($companyId)
    {
        $record  = array();
        $sqldata = 'C.logo,COM.name AS company_name,COM.address_1 AS company_address1,
                    COM.address_2 AS company_address2,COM.postcode AS company_postcode,
                    COM.city AS company_city,COM.country  AS company_county';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "users AS COM
               LEFT JOIN " . DB_PREFIX . "configuration as C on C.company_id = COM.id
               where  COM.id = '$companyId'";
        $record  = $this->db->getRowRecord($sql);
        return $record;
    }
    public function getjobDetails($shipmentRef)
    {
        $record  = array();
        $sqldata = 'S1.instaDispatch_loadIdentity,S1.shipment_required_service_date,
                    S1.instaDispatch_loadGroupTypeCode,
                    S1.shipment_service_type,
                    S1.icargo_execution_order,
                    S1.shipment_postcode as shipment_postcode,
                    S1.shipment_customer_country AS shipment_customer_country';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment AS S1
                 LEFT JOIN " . DB_PREFIX . "address_book AS ADDR ON ADDR.id = S1.address_id
          WHERE S1.instaDispatch_loadIdentity  = '" . $shipmentRef . "'";
        $record  = $this->db->getAllRecords($sql);
        return $record;
    }

    public function getShipmentDetails($loadidentity, $companyId)
    {
        $record  = array();
        $sqldata = 'S.shipment_id as reference_id,A.load_identity as reference,
                    DATE_FORMAT(S.shipment_create_date,"%Y-%m-%d") AS booking_date,
                    S.shipment_total_item AS items,S.shipment_total_weight AS weight,
                    S.shipment_total_volume AS volume,S.shipment_customer_name AS consignee,
                    S.instaDispatch_customerReference AS customer_booking_reference,
                    A.service_name as service_name,
                    (A.base_price +  A.courier_commission_value)as base_amount,
                    A.surcharges as surcharge_total,A.taxes as tax,A.rate_type as rate_type,
                    A.transit_distance_text as chargable_value,A.total_price as total,A.customer_id,
                    SP.price as fual_surcharge';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment_service as A
                LEFT JOIN " . DB_PREFIX . "shipment as S on S.instaDispatch_loadIdentity = A.load_identity
                LEFT JOIN " . DB_PREFIX . "shipment_price as SP on (SP.load_identity = A.load_identity AND SP.api_key = 'surcharges' AND SP.price_code = 'fual_surcharge')
                WHERE  A.load_identity = '" . $loadidentity . "'
                AND S.company_id = '" . $companyId . "'
                AND S.shipment_service_type = 'P'";
        $record  = $this->db->getRowRecord($sql);
        return $record;
    }

    public function getVoucherDetail($companyId, $voucherRef)
    {
        $record  = array();
        $sqldata = 'A.*,DATE_FORMAT(S.shipment_create_date,"%Y-%m-%d") AS booking_date,
                    S.shipment_total_item AS items,
                    B.service_name as service_name,
                    B.rate_type as rate_type,
                    B.transit_distance_text as chargable_value,
                    S.shipment_id as reference_id';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "vouchers as A
                LEFT JOIN " . DB_PREFIX . "shipment_service as B on B.load_identity = A.shipment_reference
                LEFT JOIN " . DB_PREFIX . "shipment as S on S.instaDispatch_loadIdentity = A.shipment_reference
                WHERE 1 =1
                AND A.voucher_reference = '" . $voucherRef . "'
                AND A.company_id = '" . $companyId . "'
                AND S.shipment_service_type = 'P'
                ORDER BY A.id ";
        $record  = $this->db->getAllRecords($sql);
        return $record;
    }
    public function checkCountryCodeExist($code)
    {
        $record  = array();
        $sqldata = 'count(1) as exist';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "countries AS t1
             WHERE t1.alpha3_code  = '" . $code . "'";
        $record  = $this->db->getOneRecord($sql);
        return $record['exist'];
    }
    public function getAllAccountOfCompany($companyId)
    {
        $record  = array();
        $sqldata = 'A.id as courier_account_id,A.account_number,A.courier_id';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "courier_vs_company as A
                WHERE A.company_id = '" . $companyId . "'
                ORDER BY A.id ";
        $record  = $this->db->getAllRecords($sql);
        return $record;
    }
    public function getAllAccountServices($companyId, $courierAccountId)
    {
        $record  = array();
        $sqldata = 'A.*';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "courier_vs_services_vs_company as A
                WHERE A.company_id = '" . $companyId . "'
                AND A.courier_id = '" . $courierAccountId . "'
                ORDER BY A.id ";
        $record  = $this->db->getAllRecords($sql);
        return $record;
    }
    public function getAllAccountSurcharges($companyId, $courierAccountId)
    {
        $record  = array();
        $sqldata = 'A.*';
        $sql     = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "courier_vs_surcharge_vs_company as A
                WHERE A.company_id = '" . $companyId . "'
                AND A.courier_id = '" . $courierAccountId . "'
                ORDER BY A.id ";
        $record  = $this->db->getAllRecords($sql);
        return $record;
    }

	public function getCompanyConfiguration($companyId){
		$sql     = "SELECT configuration_json FROM " . DB_PREFIX . "configuration AS t1 WHERE t1.company_id = '$companyId'";
		$record  = $this->db->getRowRecord($sql);
		return $record;
	}
	
	public function checkChildAccountData($companyId,$customerId,$courierId,$accountNo){
		$sql = "SELECT COUNT(1) as account_exist, t1.* FROM " . DB_PREFIX . "customer_courier_child_accont AS t1 WHERE t1.company_id = $companyId AND t1.courier_id = $courierId AND t1.customer_id = $customerId AND t1.parent_account_number='$accountNo'";
		$record  = $this->db->getRowRecord($sql);
		return $record;
	}
}
?>
