<?php

class ServiceProviderModel
{

    public static $_dbObj = NULL;

    public function __construct(){        
        if (self::$_dbObj == NULL) {
            self::$_dbObj = new DbHandler();
        }
        $this->_db = self::$_dbObj;
    }

    public function getServiceProvider($companyId, $spId = '') {
        $condtn = " WHERE user_id = '$companyId' AND status=1 ";
        ($spId) ? $condtn." AND id='$spId' " : '';
        
        $sql = "SELECT * FROM " . DB_PREFIX . "payment_provider ".$condtn;
        return $this->_db->getRowRecord($sql);
    }

    public function createStripeCustomer($data) {
        $id = $this->_db->save("customer_payment_details", $data);
        return $id;
    }
    
    public function getSPcustomerId($spId, $customer_id) {        
        $sql = "SELECT * FROM " . DB_PREFIX . "customer_payment_details WHERE service_provider_id = '$spId' AND customer_id='$customer_id' AND status=1";
        return $this->_db->getRowRecord($sql);
    }
    
    public function getCustomerCardDetail($spId, $customer_id, $spCustomerId) {        
        $sql = "SELECT * FROM " . DB_PREFIX . "customer_card_details WHERE sp_id = '$spId' AND sp_customer_id='$spCustomerId' AND customer_id='$customer_id' AND status=1";
        return $this->_db->getAllRecords($sql);
    }
       
    public function saveCustomerToken($data)
    {
        $id = $this->_db->save("customer_card_details", $data);
        return $id;
    }    
       
    public function saveCustomerTransaction($data)
    {
        $id = $this->_db->save("customer_transactions", $data);
        return $id;
    }
    
}
?>
