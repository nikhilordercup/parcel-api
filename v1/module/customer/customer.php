<?php
class Customer extends Icargo{

    public $modelObj = null;
	private $_user_id;
	protected $_parentObj;
	public $fb  = null;
	private function _setUserId($v){
		$this->_user_id = $v;
	}

	private function _getUserId(){
		return $this->_user_id;
	}

	public function __construct($data){
		$this->_parentObj = parent::__construct(array("email"=>$data->email, "access_token"=>$data->access_token));
        $this->modelObj  = Customer_Model::getInstanse();
        $this->fb       = new Firebase_Api();
	}



    public function saveCustomer($param){
         $data = array();
         $exist = $this->modelObj->checkCustomerEmailExist($param->customer->customer_email);
         if($exist >0){
           return array("status"=>"error","message"=>"Customer Email Already Exist ");
         }
         if($param->customer->name!='' && $param->customer->type!='' && $param->customer->invoicecycle!='' && $param->customer->customer_email!='' && $param->customer->password!=''){
            if($param->customer->type =='POSTPAID' && $param->customer->creditlimit==''){
              return array("status"=>"error","message"=>"Please Fill Credit limit in Number");
            }
             $companyConf = $this->modelObj->getCompanyConfiguration($param->company_id);
             $companyConf = json_decode($companyConf["configuration_json"]);

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
                "country"=>isset($param->customer->country->short_name)?$param->customer->country->short_name:'',
                "status"=>"1",
                "uid"=>$param->uid,
                "register_in_firebase"=>"1",
                "email_verified"=>"1",
                "access_token"=>"",
                "free_trial_expiry"=>"1970-01-01 00:00:00",
                "parent_id"=>$param->company_id,"is_default"=>1);
                $customer_id = $this->modelObj->addContent('users',$data);

                $this->modelObj->addContent('company_warehouse',array('company_id'=>$customer_id,'warehouse_id'=>$param->warehouse_id,'status'=>"1",'update_date'=>date("Y-m-d h:i:s", strtotime('now'))));

                $this->modelObj->addContent('company_users',array('user_id'=>$customer_id,'company_id'=>$param->company_id,'warehouse_id'=>$param->warehouse_id,'status'=>"1",'update_date'=>date("Y-m-d h:i:s", strtotime('now'))));

                $customerinfo  = array();
                $customerinfo['ccf'] = (isset($param->customer->ccf) && $param->customer->ccf !='')?$param->customer->ccf:0.00;
                $customerinfo['ccf_operator_service'] = (isset($param->customer->ccf_operator) && ($param->customer->ccf_operator !=''))?$param->customer->ccf_operator:'NONE';
                $customerinfo['ccf_operator_surcharge'] = (isset($param->customer->surcharge_operator) && ($param->customer->surcharge_operator !=''))?$param->customer->surcharge_operator:'NONE';
                $customerinfo['apply_ccf'] = "1";
                $customerinfo['user_id'] = $customer_id;
                $customerinfo['surcharge'] = (isset($param->customer->surcharge) && ($param->customer->surcharge !=''))?$param->customer->surcharge:0.00;
                $customerinfo['address_id'] = "0";
                $customerinfo['customer_type'] = $param->customer->type;
                $customerinfo['accountnumber'] = $this->generateCustomerAccount($param->companyname,$customer_id);
                $customerinfo['vatnumber']    = isset($param->customer->vatnumber)?$param->customer->vatnumber:'';
                $customerinfo['creditlimit'] = isset($param->customer->creditlimit)?$param->customer->creditlimit:0;
                $customerinfo['available_credit'] = ($param->customer->type=='POSTPAID')?$customerinfo['creditlimit']:$param->customer->availablebalance;
                $customerinfo['invoicecycle'] = $param->customer->invoicecycle;
                $customerinfo['charge_from_base'] = $param->customer->charge_from_base;
                $customerinfo['tax_exempt'] = $param->customer->tax_exempt;

                if(isset($param->customer->round_trip)){
                    $customerinfo['round_trip'] = $param->customer->round_trip;
                }elseif($companyConf->round_trip){
                    $customerinfo['round_trip'] = $companyConf->round_trip;
                }else{
                    $customerinfo['round_trip'] = ROUND_TRIP;
                }

                if(isset($param->customer->driving_mode)){
                    $customerinfo['driving_mode'] = $param->customer->driving_mode;
                }elseif($companyConf->driving_mode){
                    $customerinfo['driving_mode'] = $companyConf->driving_mode;
                }else{
                    $customerinfo['driving_mode'] = DRIVING_MODE;
                }

                $this->modelObj->addContent('customer_info',$customerinfo);
            //if($customerinfo['customer_type']=='POSTPAID'){
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
            //}
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
          //$infoStatus    = $this->modelObj->editContent("customer_info",array('webapi_token'=>encodeJwtData(array('customer'=>$val['accountnumber']))), "user_id = '" . $val['id'] . "'");
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
        $data = $this->modelObj->getAllCourierServicesForCustomer($param->company_id/* ,$param->viewid */);
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
        $data = $this->modelObj->getAllCourierSurchargeForCustomer($param->company_id/* ,$param->viewid */);
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
      $data['customer']['availablebalance'] = (float)$customerpersonaldata['availablebalance'];
      $data['customer']['invoicecycle'] = (int)$customerpersonaldata['invoicecycle'];
      $data['customer']['type'] = $customerpersonaldata['customer_type'];
      $data['customer']['charge_from_base'] = $customerpersonaldata['charge_from_base'];
      $data['customer']['tax_exempt'] = $customerpersonaldata['tax_exempt'];
      $data['customer']['auto_label_print'] = $customerpersonaldata['auto_label_print'];
      $data['customer']['round_trip'] = $customerpersonaldata['round_trip'];
      $data['customer']['driving_mode'] = $customerpersonaldata['driving_mode'];

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
     $companyConf = $this->modelObj->getCompanyConfiguration($param->company_id);
     $companyConf = json_decode($companyConf["configuration_json"]);
            $data = array(
                "name"=>$param->customer->name,
                "phone"=>isset($param->customer->phone)?$param->customer->phone:'',
                "postcode"=>isset($param->customer->postcode)?$param->customer->postcode:'',
                "city"=>isset($param->customer->city)?$param->customer->city:'',
                "state"=>isset($param->customer->state)?$param->customer->state:'',
                //"country"=>isset($param->customer->country)?$param->customer->country:'',
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
            $customerinfo['charge_from_base'] = isset($param->customer->charge_from_base)?$param->customer->charge_from_base:'YES';
            $customerinfo['tax_exempt'] = isset($param->customer->tax_exempt)?$param->customer->tax_exempt:'YES';
            $customerinfo['auto_label_print'] = isset($param->customer->auto_label_print)?$param->customer->auto_label_print:'YES';

            //$customerinfo['round_trip'] = isset($param->customer->round_trip) ? $param->customer->round_trip : 'NO';
            //$customerinfo['driving_mode'] = isset($param->customer->driving_mode) ? $param->customer->driving_mode : 'BICYCLING';

            if(isset($param->customer->round_trip)){
                $customerinfo['round_trip'] = $param->customer->round_trip;
            }elseif($companyConf->round_trip){
                $customerinfo['round_trip'] = $companyConf->round_trip;
            }else{
                $customerinfo['round_trip'] = ROUND_TRIP;
            }

            if(isset($param->customer->driving_mode)){
                $customerinfo['driving_mode'] = $param->customer->driving_mode;
            }elseif($companyConf->driving_mode){
                $customerinfo['driving_mode'] = $companyConf->driving_mode;
            }else{
                $customerinfo['driving_mode'] = DRIVING_MODE;
            }

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
     // $data = $this->modelObj->getAllCourierServicesForCustomer($param->company_id,$param->cid);
      $data = $this->modelObj->getAllCourierServicesForCustomer($param->company_id/* ,$param->viewid */);
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
      //$data = $this->modelObj->getAllCourierSurchargeForCustomer($param->company_id,$param->cid);
      $data = $this->modelObj->getAllCourierSurchargeForCustomer($param->company_id/* ,$param->viewid */);
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

	/***get all address list from db by customer id****/
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

	public function editAddress($param){//print_r($param);die;
		$carrier_time_data = array();
		 if(isset($param->carrier)){
			$carrier_time_data = $param->carrier;
			unset($param->carrier);
		}
		$response = array();
		//print_r($param);die;
//echo($param->address_1.'-'.$param->address_2.'--'.$param->city.'---'.$param->state.'----'.$param->country.'-----'.$param->postcode.'------'.$param->address_type.'-------'.$param->email.'--------');die;
		$searchString = array($param->address_1, $param->address_2, $param->city, $param->state, $param->country, $param->postcode, $param->address_type, $param->email);
		//$searchString = array($param->address_1, $param->address_2, $param->city, $param->state, $param->country, $param->postcode, $param->address_type, $param->email);
        //print_r($searchString);die;
        $searchString = preg_replace('!\s+!', '', strtolower(implode('',$searchString)));

        $param->search_string = $searchString;
        $param->country = $param->country;
		$data =  $this->modelObj->editAddress($param);

		if ($data!= NULL) {
			if(count($carrier_time_data)>0){
				foreach($carrier_time_data as $carrier=>$carrier_time){
					$deleteCarrierData = $this->modelObj->deleteCarrierData($carrier,$param->customer_id,$param->id);
					$addCarrierData = $this->modelObj->addCarrierData($carrier,$param->customer_id,$carrier_time,$param->id);
				}
			}
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
		$data =  $this->modelObj->checkDefaultUserExist(/* $param->company_id,*/$param->customer_id);
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

	public function setCustomerWarehouse($param){
	    try{
            $this->modelObj->startTransaction();
            if($param->status==1){
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
				$status = $this->modelObj->disableCustomerWarehouseAddressByAddressId($param);
				if($status){
					$this->modelObj->commitTransaction();
					return array("status"=>"success","message"=>"Warehouse updated successfully");
				}else{
					$this->modelObj->rollBackTransaction();
					return array("status"=>"error","message"=>"Warehouse not updated");
				}
			}
        }catch(Exception $e){
            $this->modelObj->rollBackTransaction();
            return array("status"=>"error","message"=>"Warehouse not updated");
        }
    }

	public function getAddressBySearchString($param){
		$data =  $this->modelObj->getAddressBySearchStr($param);
		return $data;

	}

    public function getCustomerAllTransactionData($param){
        $data =  $this->modelObj->getCustomerAllTransactionData($param->customer_id);
        $result = array();
        foreach($data as $item){
            $temp = array();
            $temp['date'] = date('d-m-Y',strtotime($item['create_date']));
            $temp['credit'] = ($item['payment_type']=='CREDIT')?$item['amount']:'';
            $temp['debit']  = ($item['payment_type']=='DEBIT')?$item['amount']:'';
            $temp['amount'] = $item['balance'];
            $temp['reference'] = $item['payment_reference'];
            $temp['description'] = $item['payment_desc'];
            $temp['payment_for'] = $item['payment_for'];
            array_push($result, $temp);
        }
        return $result;
	}
    public function getAuthorizationData($param){
        $data =  $this->modelObj->getCustomerAllAuthorizationData($param->customer_id);
        foreach($data as $key=>$item){
            $data[$key]['status'] =  ($item['status']==1)?true:false;
            $data[$key]['actioncode'] =  'Authorization';
        }
        return $data;
	}
    public function editAuthorizationStatus($param){
		$response = array();
		$data =  $this->modelObj->editAuthorizationStatus($param);
		if ($data!= NULL) {
			$response["status"] = "success";
			$response["message"] = "updated successfully";
		}else{
			$response["status"] = "error";
			$response["message"] = "Failed to update. Please try again";
		}
        return $response;
	}
    public function editAuthorization($param){
		$response = array();
		$data =  $this->modelObj->editAuthorization($param);
		if ($data!= NULL) {
			$response["status"] = "success";
			$response["message"] = "updated successfully";
		}else{
			$response["status"] = "error";
			$response["message"] = "Failed to update. Please try again";
		}
        return $response;
	}
    public function addAuthorization($param){
        $temp = array();
        $temp['title'] = $param->title;
        $temp['description'] = $param->description;
        $temp['url'] = $param->url;
        $temp['token'] = '';
        $temp['customer_id'] = $param->customer_id;
        $tokenId = $this->modelObj->addContent('customer_tokens',$temp);
        if($tokenId){
           $token  =  encodeJwtData(array("identity"=>$tokenId,"email"=>$param->email,"iss"=>"icargo","iat"=>1493968811));
           $status =  $this->modelObj->editContent("customer_tokens",array("token"=>$token),"token_id =  $tokenId");
           if($status){
             $response["status"] = "success";
			 $response["message"] = "Created successfully";
           }
        }else{
            $response["status"] = "error";
			$response["message"] = "Failed to create. Please try again";
        }
        return $response;
	 }


    private function getOriginandDestination($jobId){
        $shipmentsData = $this->modelObj->getjobDetails($jobId);
        $dataArray   =     array();
        $data   =     array();
        foreach($shipmentsData as $key=>$val){
          $val['instaDispatch_loadGroupTypeCode']  = strtoupper($val['instaDispatch_loadGroupTypeCode']);
          $dataArray[$val['instaDispatch_loadIdentity']][strtoupper($val['instaDispatch_loadGroupTypeCode'])][$val['shipment_service_type']][]   = $val;
         }

        if(count($dataArray)>0){
          foreach($dataArray as $innerkey=>$innerval){
            if(key($innerval) == 'SAME'){
              if(array_key_exists('P',$innerval['SAME'])){
               foreach($innerval['SAME']['P'] as $pickupkey=>$pickupData){
                 $data['collection'] = $pickupData['shipment_postcode'].' '.$pickupData['shipment_customer_country'];
                 $data['collection_date'] = $pickupData['shipment_required_service_date'];

              }
            }
              if(array_key_exists('D',$innerval['SAME'])){
                $temp = array();
                foreach ($innerval['SAME']['D'] as $key => $row){
                   $temp[$key] = $row['icargo_execution_order'];
                }
                array_multisort($temp, SORT_ASC, $innerval['SAME']['D']);
                $lastDeliveryarray =  end($innerval['SAME']['D']);
                $data['delivery']  = $lastDeliveryarray['shipment_postcode'].' '.$lastDeliveryarray['shipment_customer_country'];
            }
            }
            if(key($innerval) == 'NEXT'){
             if(array_key_exists('P',$innerval['NEXT'])){
               foreach($innerval['NEXT']['P'] as $pickupkey=>$pickupData){
                  $data['collection']          = $pickupData['shipment_postcode'].' '.$pickupData['shipment_customer_country'];
                    $data['collection_date'] = $pickupData['shipment_required_service_date'];
              }
            }
             if(array_key_exists('D',$innerval['NEXT'])){
                krsort($innerval['NEXT']['D']);
                $deliveryPostcode = array();
                foreach($innerval['NEXT']['D'] as $deliverykey=>$deliveryData){
                 $deliveryPostcode[$deliveryData['icargo_execution_order']]  = $deliveryData['shipment_postcode'].' '.$deliveryData['shipment_customer_country'];
                }
                krsort($deliveryPostcode);
                $data['delivery']  = end($deliveryPostcode);
            }
           }
          }
        }
       return $data;
   }
    public function downloadAccountStatements($param){
        $data =  $this->modelObj->downloadAccountStatements($param->customer_id,$param->from,$param->to,$param->company_id);
        $customerdata =  $this->modelObj->getCustomerDetails($param->customer_id);
        $companydata  =  $this->modelObj->getCompanyDetails($param->company_id);
        $customerdata =  array_merge($companydata,$customerdata);

        $img_file = realpath(dirname(dirname(dirname(dirname(__FILE__))))).'/assets/logo/'.$companydata['logo'];
        $imgData = base64_encode(file_get_contents($img_file));
        $src = 'data:'.mime_content_type($img_file).';charset=binary;base64,'.$imgData;
        
        foreach($customerdata as $key=>$val){
            $customerdata[$key] = !empty($val)?$val:'NA';
        }
        $pdfData   = array();
        $ammountBucket = array('creditBucket'=>array(),'debitBucket'=>array());
        if(count($data)>0){
            foreach($data as $key=>$value){
              if($value['payment_for'] == 'RECHARGE'){
                    $temp = array();
                    $temp['reference']          = $value['payment_reference'];
                    $temp['invoice_type']       = $value['payment_for'];
                    $temp['collection_date']    = date('Y-m-d',strtotime($value['create_date']));
                    $temp['origin']             = 'NA';
                    $temp['destination']        = 'NA';
                    $temp['transaction']        = $value['payment_type'];
                    $temp['chargable_value']    = 'NA';
                    $temp['reference1']         = 'NA';
                    $temp['reference2']         = 'NA';
                    $temp['service_name']       = 'NA';
                    $temp['customer_booking_reference'] = $value['payment_reference'];
                    $temp['base_amount']        = '0.00';
                    $temp['surcharge_total']    = 0.00;
                    $temp['fual_surcharge']     = 0.00;
                    $temp['tax']                = '0.00';
                    $temp['total']              = $value['amount'];
                    if($value['payment_type']=='DEBIT'){
                        $ammountBucket['debitBucket'][] = $value['amount'];
                    }elseif($value['payment_type']=='CREDIT'){
                        $ammountBucket['creditBucket'][] = $value['amount'];
                    }else{
                        //
                    }
                    $pdfData[] = $temp;
               }elseif($value['payment_for'] == 'BOOKSHIP' || $value['payment_for'] == 'PRICECHANGE' || $value['payment_for'] == 'CANCELSHIP'){
                $temp = array();
                $getDocketOriginandEndPoint = $this->getOriginandDestination($value['payment_reference']);
                if(!empty($getDocketOriginandEndPoint)){
                    $temp['origin'] = isset($getDocketOriginandEndPoint['collection'])?$getDocketOriginandEndPoint['collection']:'';
                    $temp['destination'] = isset($getDocketOriginandEndPoint['delivery'])?$getDocketOriginandEndPoint['delivery']:'';
                    $temp['collection_date'] = ($getDocketOriginandEndPoint['collection_date']=='0000-00-00')?'1970-01-01':$getDocketOriginandEndPoint['collection_date'];
                 }else{
                    $temp['collection_date']    = 'NA';
                    $temp['origin']             = 'NA';
                    $temp['destination']        = 'NA';
                 }
                $shipmentDetails    =  $this->modelObj->getShipmentDetails($value['payment_reference'],$param->company_id);
                $temp['reference']          = $value['payment_reference'];
                $temp['reference1']         = (isset($shipmentDetails['reference1']) && $shipmentDetails['reference1']!='')?$shipmentDetails['reference1']:'NA';
                $temp['reference2']         = (isset($shipmentDetails['reference2']) && $shipmentDetails['reference1']!='')?$shipmentDetails['reference2']:'NA';
                $temp['invoice_type']       = $value['payment_for'];
                $temp['transaction']        = $value['payment_type'];
                $temp['chargable_value']    = isset($shipmentDetails['chargable_value'])?$shipmentDetails['chargable_value']:'0';
                $temp['service_name']       = isset($shipmentDetails['service_name'])?$shipmentDetails['service_name']:'NA';
                $temp['customer_booking_reference'] = isset($shipmentDetails['customer_booking_reference'])?$shipmentDetails['customer_booking_reference']:'NA';
                $temp['base_amount']        =  isset($shipmentDetails['base_amount'])?$shipmentDetails['base_amount']:'0';
                $temp['fual_surcharge']     =  isset($shipmentDetails['fual_surcharge'])?$shipmentDetails['fual_surcharge']:0;
                $temp['surcharge_total']    =  ($shipmentDetails['surcharge_total']-$temp['fual_surcharge']);
                $temp['tax']                =  isset($shipmentDetails['tax'])?$shipmentDetails['tax']:'0';
                $temp['total']              =  ($temp['base_amount']+$temp['surcharge_total']+$temp['fual_surcharge']+$temp['tax']);
                if($value['payment_type']=='DEBIT'){
                        $ammountBucket['debitBucket'][] = $value['amount'];
                }elseif($value['payment_type']=='CREDIT'){
                        $ammountBucket['creditBucket'][] = $value['amount'];
                }else{
                        //
                    }
               $pdfData[] = $temp;
              }else{
                  //
              }
            }
        }
        $result = array('amount'=>array('creditBucket'=>array_sum($ammountBucket['creditBucket']),'debitBucket'=>array_sum($ammountBucket['debitBucket']))
                        ,'data'=>$pdfData,'customer'=>$customerdata,'companyimg'=>$src);
        return $result;
	}

     public function checkCustomerData($param){
         $tempdata = $param->data;
         $errorBucket = array();
         $successBucket = array();
         $param->uid = '0TEST00';
         $param->customer        = (object)array('country'=>(object)array());
         $param->customerbilling = (object)array('country'=>(object)array());
         $param->customerpickup  = (object)array('country'=>(object)array());
         unset($param->data);
         foreach($tempdata as $key=>$dataOfCustomers){
             $isErrorRow = false;
             if(!isset($dataOfCustomers->customername) || $dataOfCustomers->customername==''){
                 $dataOfCustomers->status = "Company Name is missing "; $isErrorRow = true;
             }elseif(!isset($dataOfCustomers->email) || $dataOfCustomers->email=='' || !(filter_var($dataOfCustomers->email, FILTER_VALIDATE_EMAIL))){
                  $dataOfCustomers->status = "Email is missing"; $isErrorRow = true;
             }elseif(!isset($dataOfCustomers->customertype) || ($dataOfCustomers->customertype=='') || !in_array($dataOfCustomers->customertype,array('POSTPAID','PREPAID'))){
                  $dataOfCustomers->status = "Customer type is missing or invalid"; $isErrorRow = true;

             }elseif(!isset($dataOfCustomers->creditlimit) || $dataOfCustomers->creditlimit=='' || !is_numeric($dataOfCustomers->creditlimit)){
                  $dataOfCustomers->status = "Credit limit is missing or invalid"; $isErrorRow = true;

             }elseif(!isset($dataOfCustomers->availablebalance) || $dataOfCustomers->availablebalance=='' || !is_numeric($dataOfCustomers->availablebalance)){
                  $dataOfCustomers->status = "Available balance is missing or invalid"; $isErrorRow = true;

             }elseif(!isset($dataOfCustomers->invoicecycle) || $dataOfCustomers->invoicecycle=='' || !is_numeric($dataOfCustomers->invoicecycle)){
                  $dataOfCustomers->status = "Invoice cycle is missing or invalid"; $isErrorRow = true;

             }elseif(!isset($dataOfCustomers->password) || $dataOfCustomers->password==''){
                  $dataOfCustomers->status = "Password is missing"; $isErrorRow = true;

             }elseif(!isset($dataOfCustomers->chargefrombase) || $dataOfCustomers->chargefrombase=='' || !in_array($dataOfCustomers->chargefrombase,array('YES','NO'))){
                  $dataOfCustomers->status = "Charge from base is missing or invalid"; $isErrorRow = true;

             }elseif(!isset($dataOfCustomers->taxexempt) || $dataOfCustomers->taxexempt=='' || !in_array($dataOfCustomers->taxexempt,array('YES','NO'))){
                  $dataOfCustomers->status = "Tax exempt is missing or invalid"; $isErrorRow = true;

             }elseif(!isset($dataOfCustomers->countryname) || $dataOfCustomers->countryname==''){
                  $dataOfCustomers->status = "Country Name is missing or invalid"; $isErrorRow = true;

             }elseif(!isset($dataOfCustomers->address1) || $dataOfCustomers->address1==''){
                  $dataOfCustomers->status = "Address1 is missing"; $isErrorRow = true;

             }elseif(!isset($dataOfCustomers->countrycode) || $dataOfCustomers->countrycode=='' || !$this->isExistCountryCode($dataOfCustomers->countrycode)){
                  $dataOfCustomers->status = "Country code is missing or invalid"; $isErrorRow = true;

             }elseif(!isset($dataOfCustomers->state) || $dataOfCustomers->state==''){
                   $dataOfCustomers->status = "State is missing or invalid"; $isErrorRow = true;

             }elseif(isset($dataOfCustomers->postcode) && $dataOfCustomers->postcode!='' && !$this->isValidPostcode($dataOfCustomers->postcode,$dataOfCustomers->countrycode)){ //
                  $dataOfCustomers->status = "postcode value is invalid"; $isErrorRow = true;

             }elseif(isset($dataOfCustomers->phone) && $dataOfCustomers->phone!='' && !$this->isValidPhone($dataOfCustomers->phone)){ //
                  $dataOfCustomers->status = "phone value is invalid"; $isErrorRow = true;

             }elseif(isset($dataOfCustomers->ccf) && $dataOfCustomers->ccf!='' && !is_numeric($dataOfCustomers->ccf)){
                  $dataOfCustomers->status = "ccf value is invalid"; $isErrorRow = true;

             }elseif(isset($dataOfCustomers->surcharge)  && $dataOfCustomers->surcharge!='' && !is_numeric($dataOfCustomers->surcharge)){
                  $dataOfCustomers->status = "surcharge value is invalid"; $isErrorRow = true;

             }elseif(isset($dataOfCustomers->surchargeoperator) && ($dataOfCustomers->surchargeoperator!='') && (!in_array($dataOfCustomers->surchargeoperator,array('FLAT','PERCENTAGE')))){
                  $dataOfCustomers->status = "surcharge operator value is invalid"; $isErrorRow = true;

             }elseif(isset($dataOfCustomers->ccfoperator) && ($dataOfCustomers->ccfoperator!='') && (!in_array($dataOfCustomers->ccfoperator,array('FLAT','PERCENTAGE')))){
                  $dataOfCustomers->status = "ccf operator value is invalid"; $isErrorRow = true;

             }elseif(!($this->registeronFirebase($dataOfCustomers->email,$dataOfCustomers->password))){
                   $dataOfCustomers->status = "email already registered with icargo"; $isErrorRow = true;

             }else{
             $param->customer->name                 = $dataOfCustomers->customername;
             $param->customer->customer_email       = $dataOfCustomers->email;
             $param->customer->type                 = $dataOfCustomers->customertype;
             $param->customer->creditlimit          = $dataOfCustomers->creditlimit;
             $param->customer->ccf_operator         = $dataOfCustomers->ccfoperator;
             $param->customer->surcharge            = $dataOfCustomers->surcharge;
             $param->customer->ccf                  = $dataOfCustomers->ccf;
             $param->customer->surcharge_operator   = $dataOfCustomers->surchargeoperator;
             $param->customer->availablebalance     = $dataOfCustomers->availablebalance;
             $param->customer->invoicecycle         = $dataOfCustomers->invoicecycle;
             $param->customer->password             = $dataOfCustomers->password;
             $param->customer->charge_from_base     = $dataOfCustomers->chargefrombase;
             $param->customer->tax_exempt           = $dataOfCustomers->taxexempt;
             $param->customer->vatnumber            = $dataOfCustomers->vatnumber;
             $param->customer->country->short_name  = $dataOfCustomers->countryname;

             $param->customerbilling->address_1               = $dataOfCustomers->address1;
             $param->customerbilling->country->short_name     = $dataOfCustomers->countryname;
             $param->customerbilling->country->alpha3_code    = $dataOfCustomers->countrycode;
             $param->customerbilling->state                   = $dataOfCustomers->state;

             $param->customerbilling->address_2               = $dataOfCustomers->address2;
             $param->customerbilling->city                    = $dataOfCustomers->city;
             $param->customerbilling->name                    = $dataOfCustomers->person;
             $param->customerbilling->phone                   = $dataOfCustomers->phone;
             $param->customerbilling->postcode                = $dataOfCustomers->postcode;


             $param->customerpickup->address_1               = $dataOfCustomers->address1;
             $param->customerpickup->country->short_name     = $dataOfCustomers->countryname;
             $param->customerpickup->country->alpha3_code    = $dataOfCustomers->countrycode;
             $param->customerpickup->state                   = $dataOfCustomers->state;

             $param->customerpickup->address_2               = $dataOfCustomers->address2;
             $param->customerpickup->city                    = $dataOfCustomers->city;
             $param->customerpickup->name                    = $dataOfCustomers->person;
             $param->customerpickup->phone                   = $dataOfCustomers->phone;
             $param->customerpickup->postcode                = $dataOfCustomers->postcode;
             $customerData    =  $this->saveCustomer($param);
             if($customerData['status']=='success'){
                 $customer_id           = $customerData['customer_id'];
                 $customerAccountData   = $this->saveCustomerAccount($param->company_id,$customer_id);
                 $successBucket[]       = $customerData;
             }else{
                 $errorBucket[] =   $dataOfCustomers;
               }
             }
             if($isErrorRow){
              $errorBucket[] =   $dataOfCustomers;
             }
          }
      return array('edata'=>$errorBucket,'message'=>'Total '.count($successBucket).' customer created and '.count($errorBucket).' customer creation request failed.','status'=>'success');
   }
    public function isExistCountryCode($code){
        return ($this->modelObj->checkCountryCodeExist($code) > 0)?true:false;
    }
    public function registeronFirebase($email,$password){
        try{
          //$fb = new Firebase_Api();
          $firebase = $this->fb->getFirebase();
          $userProperties = [
              'email' => $email,
              'emailVerified' => false,
              //'phoneNumber' => '+919540925227',
              'password' => $password,
              //'displayName' => 'Roopesh',
              //'photoUrl' => 'http://app-tree.co.uk/icargoN/assets/img/iCargo-Logo.png',
              'disabled' => false,
          ];
          $status = $firebase->getAuth()->createUser($userProperties);
          $this->newUid = $status->uid;
          return true;
        }catch(Exception $e){
          return false;
        }
    }
    public function isValidPostcode($postcode,$countrycode){
    $postcode = strtoupper(str_replace(' ','',$postcode));
    if(preg_match("/(^[A-Z]{1,2}[0-9R][0-9A-Z]?[\s]?[0-9][ABD-HJLNP-UW-Z]{2}$)/i",$postcode) || preg_match("/(^[A-Z]{1,2}[0-9R][0-9A-Z]$)/i",$postcode))
     {
        return true;
    }
    else
    {
        return false;
    }
}
    public function isValidPhone($phone){
     $filtered_phone_number = filter_var($phone, FILTER_SANITIZE_NUMBER_INT);
     $phone_to_check = str_replace("-", "", $filtered_phone_number);
     if (strlen($phone_to_check) < 10 || strlen($phone_to_check) > 14) {
        return false;
     } else {
       return true;
     }
}
    public function saveCustomerAccount($company_id,$customerId){
      $this->saveCarrierCustomerFirebaseInfo((object)array('uid'=>$this->newUid,'customer_id'=>$customerId));
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
    }
?>
