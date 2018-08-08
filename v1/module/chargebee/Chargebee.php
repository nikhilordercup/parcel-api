<?php
require_once("model/Chargebee.php");

class Module_Chargebee extends Icargo
	{
	private $_varifyChargeBee = true;
	
	public function __construct($data)
		{
		if(isset($data->verifyChargeBee)){
			$this->_varifyChargeBee = $data->verifyChargeBee;
			$this->_parentObj = parent::__construct(array("email"=>$data->email, "access_token"=>$data->access_token, "verifyChargeBee"=>$this->_varifyChargeBee));
		}
		
		$this->modelObj = Chargebee_Model_Chargebee::getInstanse();
		$this->_chargeAPiInvironment();
		}
	
	private
	
	function _chargeAPiInvironment()
		{
		ChargeBee_Environment::configure("instadispatch-test", "test_SXcdH4OWVOcd91fCcuYr2UYKhYnFJPfEFZ6");
                
		}
	
	private
	
	function _createPlan($param)
		{
		try
			{
			$result = ChargeBee_Plan::create(array(
				"id" => $param["plan_id"], 
				"name" => $param["plan_name"], 
				"invoiceName" => $param["invoice_name"],
				"description" => $param["description"],
				"price" => $param["price"] * 100,
				"currencyCode"=> $param["currency_code"],
				"period"=> $param["period"],
				"periodUnit"=> $param["period_unit"],
				"trialPeriod"=> $param["trial_period"],
				"trialPeriodUnit"=> $param["trial_period_unit"],
				"freeQuantity"=> $param["free_quantity"],
				"status"=> $param["status"],
				"billingCycles"=> $param["billing_cycle"],
				"invoiceNotes"=> $param["invoice_notes"]
			));
			$plan = $result->plan();
			return $plan;
			}catch(Exeption $e){
				throw new Exception($e->getMessage());
			}
		}
	
	private
	
	function _updatePlan($param)
		{
		try
			{
			$result = ChargeBee_Plan::update($param->plan_id,array(
				"name" => $param->plan_name, 
				"invoiceName" => $param->invoice_name,
				"price" => $param->price * 100,
				"trialPeriod"=> $param->trial_period,
				"trialPeriodUnit"=> $param->trial_period_unit,
			));
			$plan = $result->plan();
			return $plan;
			}catch(Exeption $e){
				throw new Exception($e->getMessage());
			}
		}
	
	private
	
	function _createSubscription($param)
		{
		try
			{
			$data = array(
				"planId" => $param["plan_id"],
				"startDate" => $param["start_date"],
				"trialEnd" => $param["trial_end"]
				);
			if(isset($param["auto_collection"])){
				$data["auto_collection"] = $param["auto_collection"];
			}
			if(isset($param["terms_to_charge"])){
				$data["terms_to_charge"] = $param["terms_to_charge"];
			}
			if(isset($param["invoice_notes"])){
				$data["invoice_notes"] = $param["invoice_notes"];
			}
			if(isset($param["invoice_immediately"])){
				$data["invoice_immediately"] = $param["invoice_immediately"];
			}
			
			$result = ChargeBee_Subscription::createForCustomer($param["customer_id"],$data);
			$subscription = $result->subscription()->getValues();
			$data = array("subscription"=>$subscription);
			return $data;
			}catch(Exeption $e){
				throw new Exception($e->getMessage());
			}
		}
	
	private
	
	function _updateSubscription($param, $subscription_id)
		{
		try
			{
			$data = array(
				"planId" => $param["plan_id"],
				);
			if(isset($param["plan_unit_price"]))
				{
				$data['planUnitPrice'] = $param["plan_unit_price"]*100;
				}
			if(isset($param["invoice_immediately"]))
				{
				$data['invoiceImmediately'] = $param["invoice_immediately"];
				}
			if(isset($param["billing_cycles"]))
				{
				$data['billingCycles'] = $param["billing_cycles"];
				}
			if(isset($param["prorata"]))
				{
				$data['prorata'] = $param["prorata"];
				}
			
			/*$result = ChargeBee_Subscription::update($subscription_id, array(
				"planId" => $param["plan_id"],
				"planUnitPrice" => $param["plan_unit_price"]*100,
				"invoiceImmediately" => $param["invoice_immediately"],
				"billingCycles" => $param["billing_cycles"],
				"prorata" => $param["prorata"]
				)
			);*/
			$result = ChargeBee_Subscription::update($subscription_id, $data);
			
			$subscription = $result->subscription()->getValues();
			$data = array("subscription"=>$subscription);
			return $data;
			}catch(Exeption $e){
				throw new Exception($e->getMessage());
			}
		}
	
	private
	
	function _createCustomer($param)
		{   
		$result = ChargeBee_Customer::create(array(
			"firstName" => $param["first_name"],
			"lastName" => $param["last_name"],
			"email" => $param["email"],
			"billingAddress" => array(
			"firstName" => $param["billing_first_name"],
			"lastName" => $param["billing_last_name"],
                            "company"=>$param["company"],
                            "phone"=>$param["phone"],
                            "email"=>$param["email"],
			"line1" => $param["billing_line1"],
			"city" => $param["billing_city"],
			"state" => $param["billing_state"],
			"zip" => $param["billing_zip"],
			"country" => $param["billing_country"],
			))
		);
	
		$customer = $result->customer();
		return array("chargebee_customer_id"=>$customer->id,"first_name"=>$customer->firstName,"last_name"=>$customer->lastName,"auto_collection"=>$customer->autoCollection,"net_term_days"=>$customer->netTermDays,"preferred_currency_code"=>$customer->preferredCurrencyCode,"billing_address"=>array("first_name"=>$customer->billingAddress->firstName,"last_name"=>$customer->billingAddress->lastName,"line1"=>$customer->billingAddress->line1,"city"=>$customer->billingAddress->city,"state"=>$customer->billingAddress->state,"country"=>$customer->billingAddress->country,"zip"=>$customer->billingAddress->zip,"validation_status"=>$customer->billingAddress->validationStatus));
		}
	
	private
	
	function _updateCustomer($param, $chargebee_customer_id)
		{
		$result = ChargeBee_Customer::update($chargebee_customer_id,array(
			"firstName" => $param["first_name"],
			"lastName" => $param["last_name"],
			"email" => $param["email"],
			"billingAddress" => array(
			"firstName" => $param["billing_first_name"],
			"lastName" => $param["billing_last_name"],
			"line1" => $param["billing_line1"],
			"city" => $param["billing_city"],
			"state" => $param["billing_state"],
			"zip" => $param["billing_zip"],
			"country" => $param["billing_country"],
			))
		);
	
		$customer = $result->customer();
		return array("chargebee_customer_id"=>$customer->id,"first_name"=>$customer->firstName,"last_name"=>$customer->lastName,"auto_collection"=>$customer->autoCollection,"net_term_days"=>$customer->netTermDays,"preferred_currency_code"=>$customer->preferredCurrencyCode,"billing_address"=>array("first_name"=>$customer->billingAddress->firstName,"last_name"=>$customer->billingAddress->lastName,"line1"=>$customer->billingAddress->line1,"city"=>$customer->billingAddress->city,"state"=>$customer->billingAddress->state,"country"=>$customer->billingAddress->country,"zip"=>$customer->billingAddress->zip,"validation_status"=>$customer->billingAddress->validationStatus));
		}
		
	private
	
	function _saveCustomerCard($param, $customer_id)
		{
		try
			{
			$result = ChargeBee_Card::updateCardForCustomer($customer_id,array(
				"firstName" => $param["card_first_name"],
				"lastName" => $param["card_last_name"],
				"number" => $param["card_number"], //credit card number
				"expiryMonth" => $param["card_exp_month"],
				"expiryYear" => $param["card_exp_year"],
				"cvv" => $param["security_code"],
				"billingAddr1" => $param["billing_address_line1"],
				"billingAddr2" => $param["billing_address_line2"],
				"billingCity" => $param["billing_city"],
				"billingState" => $param["billing_state"],
				"billingZip" => $param["billing_postcode"],
				"billingCountry" => $param["billing_country"],
				)
			);
			$card = $result->card()->getValues();
			$data = array("card"=>$card);
			return $data;
			}catch(Exception $e){
				throw new Exception($e->getMessage());
			}
			
		
		}
	
	public
	
	function createPlan($param)
		{
		//save info to database
		$plan_id = strtolower(str_replace(" ","-",$param->plan_name));
		$data =array(
			"plan_name"=>$param->plan_name,
			"plan_id"=>$plan_id,
			"invoice_name"=>$param->invoice_name,
			"invoice_notes"=>$param->invoice_notes,
			"description"=>$param->description,
			"price"=>$param->price,
			"currency_code"=>$param->currency_code,
			"period"=>$param->period,
			"period_unit"=>$param->period_unit,
			"trial_period"=>$param->trial_period,
			"trial_period_unit"=>$param->trial_period_unit,
			"free_quantity"=>0,//$param->free_quantity,
			"billing_cycle"=>$param->billing_cycle,
			"controller_count"=>$param->controller_count,
			"warehouse_count"=>$param->warehouse_count,
			"driver_count"=>$param->driver_count,
			"status"=>$param->status
			);
		//
		//request to api
		try
			{
			$response = $this->_createPlan($data);
			$id = $this->modelObj->savePlan($data);
			return array("status"=>"success","message"=>"Plan created successfully");
			}catch(Exeption $e){
				return array("status"=>"error","message"=>"Plan not created");
			}
		}
	
	public
	
	function listPlan()
		{
		$record = $this->modelObj->listPlan();
		return array("records"=>$record);
		}
	
	public
	
	function getPlanById($param)
		{
		$record = $this->modelObj->getPlanById($param);
		return array("records"=>$record);
		}
	
	public
	
	function updatePlan($param)
		{
		$data =array(
				"plan_name"=>$param->plan_name,
				"invoice_name"=>$param->invoice_name,
				"price"=>$param->price,
				"trial_period"=>$param->trial_period,
				"trial_period_unit"=>$param->trial_period_unit
			);
		//request to api
		try
			{
			$response = $this->_updatePlan($param);
			$this->modelObj->updatePlan($data, $param->plan_id);
			return array("status"=>"success","message"=>"Plan updated successfully");
			} catch(Exeption $e){
				return array("status"=>"error","message"=>"Plan not created");
			}
		}
	
	public
	
	function getSubscriptionById($param)
		{
		$record = $this->modelObj->getSubscriptionById($param);
		return array("records"=>$record);
		}
	
	public
	
	function listSubscription()
		{
		$record = $this->modelObj->listSubscription();
		return array("records"=>$record);
		}
	
	public
	
	function createSubscription($param)
		{
		try
			{
			$data = array(
				"plan_id"=>$param->plan_id,
				"plan_quantity"=>$param->plan_quantity,
				"customer_id"=>$param->customer_id,
				"plan_unit_price"=>$param->plan_unit_price,
				"start_date"=>strtotime($param->start_date),
				"trial_end"=>strtotime($param->trial_end),
				"billing_cycles"=>$param->billing_cycles,
			);
			if(isset($param->auto_collection)){
				$data['auto_collection'] = $param->auto_collection;
			}
			if(isset($param->terms_to_charge)){
				$data['terms_to_charge'] = $param->terms_to_charge;
			}
			if(isset($param->invoice_notes)){
				$data['invoice_notes'] = $param->invoice_notes;
			}
			if(isset($param->invoice_immediately)){
				$data['invoice_immediately'] = $param->invoice_immediately;
			}
			
			$subscriptionData = $this->_createSubscription($data);
			
			$data["chargebee_subscription_id"] = $subscriptionData["subscription"]["id"];
			$data["chargebee_customer_id"] = $param->customer_id;
			
			$data["trial_end"] = date("Y-m-d 00:00:00", $subscriptionData["subscription"]["trial_end"]);
			$data["next_billing_date"] = date("Y-m-d 00:00:00", $subscriptionData["subscription"]["next_billing_at"]);
			
			$data["plan_quantity"] = $subscriptionData["subscription"]["plan_quantity"];
			$data["plan_unit_price"] = $subscriptionData["subscription"]["plan_unit_price"]/100;
			$data["status"] = $subscriptionData["subscription"]["status"];
			
			//$data["billing_cycles"] = $subscriptionData["subscription"]["billing_period"];
			
			
			$data["start_date"] = $param->start_date;
			//$data["trial_end"] = $param->trial_end;
			unset($data['customer_id']);
			//print_r($data);die;
			$id = $this->modelObj->saveSubscription($data);
			return $response = array("status"=>"success","message"=>"Subscription created successfully");
			}
			catch(Exception $e){
				return array("status"=>"error","message"=>$e->getMessage());
			}
		}
	
	public
	
	function editSubscription($param)
		{
		try
			{
			$data = array(
				"plan_id"=>$param->plan_id,
				"plan_unit_price"=>$param->plan_unit_price,
				"billing_cycles"=>$param->billing_cycles,
				"invoice_immediately"=>$param->invoice_immediately,
				"prorata"=>$param->prorata
			);
			$subscriptionData = $this->_updateSubscription($data,$param->subscription_id);
			$id = $this->modelObj->updateSubscription($data,$param->subscription_id);
			return $response = array("status"=>"success","message"=>"Subscription updated successfully");
			}
			catch(Exception $e){
				return array("status"=>"error","message"=>$e->getMessage());
			}
		}
	
	public
	
	function createCustomer($param)
		{
		try
			{
			$data = array(
				"first_name"=>$param->first_name,
				"last_name"=>$param->last_name,
				"email"=>$param->customer_email,
				"billing_first_name"=>$param->billing_first_name,
				"billing_last_name"=>$param->billing_last_name,
				"billing_line1"=>$param->billing_line1,
				"billing_city"=>$param->billing_city,
				"billing_state"=>$param->billing_state,
				"billing_zip"=>$param->billing_zip,
				"billing_country"=>$param->billing_country,
                                "user_id"=>$param->user_id,
                                "company"=>$param->first_name,
                                "phone"=>$param->phone,
			);

			$customer_info = $this->_createCustomer($data);
			
			$data["chargebee_customer_id"] = $customer_info['chargebee_customer_id'];

			$this->modelObj->saveCustomer($data);
			$response = array("status"=>"success","message"=>"Customer created successfully","customer_info"=>$customer_info);
			return $response;
			}
			catch(Exception $e){print_r($e->getMessage());die;
				$response = array("status"=>"error","message"=>$e->getMessage());
				return $response;
			}
		}
	
	public
	
	function getChargebeeCustomerById($param)
		{
		$record = $this->modelObj->getChargebeeCustomerById($param);
		$record['customer_email'] = $record['email'];
		return array("records"=>$record);
		}
	
	public
	
	function editCustomer($param)
		{
		try
			{
			$data = array(
				"first_name"=>$param->first_name,
				"last_name"=>$param->last_name,
				"email"=>$param->customer_email,
				"billing_first_name"=>$param->billing_first_name,
				"billing_last_name"=>$param->billing_last_name,
				"billing_line1"=>$param->billing_line1,
				"billing_city"=>$param->billing_city,
				"billing_state"=>$param->billing_state,
				"billing_zip"=>$param->billing_zip,
				"billing_country"=>$param->billing_country
			);

			$customer_info = $this->_updateCustomer($data, $param->chargebee_customer_id);
			
			$this->modelObj->updateCustomer($data, $param->chargebee_customer_id);
			$response = array("status"=>"success","message"=>"Customer updated successfully");
			return $response;
			}
			catch(Exception $e){
				$response = array("status"=>"error","message"=>$e->getMessage());
				return $response;
			}
		}
	
	public
	
	function listCustomer()
		{
		$record = $this->modelObj->listCustomer();
		return array("records"=>$record);
		}
	
	public
	
	function listAllCustomerAndPlanForSubscription()
		{
		//list lcustomer
		$customers = $this->modelObj->listAllCustomerForSubscription();
		$plans = $this->modelObj->listAllPlanForSubscription();
		return array("customers"=>$customers,"plans"=>$plans);
		}
		
	public
	
	function getPlanDetailForSubscription($param)
		{
		//list lcustomer
		$planData = $this->modelObj->getPlanDetailForSubscription($param->plan_id);
		
		if(strtolower($planData['trial_period_unit'])=="month")
			$day = $planData['trial_period']*30;
		else if(strtolower($planData['trial_period_unit'])=="day")
			$day = $planData['trial_period'];

		$planData["trial_end_date"] = date("Y-m-d", strtotime($param->start_date . " + $day days"));
		return $planData;
		}
		
	public
	
	function saveCard($param)
		{
		try
			{
			$customerData = $this->modelObj->getCustomerById($param->user_id);
			$chargeBeeCustomerId = "1mkVvkqQbo0wVTgXF";//$customerData["chargebee_customer_id"]
			$data = array(
				"card_number" =>$param->card_number,
				"card_first_name" =>$param->card_first_name,
				"card_last_name" =>$param->card_last_name,
				"card_exp_month" =>$param->card_exp_month,
				"card_exp_year" =>$param->card_exp_year,
				"security_code" =>$param->security_code,
				"billing_address_line1" =>$param->billing_address_line1,
				"billing_address_line2" =>$param->billing_address_line2,
				"billing_city" =>$param->billing_city,
				"billing_country" =>$param->billing_country,
				"billing_state" =>$param->billing_state,
				"billing_postcode" =>$param->billing_postcode
			);
			$data = $this->_saveCustomerCard($data, $chargeBeeCustomerId);
			
			// get customer plan
			$planData = $this->modelObj->getCustomerPlanById($chargeBeeCustomerId);
			
			$days = 0;
			if($planData["period_unit"]=="month"){
				$days = $planData["period"] * 30;
			}
			
			if($planData["period_unit"]=="days"){
				$days = $planData["period"];
			}
			$next_billing_date = date("Y-m-d", strtotime(date("Y-m-d"). "+$days days"));
			
			// update user subscription. next billing date
			$this->modelObj->updateCustomerNextBillingDate((object)array("chargebee_customer_id"=>$chargeBeeCustomerId,"trial_end"=>$next_billing_date));
			
			$data["card"]["user_id"] = $param->user_id;
			$id = $this->modelObj->saveCard($data["card"]);
			
			return $response = array("status"=>"success","message"=>"Payment Method created successfully");
			
			}
			catch(Exception $e){
				print_r($e->getMessage());die;
			}
		}
		
	public
	
	function getCustomerCurrentPlan($param)
		{
		$customerPlan = $this->modelObj->getCustomerCurrentPlan($param);
		$allPlan = $this->modelObj->listAllPlanForSubscription();
		return array("plans"=>$allPlan,"customer_plan"=>$customerPlan);
		}
		
	public
	
	function upgradeCustomerPlan($param)
		{
		try
			{
			$customerPlan = $this->modelObj->getCustomerCurrentPlan($param);
			$subscription = $this->_updateSubscription(array("plan_id"=>$param->plan_id), $customerPlan["chargebee_subscription_id"]);
			$this->modelObj->updateSubscription(array("plan_id"=>$subscription["subscription"]["plan_id"],"next_billing_date"=>date("Y-m-d 00:00:00",$subscription["subscription"]["next_billing_at"]),"update_date"=>date("Y-m-d 00:00:00",$subscription["subscription"]["updated_at"])), $customerPlan["chargebee_subscription_id"]);
			$customerPlan = $this->modelObj->getCustomerCurrentPlan($param);
			$allPlan = $this->modelObj->listAllPlanForSubscription();
			return array("status"=>"success","message"=>"Subscription updated successfully","customer_plan"=>$customerPlan,"plans"=>$allPlan);
			} catch(Exeption $e){
				return array("status"=>"error","message"=>"Subscription not updated");
			}
		}
	}
?>