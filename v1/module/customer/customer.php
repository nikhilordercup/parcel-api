<?php
class Customer extends Icargo{ 
    
    public $modelObj = null;
	private $_user_id;
	protected $_parentObj;
	   
	private function _setUserId($v){
		$this->_user_id = $v;
	}
	
	private function _getUserId(){
		return $this->_user_id;
	}
	
	public function __construct($data){
		$this->_parentObj = parent::__construct(array("email"=>$data->email, "access_token"=>$data->access_token));
        $this->modelObj  = Customer_Model::getInstanse();  
	}
	
	
    
    public function saveCustomer($param){ 
         $data = array(); 
         $exist = $this->modelObj->checkCustomerEmailExist($param->customer->customer_email);
         if($exist >0){ return array("status"=>"error","message"=>"Customer Email Already Exist ");}
        
         if($param->customer->name!='' && $param->customer->type!='' && $param->customer->invoicecycle!='' && $param->customer->customer_email!='' && $param->customer->password!=''){ 
            if($param->customer->type =='POSTPAID' && $param->customer->creditlimit==''){
              return array("status"=>"error","message"=>"Please Fill Credit limit in Number"); 
            }
             $data = array(
                "user_level"=>5,
                "name"=>$param->customer->name,
                "contact_name"=>"N/A",
                "phone"=>isset($param->customer->phone)?$param->customer->phone:'',
                "email"=>$param->customer->customer_email,
                "password"=>passwordHash::hash($param->customer->password),
                //"address_1"=>isset($param->customer->address_1)?$param->customer->address_1:'',
                //"address_2"=>isset($param->customer->address_2)?$param->customer->address_2:'',
                "postcode"=>isset($param->customer->postcode)?$param->customer->postcode:'',
                "city"=>isset($param->customer->city)?$param->customer->city:'',
                "state"=>isset($param->customer->state)?$param->customer->state:'',
                "country"=>isset($param->customer->country)?$param->customer->country:'',
                "status"=>"1",
                "uid"=>$param->uid,
                "register_in_firebase"=>"1",
                "email_verified"=>"1",
                "access_token"=>"",
                "free_trial_expiry"=>"1970-01-01 00:00:00",
                "parent_id"=>$param->parent_id,"is_default"=>1);
                $customer_id = $this->modelObj->addContent('users',$data);
                
                $this->modelObj->addContent('company_warehouse',array('company_id'=>$customer_id,'warehouse_id'=>$param->warehouse_id,'status'=>"1",'update_date'=>date("Y-m-d h:i:s", strtotime('now'))));
                 
                $this->modelObj->addContent('company_users',array('user_id'=>$customer_id,'company_id'=>$param->company_id,'warehouse_id'=>$param->warehouse_id,'status'=>"1",'update_date'=>date("Y-m-d h:i:s", strtotime('now'))));
                    
                $customerinfo  = array();
                $customerinfo['ccf'] = isset($param->customer->ccf)?$param->customer->ccf:0.00;
                $customerinfo['ccf_operator_service'] = isset($param->customer->ccf_operator)?$param->customer->ccf_operator:'NONE';
                $customerinfo['ccf_operator_surcharge'] = isset($param->customer->surcharge_operator)?$param->customer->surcharge_operator:'NONE';
                $customerinfo['apply_ccf'] = "1";
                $customerinfo['user_id'] = $customer_id;
                $customerinfo['surcharge'] = isset($param->customer->surcharge)?$param->customer->surcharge:0.00;
                $customerinfo['address_id'] = "0";
                $customerinfo['customer_type'] = $param->customer->type;
                $customerinfo['accountnumber'] = $this->generateCustomerAccount($param->companyname,$customer_id);
                $customerinfo['vatnumber']    = isset($param->customer->vatnumber)?$param->customer->vatnumber:'';
                $customerinfo['creditlimit'] = isset($param->customer->creditlimit)?$param->customer->creditlimit:0;
                $customerinfo['available_credit'] = $customerinfo['creditlimit'];
                $customerinfo['invoicecycle'] = $param->customer->invoicecycle;
                $this->modelObj->addContent('customer_info',$customerinfo);
             
             if($customerinfo['customer_type']=='POSTPAID'){
                 $creditbalanceData = array();
                 $creditbalanceData['customer_id'] = $customer_id;
                 $creditbalanceData['customer_type'] = 'POSTPAID';
                 $creditbalanceData['company_id'] = $param->company_id;
                 $creditbalanceData['payment_type'] = 'CREDIT';
                 $creditbalanceData['amount'] = $customerinfo['available_credit'];
                 $creditbalanceData['balance'] = $customerinfo['available_credit'];
                 $creditbalanceData['create_date'] = date("Y-m-d");
                 $creditbalanceData['payment_reference'] = 'CustomerSignupRecharge';
                 $creditbalanceData['payment_desc'] = 'Customer Registration Credit Balance';
                 $this->modelObj->addContent('creditbalance_history',$creditbalanceData);
            }
             if(key_exists('customerpickup',$param) && is_object($param->customerpickup)){ 
                 $param->customerpickup->customer_id = $customer_id;
                 $this->saveCarrierCustomerPickupInfo($param->customerpickup);
             }
             if(key_exists('customerbilling',$param) && is_object($param->customerbilling)){ 
                  $param->customerbilling->customer_id = $customer_id;
                 $this->saveCarrierCustomerBillingInfo($param->customerbilling);
             }
              if($customer_id){
                    return array("status"=>"success","message"=>"Customer added successfully","customer_id"=>$customer_id);
              }
           }else{
               return array("status"=>"error","message"=>"Please insure Bussiness Name, Customer Type, Invoice Cycle, Customer Email, Customer Password are not blank");
         }
          return array("status"=>"error","message"=>"Customer not added");
      }
    
   public function generateCustomerAccount($companyname,$customerid){
       $str = strtoupper($companyname).$customerid.rand(300,60000);
       $exist = $this->modelObj->checkCustomerAccountExist($str);
        if($exist >0){ 
             return $this->generateCustomerAccount($companyname,$customerid);
        }
       return $str;
       
   }
    
    public function saveCarrierCustomerFirebaseInfo($param){
            $data = array("uid"=>$param->uid,"register_in_firebase"=>1);
            $condition = "id = '" . $param->customer_id . "'"; 
            $infoStatus    = $this->modelObj->editContent("users",$data, $condition);
            if($infoStatus){
                return array("status"=>"success","message"=>"Customer added successfully");
            }
            return array("status"=>"error","message"=>"Customer added. But firebase updateion failed");
        }
    
    
    
