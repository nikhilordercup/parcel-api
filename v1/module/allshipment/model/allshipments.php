<?php
class AllShipment_Model
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
            self::$_modelObj = new AllShipment_Model();
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
        return $this->db->query($sql);
        }

    public

    function getAffectedRows()
        {
        return $this->db->getAffectedRows();
        }

   
    
        
    public function getAllShipmentsDrop($whareHouseId,$componyId,$limitstr,$filter){
        $record = array();
        $sqldata ='S.instaDispatch_loadIdentity';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipments_view AS S
                WHERE S.warehouse_id  = '" . $whareHouseId . "'
                AND S.company_id  = '" . $componyId . "'
                ".$filter."
                ".$limitstr."
                "; 
        $record = $this->db->getAllRecords($sql);
        return $record;
        
    }
    
    /*
CREATE VIEW `icargo_shipments_view` AS
SELECT  S.warehouse_id as warehouse_id,
		S.company_id as company_id,
        S.instaDispatch_loadIdentity,
        S.customer_id,
        SST.carrier,
        SST.service_name,
        S.instaDispatch_loadGroupTypeCode as shipment_type,
        S.shipment_create_date as booking_date,
        S.booked_by as booked_by,
		SST.total_price as amount,
        SST.isInvoiced as isInvoiced
        FROM icargo_shipment AS S
        LEFT JOIN icargo_shipment_service AS SST ON SST.shipment_id = S.shipment_id
		WHERE (S.current_status = 'C' OR  S.current_status = 'O' OR  S.current_status = 'S' OR  S.current_status = 'D' OR  S.current_status = 'Ca')
		AND (S.instaDispatch_loadGroupTypeCode  = 'SAME' OR S.instaDispatch_loadGroupTypeCode  = 'NEXT') 
        GROUP BY S.instaDispatch_loadIdentity
    */
    public function getAllShipments($filter=''){ 
       
        $record = array();
        $sqldata = 'S.instaDispatch_loadIdentity,
                    S.icargo_execution_order,
                    S.shipment_service_type,
                    S.instaDispatch_loadGroupTypeCode,
                    S.shipment_service_type,
                    S.current_status,
                    S.shipment_required_service_date,
                    S.shipment_required_service_starttime,
                    ADDR.postcode AS shipment_postcode,
                    ADDR.address_line1 AS address_line1,
                    ADDR.address_line2 AS address_line2,
                    ADDR.country AS shipment_customer_country,
                    CI.accountnumber as shipment_customer_account,
                    UTT.name as shipment_customer_name,
                    (SST.base_price +  SST.courier_commission_value + SST.surcharges + SST.taxes) as shipment_customer_price,
                    SST.service_name as shipment_service_name,
                    SST.carrier as carrier,
                    UT.name as booked_by,
                    SST.isInvoiced as isInvoiced';
      $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment AS S
                    LEFT JOIN " . DB_PREFIX . "customer_info AS CI ON CI.user_id = S.customer_id
                    LEFT JOIN " . DB_PREFIX . "users AS UTT ON UTT.id = S.customer_id
                    LEFT JOIN " . DB_PREFIX . "users AS UT ON UT.id = S.booked_by
                    LEFT JOIN " . DB_PREFIX . "shipment_service AS SST ON SST.load_identity = S.instaDispatch_loadIdentity
                    LEFT JOIN " . DB_PREFIX . "address_book AS ADDR ON ADDR.id = S.address_id
                    WHERE 1 = 1  
                    ".$filter."
                    ORDER BY  FIELD(\"S.shipment_service_type\",\"P\",\"D\"),S.shipment_id DESC";
        $record = $this->db->getAllRecords($sql);
        //$record = $this->db->getRowRecord($sql);
        //$record = $this->db->getOneRecord($sql);
        return $record;
    }
    
    public function getAllShipmentsIdentity($filter='',$limitstr){  
      $record = array();
      $sqldata = 'DISTINCT(S.instaDispatch_loadIdentity)';
      $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment AS S
              LEFT JOIN " . DB_PREFIX . "address_book AS ADDR ON ADDR.id = S.address_id
              WHERE (S.current_status = 'C' OR  S.current_status = 'O' OR  S.current_status = 'S' OR  S.current_status = 'D' OR  S.current_status = 'Ca')
		AND (S.instaDispatch_loadGroupTypeCode  = 'SAME' OR S.instaDispatch_loadGroupTypeCode  = 'NEXT') 
              ".$filter."
              ".$limitstr."
              ";
        $record = $this->db->getAllRecords($sql);
        return $record;
    }
     

    public function getShipmentsDetail($identity){ 
       $record = array();
         $sqldata = '
         S.instaDispatch_loadGroupTypeCode as job_type,
         S.shipment_service_type as shipment_type,
         S.shipment_create_date as bookingdate,
         S.shipment_required_service_date as expecteddate,
         S.shipment_required_service_starttime as expectedstarttime,
         S.shipment_required_service_endtime as expectedendtime,
         UTT.name as customer,
         SST.service_name as service,
         SST.rate_type as chargeableunit,
         SST.transit_distance_text as chargeablevalue,
         SST.transit_time_text as transittime,
         UTS.name as user,
         SST.carrier as carrier,
         SST.load_identity as reference,
         DRIV.name as collectedby, 
         S.waitAndReturn as waitandreturn,
         SST.load_identity as carrierreference,
         CI.accountnumber as carrierbillingacount,
         S.shipment_customer_phone as customerphone,
         S.shipment_customer_name as customername,
         S.shipment_customer_email as customeremail,
         ADDR.postcode as postcode,
         ADDR.address_line1 AS address_line1,
         ADDR.address_line2 AS address_line2,
         ADDR.country AS country,
         ADDR.city AS city,
         ADDR.state AS state,
         (SST.base_price +  SST.courier_commission_value)as customerbaseprice,
         SST.surcharges as customersurcharge,
         (SST.base_price +  SST.courier_commission_value + SST.surcharges)as customersubtotal,
         SST.taxes as customertax,
         SST.total_price as customertotalprice,
         (SST.base_price +  SST.courier_commission_value + SST.surcharges + SST.taxes)as customertotalprice,
         SST.base_price as carrierbaseprice,
         SST.surcharges as carriersurcharge,
         (SST.base_price + SST.surcharges) as carriersubtotal,
         SST.taxes as carriertax,
         (SST.base_price + SST.surcharges) as carriertotalprice,
         SST.invoice_reference as customerinvoicereference,
         UL.user_type as bookingtype,
         UT.name as customer_desc,
         SST.load_identity as customerreference
        ';
      $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment AS S
                    LEFT JOIN " . DB_PREFIX . "address_book AS ADDR ON ADDR.id = S.address_id
                    LEFT JOIN " . DB_PREFIX . "shipment_service AS SST ON SST.shipment_id = S.shipment_id
                    LEFT JOIN " . DB_PREFIX . "customer_info AS CI ON CI.user_id = S.customer_id
                    LEFT JOIN " . DB_PREFIX . "users AS UT ON UT.id = S.booked_by
                    LEFT JOIN " . DB_PREFIX . "user_level AS UL ON UL.id = UT.user_level
                    LEFT JOIN " . DB_PREFIX . "users AS UTT ON UTT.id = S.customer_id
                    LEFT JOIN " . DB_PREFIX . "users AS UTS ON UTS.id = S.user_id
                    LEFT JOIN " . DB_PREFIX . "users AS DRIV ON DRIV.id = S.assigned_driver AND DRIV.user_level = 4
                    WHERE(S.instaDispatch_loadGroupTypeCode  = 'SAME' OR S.instaDispatch_loadGroupTypeCode  = 'NEXT')
                    AND S.instaDispatch_loadIdentity = '" . $identity . "' 
                    ORDER BY  FIELD(\"S.shipment_service_type\",\"P\",\"D\")";
        $record = $this->db->getAllRecords($sql); 
        return $record;
      }
    
    
     public function getShipmentsInvoiceDetail($identity){ 
       $record = array();
         $sqldata = 'S.*,SST.invoice_status,SST.raised_on,SST.deu_date';
         $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "invoice_vs_docket AS S
              LEFT JOIN " . DB_PREFIX . "invoices AS SST ON SST.invoice_reference = S.invoice_reference
              WHERE S.reference = '" . $identity . "'
              AND SST.invoice_status != 'CANCEL'";
        $record = $this->db->getRowRecord($sql); 
        return $record;
      }
    
     public function getShipmentsurchargeData($identity){ 
       $record = array();
         $sqldata = 'S.*';
         $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment_price AS S
              WHERE S.load_identity = '" . $identity . "'";
        $record = $this->db->getAllRecords($sql); 
        return $record;
      } 
  }
?>