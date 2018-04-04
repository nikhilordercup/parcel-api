<?php
class AllInvoice_Model
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
            self::$_modelObj = new AllInvoice_Model();
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

    public function getAffectedRows()
        {
        return $this->db->getAffectedRows();
        }

   
    
    public function getAllInvice($whareHouseId,$componyId){
        $record = array();
        $sqldata = 'I.invoice_reference,I.total_ammount as total_amount,I.raised_on,
                    I.deu_date as due_on,I.from,I.to,I.voucer as voucher,
                    I.tot_shipmets as shipments,I.tot_item as item,I.invoice_status as status,
                    CI.accountnumber as shipment_customer_account,CI.billing_full_name as customer';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "invoices AS I
                    LEFT JOIN " . DB_PREFIX . "customer_info AS CI ON CI.user_id = I.customer_id
                    WHERE I.company_id  = '" . $componyId . "'";
        $record = $this->db->getAllRecords($sql);
        return $record;
     }
    public function getCustomerInvoiceCycle($customerid){ 
         $record = array();
         $sqldata ='t1.invoicecycle';
         $sql = "SELECT ".$sqldata." FROM " . DB_PREFIX . "customer_info AS t1
          WHERE t1.user_id  = '" . $customerid . "'";
         $record = $this->db->getRowRecord($sql);
         return  $record['invoicecycle'];  
    }
    public function  getAllInvoicedDocket($companyId,$from,$to,$customerfilter){   
        $record = array();
        $sqldata = 'A.shipment_id as reference_id,A.load_identity as reference,
                    DATE_FORMAT(S.shipment_create_date,"%Y-%m-%d") AS booking_date,
                    S.shipment_total_item AS items,S.shipment_total_weight AS weight,
                    S.shipment_total_volume AS volume,S.shipment_customer_name AS consignee,
                    S.instaDispatch_customerReference AS customer_booking_reference,
                    A.service_name as service_name,
                    (A.base_price +  A.courier_commission_value)as base_amount,
                    A.surcharges as surcharge_total,A.taxes as tax,A.rate_type as rate_type,
                    A.transit_distance_text as chargable_value,A.total_price as total,A.customer_id,
                    SP.price as fual_surcharge';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment_service as A
                LEFT JOIN " . DB_PREFIX . "shipment as S on S.shipment_id = A.shipment_id
                LEFT JOIN " . DB_PREFIX . "shipment_price as SP on (SP.shipment_id = A.shipment_id AND SP.api_key = 'surcharges' AND SP.price_code = 'fual_surcharge')
                WHERE A.isInvoiced = 'NO'
                AND (S.current_status = 'C' OR  S.current_status = 'O' OR  S.current_status = 'S' OR  S.current_status = 'D' OR  S.current_status = 'Ca')
                ".$customerfilter."
                AND DATE_FORMAT(S.shipment_create_date,'%Y-%m-%d') between '" . $from . "' AND '" . $to . "'
                AND S.company_id = '" .$companyId ."' 
                ORDER BY A.customer_id"; 
        $record = $this->db->getAllRecords($sql);
        return $record; 
     }
    
    public function _generate_invoice_no($company_id){ 
        $libObj = new Library();
		$record = $this->db->getRowRecord("SELECT (invoice_end_number + 1) AS invoice_reference, invoice_prefix AS invoice_prefix FROM " . DB_PREFIX . "configuration WHERE company_id = ".$company_id);
		$invoice_number = $record['invoice_prefix'].str_pad($record['invoice_reference'],6,0,STR_PAD_LEFT);
		$check_digit = $libObj->generateCheckDigit($invoice_number);
		$invoice_number = "$invoice_number$check_digit";
		$this->db->updateData("UPDATE " . DB_PREFIX . "configuration SET invoice_end_number = invoice_end_number + 1 WHERE company_id = ".$company_id);
		
		if($this->_test_invoice_number($invoice_number)){
			$this->_generate_invoice_no($company_id);
		}
		return $invoice_number;
	}
    private function _test_invoice_number($invoice_number){
		$record = $this->db->getOneRecord("SELECT COUNT(1) AS exist FROM " . DB_PREFIX . "invoices WHERE invoice_reference = '". $invoice_number ."'");
		if($record['exist'] > 0)
			return true;
		else
			return false;
	}
    public function getjobDetails($shipmentRef){
         $record = array();
         $sqldata ='S1.instaDispatch_loadIdentity,S1.shipment_required_service_date,
                    S1.instaDispatch_loadGroupTypeCode,
                    S1.shipment_service_type,
                    S1.icargo_execution_order,
                    ADDR.postcode as shipment_postcode,
                    ADDR.country AS shipment_customer_country';
         $sql = "SELECT ".$sqldata." FROM " . DB_PREFIX . "shipment AS S1
                 LEFT JOIN " . DB_PREFIX . "address_book AS ADDR ON ADDR.id = S1.address_id
          WHERE S1.instaDispatch_loadIdentity  = '" . $shipmentRef . "'";
         $record = $this->db->getAllRecords($sql);
         return  $record;       
     }
    
    public function getAllInviceShip($ref){
        $record = array();
         $sqldata ='S1.*';
         $sql = "SELECT ".$sqldata." FROM " . DB_PREFIX . "invoice_vs_docket AS S1
          WHERE S1.invoice_reference  = '" . $ref . "'";
         $record = $this->db->getAllRecords($sql);
         return  $record;     
    }
   public function getAllInviceCustomerDetails($ref){
         $record = array();
         $sqldata ='COM.name AS company_name,COM.address_1 AS company_address1,
                    COM.address_2 AS company_address2,COM.postcode AS company_postcode,
                    COM.city AS company_city,COM.country  AS company_county,
                    CUS.billing_full_name AS customername,CUS.billing_address_1 AS customeraddress1,
                    CUS.billing_address_2 AS customeraddress2,CUS.billing_postcode AS customerpostcode,
                    CUS.billing_city AS customercity,CUS.billing_country AS customercountry,
                    CUS.billing_phone AS customerphone,CUS.accountnumber AS customeraccount,
                    CUS.vatnumber AS customervat,S1.invoice_reference AS customerinvoiceref,
                    DATE_FORMAT(S1.raised_on,"%Y-%m-%d")  AS customerinvoicedate,S1.invoice_reference AS customerinvoiceref,
                    DATE_FORMAT(S1.deu_date,"%Y-%m-%d")  AS customerinvoiceduedate,
                    S1.base_amount AS baseprice,S1.surcharge_total AS surcharge,
                    S1.invoice_status as status,
                    S1.fual_surcharge AS fualsurcharge,S1.tax AS tax,S1.total_ammount AS total';
        $sql = "SELECT ".$sqldata." FROM " . DB_PREFIX . "invoices AS S1
                LEFT JOIN " . DB_PREFIX . "users AS COM ON COM.id = S1.company_id
                LEFT JOIN " . DB_PREFIX . "customer_info AS CUS on CUS.user_id = S1.customer_id
                WHERE S1.invoice_reference  = '" . $ref . "'";
         $record = $this->db->getRowRecord($sql);
         return  $record;     
    } 
    
   }
?>