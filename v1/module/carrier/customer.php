<?php
    require_once "model/carrier.php";
    class Module_Carrier_Customer extends Icargo{
        private $_user_id;
        protected $_parentObj;
        private $modelObj = NULL;
        private function _setUserId($v){
            $this->_user_id = $v;
        }
        
        private function _getUserId(){
            return $this->_user_id;
        }
        
        public function __construct($data){
            $this->_parentObj = parent::__construct(array("email"=>$data->email, "access_token"=>$data->access_token));
            $this->modelObj = Carrier_Model::_getInstance();
        }
        
        public function saveCustomer($param){
            $data = array(
                "user_level"=>5,
                "name"=>$param->name,
                "contact_name"=>"N/A",
                "phone"=>$param->phone,
                "email"=>$param->customer_email,
                "password"=>passwordHash::hash($param->password),
                "address_1"=>$param->address_1,
                "address_2"=>$param->address_2,
                "postcode"=>$param->postcode,
                "city"=>$param->city,
                "state"=>$param->state,
                "country"=>$param->country,
                "status"=>"1",
                "uid"=>"not set",
                "register_in_firebase"=>"0",
                "email_verified"=>"1",
                "access_token"=>"",
                "free_trial_expiry"=>"1970-01-01 00:00:00",
                "parent_id"=>$param->parent_id,
            );
            $user_id = $this->modelObj->saveCustomer($data);

            $this->modelObj->saveCompanyWarehouseOfUser(array('user_id'=>$user_id,'company_id'=>$param->company_id,'warehouse_id'=>$param->warehouse_id,'status'=>"1",'update_date'=>date("Y-m-d h:i:s", strtotime('now'))));

            $custInfo = array(
                "billing_full_name"=>$param->name,
                "billing_address_1"=>$param->billing_address_1,
                "billing_address_2"=>$param->billing_address_2,
                "billing_postcode"=>$param->billing_postcode,
                "billing_city"=>$param->billing_city,
                "billing_state"=>$param->billing_state,
                "billing_country"=>$param->billing_country,
                "billing_phone"=>$param->phone,
                "ccf"=>$param->ccf,
                "apply_ccf"=>1,
                "user_id"=>$user_id
            );
            $customerInfo = $this->modelObj->saveCustomerInfo($custInfo);
            if($customerInfo){
                return array("status"=>"success","message"=>"Customer added successfully","user_id"=>$user_id);
            }
            return array("status"=>"error","message"=>"Customer not added");
        }
        
        public
        
        function saveCarrierCustomerFirebaseInfo($param){
            $data = array("uid"=>$param->uid,"register_in_firebase"=>1);
            $infoStatus = $this->modelObj->saveCarrierCustomerFirebaseInfo($data, "id='$param->customer_id'");
            if($infoStatus){
                return array("status"=>"success","message"=>"Customer added successfully");
            }
            return array("status"=>"error","message"=>"Customer added. But firebase updateion failed");
        }
    }
    ?>