    public function getAllCustomerData($param){
        $data =  $this->modelObj->getAllCustomerData();
         foreach($data as $key=>$val){
          $data[$key]['status'] = ($val['status']==1)?true:false;
          $data[$key]['action'] = 'customerdetail';
             
        }
       return $data;
    }
    
	public function getCustomerDataByCompanyId($param){  
        $data =  $this->modelObj->getCustomerDataByCompanyId($param->company_id);
         foreach($data as $key=>$val){
          $data[$key]['status'] = ($val['status']==1)?true:false;
          $data[$key]['action'] = 'customerdetail';
        }
       return $data;
        
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
      $addressBook['country']       = isset($param->country)?$param->country:''; 
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
        if($addressId){
        return array("status"=>"success","message"=>"Customer Pickup Address added successfully");
       }
      return array("status"=>"error","message"=>"Customer Pickup Address added. But firebase updateion failed");
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
      $addressBook['country']       = isset($param->country)?$param->country:''; 
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
      if($infoStatus){
        return array("status"=>"success","message"=>"Customer Billing Address added successfully");
       }
      return array("status"=>"error","message"=>"Customer Billing Address added. But firebase updateion failed");
    } 
      
    public function getAllCouriersofCustomer($param){
        $data = $this->modelObj->getAllCouriersofCustomer($param->company_id,$param->customer_id);
        foreach($data as $key=>$val){
           $data[$key]['action'] = 'editCustomerAccountStatus';
           $data[$key]['actioncode'] = 'INNER';
           $data[$key]['status'] = false;
           $data[$key]['customer_id'] = $param->customer_id;
           //$data[$key]['status'] = ($val['status']==1)?true:false;
       }
      return  $data;
    }
    
    
 public function getAllCouriersofCustomerAccount($param){ 
     $data = $this->modelObj->getAllCouriersofCustomer($param->company_id,$param->customer_id);  
     foreach($data as $key=>$val){
          $innerdata  = $this->modelObj->getAllCouriersofCompany($param->company_id,$param->customer_id,$val['courier_id'],$val['id']);
          $data[$key]['customer_status']    =  isset($innerdata['status'])?$innerdata['status']:0; 
          $data[$key]['customer_ccf']       =  isset($innerdata['customer_ccf_value'])?$innerdata['customer_ccf_value']:0.00; 
          $data[$key]['customer_surcharge'] =  isset($innerdata['customer_surcharge_value'])?$innerdata['customer_surcharge_value']:0.00;
          $data[$key]['action'] = 'editSelectedCustomerAccountStatus';
          $data[$key]['actioncode'] = 'INNER';
          $data[$key]['status'] = ($data[$key]['customer_status']==1)?true:false;
          $data[$key]['customer_id'] = $param->customer_id;
      }
     return  $data;
    } 
    
 public function getAllCourierServicesForCustomer($param){
        $data = $this->modelObj->getAllCourierServicesForCustomer($param->company_id,$param->viewid);
        foreach( $data as $key=>$val){
           $data[$key]['action'] = 'editServiceAccountStatus';
           $data[$key]['actioncode'] = 'INNER';
           $data[$key]['customer_id'] = $param->customer_id;
           //$data[$key]['status'] = ($val['status']==1)?true:false;
           $data[$key]['status'] = false;
       } 
      return  $data;
    }
    
    
    
