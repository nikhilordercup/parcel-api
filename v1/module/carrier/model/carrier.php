<?php
    class Carrier_Model
    {
        
        public
        
        static $modelObj = NULL;
        
        public
        
        static $_db = NULL;

        public 

        $db = NULL;
        
        public
        
        function __construct()
        {
            if(self::$_db==NULL)
            {
                self::$_db = new DbHandler();
            }
            $this->db = self::$_db;
        }
        
        public
        
        static function _getInstance()
        {
            if(self::$modelObj==NULL)
            {
                self::$modelObj = new Carrier_Model();
            }
            return self::$modelObj;
        }
        
        public
        
        function saveCustomer($data)
        {
            return $this->db->save("users", $data);
        }
        
        public
        
        function saveCustomerInfo($data)
        {
            return $this->db->save("customer_info", $data);
        }
        
        public
        
        function saveCarrierCustomerFirebaseInfo($data, $condition)
        {
            return $this->db->update("users", $data, $condition);
        }

        /*public
        
        function saveControllerWarehouse($data)
        {
            return $this->db->save("company_warehouse", $data);
        }*/

        public
        
        function saveCompanyWarehouseOfUser($data)
        {
            return $this->db->save("company_users", $data);
        }

    }
?>
