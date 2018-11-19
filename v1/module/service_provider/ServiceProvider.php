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
        //print_r($data); die;
        $customer_id =  $this->_param->customer_id;
        $companyId = $this->_param->company_id;                
        //$spCustomer = $this->modelObj->getSPcustomerId($spList['id'], $customer_id);
        
        $postData = array(            
            'customer_id'           => $data->customer_id,
            'sp_id'                 => $data->sp_id,
            'sp_customer_id'        => $data->sp_customer_id,
            'charge_id'             => $data->charge_detail->id,
            'card_id'               => $data->charge_detail->source->id,
            'transaction_id'        => $data->charge_detail->balance_transaction,
            'currency'              => $data->charge_detail->currency,
            'amount'                => $data->charge_detail->amount,
            'card_last_four'        => $data->charge_detail->source->last4,
            'exp_month'             => $data->charge_detail->source->exp_month,
            'exp_year'              => $data->charge_detail->source->exp_year,
            //'city'                  => $data->charge_detail->source->address_city,
            //'state'                 => $data->charge_detail->source->address_state,
            //'country'               => $data->charge_detail->source->address_country,
            //'zip_code'              => $data->charge_detail->source->address_zip,
            //'address_line1'         => $data->charge_detail->source->address_line1,
            //'address_line2'         => $data->charge_detail->source->address_line2,
            'card_type'             => $data->charge_detail->source->brand,
            'transaction_created'   => $data->charge_detail->created,
            'json_data'             => json_encode($data->charge_detail),
            'status'                => 1,
            'created'               => date('Y-m-d H:i:s'),
            'updated'               => date('Y-m-d H:i:s')
        );
                      
       $spCustomer = $this->modelObj->saveCustomerTransaction($postData);
       
       if($spCustomer) {
            $customerData = array(
                'access_token' => $data->access_token,
                'company_id' => $data->company_id,
                'customer' => $data->customer_id,
                'email' => $data->user_email,
                'endPointUrl' => 'prepaidrecharge',
                'payamount' => $data->charge_detail->amount,
                'payment_reference' => $spCustomer,
                'payment_desc' => $data->charge_detail->description,
                'payment_for' => ($data->customer_type == 'PREPAID') ? 'RECHARGE' : 'PAYINVOICE',
                'paymode' => 'ONLINE',
                'payment_provider' => 'Stripe',
                'paydate' => date('Y-m-d'),
            );          
                        
            //$postData['id'] = $postData;
            $invoiceObj = new Invoice( (object)$customerData );
            if($data->customer_type == 'PREPAID') {
                $response = $invoiceObj->prepaidrecharge((object)$customerData );           
            } else {
                $iDetail = (array)$data->invoice_detail;
                $invoice = array_merge($customerData, $iDetail);
                
                $response = $invoiceObj->payinvoices((object)$invoice );
            }
        } else {
            $response = array('status'=>false, 'message'=>"Not able to create the data, please try again.");
        }
        return $response;
    
    }
    
}
?>