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
        return $this->db->delete($sql);
        }

    public

    function getAffectedRows()
        {
        return $this->db->getAffectedRows();
        }




    public function getAllShipmentsDrop($whareHouseId,$componyId,$limitstr,$filter){
        $record = array();
        $subquery = ($whareHouseId!=0)?"AND S.warehouse_id  = '" . $whareHouseId . "'":"";
        $sqldata = 'S.instaDispatch_loadIdentity';
        /*$sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "xyz AS S
                WHERE S.warehouse_id  = '" . $whareHouseId . "'
                AND S.company_id  = '" . $componyId . "'
                ".$filter."
                ".$limitstr."
        "; */
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipments_view AS S
                WHERE 1 ".$subquery."
                AND S.company_id  = '" . $componyId . "'
                ".$filter."
				order by booking_date DESC
                ".$limitstr."
                ";
		//echo $sql;die;
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
					S.shipment_create_date,
                    S.shipment_required_service_date,
                    S.shipment_required_service_starttime,
                    S.shipment_postcode AS shipment_postcode,
                    S.shipment_address1 AS address_line1,
                    S.shipment_address2 AS address_line2,
                    S.shipment_customer_country AS shipment_customer_country,
                    CI.accountnumber as shipment_customer_account,
                    UTT.name as shipment_customer_name,
                    (SST.base_price + SST.courier_commission_value + SST.surcharges + SST.taxes) as shipment_customer_price,
                    SST.service_name as shipment_service_name,
                    SST.is_hold as is_hold,
                    SST.is_recurring as is_recurring,
                    SST.booked_by_recurring as booked_by_recurring,
                    COUR.name as carrier,
					          COUR.icon as carrier_icon,
                    UT.name as booked_by,
                    SST.isInvoiced as isInvoiced,
					          SST.tracking_code as cancel_status,
					          SST.label_json as label_json,
                    SST.tracking_code as current_status,
                    SST.customer_reference1 AS customer_reference1,
                    SST.customer_reference2 AS customer_reference2'; 
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment AS S
                    LEFT JOIN " . DB_PREFIX . "customer_info AS CI ON CI.user_id = S.customer_id
                    LEFT JOIN " . DB_PREFIX . "users AS UTT ON UTT.id = S.customer_id
                    LEFT JOIN " . DB_PREFIX . "users AS UT ON UT.id = S.booked_by
                    LEFT JOIN " . DB_PREFIX . "shipment_service AS SST ON SST.load_identity = S.instaDispatch_loadIdentity
                    LEFT JOIN " . DB_PREFIX . "courier_vs_company AS COMCOUR ON COMCOUR.id = SST.carrier
                    LEFT JOIN " . DB_PREFIX . "courier AS COUR ON COUR.id = COMCOUR.courier_id
                    WHERE 1 = 1
                    ".$filter."
                    ORDER BY  FIELD(\"S.shipment_service_type\",\"P\",\"D\"),S.shipment_id DESC";
        $record = $this->db->getAllRecords($sql);
        return $record;
    }

    public function getAllShipmentsIdentity($filter='',$limitstr){
      $record = array();
      $sqldata = 'DISTINCT(S.instaDispatch_loadIdentity)';
      $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment AS S
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
         /*$sqldata = '
         S.company_id as companyid,
         S.instaDispatch_loadGroupTypeCode as job_type,
         S.shipment_service_type as shipment_type,
         S.shipment_create_date as bookingdate,
         S.shipment_required_service_date as expecteddate,
         S.shipment_required_service_starttime as expectedstarttime,
         S.shipment_required_service_endtime as expectedendtime,
         S.shipment_ticket,
         UTT.name as customer,
         SST.carrier as carrierid,
         COUR.name as carriername,
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
         S.shipment_postcode AS postcode,
         S.shipment_address1 AS address_line1,
         S.shipment_address2 AS address_line2,
         S.shipment_customer_country AS country,
         S.shipment_customer_city AS city,
         S.shipment_county AS state,
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
        ';*/
		$sqldata = '
         S.company_id as companyid,
         S.instaDispatch_loadGroupTypeCode as job_type,
         S.shipment_service_type as shipment_type,
         S.shipment_create_date as bookingdate,
         S.shipment_required_service_date as expecteddate,
         S.shipment_required_service_starttime as expectedstarttime,
         S.shipment_required_service_endtime as expectedendtime,
         S.shipment_ticket,
         UTT.name as customer,
         SST.carrier as carrierid,
         COUR.name as carriername,
         SST.service_name as service,
         SST.rate_type as chargeableunit,
         SST.transit_distance_text as chargeablevalue,
         SST.transit_time_text as transittime,
         UTS.name as user,
         SST.carrier as carrier,
         SST.load_identity as reference,
         DRIV.name as collectedby,
         S.waitAndReturn as waitandreturn,
         SST.label_tracking_number as carrierreference,
         CI.accountnumber as carrierbillingacount,
         S.shipment_customer_phone as customerphone,
         S.shipment_customer_name as customername,
         S.shipment_customer_email as customeremail,
         S.shipment_postcode AS postcode,
         S.shipment_address1 AS address_line1,
         S.shipment_address2 AS address_line2,
         S.shipment_customer_country AS country,
         S.shipment_customer_city AS city,
         S.shipment_county AS state,
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
         SST.load_identity as customerreference,
         SST.customer_reference1 AS customer_reference1,
         SST.customer_reference2 AS customer_reference2
        ';
      $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment AS S
                    LEFT JOIN " . DB_PREFIX . "shipment_service AS SST ON (SST.load_identity = S.instaDispatch_loadIdentity AND S.shipment_service_type = 'P')
                    LEFT JOIN " . DB_PREFIX . "customer_info AS CI ON CI.user_id = S.customer_id
                    LEFT JOIN " . DB_PREFIX . "users AS UT ON UT.id = S.booked_by
                    LEFT JOIN " . DB_PREFIX . "user_level AS UL ON UL.id = UT.user_level
                    LEFT JOIN " . DB_PREFIX . "users AS UTT ON UTT.id = S.customer_id
                    LEFT JOIN " . DB_PREFIX . "users AS UTS ON UTS.id = S.user_id
                    LEFT JOIN " . DB_PREFIX . "users AS DRIV ON DRIV.id = S.assigned_driver AND DRIV.user_level = 4
                    LEFT JOIN " . DB_PREFIX . "courier AS COUR ON COUR.id = SST.carrier
                    WHERE(S.instaDispatch_loadGroupTypeCode  = 'SAME' OR S.instaDispatch_loadGroupTypeCode  = 'NEXT')
                    AND S.instaDispatch_loadIdentity = '" . $identity . "'
                    ORDER BY  FIELD(\"S.shipment_service_type\",\"P\",\"D\")";
        $record = $this->db->getAllRecords($sql);
        return $record;
      }

	public function getAllParcelsByIdentity($identity){
      $sqldata = 'DISTINCT(S.instaDispatch_loadIdentity)';
      $sql = "SELECT parcel_weight,parcel_height,parcel_length,parcel_width,package FROM ".DB_PREFIX."shipments_parcel AS P WHERE P.instaDispatch_loadIdentity = '$identity' GROUP BY P.instaDispatch_loadIdentity";
	  $record = $this->db->getAllRecords($sql);
      return $record;
    }

    /*
    public function getShipmentsPriceDetail($identity,$courier_id,$company_id,$priceversion){
       $record = array();
       $sqldata = 'CSER.company_service_name,COUSER.service_name,CSUR.company_surcharge_name,COUSUR.surcharge_name,P.*';
       $sql = " SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment_price AS  P
                LEFT JOIN " . DB_PREFIX . "courier_vs_services_vs_company AS CSER ON CSER.service_id = P.service_id AND CSER.courier_id = '" .$courier_id . "'  AND CSER.company_id = '" .$company_id . "'
                LEFT JOIN " . DB_PREFIX . "courier_vs_surcharge_vs_company AS CSUR ON CSUR.surcharge_id = P.surcharge_id AND CSUR.courier_id = '" .$courier_id . "'  AND CSUR.company_id = '" .$company_id . "'
                LEFT JOIN " . DB_PREFIX . "courier_vs_services AS COUSER ON COUSER.id = P.service_id
                LEFT JOIN " . DB_PREFIX . "courier_vs_surcharge AS COUSUR ON COUSUR.id = P.surcharge_id
                WHERE P.load_identity = '" . $identity . "'
                AND  P.version = '" . $priceversion . "'";
        $record = $this->db->getAllRecords($sql);
        return $record;
      }
    */


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
    public function getShipmentPriceDetails($identity){
       $record = array();
         $sqldata = 'S.*';
         $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment_service AS S
                WHERE S.load_identity = '" . $identity . "'";
        $record = $this->db->getRowRecord($sql);
        return $record;
      }
    public function getShipmentPricebreakdownDetails($identity){
       $record = array();
         $sqldata = 'S.*';
         $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment_price AS S
                WHERE S.load_identity = '" . $identity . "'";
        $record = $this->db->getAllRecords($sql);
        return $record;
      }
    public function getShipmentPricebreakdownDetailsWithVersion($identity,$priceVersion){
       $record = array();
         $sqldata = 'S.*';
         $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment_price AS S
                WHERE S.load_identity = '" . $identity . "' AND S.version = '" . $priceVersion . "'
                AND show_for != 'C'";
        $record = $this->db->getAllRecords($sql);
        return $record;
      }
    public function getShipmentPricebreakdownDetailsWithVersionOfCustomer($identity,$priceVersion){
       $record = array();
         $sqldata = 'S.*';
         $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment_price AS S
                WHERE S.load_identity = '" . $identity . "' AND S.version = '" . $priceVersion . "'
                AND show_for != 'CA'";
        $record = $this->db->getAllRecords($sql);
        return $record;
      }


     public function getAllSurchargeOfCarrier($carrierId,$companyId){
       $record = array();
         $sqldata = 'S.surcharge_id,S.company_surcharge_name';
         $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "courier_vs_surcharge_vs_company AS S
                WHERE S.company_id = '" . $companyId . "' AND S.courier_id = '" . $carrierId . "' AND S.status = 1";
        $record = $this->db->getAllRecords($sql);
        return $record;
      }

   /*  public function getCcfOfCarrierSurcharge($surchargeId,$company_id,$customer_id,$courier_id)
    {
        $sql = "
        SELECT
                COURSER.id as surcharge_id,
                CCST.customer_surcharge AS customer_carrier_surcharge_ccf,
                CCST.ccf_operator AS customer_carrier_surcharge_operator,
                CCC.customer_surcharge_value AS customer_carrier_surcharge,
                CCC.company_ccf_operator_surcharge AS customer_carrier_operator,
                CINFO.surcharge AS customer_surcharge,
                CINFO.ccf_operator_surcharge AS customer_operator,
                COMSER.company_surcharge_surcharge AS company_carrier_surcharge_ccf,
                COMSER.company_ccf_operator AS company_carrier_surcharge_operator,
                COMSER.company_surcharge_code,
                COMSER.company_surcharge_name,
                COMCOUR.company_surcharge_value AS company_carrier_ccf,
                COMCOUR.company_ccf_operator_surcharge AS company_carrier_operator,
                COURSER.surcharge_name AS courier_surcharge_name,
                COURSER.surcharge_code AS courier_surcharge_code
                FROM " . DB_PREFIX . "company_vs_customer_vs_surcharge CCST
                INNER JOIN " . DB_PREFIX . "courier_vs_company_vs_customer as CCC on CCC.customer_id = CCST.company_customer_id AND CCC.courier_id = CCST.courier_id
                INNER JOIN " . DB_PREFIX . "customer_info as CINFO on CINFO.user_id = CCST.company_customer_id
                INNER JOIN " . DB_PREFIX . "courier_vs_surcharge_vs_company as COMSER on (COMSER.surcharge_id = CCST.surcharge_id AND COMSER.courier_id = CCST.courier_id   AND COMSER.company_id =  '$company_id')
                INNER JOIN " . DB_PREFIX . "courier_vs_company as COMCOUR on (COMCOUR.courier_id = CCST.courier_id AND  COMCOUR.company_id =  '$company_id')
                INNER JOIN " . DB_PREFIX . "courier_vs_surcharge as COURSER on (COURSER.id = CCST.surcharge_id)
                WHERE CCST.status = 1
                AND CCC.status = 1
                AND COMSER.status = 1
                AND COMCOUR.status = 1
                AND COURSER.status = 1
                AND CCST.company_customer_id = '$customer_id'
                AND CCST.company_id = '$company_id'
                AND CCST.courier_id = '$courier_id'
                AND COURSER.id = '$surchargeId'";
        return $this->db->getRowRecord($sql);
    }

     public function getSurchargeOfCarrier($customer_id, $company_id, $courier_id)
    {
        $sql = "
        SELECT
        CCC.customer_surcharge_value AS customer_surcharge_value,
        CCC.company_ccf_operator_surcharge AS company_ccf_operator_surcharge,
        CINFO.surcharge AS customer_surcharge,
        CINFO.ccf_operator_surcharge AS customer_operator,
        COMCOUR.company_surcharge_value AS company_carrier_ccf,
        COMCOUR.company_ccf_operator_surcharge AS company_carrier_operator
        FROM " . DB_PREFIX . "courier_vs_company_vs_customer as CCC
        INNER JOIN " . DB_PREFIX . "customer_info as CINFO on CINFO.user_id = CCC.customer_id
        INNER JOIN " . DB_PREFIX . "courier_vs_company as COMCOUR on (COMCOUR.courier_id = CCC.courier_id AND  COMCOUR.company_id =  CCC.company_id )
        WHERE   CCC.status = 1 AND  COMCOUR.status = 1
        AND CCC.customer_id = '$customer_id'
        AND CCC.company_id = '$company_id'
        AND CCC.courier_id = '$courier_id'";
        return $this->db->getRowRecord($sql);
    }*/

    public function getCcfOfCarrierSurcharge($surchrage_code, $customer_id, $company_id, $courier_id)
    {
       $sql = "
        SELECT
                COURSER.id as surcharge_id,
                CCST.customer_surcharge AS customer_carrier_surcharge_ccf,
                CCST.ccf_operator AS customer_carrier_surcharge_operator,
                CCC.customer_surcharge_value AS customer_carrier_surcharge,
                CCC.company_ccf_operator_surcharge AS customer_carrier_operator,
                CINFO.surcharge AS customer_surcharge,
                CINFO.ccf_operator_surcharge AS customer_operator,
                COMSER.company_surcharge_surcharge AS company_carrier_surcharge_ccf,
                COMSER.company_ccf_operator AS company_carrier_surcharge_operator,
                COMSER.company_surcharge_code,
                COMSER.company_surcharge_name,
                COMCOUR.company_surcharge_value AS company_carrier_ccf,
                COMCOUR.company_ccf_operator_surcharge AS company_carrier_operator,
                COURSER.surcharge_name AS courier_surcharge_name,
                COURSER.surcharge_code AS courier_surcharge_code,
                COMCOUR.pickup,COMCOUR.pickup_surcharge
                FROM " . DB_PREFIX . "company_vs_customer_vs_surcharge CCST
                INNER JOIN " . DB_PREFIX . "courier_vs_company_vs_customer as CCC on CCC.customer_id = CCST.company_customer_id AND CCC.company_courier_account_id = CCST.courier_id
                INNER JOIN " . DB_PREFIX . "customer_info as CINFO on CINFO.user_id = CCST.company_customer_id
                INNER JOIN " . DB_PREFIX . "courier_vs_surcharge_vs_company as COMSER on (COMSER.surcharge_id = CCST.surcharge_id AND COMSER.courier_id = CCST.courier_id   AND COMSER.company_id =  '$company_id')
                INNER JOIN " . DB_PREFIX . "courier_vs_company as COMCOUR on (COMCOUR.id = CCST.courier_id AND  COMCOUR.company_id =  '$company_id')
                INNER JOIN " . DB_PREFIX . "courier_vs_surcharge as COURSER on (COURSER.id = CCST.surcharge_id)
                WHERE CCST.status = 1
                AND CCC.status = 1
                AND COMSER.status = 1
                AND COMCOUR.status = 1
                AND COURSER.status = 1
                AND CCST.company_customer_id = '$customer_id'
                AND CCST.company_id = '$company_id'
                AND CCST.courier_id = '$courier_id'
                AND COURSER.surcharge_code = '$surchrage_code'";
        return $this->db->getRowRecord($sql);
    }

    public function getSurchargeOfCarrier($customer_id, $company_id, $courier_id)
    {
        $sql = "
        SELECT
        CCC.customer_surcharge_value AS customer_surcharge_value,
        CCC.company_ccf_operator_surcharge AS company_ccf_operator_surcharge,
        CINFO.surcharge AS customer_surcharge,
        CINFO.ccf_operator_surcharge AS customer_operator,
        COMCOUR.company_surcharge_value AS company_carrier_ccf,
        COMCOUR.company_ccf_operator_surcharge AS company_carrier_operator,
        COMCOUR.pickup,COMCOUR.pickup_surcharge
        FROM " . DB_PREFIX . "courier_vs_company_vs_customer as CCC
        INNER JOIN " . DB_PREFIX . "customer_info as CINFO on CINFO.user_id = CCC.customer_id
        INNER JOIN " . DB_PREFIX . "courier_vs_company as COMCOUR on (COMCOUR.id = CCC.company_courier_account_id AND  COMCOUR.company_id =  CCC.company_id )
        WHERE   CCC.status = 1 AND  COMCOUR.status = 1
        AND CCC.customer_id = '$customer_id'
        AND CCC.company_id = '$company_id'
        AND CCC.courier_id = '$courier_id'";
        return $this->db->getRowRecord($sql);
    }


    public function getSurchargeCodeBySurchargeId($surchargeId,$companyId){
       $record = array();
         $sqldata = 'S.company_surcharge_code';
         $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "courier_vs_surcharge_vs_company AS S
                WHERE S.company_id = '" . $companyId . "' AND S.surcharge_id = '" . $surchargeId . "' AND S.status = 1";
        $record = $this->db->getRowRecord($sql);
        return $record['company_surcharge_code'];
      }
    public function getShipmentsPriceVersion($identity){
       $record = array();
         $sqldata = 'S.price_version';
         $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment_service AS S
                WHERE S.load_identity = '" . $identity . "'";
        $record = $this->db->getRowRecord($sql);
        return $record['price_version'];
      }
     public function getShipmentsPriceDetailCarrier($identity,$courier_id,$company_id,$priceversion){
       $record = array();
       $sqldata = 'CSER.company_service_name,COUSER.service_name,CSUR.company_surcharge_name,COUSUR.surcharge_name,P.*';
       $sql = " SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment_price AS  P
                LEFT JOIN " . DB_PREFIX . "courier_vs_services_vs_company AS CSER ON CSER.service_id = P.service_id AND CSER.courier_id = '" .$courier_id . "'  AND CSER.company_id = '" .$company_id . "'
                LEFT JOIN " . DB_PREFIX . "courier_vs_surcharge_vs_company AS CSUR ON CSUR.surcharge_id = P.surcharge_id AND CSUR.courier_id = '" .$courier_id . "'  AND CSUR.company_id = '" .$company_id . "'
                LEFT JOIN " . DB_PREFIX . "courier_vs_services AS COUSER ON COUSER.id = P.service_id
                LEFT JOIN " . DB_PREFIX . "courier_vs_surcharge AS COUSUR ON COUSUR.id = P.surcharge_id
                WHERE P.load_identity = '" . $identity . "'
                AND  P.version = '" . $priceversion . "'
                AND  P.show_for != 'C'";
        $record = $this->db->getAllRecords($sql);
        return $record;
      }
     public function getShipmentsPriceDetailCustomer($identity,$courier_id,$company_id,$priceversion){
       $record = array();
       $sqldata = 'CSER.company_service_name,COUSER.service_name,CSUR.company_surcharge_name,COUSUR.surcharge_name,P.*';
       $sql = " SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment_price AS  P
                LEFT JOIN " . DB_PREFIX . "courier_vs_services_vs_company AS CSER ON CSER.service_id = P.service_id AND CSER.courier_id = '" .$courier_id . "'  AND CSER.company_id = '" .$company_id . "'
                LEFT JOIN " . DB_PREFIX . "courier_vs_surcharge_vs_company AS CSUR ON CSUR.surcharge_id = P.surcharge_id AND CSUR.courier_id = '" .$courier_id . "'  AND CSUR.company_id = '" .$company_id . "'
                LEFT JOIN " . DB_PREFIX . "courier_vs_services AS COUSER ON COUSER.id = P.service_id
                LEFT JOIN " . DB_PREFIX . "courier_vs_surcharge AS COUSUR ON COUSUR.id = P.surcharge_id
                WHERE P.load_identity = '" . $identity . "'
                AND  P.version = '" . $priceversion . "'
                AND  P.show_for != 'CA'";
        $record = $this->db->getAllRecords($sql);
        return $record;
      }
    public function _generate_voucher_no($company_id){
        $libObj = new Library();
		$record = $this->db->getRowRecord("SELECT (voucher_end_number + 1) AS voucher_reference, voucher_prefix AS voucher_prefix FROM " . DB_PREFIX . "configuration WHERE company_id = ".$company_id);
		$voucher_number = $record['voucher_prefix'].str_pad($record['voucher_reference'],6,0,STR_PAD_LEFT);
		$check_digit = $libObj->generateCheckDigit($voucher_number);
		$voucher_number = "$voucher_number$check_digit";
		$this->db->updateData("UPDATE " . DB_PREFIX . "configuration SET voucher_end_number = voucher_end_number + 1 WHERE company_id = ".$company_id);

		if($this->_test_voucher_number($voucher_number)){
			$this->_generate_voucher_no($company_id);
		}
		return $voucher_number;
	}
   private function _test_voucher_number($voucher_number){
		$record = $this->db->getOneRecord("SELECT COUNT(1) AS exist FROM " . DB_PREFIX . "vouchers WHERE voucher_reference = '". $voucher_number ."'");
		if($record['exist'] > 0)
			return true;
		else
			return false;
	}
   public function getVoucherHistory($identity){
		$record = $this->db->getOneRecord("SELECT id  FROM " . DB_PREFIX . "vouchers WHERE shipment_reference = '". $identity ."' ORDER BY id DESC");
		if($record['id'] !=0)
			return $record['id'];
		else
			return 0;
	}
     public

     function getShipmentLifeCycleHistoryByIdentity($identity){
        $record = array();
        $sqldata = 'R1.*,R2.name,R3.route_name,S.shipment_service_type,R4.name as actions ';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment_life_history AS R1
                LEFT JOIN " . DB_PREFIX . "users AS R2  ON R1.driver_id = R2.id
                LEFT JOIN " . DB_PREFIX . "shipment AS S  ON R1.shipment_ticket = S.shipment_ticket
                LEFT JOIN " . DB_PREFIX . "shipment_route AS R3  ON R1.route_id = R3.shipment_route_id
                LEFT JOIN " . DB_PREFIX . "shipments_master AS R4 ON R1.internel_action_code = R4.tracking_internal_code
                WHERE R1.instaDispatch_loadIdentity = '" . $identity . "'
                AND  R4.is_used_for_tracking = 'YES'
                ORDER BY S.shipment_service_type,R1.his_id ASC";
        $record = $this->db->getAllRecords($sql);
        return $record;
        }
  public  function getShipmentPodByShipmentTicket($tickets){
        $record = array();
        $sql = "SELECT R1.* FROM " . DB_PREFIX . "shipments_pod AS R1 WHERE R1.shipment_ticket IN ($tickets)";
        $record = $this->db->getAllRecords($sql);
        return $record;
        }
  public function allowedTracking(){
        $record = array();
        //$sqldata = 'R1.tracking_internal_code as id,R1.name';
        $sql = "SELECT R1.code as id,R1.name FROM " . DB_PREFIX . "shipments_master AS R1 WHERE R1.is_used_for_tracking = 'YES'";
        $record = $this->db->getAllRecords($sql);
        return $record;
        }
   public  function deleteTracking($hisid){
       $sql = "DELETE  FROM " . DB_PREFIX . "shipment_life_history
                    WHERE his_id = '".$hisid."'";
        $record = $this->deleteContent($sql);
        return $record;
        }
   public function getAllowedAllShipmentsStatus($companyid){
         $record = array();
         $sqldata ='t1.*';
         $sql = "SELECT ".$sqldata." FROM " . DB_PREFIX . "shipments_master AS t1 WHERE is_used_for_tracking = 'YES'";
         $record = $this->db->getAllRecords($sql);
         return  $record;
	 }
    public function getAllowedAllServices($companyid){
         $record = array();
         $sqldata =' DISTINCT COUSER.service_name';
         $sql = "SELECT ".$sqldata." FROM " . DB_PREFIX . "courier_vs_services_vs_company AS t1
                LEFT JOIN " . DB_PREFIX . "courier_vs_services AS COUSER ON COUSER.id = t1.service_id
                WHERE t1.company_id = '".$companyid."'";
         $record = $this->db->getAllRecords($sql);
         return  $record;
	 }
     public function getAllCarrier($companyid){
         $record = array();
         $sqldata =' DISTINCT t1.courier_id as id,t2.name';
         $sql = "SELECT ".$sqldata." FROM " . DB_PREFIX . "courier_vs_company AS t1
                LEFT JOIN " . DB_PREFIX . "courier AS t2 on t1.courier_id = t2.id
                WHERE t1.company_id = '$companyid'";
         $record = $this->db->getAllRecords($sql);
         return  $record;
	 }

	//get status from shipment service table_name
	 public function getStatusByLoadIdentity($load_identity){
		//$record = $this->db->getOneRecord("SELECT status  FROM " . DB_PREFIX . "shipment_service WHERE load_identity = '". $load_identity ."'");
		$records = $this->db->getAllRecords("SELECT status  FROM " . DB_PREFIX . "shipment_service WHERE load_identity IN ('$load_identity')");
		return $records;
	}

    public function getCurrentTrackingStatusByLoadIdentity($load_identity){
        return $this->db->getRowRecord("SELECT SST.tracking_code AS tracking_code, SMT.name AS code_translation FROM " . DB_PREFIX . "shipment_service AS SST INNER JOIN " . DB_PREFIX . "shipments_master AS SMT ON SMT.code=SST.tracking_code WHERE load_identity = '$load_identity'");
        //return $this->db->getRowRecord("SELECT SST.tracking_code AS tracking_code, SST.tracking_code AS code_translation FROM " . DB_PREFIX . "shipment_service AS SST WHERE SST.load_identity = '$load_identity'");
    }
    public function getCustomerInfo($customerId){
       $record = array();
         $sqldata = 'C.available_credit,C.customer_type';
         $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "customer_info AS C
                WHERE C.user_id = '" . $customerId . "'";
        return $this->db->getRowRecord($sql);

      }

    public function getCompanylogo($company_id){
    $record = $this->db->getRowRecord("SELECT logo FROM " . DB_PREFIX . "configuration WHERE company_id = ".$company_id);
    return $record['logo'];
     }
    public function getRecurringShipmentDetails($identity){
       $record = array();
         $sqldata = '
         S.instaDispatch_loadGroupTypeCode as job_type,
         S.shipment_service_type as shipment_type,
         S.shipment_customer_phone as customerphone,
         S.shipment_customer_name as customername,
         S.shipment_customer_email as customeremail,
         S.shipment_postcode AS postcode,
         S.shipment_address1 AS address_line1,
         S.shipment_address2 AS address_line2,
         S.shipment_customer_country AS country,
         S.shipment_customer_city AS city,
         S.shipment_county AS state,
         S.customer_id AS customer_id,
         S.company_id AS company_id,
         SST.carrier AS company_carrier_id,
         SST.service_name as service,
         SST.rate_type as chargeableunit,
         SST.transit_distance_text as chargeablevalue,
         SST.transit_time_text as transittime,
         SST.carrier as carrier,
         SST.load_identity as reference,
         (SST.base_price +  SST.courier_commission_value + SST.surcharges + SST.taxes)as customertotalprice,
         (SST.base_price + SST.surcharges) as carriertotalprice,
         SST.load_identity as customerreference,
         UTT.name as customer,
         UTS.name as user,
         UT.name as customer_desc,
         COUR.name as carriername';
         $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment AS S
                    LEFT JOIN " . DB_PREFIX . "shipment_service AS SST ON (SST.load_identity = S.instaDispatch_loadIdentity AND S.shipment_service_type = 'P')
                    LEFT JOIN " . DB_PREFIX . "users AS UT ON UT.id = S.booked_by
                    LEFT JOIN " . DB_PREFIX . "users AS UTT ON UTT.id = S.customer_id
                    LEFT JOIN " . DB_PREFIX . "users AS UTS ON UTS.id = S.user_id
                    LEFT JOIN " . DB_PREFIX . "courier AS COUR ON COUR.id = SST.carrier
                    WHERE(S.instaDispatch_loadGroupTypeCode  = 'SAME' OR S.instaDispatch_loadGroupTypeCode  = 'NEXT')
                    AND S.instaDispatch_loadIdentity in($identity)
                    ORDER BY  FIELD(\"S.shipment_service_type\",\"P\",\"D\")";
        $record = $this->db->getAllRecords($sql);
        return $record;
      }

    public function getRecurringDetails($identity){
       $record = array();
         $sqldata = '
         SST.customer_id AS customer_id,
         SST.carrier AS company_carrier_id,
         SST.booked_service_id as company_service_id';
         $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment_service AS SST
         where SST.load_identity in($identity)";
        $record = $this->db->getAllRecords($sql);
        return $record;
      }
     public function getRecurringJobsByCompanyId($companyId){
       $record = array();
         $sqldata = '
         RECJOB.job_id AS id,
         RECJOB.load_identity AS job_reference,
         RECJOB.load_type AS type,
         RECJOB.last_booking_date AS last_booked_date,
         RECJOB.last_booking_time AS last_booked_time,
         RECJOB.last_booking_reference AS last_booked_ref,
         RECJOB.recurring_type AS recurring_type,
         RECJOB.status AS status,
         COUR.name as carrier,
         UTT.name as customer';
         $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "recurring_jobs AS RECJOB
                 LEFT JOIN " . DB_PREFIX . "users AS UTT ON UTT.id = RECJOB.customer_id
                 LEFT JOIN " . DB_PREFIX . "courier AS COUR ON COUR.id = RECJOB.company_carrier_id
         where RECJOB.company_id  = '$companyId' group by RECJOB.load_identity ";
        $record = $this->db->getAllRecords($sql);
        return $record;
      }

    public function getRecurringJobDetail($identity){
       $record = array();
         $sqldata = '*';
         $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "recurring_jobs AS RECJOB
         where RECJOB.load_identity = '$identity'";
         $record = $this->db->getAllRecords($sql);
         return $record;
      }
     public function getRecurringJobsBreakDown($companyId,$loadidentity){
       $record = array();
         $sqldata = '
         RECJOB.job_id AS id,
         RECJOB.recurring_day AS recurring_day,
         RECJOB.recurring_date AS recurring_date,
         RECJOB.recurring_time AS recurring_time,
         RECJOB.recurring_month_date AS recurring_month_date,
         RECJOB.status AS status';
         $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "recurring_jobs AS RECJOB
         where RECJOB.company_id  = '$companyId' AND RECJOB.load_identity  = '$loadidentity' ";
        $record = $this->db->getAllRecords($sql);
        return $record;
      }
      public function getLoadDetail($loadidentity){
       $record = array();
         $sqldata = 'SST.tracking_code AS current_status,SST.isInvoiced,SST.accountkey,SST.grand_total,COUR.cancelation_charge,SST.customer_id,SST.load_identity,SST.status';
         $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment_service AS SST
                LEFT JOIN " . DB_PREFIX . "courier_vs_company AS COUR ON COUR.account_number = SST.accountkey
                #LEFT JOIN " . DB_PREFIX . "courier_vs_company AS COUR ON COUR.id = SST.carrier
                WHERE  SST.load_identity  = '$loadidentity' ";
        $record = $this->db->getRowRecord($sql);
        return $record;
      }
     public function getEligibleCancelCode(){
       $record = array();
         $sqldata = 'code';
         $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipments_master AS SMAS
                 where SMAS.is_used_for_cancel = 'YES' and status = '1'";
         $record = $this->db->getAllRecords($sql);
         return $record;
      }



       public function getShipmentTrackingID($identity){
       $record = array();
         $sqldata = 'S.tracking_id';
         $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment_tracking AS S
                 WHERE S.load_identity = '" . $identity . "'";
         return $this->db->getRowRecord($sql);

      }
      public function getShipmentTrackingDetails($identity){
       $record = array();
         $sqldata = 'S.code as status,S.create_date as event_date';
         $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment_tracking AS S
                 WHERE S.load_identity = '" . $identity . "'";
         $record =  $this->db->getAllRecords($sql);
        return $record;
      }
     public function getShipmentsType($loadidentity){
        $sqldata = 'S.shipment_type,COUR.code,S.booking_date';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipments_view AS S
                LEFT JOIN " . DB_PREFIX . "courier_vs_company AS COMCOUR ON COMCOUR.id = S.carrier
                LEFT JOIN " . DB_PREFIX . "courier AS COUR ON COUR.id = COMCOUR.courier_id
                WHERE S.instaDispatch_loadIdentity  = '" . $loadidentity . "'";
        $record = $this->db->getRowRecord($sql);
        return $record;
       }

    public function checkEligibleforRecurring($identity){
       $record = array();
         $sqldata = 'S.is_recurring';
         $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "shipment_service AS S
                 WHERE S.load_identity = " . $identity . "";
         return $this->db->getRowRecord($sql);

      }
    public function getDropTrackingByLoadIdentity($load_identity){
        //$sql = "SELECT ST.shipment_service_type, ST.shipment_ticket, STT.code, STT.create_date, SMT.name AS code_text FROM " . DB_PREFIX . "shipment_tracking AS STT INNER JOIN " . DB_PREFIX . "shipment AS ST ON STT.load_identity = ST.instaDispatch_loadIdentity INNER JOIN " . DB_PREFIX . "shipments_master AS SMT ON STT.code=SMT.code WHERE STT.load_identity='$load_identity' ORDER BY FIELD(ST.shipment_service_type, 'P','D') "; //ORDER BY STT.id ASC;
        $sql = "SELECT STT.shipment_ticket, STT.code, STT.create_date, SMT.name AS code_text FROM " . DB_PREFIX . "shipment_tracking AS STT INNER JOIN " . DB_PREFIX . "shipments_master AS SMT ON STT.code=SMT.code WHERE STT.load_identity='$load_identity' ORDER BY STT.create_date, FIELD(service_type, 'collection','delivery')";
        $record = $this->db->getAllRecords($sql);
        return  $record;
    }

    public function getShipmentTrackingByLoadIdentity($load_identity){
        $sql = "SELECT ST.shipment_service_type, STT.load_identity, STT.code, STT.create_date, SMT.name AS code_text FROM " . DB_PREFIX . "shipment_tracking AS STT INNER JOIN " . DB_PREFIX . "shipment AS ST ON STT.load_identity = ST.instaDispatch_loadIdentity INNER JOIN " . DB_PREFIX . "shipments_master AS SMT ON STT.code=SMT.code WHERE STT.load_identity='$load_identity' ORDER BY STT.create_date, FIELD(service_type, 'collection','delivery')";
        $record = $this->db->getAllRecords($sql);
        return  $record;
    }

    public function getShipmentInfoByShipmentTicket($shipment_ticket){
        $sql = "SELECT ST.shipment_service_type, ST.instaDispatch_loadGroupTypeCode AS load_type FROM " . DB_PREFIX . "shipment AS ST WHERE shipment_ticket='$shipment_ticket';";
        $record = $this->db->getRowRecord($sql);
        return  $record;
    }

  }
?>
