<?php

class Chargebee_Model_Chargebee {

    public static $model_instance = null;
    public static $db = null;

    public static function getInstanse() {
        if (Chargebee_Model_Chargebee::$model_instance == null) {
            Chargebee_Model_Chargebee::$model_instance = new Chargebee_Model_Chargebee();
            Chargebee_Model_Chargebee::$db = new DbHandler();
        }
        return Chargebee_Model_Chargebee::$model_instance;
    }

    private function _db() {
        return Chargebee_Model_Chargebee::$db;
    }

    public function savePlan($param) {
        //save record to table
        $plan_id = $this->_db()->save("chargebee_plan", $param);
        return $plan_id;
    }

    public function listPlan() {
        //list all plan
        $plan_id = $this->_db()->getAllRecords("SELECT * FROM " . DB_PREFIX . "chargebee_plan");
        return $plan_id;
    }

    public function getPlanById($param) {
        //list all plan
        $record = $this->_db()->getRowRecord("SELECT * FROM " . DB_PREFIX . "chargebee_plan WHERE plan_id = '" . $param->plan_id . "'");
        return $record;
    }

    public function updatePlan($param, $plan_id) {
        //save record to table
        $plan_id = $this->_db()->update("chargebee_plan", $param, "plan_id='$plan_id'");
        return $plan_id;
    }

    public function listSubscription() {
        //list all plan
        $records = $this->_db()->getAllRecords("SELECT * FROM " . DB_PREFIX . "chargebee_subscription");
        return $records;
    }

    public function saveSubscription($param) {
        //save record to table
        $plan_id = $this->_db()->save("chargebee_subscription", $param);
        return $plan_id;
    }

    public function getSubscriptionById($param) {
        //list all plan
        $record = $this->_db()->getRowRecord("SELECT * FROM " . DB_PREFIX . "chargebee_subscription WHERE chargebee_subscription_id = '" . $param->chargebee_subscription_id . "'");
        return $record;
    }

    public function updateSubscription($param, $subscription_id) {
        //save record to table
        $plan_id = $this->_db()->update("chargebee_subscription", $param, "chargebee_subscription_id='$subscription_id'");
        return $plan_id;
    }

    public function saveCustomer($param) {
        //save record to table
        $customer_id = $this->_db()->save("chargebee_customer", $param);
        return $customer_id;
    }

    public function getChargebeeCustomerById($param) {
        //list all plan
        $record = $this->_db()->getRowRecord("SELECT * FROM " . DB_PREFIX . "chargebee_customer WHERE chargebee_customer_id = '" . $param->chargebee_customer_id . "'");
        return $record;
    }

    public function updateCustomer($param, $chargebee_customer_id) {
        //save record to table
        $plan_id = $this->_db()->update("chargebee_customer", $param, "chargebee_customer_id='$chargebee_customer_id'");
        return $plan_id;
    }

    public function listCustomer() {
        //list all plan
        $records = $this->_db()->getAllRecords("SELECT * FROM " . DB_PREFIX . "chargebee_customer");
        return $records;
    }

    public function listAllCustomerForSubscription() {
        //list all plan
        $records = $this->_db()->getAllRecords("SELECT chargebee_customer_id, CONCAT(billing_first_name,' ',billing_last_name) AS customer_full_name FROM " . DB_PREFIX . "chargebee_customer");
        return $records;
    }

    public function listAllPlanForSubscription() {
        //list all plan
        $records = $this->_db()->getAllRecords("SELECT plan_id, plan_name FROM " . DB_PREFIX . "chargebee_plan WHERE status='active'");
        return $records;
    }

    public function getPlanDetailForSubscription($plan_id) {
        //list all plan
        $records = $this->_db()->getRowRecord("SELECT `plan_id`, `plan_name`, `period`, `period_unit`, `trial_period`, `trial_period_unit`, `price` FROM " . DB_PREFIX . "chargebee_plan WHERE status='active' AND plan_id='$plan_id'");
        return $records;
    }

    public function getCustomerById($user_id) {
        //list all plan
        $record = $this->_db()->getRowRecord("SELECT * FROM " . DB_PREFIX . "chargebee_customer WHERE user_id = '" . $user_id . "'");
        return $record;
    }

    public function saveCard($param) {
        //save record to table
        $id = $this->_db()->save("chargebee_customer_card", $param);
        return $id;
    }

    public function getCustomerPlanById($chargebee_customer_id) {
        //list all plan
        $record = $this->_db()->getRowRecord("SELECT * FROM " . DB_PREFIX . "chargebee_plan AS PT INNER JOIN " . DB_PREFIX . "chargebee_subscription AS ST ON PT.plan_id=ST.plan_id WHERE ST.chargebee_customer_id = '$chargebee_customer_id'");
        return $record;
    }

    public function updateCustomerNextBillingDate($param) {
        //list all plan
        return $this->_db()->update("chargebee_subscription", array("trial_end" => $param->trial_end), "chargebee_customer_id='$param->chargebee_customer_id'");
    }

    public function getCustomerCurrentPlan($param) {
        //list all plan
        $record = $this->_db()->getRowRecord("SELECT T1.*, T2.next_billing_date, T2.chargebee_subscription_id, T2.chargebee_customer_id, T2.status AS subscription_status  FROM `" . DB_PREFIX . "chargebee_plan` AS T1 INNER JOIN `" . DB_PREFIX . "chargebee_subscription` AS T2 ON T1.plan_id=T2.plan_id INNER JOIN `" . DB_PREFIX . "chargebee_customer` AS T3 ON T3.chargebee_customer_id = T2.chargebee_customer_id INNER JOIN `" . DB_PREFIX . "users` AS T4 ON T3.user_id = T4.id WHERE T4.email='$param->email'");
        return $record;
    }

    public function updateBillingInfo($userId, $chargeBeeId) {
        try {
            $savedCard = ChargeBee_Customer::retrieve($chargeBeeId);
            $address = $savedCard->customer()->billingAddress;
            $billingAddressInfo = array(
                'user_id' => $userId,
                'name' => $address->firstName,
                'company' => $address->company,
                'address_one' => $address->line1,
                'address_two' => $address->line2,
                'city' => $address->city,
                'state' => $address->state,
                'zip' => $address->zip,
                'country' => $address->country,
            );
            $exist = $this->_db()->getOneRecord("SELECT * FROM " . DB_PREFIX . "billing_addresses WHERE user_id=" . $userId);
            if ($exist) {
                return $this->_db()->update("billing_addresses", $billingAddressInfo, "user_id=" . $userId);
            } else {
                return $this->_db()->save("billing_addresses", $billingAddressInfo);
            }
        } catch (Exception $ex) {
            return array('error' => TRUE, 'error_message' => $ex->getMessage());
        }
    }

}