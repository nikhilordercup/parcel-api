<?php

class ServiceProvider extends Icargo
{

    private $_param = array();
    private $modelObj;

    public function __construct($data)
    {                        
        $this->_parentObj = parent::__construct(array("email" => $data->user_email, "access_token" => $data->access_token));        
        $this->_param = $data;        
        $this->modelObj = new ServiceProviderModel();        
    }
    
    public function getStripeCustomer() {
        $customerId =  $this->_param->customer_id;
        $companyId = $this->_param->company_id;
        $spId = $this->_param->service_provider_id;
        $spCustomer = $this->modelObj->getSPcustomerId($spId, $customerId);        
        return $spCustomer;
    }
    
    public function createStripeCustomer() {        
        $data = $this->_param;
        
        $postData = array(            
            'customer_id' => $data->stripe_data->customer_id,
            'sp_customer_id' => $data->stripe_data->sp_customer_id,
            'service_provider_id' => $data->stripe_data->service_provider_id,
            'customer_created' => $data->stripe_data->customer_created,            
            'status' => $data->stripe_data->status,            
            'created' => $data->stripe_data->created,            
            'updated' => $data->stripe_data->updated,            
            'json_data' => json_encode($data->stripe_data->json_data)
        );
        
        $spCustomer = $this->modelObj->createStripeCustomer( $postData );  
        $postData['id'] = $spCustomer;
        return $postData;
    }

    public function getCustomerServiceProvider()
    {
        $companyId = $this->_param->company_id;
        $customerId = $this->_param->customer_id;
        $spList = $this->modelObj->getServiceProvider($companyId);           
        $spId = $spList['id'];
        $spCustomer = $this->modelObj->getSPcustomerId($spId, $customerId); 
        $spList['customer_detail'] = ($spCustomer) ? $spCustomer : array(); 
        $spList['card_detail'] = array(); 
        if( isset($spCustomer['id']) ) {
            $spCustomerId = $spCustomer['sp_customer_id'];
            $cardDetail = $this->modelObj->getCustomerCardDetail($spId, $customerId, $spCustomerId);
            $spList['card_detail'] = ($cardDetail) ? $cardDetail : array();
        }        
        return $spList;
    }

    public function getServiceProviderById()
    {
        $companyId = $this->_param->company_id;
        $spId = $this->_param->sp_id;
        $spList = $this->modelObj->getServiceProvider($companyId, $spId);                         
        return $spList;
    }
    
    public function createUpdateCustomer()
    {
        $spCustomer = $this->modelObj->getcustomerId($this->_param);
    }

    public function saveCustomerToken()
    {        
        $data = $this->_param;
        $customer_id =  $this->_param->customer_id;
        $companyId = $this->_param->company_id;
        $spList = $this->modelObj->getServiceProvider($companyId);  
        
        //$spCustomer = $this->modelObj->getSPcustomerId($spList['id'], $customer_id);
        
        $postData = array(            
            'customer_id' => $data->customer_id,
            'sp_id'  => $data->sp_id,
            'sp_customer_id' => $data->sp_customer_id,
            'sp_token_id' => $data->stripe_token->id,
            'sp_card_id'  => $data->stripe_token->card->id,
            'card_last_four'  => $data->stripe_token->card->last4,
            'exp_month'  => $data->stripe_token->card->exp_month,
            'exp_year' => $data->stripe_token->card->exp_year,
            'city'  => $data->stripe_token->card->address_city,
            'state'  => $data->stripe_token->card->address_state,
            'country'  => $data->stripe_token->card->address_country,
            'zip_code'  => $data->stripe_token->card->address_zip,
            'address_line1'  => $data->stripe_token->card->address_line1,
            'address_line2'  => $data->stripe_token->card->address_line2,
            'card_type'  => $data->stripe_token->card->brand,
            'token_added' => $data->stripe_token->created,
            'json_data' => json_encode($data->stripe_token),
            'status'  => 1,
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s')
        );
                      
       $spCustomer = $this->modelObj->saveCustomerToken($postData);
       $postData['id'] = $postData;
       return $postData;
     
    }

    public function saveCustomerTransaction()
    {        
        $data = $this->_param;
        print_r($data);
        $customer_id =  $this->_param->customer_id;
        $companyId = $this->_param->company_id;
        $spList = $this->modelObj->getServiceProvider($companyId);  
        
        //$spCustomer = $this->modelObj->getSPcustomerId($spList['id'], $customer_id);
        
        $postData = array(            
            'customer_id' => $data->customer_id,
            'sp_id'  => $data->sp_id,
            'sp_customer_id' => $data->sp_customer_id,
            'sp_token_id' => $data->stripe_token->id,
            'sp_card_id'  => $data->stripe_token->card->id,
            'card_last_four'  => $data->stripe_token->card->last4,
            'exp_month'  => $data->stripe_token->card->exp_month,
            'exp_year' => $data->stripe_token->card->exp_year,
            'city'  => $data->stripe_token->card->address_city,
            'state'  => $data->stripe_token->card->address_state,
            'country'  => $data->stripe_token->card->address_country,
            'zip_code'  => $data->stripe_token->card->address_zip,
            'address_line1'  => $data->stripe_token->card->address_line1,
            'address_line2'  => $data->stripe_token->card->address_line2,
            'card_type'  => $data->stripe_token->card->brand,
            'token_added' => $data->stripe_token->created,
            'json_data' => json_encode($data->stripe_token),
            'status'  => 1,
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s')
        );
                      
       $spCustomer = $this->modelObj->saveCustomerToken($postData);
       $postData['id'] = $postData;
       return $postData;
     
    }
    
}
?>