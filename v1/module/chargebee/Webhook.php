<?php
require_once("model/Chargebee.php");
class Module_Chargebee_Webhook{
	
	public function __construct(){
		$this->modelObj = Chargebee_Model_Chargebee::getInstanse();	
	}
	
	public function consume($params){
		switch($params->event_type){
			case "subscription_activated":
				$this->_subscription_activated($params);
			break;
			
			case "subscription_reactivated":
				$this->_subscription_activated($params);
			break;
			
			case "subscription_deleted":
				$this->_subscription_deleted($params);
			break;
			
			case "subscription_renewed":
				$this->_subscription_activated($params);
			break;
			
			case "subscription_cancelled":
				$this->_subscription_cancelled($params);
			break;
			
			case "subscription_trial_end_reminder":
				$this->_subscription_trial_end_reminder($params);
			break;
			
			case "payment_succeeded":
				$this->_payment_succeeded($params);
			break;
			
			case "payment_failed":
				$this->_payment_failed($params);
			break;
		}
	}
	
	private function _subscription_activated($params){
		$next_billing_date = date("Y-m-d 00:00:00", $params->content->subscription->next_billing_at);
		$data = array("trial_end"=>$next_billing_date, "status"=>"subscription_activated", "update_date"=>date("Y-m-d h:i:s"));
		$subscription_id = $params->content->subscription->id; 
		$this->modelObj->updateSubscription($data, $subscription_id);
	}

	private function _subscription_deleted($params){
		$data = array("status"=>"subscription_deleted", "update_date"=>"Y-m-d h:i:s");
		$subscription_id = $params->content->subscription->id; 
		
		$this->modelObj->updateSubscription($data, $subscription_id);
	}
	
	/*private function _subscription_renewed($params){
		
	}*/
	
	private function _subscription_cancelled($params){
		$data = array("status"=>"subscription_cancelled", "update_date"=>"Y-m-d h:i:s");
		$subscription_id = $params->content->subscription->id;
		$this->modelObj->updateSubscription($data, $subscription_id);
	}
	
	private function _subscription_trial_end_reminder($params){
		$data = array("status"=>"subscription_trial_end_reminder", "update_date"=>"Y-m-d h:i:s");
		$subscription_id = $params->content->subscription->id;
		$this->modelObj->updateSubscription($data, $subscription_id);
	}
	
	private function _payment_succeeded($params){
		$data = array("payment_status"=>"payment_succeeded", "payment_counter"=>"0");
		$subscription_id = $params->content->subscription->id;
		$this->modelObj->updateSubscription($data, $subscription_id);
	}
	
	private function _payment_failed($params){
		$data = array("payment_status"=>"payment_failed", "payment_counter"=>"payment_counter+1");
		$subscription_id = $params->content->subscription->id;
		$this->modelObj->updateSubscription($data, $subscription_id);
	}
}
?>