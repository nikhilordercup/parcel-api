<?php
require_once "Providers/Auth_Provider.php";
require_once("module/authentication/authentication.php");
class User_Management{
    public static $authProviderObj= null;

    public function __construct(){
        if(self::$authProviderObj===null){
            self::$authProviderObj = new Auth_Provider();
        }
        $this->authProvider = self::$authProviderObj;
		$this->modelObj  = Model::getInstanse();
    }
    
	public function customerLogin($email,$password){
		$r = array("email"=>$email,"password"=>$password);
		$r = (object)$r;
		$param = array("auth"=>$r);
		$obj = new Authentication((object)$param);
		$response = $obj->process();
        return $response;
	}
	
	
    public function createApiCustomer($param,$signupData){
		$data = array();
		$signupData = (array)$signupData;
		$param->uid = $signupData['uid'];
		$param->password = $signupData['passwordHash'];
		//$param->country = 'United Kingdom';
         $exist = $this->modelObj->checkCustomerEmailExist($param->email);
         if($exist >0){ 
			return array("status"=>"error","message"=>"Customer Email Already Exist ");
		 }
            $data = array("user_level"=>5,"name"=>$param->name,"contact_name"=>"N/A","phone"=>isset($param->phone)?$param->phone:'',"email"=>$param->email,
							"password"=>$param->password,"postcode"=>'',"city"=>'',"state"=>'',"country"=>$param->country,
							"status"=>"1","uid"=>$param->uid,"register_in_firebase"=>"1","email_verified"=>"1","access_token"=>"","free_trial_expiry"=>"1970-01-01 00:00:00","parent_id"=>$param->company_id,"is_default"=>1);
				
			$customer_id = $this->modelObj->addContent('users',$data);
			if($customer_id){
				$this->modelObj->addContent('company_warehouse',array('company_id'=>$customer_id,'warehouse_id'=>$param->warehouse_id,'status'=>"1",'update_date'=>date("Y-m-d h:i:s", strtotime('now'))));

				$this->modelObj->addContent('company_users',array('user_id'=>$customer_id,'company_id'=>$param->company_id,'warehouse_id'=>$param->warehouse_id,'status'=>"1",'update_date'=>date("Y-m-d h:i:s", strtotime('now'))));
				
				$customerinfo  = array();
				$customerinfo['ccf'] = 0.00;
				$customerinfo['ccf_operator_service'] = 'NONE';
				$customerinfo['ccf_operator_surcharge'] = 'NONE';
				$customerinfo['apply_ccf'] = "1";
				$customerinfo['user_id'] = $customer_id;
				$customerinfo['surcharge'] = 0.00;
				$customerinfo['address_id'] = "0";
				$customerinfo['customer_type'] = 'PREPAID';
				$customerinfo['accountnumber'] = $this->generateCustomerAccount($param->companyname,$customer_id);
				$customerinfo['vatnumber']    = '';
				$customerinfo['creditlimit'] = 0;
				$customerinfo['available_credit'] = 0.00;
				$customerinfo['invoicecycle'] = 30;
				$customerinfo['charge_from_base'] = 'YES';
				$customerinfo['tax_exempt'] = 'NO';


				$this->modelObj->addContent('customer_info',$customerinfo);

				 $creditbalanceData = array();
				 $creditbalanceData['customer_id'] = $customer_id;
				 $creditbalanceData['customer_type'] = $customerinfo['customer_type'];
				 $creditbalanceData['company_id'] = $param->company_id;
				 $creditbalanceData['payment_type'] = 'CREDIT';
				 $creditbalanceData['amount'] = $customerinfo['available_credit'];
				 $creditbalanceData['balance'] = $customerinfo['available_credit'];
				 $creditbalanceData['create_date'] = date("Y-m-d");
				 $creditbalanceData['payment_reference'] = NULL;
				 $creditbalanceData['payment_desc'] = 'Customer Registration Credit Balance';
				 $creditbalanceData['payment_for'] = 'RECHARGE';
				 $this->modelObj->addContent('accountbalancehistory',$creditbalanceData);

				 $customerAccountData   = $this->saveCustomerAccount($param->company_id,$customer_id);
				 
				 if(key_exists('customerpickup',$param) && is_object($param->customerpickup)){
					 //get country id by iso code
					 
					 $country = $param->customerpickup->country;
					 $countrycode = $param->customerpickup->countrycode;
					 
					 $param->customerpickup->country = new stdClass();
					 $param->customerpickup->country->short_name = $country;
					 $param->customerpickup->country->alpha3_code = $countrycode;
					 
					 $param->customerpickup->customer_id = $customer_id;
					 $this->saveCarrierCustomerPickupInfo($param->customerpickup);
				 }
				 if(key_exists('customerbilling',$param) && is_object($param->customerbilling)){
					 $country = $param->customerbilling->country;
					 $countrycode = $param->customerbilling->countrycode;
					 
					 $param->customerbilling->country = new stdClass();
					 $param->customerbilling->country->short_name = $country;
					 $param->customerbilling->country->alpha3_code = $countrycode;
					 
					  $param->customerbilling->customer_id = $customer_id;
					 $this->saveCarrierCustomerBillingInfo($param->customerbilling);
				 }
				 
				 return array("status"=>"success","message"=>"Signup successful, Please login with entered email and password!");				 
			}
			else{
				return array("status"=>"error","message"=>"Signup failed, Please try again");
			}           
	}
	