  public function editCustomerStatus($param){ 
         switch($param->action){
             case "editCustomerAccountStatus":
                
               $isRowExist = $this->modelObj->checkDataExistFromCustomerAccount($param->company_id,$param->data->account_number,$param->data->customer_id,$param->data->courier_id);
               
                if($isRowExist >0){
                  $data = array();
                  $data['status'] = $param->status; 
                  $data['update_date'] = date('Y-m-d'); 
                  $data['updated_by'] = $param->user_id;
                  $condition = "customer_id = '" . $param->customer_id . "' AND courier_id = '" . $param->data->courier_id . 
                                "' AND account_number = '" . $param->data->account_number . "' AND company_id = '" . $param->company_id . "' "; 
                  $infoStatus    = $this->modelObj->editContent("courier_vs_company_vs_customer",$data, $condition);
                 if($infoStatus){
                      return array("status"=>"success","message"=>"Action Perform successfully","action"=>"carrieraction");
                   }
                   return array("status"=>"error","message"=>"Action not Performe","action"=>"carrieraction");
                }else{
                  $data = array();
                  $data['status'] = $param->status; 
                  $data['company_id'] = $param->company_id; 
                  $data['company_courier_account_id'] = $param->data->id; 
                  $data['account_number'] = $param->data->account_number; 
                  $data['courier_id'] = $param->data->courier_id; 
                  $data['customer_id'] = $param->customer_id; 
                  $data['create_date'] = date('Y-m-d'); 
                  $data['created_by'] = $param->user_id;
                   
                  $satatusId = $this->modelObj->addContent('courier_vs_company_vs_customer',$data);
                   if($satatusId){
                      return array("status"=>"success","message"=>"Action Perform successfully","action"=>"carrieraction");
                   }
                   return array("status"=>"error","message"=>"Action not Performe","action"=>"carrieraction");
                }    
             break;
                 
             case "editServiceAccountStatus":
              $isRowExist = $this->modelObj->checkServiceExistFromCustomerAccount($param->data->service_id,$param->data->id,$param->data->courier_id,$param->company_id,$param->data->customer_id);
              if($isRowExist >0){
                  $data = array();
                  $data['status'] = $param->status; 
                  $data['update_date'] = date('Y-m-d'); 
                  $data['updated_by'] = $param->user_id;
                  $condition = "company_customer_id = '" . $param->data->customer_id. "' AND courier_id = '" . $param->data->courier_id . "' 
                                AND company_service_id = '" . $param->data->id . "' AND company_id = '" . $param->company_id . "' 
                                AND service_id = '" . $param->data->service_id . "' "; 
                  $infoStatus    = $this->modelObj->editContent("company_vs_customer_vs_services",$data, $condition);
                 if($infoStatus){
                      return array("status"=>"success","message"=>"Action Perform successfully");
                   }
                   return array("status"=>"error","message"=>"Action not Performe");  
                  
              }else{
                  $data = array();
                  $data['status'] = $param->status; 
                  $data['company_id'] = $param->company_id; 
                  $data['company_customer_id'] = $param->data->customer_id; 
                  $data['courier_id'] = $param->data->courier_id; 
                  $data['company_service_id'] = $param->data->id; 
                  $data['service_id'] = $param->data->service_id; 
                  $data['create_date'] = date('Y-m-d'); 
                  $data['create_by'] = $param->user_id;
                  $satatusId = $this->modelObj->addContent('company_vs_customer_vs_services',$data);
                   if($satatusId){
                      return array("status"=>"success","message"=>"Action Perform successfully");
                   }
                   return array("status"=>"error","message"=>"Action not Performe");
                }
            break;     
            case "editSurchargeAccountStatus":
              $isRowExist = $this->modelObj->checkSurchargeExistFromCustomerAccount($param->data->surcharge_id,$param->data->id,$param->data->courier_id,$param->company_id,$param->data->customer_id);
              if($isRowExist >0){
                  $data = array();
                  $data['status'] = $param->status; 
                  $data['updated_date'] = date('Y-m-d'); 
                  $data['updated_by'] = $param->user_id;
                  $condition = "company_customer_id = '" . $param->data->customer_id . "' AND courier_id = '" . $param->data->courier_id . "' 
                                AND company_surcharge_id = '" . $param->data->id . "' AND company_id = '" . $param->company_id . "' 
                                AND surcharge_id = '" . $param->data->surcharge_id . "' "; 
                  $infoStatus    = $this->modelObj->editContent("company_vs_customer_vs_surcharge",$data, $condition);
                 if($infoStatus){
                      return array("status"=>"success","message"=>"Action Perform successfully");
                   }
                   return array("status"=>"error","message"=>"Action not Performe");  
                  
              }else{
                  $data = array();
                  $data['status'] = $param->status; 
                  $data['company_id'] = $param->company_id; 
                  $data['company_surcharge_id'] = $param->data->id; 
                  $data['surcharge_id'] = $param->data->surcharge_id; 
                  $data['courier_id'] = $param->data->courier_id; 
                  $data['company_customer_id'] = $param->data->customer_id; 
                  $data['create_date'] = date('Y-m-d'); 
                  $data['create_by'] = $param->user_id;
                  $satatusId = $this->modelObj->addContent('company_vs_customer_vs_surcharge',$data);
                   if($satatusId){
                      return array("status"=>"success","message"=>"Action Perform successfully");
                   }
                   return array("status"=>"error","message"=>"Action not Performe");
                }
            break;      
           case "editCustomerAccount":
                  $data = array();
                  $data['status'] = $param->status; 
                  $data['update_date'] = date('Y-m-d'); 
                  $data['updated_by'] = $param->user_id;
                  $condition = "id = '" . $param->data->customer . "'"; 
                  $infoStatus    = $this->modelObj->editContent("users",$data, $condition);
                 if($infoStatus){
                      return array("status"=>"success","message"=>"Action Perform successfully");
                   }
                   return array("status"=>"error","message"=>"Action not Performe");
               break;
             case "editSelectedCustomerAccountStatusFromView":
                 $param->action = "editCustomerAccountStatus";
                 return $this-> editCustomerStatus($param);
              break;  
                 
              case "editSelectedcustomerServiceAccountStatusFromView":
                 $param->action = "editServiceAccountStatus";
                 return $this->editCustomerStatus($param);
              break;     
              case "editSelectedcustomerSurchargeAccountStatusFromView":
                 $param->action = "editSurchargeAccountStatus";
                 return $this->editCustomerStatus($param);
              break;     
              
         }
      }
  public function editCustomerAccountStatus($param){  
       $isRowExist = $this->modelObj->checkDataExistFromCustomerAccount($param->company_id,$param->data->account_number,$param->customer_id,$param->data->courier_id);
   
       if($isRowExist >0){
            $data = array();
            $historyId =  $this->saveccfHistoryForCarrier($param,'CUSTOMER_CARRIER');
          
            $data['company_ccf_operator_service'] = isset($param->data->ccf_operator)?$param->data->ccf_operator:'NONE';
            $data['company_ccf_operator_surcharge'] = isset($param->data->surcharge_operator)?$param->data->surcharge_operator:'NONE';
            $data['ccf_history'] = $historyId; 
            $data['customer_surcharge_value'] = isset($param->data->customer_surcharge)?$param->data->customer_surcharge:0.00;  
            $data['customer_ccf_value'] = isset($param->data->customer_ccf)?$param->data->customer_ccf:0.00; 
            $data['update_date'] = date('Y-m-d'); 
            $data['updated_by'] = $param->user_id;
            $condition = "customer_id = '" . $param->customer_id . "' AND courier_id = '" . $param->data->courier_id . 
                        "' AND account_number = '" . $param->data->account_number . "' AND company_id = '" . $param->company_id . "' "; 
            $infoStatus    = $this->modelObj->editContent("courier_vs_company_vs_customer",$data, $condition);
                 if($infoStatus){
                      return array("status"=>"success","message"=>"Action Perform successfully",'actiongrid'=>'right1');
                   }
                   return array("status"=>"error","message"=>"Action not Performe",'actiongrid'=>'right1');
                }else{
                  $data = array();
                  $data['customer_surcharge_value'] = $param->data->customer_surcharge;  
                  $data['customer_ccf_value'] = $param->data->customer_ccf;  
                  $data['company_id'] = $param->company_id; 
                  $data['company_courier_account_id'] = $param->data->id; 
                  $data['account_number'] = $param->data->account_number; 
                  $data['courier_id'] = $param->data->courier_id; 
                  $data['customer_id'] = $param->customer_id; 
                  $data['create_date'] = date('Y-m-d'); 
                  $data['created_by'] = $param->user_id;
        
                  $satatusId = $this->modelObj->addContent('courier_vs_company_vs_customer',$data);
                   if($satatusId){
                      return array("status"=>"success","message"=>"Action Perform successfully",'actiongrid'=>'right1');
                   }
                   return array("status"=>"error","message"=>"Action not Performe",'actiongrid'=>'right1');
                }
              }  
    
  
  public function editServiceAccountStatus($param){ 
       $isRowExist = $this->modelObj->checkServiceExistFromCustomerAccount($param->data->service_id,$param->data->id,$param->data->courier_id,$param->company_id,$param->data->customer_id);
     
       if($isRowExist >0){
                  $historyId =  $this->saveccfHistoryForServicesSurcharge($param,'CUSTOMER_SERVICE');
                  $data = array();
                  $data['customer_ccf'] = $param->data->customer_ccf;  
                  $data['ccf_operator'] = $param->data->ccf_operator; 
                  $data['ccf_history'] = $historyId;  
                  $data['update_date'] = date('Y-m-d'); 
                  $data['updated_by'] = $param->user_id;
                  $condition = "company_customer_id = '" . $param->data->customer_id . "' AND courier_id = '" . $param->data->courier_id . "' 
                                AND company_service_id = '" . $param->data->id . "' AND company_id = '" . $param->company_id . "' 
                                AND service_id = '" . $param->data->service_id . "' "; 
                  
                 $infoStatus    = $this->modelObj->editContent("company_vs_customer_vs_services",$data, $condition);
                 if($infoStatus){
                      return array("status"=>"success","message"=>"Action Perform successfully",'actiongrid'=>'right2');
                   }
                   return array("status"=>"error","message"=>"Action not Performe",'actiongrid'=>'right2');
                }else{
                  $data = array();
                  $data['customer_ccf'] = $param->data->customer_ccf;  
                  $data['company_id'] = $param->company_id; 
                  $data['company_service_id'] = $param->data->id; 
                  $data['service_id'] = $param->data->service_id; 
                  $data['courier_id'] = $param->data->courier_id; 
                  $data['company_customer_id'] = $param->data->customer_id; 
                  $data['create_date'] = date('Y-m-d'); 
                  $data['create_by'] = $param->user_id;
                  $satatusId = $this->modelObj->addContent('company_vs_customer_vs_services',$data);
                   if($satatusId){
                      return array("status"=>"success","message"=>"Action Perform successfully",'actiongrid'=>'right2');
                   }
                   return array("status"=>"error","message"=>"Action not Performe",'actiongrid'=>'right2');
                }
            }    
    
