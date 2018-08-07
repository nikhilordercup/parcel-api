<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of SubscriptionController
 *
 * @author perce_qzotijf
 */
class SubscriptionController {

    protected $_parentObj;
    private $_db;
    private $_app;
    private $_requestParams;
    private $_table = 'user_grid_states';

    /**
     * Driver Controller constructor.
     */
    private function __construct($app) {
        $this->_db = new DbHandler();
        $this->_app = $app;
        $this->_requestParams = json_decode($this->_app->request->getBody());
        ChargeBee_Environment::configure("parcel-test", "test_gGnPwVHV3LAzCRzwWQUlrnQfOT8Mrnmcu");
    }

    /**
     * Register routes for subscription data
     * @param type $app
     */
    public static function initRoutes($app) {

        $app->post('/subscribePlan', function() use ($app) {
            $self = new SubscriptionController($app);
            $r = json_decode($app->request->getBody());
            verifyRequiredParams(array('access_token'), $r);
            $data = $self->subscribePlan($r->company_id);
            echoResponse(200, array('result' => 'success', 'message' => json_encode($data)));
        });
        $app->post('/getUsedShipmentCount', function() use ($app) {
            $self = new SubscriptionController($app);
            $r = json_decode($app->request->getBody());
            verifyRequiredParams(array('access_token'), $r);
            $data = $self->getShipmentCounts($r->company_id);
            $subscription=$self->getPlanInfo($r->company_id);
            echoResponse(200, array('result' => 'success', 'message' => $data,'subscription'=>$subscription));
        });
        $app->post('/getUserInfo', function() use ($app) {
            $self = new SubscriptionController($app);
            $r = json_decode($app->request->getBody());
            verifyRequiredParams(array('access_token'), $r);
            $data = $self->getUserInfo($r->access_token);          
            echoResponse(200, array('result' => 'success', 'message' => $data));
        });
        $app->post('/updateName', function() use ($app) {
            $self = new SubscriptionController($app);
            $r = json_decode($app->request->getBody());
            verifyRequiredParams(array('access_token'), $r);
            $data = $self->updateName($r->access_token,$r->newName);          
            echoResponse(200, array('result' => 'success', 'message' => $data));
        });
        $app->post('/updatePassword', function() use ($app) {
            $self = new SubscriptionController($app);
            $r = json_decode($app->request->getBody());
            verifyRequiredParams(array('access_token'), $r);
            $data = $self->updatePassword($r->access_token, passwordHash::hash($r->newPassword));          
            echoResponse(200, array('result' => 'success', 'message' => $data));
        });
        
        $app->post('/updateCardInfo', function() use ($app) {
            $self = new SubscriptionController($app);
            $r = json_decode($app->request->getBody());
            verifyRequiredParams(array('access_token'), $r);
            $cardInfo=array(
                "firstName" => $r->firstName, 
                "number" => $r->number, 
                "expiryMonth" => $r->expiryMonth, 
                "expiryYear" => $r->expiryYear, 
                "cvv" => $r->cvv  
            );
            $data=$self->updateCardInfo($r->access_token, $cardInfo);
            echoResponse(200, array('result' => 'success', 'message' => $data));
        });
        
        $app->post('/updateBillingAddress', function() use ($app) {
            $self = new SubscriptionController($app);
            $r = json_decode($app->request->getBody());
            verifyRequiredParams(array('access_token'), $r);
            $billingAddress=array(
                    "firstName" => $r->name, 
                    "line1" => $r->addressOne,
                    "line2"=>$r->addressTwo,
                    "company"=>$r->companyName,
                    "city" => $r->city, 
                    "state" => $r->state, 
                    "zip" => $r->zip, 
                    "country" => $r->country
            );
            $data=$self->updateBillingInfo($r->access_token, $billingAddress);
            echoResponse(200, array('result' => 'success', 'message' => $data));
        });

    }
    public function subscribePlan(){
        
    }
    public function upgradePlan(){
        
    }
    public function extendTrial(){
        
    }
    public function isSameDayAllowed(){
        
    }
    public function isNextDayAllowed(){
        
    }
    public function requestCustomPlan(){
        
    }
    public function paymentHistory(){
        
    }
    public function subscriptionInfo(){
        
    }
    public function addChargebeeUser($userInfo){
        $user = ChargeBee_Customer::create($userInfo);
        if($user){
            $user->customer();
            //Save user info in local
        }
    }
    public function createSubscription($plan,$user){
        $result= ChargeBee_Subscription::create($plan);
        if($result){
            $subscriptionInf=$result->subscription();
            //Save this info in database
        }
        
    }
    public function createCustomPriceSubscription($info){
        $result= ChargeBee_Subscription::create($info);
        if($result){
            $subscriptionInfo=$result->subscription();
        }
    }
    public function modifySubscriptionInf($id,$info){
        $result= ChargeBee_Subscription::update($id, $info);
        if($result){
            $updatedInfo=$result->subscription();
        }
    }
    public function addUpdateCardInfo($customerId,$info){
        $result= ChargeBee_Card::updateCardForCustomer($customerId, $info);
        if($result){
            $cardInfo=$result->card();
        }
    }
    public function getShipmentCounts($company_id){
        $sql="SELECT instaDispatch_loadGroupTypeCode as shipment_type,COUNT(*) as shipment_count FROM `".DB_PREFIX."shipment`"
                . " WHERE company_id=$company_id GROUP BY instaDispatch_loadGroupTypeCode";
        return $this->_db->getAllRecords($sql);
    }
    public function getPlanInfo($company_id){
        $sql="SELECT CS.*, U.id FROM ".DB_PREFIX."chargebee_subscription AS CS LEFT JOIN ".DB_PREFIX."chargebee_customer AS CC ON 
                CS.chargebee_customer_id = CC.chargebee_customer_id LEFT JOIN ".DB_PREFIX."users AS U ON CC.user_id=U.id
                WHERE U.id=$company_id";
        return $this->_db->getAllRecords($sql);
    }
    public function getUserInfo($token){
        $sql="SELECT U.id, U.name, U.email, CC.chargebee_customer_id AS self_id, CCP.chargebee_customer_id as p_id  
                FROM ".DB_PREFIX."users AS U LEFT JOIN ".DB_PREFIX."chargebee_customer AS CC ON U.id= CC.user_id 
                LEFT JOIN ".DB_PREFIX."chargebee_customer AS CCP ON U.parent_id=CCP.user_id
                WHERE U.access_token='$token' ";
        return $this->_db->getOneRecord($sql);
    }
    public function updateName($token,$name){
        $sql="UPDATE ".DB_PREFIX."users SET name='$name' WHERE access_token='$token'";
        return $this->_db->updateData($sql);
    }
    public function updatePassword($token,$password){
        $sql="UPDATE ".DB_PREFIX."users SET password='$name' WHERE access_token='$token'";
        return $this->_db->updateData($sql);
    }
    public function updateCardInfo($token,$info){
        $userInfo=$this->getUserInfo($token);
        $chargeBeeCustomer=($userInfo['self_id']!=NULL)?$userInfo['self_id']:$userInfo['p_id'];
        try{
            $savedCard= ChargeBee_Card::updateCardForCustomer($chargeBeeCustomer, $info);
            $card=(array)$savedCard->card();
            $maskedNumber=$card->maskedNumber;
            $cardInfo=array(
                'user_id'=>$userInfo['id'],
                'holder_name'=>$card->firstName,
                'card_number'=>$maskedNumber,
                'expiry_month'=>$card->expiryMonth,
                'expiry_year'=>$card->expiryYear
            );
            $exist=$this->_db->getOneRecord("SELECT * FROM ".DB_PREFIX."user_cards WHERE user_id=".$userInfo['id']);
            if($exist){
                $this->_db->update(DB_PREFIX."user_cards", $cardInfo, "user_id=".$userInfo['id']);
            }else{
                $this->_db->save(DB_PREFIX."user_cards", $cardInfo);
            }
        } catch (Exception $ex) {
            return array('error'=>TRUE,'error_message'=>$ex->getMessage());
        }
    }
    
    public function updateBillingInfo($token,$info){
        $userInfo=$this->getUserInfo($token);
        $chargeBeeCustomer=($userInfo['self_id']!=NULL)?$userInfo['self_id']:$userInfo['p_id'];
        try{
            $savedCard= ChargeBee_Customer::updateBillingInfo($chargeBeeCustomer, $info);
            $address=$savedCard->address();
           $billingAddressInfo=array(
               'user_id'=>$userInfo['id'],
               'name'=>$address->firstName,
               'address_one'=>$address->line1,
               'address_two'=>$address->line2,
               'city'=>$address->city,
               'state'=>$address->state,
               'zip'=>$address->zip,
               'country'=>$address->country,
           );
           $exist=$this->_db->getOneRecord("SELECT * FROM ".DB_PREFIX."billing_addresses WHERE user_id=".$userInfo['id']);
            if($exist){                
                $this->_db->update(DB_PREFIX."billing_addresses", $billingAddressInfo, "user_id=".$userInfo['id']);
            }else{
                $this->_db->save(DB_PREFIX."billing_addresses", $billingAddressInfo);
            }
           
        } catch (Exception $ex) {
            return array('error'=>TRUE,'error_message'=>$ex->getMessage());
        }
    }
}