	public function saveCustomerAccount($company_id,$customerId){
      $allAccount =   $this->modelObj->getAllAccountOfCompany($company_id);
       if(count($allAccount)>0){
          foreach($allAccount as $key=>$valdata){
                  $data = array();
                  $data['status'] = 1; 
                  $data['company_id'] = $company_id; 
                  $data['company_courier_account_id']   = $valdata['courier_account_id']; 
                  $data['account_number']               = $valdata['account_number']; 
                  $data['courier_id']                   = $valdata['courier_id']; 
                  $data['customer_id'] = $customerId; 
                  $data['create_date'] = date('Y-m-d'); 
                  $data['created_by'] = $company_id;
                  $satatusId = $this->modelObj->addContent('courier_vs_company_vs_customer',$data);
                  $allServices =   $this->modelObj->getAllAccountServices($company_id,$valdata['courier_account_id']);
                    if(count($allServices)>0){
                        foreach($allServices as $skey=>$svaldata){
                              $sdata = array();
                              $sdata['status'] = 1; 
                              $sdata['company_id'] = $company_id; 
                              $sdata['company_customer_id'] = $customerId; 
                              $sdata['courier_id'] = $valdata['courier_account_id']; 
                              $sdata['company_service_id'] = $svaldata['id']; 
                              $sdata['service_id'] = $svaldata['service_id']; 
                              $sdata['create_date'] = date('Y-m-d'); 
                              $sdata['create_by'] = $company_id;
                              $satatusId = $this->modelObj->addContent('company_vs_customer_vs_services',$sdata);
                        }
                    }
               
                $allSurcharges =   $this->modelObj->getAllAccountSurcharges($company_id,$valdata['courier_account_id']);
                if(count($allSurcharges)>0){
                        foreach($allSurcharges as $surkey=>$survaldata){
                              $surdata = array();
                              $surdata['status'] = 1; 
                              $surdata['company_id'] = $company_id; 
                              $surdata['company_customer_id'] = $customerId; 
                              $surdata['courier_id'] = $valdata['courier_account_id']; 
                              $surdata['company_surcharge_id'] = $survaldata['id']; 
                              $surdata['surcharge_id'] = $survaldata['surcharge_id']; 
                              $surdata['create_date'] = date('Y-m-d'); 
                              $surdata['create_by'] = $company_id;
                              $satatusId = $this->modelObj->addContent('company_vs_customer_vs_surcharge',$surdata);
                        }
				}
		   }
		} 
	}
	
	public function generateCustomerAccount($companyname,$customerid){
       $str = strtoupper($companyname).$customerid.rand(300,60000);
       $exist = $this->modelObj->checkCustomerAccountExist($str);
        if($exist >0){
             return $this->generateCustomerAccount($companyname,$customerid);
        }
       return $str;

   }
    