  public function editSurchargeAccountStatus($param){ 
    $isRowExist = $this->modelObj->checkSurchargeExistFromCustomerAccount($param->data->surcharge_id,$param->data->id,$param->data->courier_id,$param->company_id,$param->data->customer_id);
      
     if($isRowExist >0){
                  $historyId =  $this->saveccfHistoryForServicesSurcharge($param,'CUSTOMER_SURCHARGE');
                  $data = array();
                  $data['ccf_operator'] = $param->data->ccf_operator; 
                  $data['ccf_history'] = $historyId;  
                  $data['customer_surcharge'] = $param->data->customer_surcharge;  
                  $data['updated_date'] = date('Y-m-d'); 
                  $data['updated_by'] = $param->user_id;
                  $condition = "company_customer_id = '" . $param->data->customer_id . "' AND courier_id = '" . $param->data->courier_id . "' 
                                AND company_surcharge_id = '" . $param->data->id . "' AND company_id = '" . $param->company_id . "' 
                                AND surcharge_id = '" . $param->data->surcharge_id . "' "; 
                 $infoStatus    = $this->modelObj->editContent("company_vs_customer_vs_surcharge",$data, $condition);
                 if($infoStatus){
                      return array("status"=>"success","message"=>"Action Perform successfully",'actiongrid'=>'right3');
                   }
                   return array("status"=>"error","message"=>"Action not Performe",'actiongrid'=>'right3');
                }else{
                  $data = array();
                  $data['customer_surcharge'] = $param->data->customer_surcharge;  
                  $data['company_id'] = $param->company_id; 
                  $data['company_surcharge_id'] = $param->data->id; 
                  $data['surcharge_id'] = $param->data->surcharge_id; 
                  $data['courier_id'] = $param->data->courier_id; 
                  $data['company_customer_id'] = $param->data->customer_id; 
                  $data['create_date'] = date('Y-m-d'); 
                  $data['create_by'] = $param->user_id;
                  $satatusId = $this->modelObj->addContent('company_vs_customer_vs_surcharge',$data);
                   if($satatusId){
                      return array("status"=>"success","message"=>"Action Perform successfully",'actiongrid'=>'right3');
                   }
                   return array("status"=>"error","message"=>"Action not Performe",'actiongrid'=>'right3');
                }
            }      
    
    
  public function getAllCourierSurchargeForCustomer($param){
        $data = $this->modelObj->getAllCourierSurchargeForCustomer($param->company_id,$param->viewid);
         foreach( $data as $key=>$val){
           $data[$key]['action'] = 'editSurchargeAccountStatus';
           $data[$key]['actioncode'] = 'INNER';
         //$data[$key]['status'] = ($val['status']==1)?true:false;
           $data[$key]['status'] = false;
           $data[$key]['customer_id'] = $param->customer_id;
       }
      return  $data;
    } 
    
  public function customerdetail($param){ 
      
      $customerpersonaldata =    $this->modelObj->getCustomerPersonalDetails($param->company_id,$param->customer_id);
      $customerpickupdata   =    $this->modelObj->getCustomerPickupAddress($param->customer_id);
      $customerbillingdata  =    $this->modelObj->getCustomerBillingAddress($param->customer_id);
      $data = array();  
      $data['customer']['company'] = $customerpersonaldata['company_name'];
      $data['customer']['name'] = $customerpersonaldata['name'];
      $data['customer']['customer_email'] = $customerpersonaldata['email'];
      $data['customer']['password'] = $customerpersonaldata['password'];
      $data['customer']['phone'] = $customerpersonaldata['phone'];
      $data['customer']['address_1'] = $customerpersonaldata['address_1'];
      $data['customer']['address_2'] = $customerpersonaldata['address_2'];
      $data['customer']['postcode'] = $customerpersonaldata['postcode'];
      $data['customer']['city'] = $customerpersonaldata['city'];
      $data['customer']['state'] = $customerpersonaldata['state'];
      $data['customer']['country'] = $customerpersonaldata['country'];
      $data['customer']['ccf'] = (float)$customerpersonaldata['ccf'];
      $data['customer']['surcharge'] = (float)$customerpersonaldata['surcharge'];
      $data['customer']['surcharge_operator'] = $customerpersonaldata['ccf_operator_surcharge'];
      $data['customer']['ccf_operator'] = $customerpersonaldata['ccf_operator_service'];
      $data['customer']['customer_type'] = $customerpersonaldata['customer_type'];
      $data['customer']['accountnumber'] = $customerpersonaldata['accountnumber'];
      $data['customer']['vatnumber'] = $customerpersonaldata['vatnumber'];
      $data['customer']['creditlimit'] = (float)$customerpersonaldata['creditlimit'];
      $data['customer']['available_credit'] = $data['customer']['creditlimit'];
      $data['customer']['invoicecycle'] = (int)$customerpersonaldata['invoicecycle'];
      $data['customer']['type'] = $customerpersonaldata['customer_type'];
      
      
     $data['customerbilling']['name'] = $customerbillingdata['name'];
     $data['customerbilling']['address_1'] = $customerbillingdata['address_line1'];
     $data['customerbilling']['address_2'] = $customerbillingdata['address_line2'];
     $data['customerbilling']['postcode'] = $customerbillingdata['postcode'];
     $data['customerbilling']['city'] = $customerbillingdata['city'];
     $data['customerbilling']['state'] = $customerbillingdata['state'];
     $data['customerbilling']['country'] = $customerbillingdata['country'];
     $data['customerbilling']['phone'] = $customerbillingdata['phone'];
     $data['customerbilling']['email'] = $customerbillingdata['email'];
      
       
     $data['customerpickup']['name'] = $customerpickupdata['name'];
     $data['customerpickup']['address_1'] = $customerpickupdata['address_line1'];
     $data['customerpickup']['address_2'] = $customerpickupdata['address_line2'];
     $data['customerpickup']['postcode'] = $customerpickupdata['postcode'];
     $data['customerpickup']['city'] = $customerpickupdata['city'];
     $data['customerpickup']['state'] = $customerpickupdata['state'];
     $data['customerpickup']['country'] = $customerpickupdata['country'];
     $data['customerpickup']['phone'] = $customerpickupdata['phone'];
     $data['customerpickup']['email'] = $customerpickupdata['email'];    
     return $data;
  }
    
