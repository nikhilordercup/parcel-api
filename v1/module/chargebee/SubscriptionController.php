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
        ChargeBee_Environment::configure('', '');
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
        $sql="SELECT U.name, U.email, CC.chargebee_customer_id AS self_id, CCP.chargebee_customer_id as p_id  
                FROM ".DB_PREFIX."users AS U LEFT JOIN ".DB_PREFIX."chargebee_customer AS CC ON U.id= CC.user_id 
                LEFT JOIN ".DB_PREFIX."chargebee_customer AS CCP ON U.parent_id=CCP.user_id
                WHERE U.access_token='$token' LIMIT 1";
        return $this->_db->getOneRecord($sql);
    }
}