	public function saveCarrierCustomerBillingInfo($param){
      $libObj = new Library();
      if(isset($param->postcode) && ($param->postcode!='')){
        $shipment_geo_location =  $libObj->get_lat_long_by_postcode($param->postcode);
      }
      $addressBook = array();
      $addressBook['name']          = isset($param->name)?$param->name:'';
      $addressBook['customer_id']   = $param->customer_id;
      $addressBook['address_line1'] = isset($param->address_1)?$param->address_1:'';
      $addressBook['address_line2'] = isset($param->address_2)?$param->address_2:'';
      $addressBook['postcode']      = isset($param->postcode)?$param->postcode:'';
      $addressBook['city']          = isset($param->city)?$param->city:'';
      $addressBook['state']         = isset($param->state)?$param->state:'';
      $addressBook['country']       = isset($param->country->short_name)?$param->country->short_name:'';
      $addressBook['iso_code']      = isset($param->country->alpha3_code)?$param->country->alpha3_code:'';
      $addressBook['phone']         = isset($param->phone)?$param->phone:'';
      $addressBook['email']         = isset($param->email)?$param->email:'';
      $addressBook['latitude']      = isset($shipment_geo_location["latitude"])?$shipment_geo_location["latitude"]:0.00;
      $addressBook['longitude']     = isset($shipment_geo_location["longitude"])?$shipment_geo_location["longitude"]:0.00;

      $addressBook['search_string'] = $addressBook['address_line1'].$addressBook['address_line2'].$addressBook['postcode'].$addressBook['city'].$addressBook['state'].$addressBook['country'];

      $addressBook['is_default_address'] = "N";
      $addressBook['is_warehouse']  = "N";
      $addressBook['version_id']    = "version_1";
      $addressBook['billing_address'] = "Y";
      $addressBook['pickup_address'] = "N";
      $addressId    = $this->modelObj->addContent("address_book",$addressBook);

	  //save default address by entering data in relation table added by kavita 19april2018
	  $relationData = array("address_id"=>$addressId,"user_id"=>$param->customer_id,"default_address"=>"Y","pickup_address"=>"0","billing_address"=>"1");
	  $relationAddressId = $this->modelObj->addContent("user_address",$relationData);

      $data = array();
      $data['billing_full_name'] =   $addressBook['name'];
      $data['billing_address_1'] =   $addressBook['address_line1'];
      $data['billing_address_2'] =   $addressBook['address_line2'];
      $data['billing_postcode']  =   $addressBook['postcode'];
      $data['billing_city']      =   $addressBook['city'];
      $data['billing_state']     =   $addressBook['state'];
      $data['billing_country']   =   $addressBook['country'];
      $data['billing_phone']     =   $addressBook['phone'];
      $data['billing_email']     =   $addressBook['email'];
      $data['address_id']        =   $addressId;
      $condition = "user_id = '" . $param->customer_id . "'";
      $infoStatus    = $this->modelObj->editContent("customer_info",$data, $condition);
      /* if($infoStatus){
        return array("status"=>"success","message"=>"Customer Billing Address added successfully");
       }
      return array("status"=>"error","message"=>"Customer Billing Address added. But firebase updateion failed"); */
    }
	
	public function saveCarrierCustomerPickupInfo($param){
      $libObj = new Library();
      if(isset($param->postcode) && ($param->postcode!='')){
        $shipment_geo_location =  $libObj->get_lat_long_by_postcode($param->postcode);
      }
      $addressBook = array();
      $addressBook['name']          = isset($param->name)?$param->name:'';
      $addressBook['customer_id']   = $param->customer_id;
      $addressBook['address_line1'] = isset($param->address_1)?$param->address_1:'';
      $addressBook['address_line2'] = isset($param->address_2)?$param->address_2:'';
      $addressBook['postcode']      = isset($param->postcode)?$param->postcode:'';
      $addressBook['city']          = isset($param->city)?$param->city:'';
      $addressBook['state']         = isset($param->state)?$param->state:'';
      $addressBook['country']       = isset($param->country->short_name)?$param->country->short_name:'';
      $addressBook['iso_code']      = isset($param->country->alpha3_code)?$param->country->alpha3_code:'';
      $addressBook['phone']         = isset($param->phone)?$param->phone:'';
      $addressBook['email']         = isset($param->email)?$param->email:'';
      $addressBook['latitude']      = isset($shipment_geo_location["latitude"])?$shipment_geo_location["latitude"]:0.00;
      $addressBook['longitude']     = isset($shipment_geo_location["longitude"])?$shipment_geo_location["longitude"]:0.00;

      $addressBook['search_string'] = $addressBook['address_line1'].$addressBook['address_line2'].$addressBook['postcode'].$addressBook['city'].$addressBook['state'].$addressBook['country'];
      $addressBook['is_default_address'] = "N";
      $addressBook['is_warehouse']  = "N";
      $addressBook['version_id']    = "version_1";
      $addressBook['billing_address'] = "N";
      $addressBook['pickup_address'] = "Y";
      $addressId    = $this->modelObj->addContent("address_book",$addressBook);
        /* if($addressId){
        return array("status"=>"success","message"=>"Customer Pickup Address added successfully");
       }
      return array("status"=>"error","message"=>"Customer Pickup Address added. But firebase updateion failed"); */
    }

   
   }
?>