  public function saveccfHistoryForCarrier($param,$type){
        $req_ccf                  = isset($param->customer->ccf)?$param->customer->ccf:0;
        $req_surcharge            = isset($param->customer->surcharge)?$param->customer->surcharge:0;
        $req_ccf_operator         = isset($param->customer->ccf_operator)?$param->customer->ccf_operator:'NONE';
        $req_surcharge_operator   = isset($param->customer->surcharge_operator)?$param->customer->surcharge_operator:'NONE';
        if($type =='CUSTOMER'){
            $infodata =  $this->modelObj->getCustomerPersonalDetails($param->company_id,$param->customer_id);
        }
        if($type =='CUSTOMER_CARRIER'){
            $infodata =  $this->modelObj->getCustomerCarrierDetails($param);
        }
        $customer_ccf_value               =  $infodata['ccf'];
        $customer_surcharge_value         =  $infodata['surcharge'];
        $customer_ccf_operator_service    =  $infodata['ccf_operator_service'];
        $customer_ccf_operator_surcharge  =  $infodata['ccf_operator_surcharge'];
        $ccf_history                      =  $infodata['ccf_history'];
        $reference_id                     =  $infodata['id'];
        $courier_id                       =  isset($param->data->courier_id)?$param->data->courier_id:0;
        $company_id                       =  $param->company_id;     
        $lastId                           =  $infodata['ccf_history'];   
      
        if($req_ccf==$customer_ccf_value && $req_surcharge==$customer_surcharge_value && $req_ccf_operator==$customer_ccf_operator_service && $req_surcharge_operator==$customer_ccf_operator_surcharge){
            //No Histoty
        }
        else{
            $dataToBeinsert = array();
            $dataToBeinsert['pid'] = $ccf_history;
            $dataToBeinsert['type'] = $type;
            $dataToBeinsert['carrier_id'] = $courier_id;
            $dataToBeinsert['company_id'] = $company_id;
            $dataToBeinsert['customer_id'] = isset($param->customer_id)?$param->customer_id:0;
            $dataToBeinsert['created_by'] = $param->user_id;
            $dataToBeinsert['create_date'] = date("Y-m-d");
            $dataToBeinsert['create_time'] = date("H:m:s");
            $dataToBeinsert['reference_id'] = $reference_id;
            $dataToBeinsert['status'] = '1';
            $dataToBeinsert['ccf_value'] = $customer_ccf_value;
            $dataToBeinsert['ccf_operator'] = $customer_ccf_operator_service;
            $dataToBeinsert['surcharge_value'] = $customer_surcharge_value;
            $dataToBeinsert['surcharge_operator'] = $customer_ccf_operator_surcharge;
            $lastId = $this->modelObj->addContent('ccf_history',$dataToBeinsert);
        } 
            return $lastId;
   } 
      
   
  public function saveccfHistoryForServicesSurcharge($param,$type){
        $req_ccf_operator         = isset($param->data->ccf_operator)?$param->data->ccf_operator:'NONE';
        if($type=='CUSTOMER_SURCHARGE'){
            $req_ccf   = isset($param->data->surcharge)?$param->data->surcharge:0.00;
            $infodata =  $this->modelObj->getCustomerSurchargeDetails($param);
            $customer_ccf_value     =  isset($infodata['customer_surcharge'])?$infodata['customer_surcharge']:0.00;
        }elseif($type=='CUSTOMER_SERVICE'){
            $req_ccf    = isset($param->data->ccf)?$param->data->customer_ccf:0.00;
            $infodata =  $this->modelObj->getCustomerServiceDetails($param);
            $customer_ccf_value   =  isset($infodata['customer_ccf'])?$infodata['customer_ccf']:0.00;
        }
        $customer_ccf_operator           =  $infodata['ccf_operator'];
        $ccf_history                     =  $infodata['ccf_history'];
        $reference_id                    =  $infodata['id'];
        $courier_id                      =  $infodata['courier_id'];
        $company_id                      =  $infodata['company_id'];
        $customer_id                     =  $infodata['customer_id'];    
        $lastId                          =  $infodata['ccf_history']; 
   
       if($req_ccf==$customer_ccf_value &&  $req_ccf_operator==$customer_ccf_operator){
            //No Histoty
        }else{  
            $dataToBeinsert = array();
            $dataToBeinsert['pid'] = $ccf_history;
            $dataToBeinsert['type'] = $type;
            $dataToBeinsert['carrier_id'] = $courier_id;
            $dataToBeinsert['company_id'] = $company_id;
            $dataToBeinsert['customer_id'] = $customer_id;
            $dataToBeinsert['created_by'] = $param->user_id;
            $dataToBeinsert['create_date'] = date("Y-m-d");
            $dataToBeinsert['create_time'] = date("H:m:s");
            $dataToBeinsert['reference_id'] = $reference_id;
            $dataToBeinsert['status'] = '1';
            $dataToBeinsert['ccf_value'] = $customer_ccf_value;
            $dataToBeinsert['ccf_operator'] = $customer_ccf_operator;
            $lastId = $this->modelObj->addContent('ccf_history',$dataToBeinsert);
        }
            return $lastId;
   } 
     
