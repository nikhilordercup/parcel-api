<?php
class restservices_Model
    {
    public static $_modelObj = NULL;
    public static $_db = NULL;

    public

    function __construct(){
        if (self::$_db == NULL){
            self::$_db = new DbHandler();
            }
        $this->db = self::$_db;
        }

    public static

    function getInstanse()
        {
        if (self::$_modelObj == NULL)
            {
            self::$_modelObj = new restservices_Model();
            }

        return self::$_modelObj;
        }

    public

    function addContent($table_name, $data)
        {
        return $this->db->save($table_name, $data);
        }

    public

    function editContent($table_name, $data, $condition)
        {
        return $this->db->update($table_name, $data, $condition);
        }

    public

    function deleteContent($sql)
        {
        return $this->db->delete($sql);
        }

    public

    function getAffectedRows()
        {
        return $this->db->getAffectedRows();
        }

   public function getTokenofCustomer($tokenData){
     $record = array();
     $sqldata = 'S.*,UTT.parent_id,UT.email,UT.access_token,UT.id as company_id,CW.warehouse_id,WD.latitude as warehouse_latitude ,WD.longitude as warehouse_longitude';
     $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "customer_tokens AS S
             LEFT JOIN " . DB_PREFIX . "users AS UTT ON UTT.id = S.customer_id
             LEFT JOIN " . DB_PREFIX . "users AS UT ON UT.id = UTT.parent_id 
             LEFT JOIN " . DB_PREFIX . "company_warehouse AS CW ON CW.company_id = S.customer_id
             LEFT JOIN " . DB_PREFIX . "warehouse AS WD ON WD.id =  CW.warehouse_id
             WHERE (S.token_id = '$tokenData->identity' AND S.status = '1' AND UTT.status = '1')";
      $record = $this->db->getRowRecord($sql);
      return $record;
    }      
   public function getQuotationData($quoteRef){
     $record = array();
     $sqldata = 'S.*';
     $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "webapi_request_response AS S
             WHERE (S.session_id = '$quoteRef' AND S.request_status = 'NC' AND S.status = '1')";
      $record = $this->db->getRowRecord($sql);
      return $record;
    }
   public function getBookedShipmentsPrice($customerId){
        $sql = "SELECT C.customer_type,C.available_credit FROM " . DB_PREFIX . "customer_info as C
                WHERE  C.user_id = '$customerId'";
        return $this->db->getRowRecord($sql);
    }     
   public function getSamedayReccuringJobs(){
     $record = array();
     $sqldata = 'S.*';
     $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "recurring_jobs AS S WHERE  S.status = true AND load_type = 'SAME' ";
      $record = $this->db->getAllRecords($sql);
      return $record;
    }     
   public function getLoadServiceDetails($loadIdentity){
        $record = array();
        $sqldata = 'A.service_name AS current_status,SER.id as service_id,A.service_name,A.rate_type,A.currency,A.charge_from_base,A.customer_id,A.transit_distance,A.transit_time,
                    A.transit_distance_text,A.transit_time_text,A.max_delivery_time,A.carrier,A.booked_service_id ,SER.service_code, 
	                COM.account_number,COM.username,COM.password,COUR.code';
       $sql = "SELECT  " . $sqldata . " FROM " . DB_PREFIX . "shipment_service as A
               INNER JOIN " . DB_PREFIX . "courier_vs_company as COM on COM.id = A.carrier
               INNER JOIN " . DB_PREFIX . "courier_vs_services as SER on SER.id = A.booked_service_id
               INNER JOIN " . DB_PREFIX . "courier as COUR on COUR.id = COM.courier_id 
               WHERE  A.load_identity = '$loadIdentity'";
        return $this->db->getRowRecord($sql);
    }
   public function getLoadDetails($loadIdentity){
        $record = array();
        $sqldata ='S.shipment_service_type,S.shipment_customer_name,S.shipment_address1,S.shipment_address2,S.shipment_contact_mobile,S.shipment_customer_phone,
                   S.shipment_customer_email,S.shipment_postcode,S.shipment_customer_city,
                   S.shipment_customer_country,S.shipment_latitude,S.shipment_longitude,S.company_id,S.warehouse_id,
                   S.address_id,S.user_id,S.customer_id,S.shipment_companyName,S.shipment_country_code,S.shipment_notes,S.customer_id,S.warehouse_id,
                   W.latitude as warehouse_latitude,W.longitude as warehouse_longitude,S.company_id';
        $sql = "SELECT  " . $sqldata . " FROM " . DB_PREFIX . "shipment as S
                INNER JOIN " . DB_PREFIX . "warehouse as W on W.id = S.warehouse_id 
                WHERE  S.instaDispatch_loadIdentity = '$loadIdentity'";
        return $this->db->getAllRecords($sql);
    } 
   
   public function getCustomerCarrierDataByServiceCode($customerId,$servicecode,$company){ 
       $sql = "SELECT A.service_id FROM " . DB_PREFIX . "company_vs_customer_vs_services  as A
               INNER JOIN " . DB_PREFIX . "courier_vs_services as S on S.id = A.service_id
               WHERE A.company_customer_id =  '$customerId' AND S.service_code = '$servicecode'";
       $data =  $this->db->getRowRecord($sql);
       return $data;
    }
 }
?>