 public function editCustomerPersonalDetails($param){ 
     // start here
            $data = array(
                "name"=>$param->customer->name,
                "phone"=>isset($param->customer->phone)?$param->customer->phone:'',
                "postcode"=>isset($param->customer->postcode)?$param->customer->postcode:'',
                "city"=>isset($param->customer->city)?$param->customer->city:'',
                "state"=>isset($param->customer->state)?$param->customer->state:'',
                "country"=>isset($param->customer->country)?$param->customer->country:'',
             );
          $condition = "id = '" . $param->customer_id . "' AND user_level = '5'"; 
          $infoStatus    = $this->modelObj->editContent("users",$data, $condition);
     
    
          if($infoStatus){ 
            $historyId =  $this->saveccfHistoryForCarrier($param,'CUSTOMER');
            $customerinfo  = array();
            $customerinfo['ccf']        = isset($param->customer->ccf)?$param->customer->ccf:0;
            $customerinfo['surcharge']  = isset($param->customer->surcharge)?$param->customer->surcharge:0;
            $customerinfo['ccf_operator_service']   = isset($param->customer->ccf_operator)?$param->customer->ccf_operator:'NONE';
            $customerinfo['ccf_operator_surcharge']  = isset($param->customer->surcharge_operator)?$param->customer->surcharge_operator:'NONE';
            $customerinfo['ccf_history']    = $historyId; 
            $customerinfo['vatnumber']      = isset($param->customer->vatnumber)?$param->customer->vatnumber:'';
            $customerinfo['creditlimit'] = isset($param->customer->creditlimit)?$param->customer->creditlimit:0;
            $customerinfo['invoicecycle'] = isset($param->customer->invoicecycle)?$param->customer->invoicecycle:0;
            $condition = "user_id = '" . $param->customer_id . "'"; 
            $customerinfoStatus    = $this->modelObj->editContent("customer_info",$customerinfo, $condition);  
              
            
            if($customerinfoStatus){
            if(key_exists('customerpickup',$param) && is_object($param->customerpickup)){ 
                 $param->customerpickup->customer_id = $param->customer_id;
                 $this->editCustomerPickupDetails($param->customerpickup);
             }
             if(key_exists('customerbilling',$param) && is_object($param->customerbilling)){ 
                 
                  $param->customerbilling->customer_id = $param->customer_id;
                 $this->editCustomerBillingDetails($param->customerbilling);
             }
                return array("status"=>"success","message"=>"Customer Information Updted successfully");
            }
            else{return array("status"=>"error","message"=>"Customer data not updated");  }
              
          }else{
             return array("status"=>"error","message"=>"Customer data not updated");  
          }
        }
    
    
    
 public function editCustomerPickupDetails($param){  
      $libObj = new Library();
     if($param->postcode!=''){
        $shipment_geo_location =  $libObj->get_lat_long_by_postcode($param->postcode);
      }
     
      $addressBook = array();
      $addressBook['name']          = isset($param->name)?$param->name:'';
      $addressBook['address_line1'] = isset($param->address_1)?$param->address_1:''; 
      $addressBook['address_line2'] = isset($param->address_2)?$param->address_2:''; 
      $addressBook['postcode']      = isset($param->postcode)?$param->postcode:'';
      $addressBook['city']          = isset($param->city)?$param->city:''; 
      $addressBook['state']         = isset($param->state)?$param->state:''; 
      $addressBook['country']       = isset($param->country)?$param->country:''; 
      $addressBook['phone']         = isset($param->phone)?$param->phone:''; 
      $addressBook['email']         = isset($param->email)?$param->email:''; 
      $addressBook['latitude']      = isset($shipment_geo_location["latitude"])?$shipment_geo_location["latitude"]:0.00;
      $addressBook['longitude']     = isset($shipment_geo_location["longitude"])?$shipment_geo_location["longitude"]:0.00;
      $addressBook['search_string'] = $addressBook['address_line1'].$addressBook['address_line2'].$addressBook['postcode'].$addressBook['city'].$addressBook['state'].$addressBook['country'];
      
      $condition = "customer_id = '" . $param->customer_id . "' AND pickup_address  = 'Y' AND billing_address  = 'N'"; 
     
      $checkPickupAddressExist = $this->modelObj->existAddress($condition);
      if($checkPickupAddressExist >0){
        $infoStatus    = $this->modelObj->editContent("address_book",$addressBook, $condition);  
      }else{
        $addressBook['customer_id']   = isset($param->customer_id)?$param->customer_id:'0';
        $addressBook['pickup_address']   = 'Y';
        $addressBook['billing_address']   = 'N';
        $infoStatus    = $this->modelObj->addContent("address_book",$addressBook);  
      }
     if($infoStatus){
        return array("status"=>"success","message"=>"Customer Pickup Address updated successfully.");
       }
      return array("status"=>"error","message"=>"Customer Pickup Address not updated.");
    }
    
 public function editCustomerBillingDetails($param){  
      
    $libObj = new Library();
      if($param->postcode!=''){
        $shipment_geo_location =  $libObj->get_lat_long_by_postcode($param->postcode);
      }
      $addressBook = array();
      $addressBook['name']          = isset($param->name)?$param->name:'';
      $addressBook['address_line1'] = isset($param->address_1)?$param->address_1:''; 
      $addressBook['address_line2'] = isset($param->address_2)?$param->address_2:''; 
      $addressBook['postcode']      = isset($param->postcode)?$param->postcode:'';
      $addressBook['city']          = isset($param->city)?$param->city:''; 
      $addressBook['state']         = isset($param->state)?$param->state:''; 
      $addressBook['country']       = isset($param->country)?$param->country:''; 
      $addressBook['phone']         = isset($param->phone)?$param->phone:''; 
      $addressBook['email']         = isset($param->email)?$param->email:'';
      $addressBook['latitude']      = isset($shipment_geo_location["latitude"])?$shipment_geo_location["latitude"]:0.00;
      $addressBook['longitude']     = isset($shipment_geo_location["longitude"])?$shipment_geo_location["longitude"]:0.00;
      
      $addressBook['search_string'] = $addressBook['address_line1'].$addressBook['address_line2'].$addressBook['postcode'].$addressBook['city'].$addressBook['state'].$addressBook['country'];
      $condition = "customer_id = '" . $param->customer_id . "' AND pickup_address  = 'N' AND billing_address  = 'Y'"; 
      $checkPickupAddressExist = $this->modelObj->existAddress($condition);
     if($checkPickupAddressExist >0){
        $infoStatus    = $this->modelObj->editContent("address_book",$addressBook, $condition);
      }else{
        $addressBook['customer_id']   = isset($param->customer_id)?$param->customer_id:'0';
        $addressBook['pickup_address']   = 'N';
        $addressBook['billing_address']   = 'Y';
        $infoStatus    = $this->modelObj->addContent("address_book",$addressBook);  
      }
     if($infoStatus){
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
      $condition = "user_id = '" . $param->customer_id . "'"; 
          $customerInfoStatus    = $this->modelObj->editContent("customer_info",$data, $condition);
           if($infoStatus){
             return array("status"=>"success","message"=>"Customer Billing Address updated successfully.");
            }else{
             return array("status"=>"error","message"=>"Customer Billing Address not updated.");
           }
      }
      return array("status"=>"error","message"=>"Customer Billing Address not updated.");
    }   
    
    
public function editSelectedCustomerAccountStatus($param){  
    $return = $this->editCustomerAccountStatus($param);
    if($return['status']=='success'){
         return array("status"=>"success","message"=>"Action Perform successfully",'actiongrid'=>'right31');
    }else{
        return array("status"=>"error","message"=>"Action not Performe",'actiongrid'=>'right31');
    }
  }
   
    
public function getAllCourierDataOfSelectedCustomer($param){ 
     $data = $this->modelObj->getAllCouriersofCustomer($param->company_id,$param->customer_id);  
     foreach($data as $key=>$val){
          $innerdata  = $this->modelObj->getAllCouriersofCompany($param->company_id,$param->customer_id,$val['courier_id'],$val['id']);
          $data[$key]['customer_status']    =  isset($innerdata['status'])?$innerdata['status']:0; 
          $data[$key]['customer_ccf']       =  isset($innerdata['customer_ccf_value'])?$innerdata['customer_ccf_value']:0.00; 
          $data[$key]['customer_surcharge'] =  isset($innerdata['customer_surcharge_value'])?
          $innerdata['customer_surcharge_value']:0.00;
          
          $data[$key]['ccf_operator']    =  $innerdata['company_ccf_operator_service'];
          $data[$key]['surcharge_operator']    =  $innerdata['company_ccf_operator_surcharge'];
          $data[$key]['action'] = 'editSelectedCustomerAccountStatusFromView';
          $data[$key]['actioncode'] = 'INNER';
          $data[$key]['status'] = ($data[$key]['customer_status']==1)?true:false;
          $data[$key]['internal'] = ($data[$key]['internal']==1)?true:false;
          $data[$key]['customer_id'] = $param->customer_id;
      }
   
     return  $data;
    } 
     
     
    
public function getAllCourierDataOfSelectedCustomerwithStatus($param){ 
     $data = $this->modelObj->getAllCouriersofCustomer($param->company_id,$param->customer_id);
    $tempdata = array();
     foreach($data as $key=>$val){
        $innerdata  = $this->modelObj->getAllCouriersofCompany($param->company_id,$param->customer_id,$val['courier_id'],$val['id']);
        if($innerdata['status']==1){
          $tempdata[$key] = $val;
          $tempdata[$key]['customer_status']    =  isset($innerdata['status'])?$innerdata['status']:0; 
          $tempdata[$key]['customer_ccf']       =  isset($innerdata['customer_ccf_value'])?$innerdata['customer_ccf_value']:0.00; 
          $tempdata[$key]['customer_surcharge'] =  isset($innerdata['customer_surcharge_value'])?$innerdata['customer_surcharge_value']:0.00;
          $tempdata[$key]['action'] = 'editSelectedCustomerAccountStatusFromView';
          $tempdata[$key]['actioncode'] = 'INNER';
          //$tempdata[$key]['status'] = ($val['status']==1)?true:false;
          $tempdata[$key]['customer_id'] = $param->customer_id;
        }
     }
     return  array_values($tempdata);
    } 
        
public function getAllCourierServicesForSelectedCustomer($param){  
      $data = $this->modelObj->getAllCourierServicesForCustomer($param->company_id,$param->cid); 
       foreach($data as $key=>$val){
        $innerdata  = $this->modelObj->getAllAllowedCourierServicesofCompanyCustomer($val['service_id'],$val['id'],$val['courier_id'],$param->company_id,$param->customer_id); 
          $data[$key]['customer_status']    =  isset($innerdata['status'])?$innerdata['status']:0; 
          $data[$key]['customer_ccf']       =  isset($innerdata['customer_ccf'])?$innerdata['customer_ccf']:0.00; 
          $data[$key]['action'] = 'editSelectedcustomerServiceAccountStatusFromView';
          $data[$key]['actioncode'] = 'INNER';
          $data[$key]['ccf_operator'] = $innerdata['ccf_operator'];
          $data[$key]['status'] = ($data[$key]['customer_status']==1)?true:false;
          $data[$key]['customer_id'] = $param->customer_id;
          
     } 
     return  $data;
    }

public function getAllCourierSurchargeForSelectedCustomer($param){  
      $data = $this->modelObj->getAllCourierSurchargeForCustomer($param->company_id,$param->cid); 
       foreach($data as $key=>$val){
        $innerdata  = $this->modelObj->getAllAllowedCourierSurchargeofCompanyCustomer($val['surcharge_id'],$val['id'],$val['courier_id'],$param->company_id,$param->customer_id);   
          $data[$key]['customer_status']    =  isset($innerdata['status'])?$innerdata['status']:0; 
          $data[$key]['customer_surcharge']       =  isset($innerdata['customer_surcharge'])?$innerdata['customer_surcharge']:0.00; 
          $data[$key]['action'] = 'editSelectedcustomerSurchargeAccountStatusFromView';
          $data[$key]['actioncode'] = 'INNER';
          $data[$key]['ccf_operator'] = $innerdata['ccf_operator'];
          $data[$key]['status'] = ($data[$key]['customer_status']==1)?true:false;
          $data[$key]['customer_id'] = $param->customer_id;
          
     } 
     return  $data;
    }  
    
     
public function editSelectedcustomerServiceAccountStatus($param){  
    $return = $this->editServiceAccountStatus($param);
    if($return['status']=='success'){
         return array("status"=>"success","message"=>"Action Perform successfully",'actiongrid'=>'right32');
    }else{
        return array("status"=>"error","message"=>"Action not Performe",'actiongrid'=>'right32');
    }
  }
public function editSelectedcustomerSurchargeAccountStatus($param){  
    $return = $this->editSurchargeAccountStatus($param);
    if($return['status']=='success'){
         return array("status"=>"success","message"=>"Action Perform successfully",'actiongrid'=>'right33');
    }else{
        return array("status"=>"error","message"=>"Action not Performe",'actiongrid'=>'right33');
    }
  }
  
  /****get all users list from db by customer id****/
	public function getUserDataByCustomerId($param){  
	    $dataArr = array();
        $data =  $this->modelObj->getUserDataByCustomerId($param->customer_id,$param->company_id);
		foreach($data as $value){
			$value['is_default'] = ($value['is_default']) == 0 ? false : true ;
			array_push($dataArr,$value);
		}
        return $dataArr;
	}
	
	/****get all address list from db by customer id****/
	public function getCustomerAddressDataByCustomerId($param){  
        $data =  $this->modelObj->getCustomerAddressDataByCustomerId($param->customer_id);
        $result = array();
        foreach($data as $item){
            $item["warehouse_address"] = ($item["warehouse_address"]=="Y") ? "Y" : "N" ;
            array_push($result, $item);
        }
        return $result;
	}

	
	public function getUserAddressDataByUserId($param){  
        $data =  $this->modelObj->getUserAddressDataByUserId($param);
		$defaulAddressId = $this->modelObj->getUserDefaultAddressId($param);
        return array("address_list"=>$data,"default_address"=>$defaulAddressId);
	}
	
	public function editDefaultAddress($param){
		$data = array("address_line1"=>$param->address_line1,"address_line2"=>$param->address_line2,"postcode"=>$param->postcode,"city"=>$param->city,"state"=>$param->state,"country"=>$param->country);
		$condition = "id = ".$param->default_address->id."";
		$this->_parentObj->db->update('address_book', $data, $condition);
		
		$getDefaultAddressId = $this->modelObj->isDefaultAddressExist($param);
		if($getDefaultAddressId['address_exist']==0){
			$data = array("user_id"=>$param->id,"address_id"=>$param->default_address->address_obj->id,"default_address"=>"Y");
			$updateStatus = $this->_parentObj->db->save('user_address', $data);
		}else{
			$updateStatus = $this->_parentObj->db->updateData("UPDATE `".DB_PREFIX."user_address` SET `address_id` = ".$param->default_address->address_obj->id." WHERE `address_id` = ".$getDefaultAddressId['address_id']."");
		}
		if($updateStatus!= NULL){
		    $response["status"] = "success";
            $response["message"] = "Default address details updated successfully";
		}else{
			$response["status"] = "error";
            $response["message"] = "Failed to update default address details. Please try again";
		}
        return $response;
	}
   
	public function addUser($param){
		$data =  $this->modelObj->addUser($param);
        return $data;
	}
	
	public function addAddress($param){
		$data =  $this->modelObj->addAddress($param);
        return $data;
	}
	
	public function getAddressDataById($param){
		$data =  $this->modelObj->getAddressDataById($param);
        return $data;
	}
	
	public function deleteUserById($param){
		$response = array();
		$data =  $this->modelObj->deleteUserById($param);
		if ($data!= NULL) {
			$response["status"] = "success";
			$response["message"] = "User deleted successfully";  
		}else{
			$response["status"] = "error";
			$response["message"] = "Failed to delete user. Please try again";
		}
        return $response;
	}
	
	public function deleteAddressById($param){
		$response = array();
		$data =  $this->modelObj->deleteAddressById($param);
		if ($data!= NULL) {
			$response["status"] = "success";
			$response["message"] = "Address deleted successfully";  
		}else{
			$response["status"] = "error";
			$response["message"] = "Failed to delete address. Please try again";
		}
        return $response;
	}
	
	public function editUser($param){
		$response = array();
		$data =  $this->modelObj->editUser($param);
		if ($data!= NULL) {
			$response["status"] = "success";
			$response["message"] = "User updated successfully";  
		}else{
			$response["status"] = "error";
			$response["message"] = "Failed to update user. Please try again";
		}
        return $response;
	}
	
	public function editAddress($param){
		$response = array();

		$searchString = array($param->address_1, $param->address_2, $param->city, $param->state, $param->country, $param->postcode, $param->address_type, $param->email);

        $searchString = preg_replace('!\s+!', '', strtolower(implode('',$searchString)));

        $param->search_string = $searchString;
        
		$data =  $this->modelObj->editAddress($param);
		if ($data!= NULL) {
			$response["status"] = "success";
			$response["message"] = "Address updated successfully";  
		}else{
			$response["status"] = "error";
			$response["message"] = "Failed to update address. Please try again";
		}
        return $response;
	}
	
	public function setUserDefaultAddress($param){
		$updateStatus = $this->modelObj->setDefaultAddress($param);
		if($updateStatus){
		    $response["status"] = "success";
            $response["message"] = "Default address details updated successfully";
		}else{
			$response["status"] = "error";
            $response["message"] = "Failed to update default address details. Please try again";
		}
        return $response;
	}
	
	public function searchAddressByUserId($param){
		$response = array();
		$data = $this->modelObj->searchAddressByUserId($param);
		if($data!=NULL){
			$response["status"] = "success";
            $response["message"] = "Default address details updated successfully";
			$response["address"] = $data;
		}else{
			$response["status"] = "error";
            $response["message"] = "No address found";
			$response["address"] = array();
		}
		return $response;
	}

	public function setDefaultUser($param){
		if($param->default_exist=='Y'){ 
			$condition = "id = ".$param->default_user_id."";
			$removeExistingDefaultUser = $this->_parentObj->db->update('users', array("is_default"=>0), $condition);
			if($removeExistingDefaultUser){
					$condition = "id = ".$param->data->action."";
					$addNewDefaultUser = $this->_parentObj->db->update('users', array("is_default"=>$param->is_default), $condition);
					if($addNewDefaultUser)
						return array("status"=>"success","message"=>"Default user added successfully");
					else
						return array("status"=>"error","message"=>"Unable to add default user,please try again later");
			}else{
				return array("status"=>"error","message"=>"Unable to add default user,please try again later");
			}
		}else{
			$condition = "id = ".$param->data->action."";
			$addNewDefaultUser = $this->_parentObj->db->update('users', array("is_default"=>$param->is_default), $condition);
			if($addNewDefaultUser)
				return array("status"=>"success","message"=>"Default user added successfully");
			else
				return array("status"=>"error","message"=>"Unable to add default user,please try again later");
		}
	}
	
	public function getCustomerDefaultUser($param){
		$data =  $this->modelObj->checkDefaultUserExist(/* $param->company_id, */$param->customer_id);
		$response = array();
		if(count($data)>0)
			$response = array("status"=>"success","message"=>"Default user found","default_user_id"=>$data['id']);
		else
			$response = array("status"=>"error","message"=>"No default user found");
		return $response;
	}

	public function setCustomerDefaultWarehouse($param){
	    try{
            $this->modelObj->startTransaction();
            if($param->status==1){
                //set other address as "N"
                $status = $this->modelObj->disableCustomerWarehouseAddress($param);
                if($status){
                    //Set requested address as "Y"
                    $status = $this->modelObj->searchCustomerAddressByAddressId($param);
                    if($status>0){
                        $status = $this->modelObj->enableCustomerWarehouseAddress($param);
                    }else{
                        $status = $this->modelObj->saveCustomerWarehouseAddress($param);
                    }
                    if($status){
                        $this->modelObj->commitTransaction();
                        return array("status"=>"success","message"=>"Warehouse updated successfully");
                    }else{
                        $this->modelObj->rollBackTransaction();
                        return array("status"=>"error","message"=>"Warehouse not updated");
                    }
                }else{
                    $this->modelObj->rollBackTransaction();
                    return array("status"=>"error","message"=>"Warehouse not updated");
                }
            }else{
                $this->modelObj->disableCustomerWarehouseAddress($param);
            }
        }catch(Exception $e){
            $this->modelObj->rollBackTransaction();
            return array("status"=>"error","message"=>"Warehouse not updated");
        }
    }
}
?>