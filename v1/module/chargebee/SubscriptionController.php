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
        ChargeBee_Environment::configure("instadispatch-test", "test_SXcdH4OWVOcd91fCcuYr2UYKhYnFJPfEFZ6");
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
            $subscription = $self->getPlanInfo($r->company_id);
            echoResponse(200, array('result' => 'success', 'message' => $data, 'subscription' => $subscription));
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
            $data = $self->updateName($r->access_token, $r->newName);
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
            $cardInfo = array(
                "firstName" => $r->firstName,
                "number" => $r->number,
                "expiryMonth" => $r->expiryMonth,
                "expiryYear" => $r->expiryYear,
                "cvv" => $r->cvv
            );
            $data = $self->updateCardInfo($r->access_token, $cardInfo);
            echoResponse(200, array('result' => 'success', 'message' => $data));
        });

        $app->post('/updateBillingAddress', function() use ($app) {
            $self = new SubscriptionController($app);
            $r = json_decode($app->request->getBody());
            verifyRequiredParams(array('access_token'), $r);
            $billingAddress = array(
                "firstName" => $r->name,
                "line1" => $r->addressOne,
                "line2" => $r->addressTwo,
                "company" => $r->companyName,
                "city" => $r->city,
                "state" => $r->state,
                "zip" => $r->zip,
                "country" => $r->country
            );
            $data = $self->updateBillingInfo($r->access_token, $billingAddress);
            echoResponse(200, array('result' => 'success', 'message' => $data));
        });
        $app->post('/getCardInfo', function() use ($app) {
            $self = new SubscriptionController($app);
            $r = json_decode($app->request->getBody());
            verifyRequiredParams(array('access_token'), $r);
            $data = $self->getCardInfo($r->access_token);
            if ($data != NULL) {
                echoResponse(200, array('result' => 'success', 'message' => $data));
            } else {
                echoResponse(200, array('result' => 'fail', 'message' => $data));
            }
        });

        $app->post('/getBillingInfo', function() use ($app) {
            $self = new SubscriptionController($app);
            $r = json_decode($app->request->getBody());
            verifyRequiredParams(array('access_token'), $r);
            $data = $self->getBillingInfo($r->access_token);
            if ($data != NULL) {
                echoResponse(200, array('result' => 'success', 'message' => $data));
            } else {
                echoResponse(200, array('result' => 'fail', 'message' => $data));
            }
        });

        $app->post('/getPlanList', function() use ($app) {
            $self = new SubscriptionController($app);
            $r = json_decode($app->request->getBody());
            verifyRequiredParams(array('access_token'), $r);
            $data = $self->getPlanList();
            echoResponse(200, array('result' => 'success', 'message' => $data));
        });

        $app->post('/getSubscriptionInfo', function() use ($app) {
            $self = new SubscriptionController($app);
            $r = json_decode($app->request->getBody());
            verifyRequiredParams(array('access_token'), $r);
            $data = $self->getSubscriptionInfo($r->company_id);
            echoResponse(200, array('result' => 'success', 'message' => $data));
        });
    }    

    public function getSubscriptionInfo($company_id) {
        $sql = "SELECT CS.*, U.id, P.plan_type FROM " . DB_PREFIX . "chargebee_subscription AS CS "
                . "LEFT JOIN " . DB_PREFIX . "chargebee_customer AS CC ON 
                CS.chargebee_customer_id = CC.chargebee_customer_id " .
                "LEFT JOIN " . DB_PREFIX . "users AS U ON CC.user_id=U.id " .
                "LEFT JOIN " . DB_PREFIX . "chargebee_plan AS P ON CS.plan_id=P.plan_id " .
                " WHERE U.id=$company_id";
        return $this->_db->getAllRecords($sql);
    }
    
    public function getShipmentCounts($company_id) {
        $sql = "SELECT instaDispatch_loadGroupTypeCode as shipment_type,COUNT(*) as shipment_count FROM `" . DB_PREFIX . "shipment`"
                . " WHERE company_id=$company_id GROUP BY instaDispatch_loadGroupTypeCode";
        return $this->_db->getAllRecords($sql);
    }

    public function getPlanInfo($company_id) {
        $sql = "SELECT CS.*, U.id FROM " . DB_PREFIX . "chargebee_subscription AS CS LEFT JOIN " . DB_PREFIX . "chargebee_customer AS CC ON 
                CS.chargebee_customer_id = CC.chargebee_customer_id LEFT JOIN " . DB_PREFIX . "users AS U ON CC.user_id=U.id
                WHERE U.id=$company_id";
        return $this->_db->getAllRecords($sql);
    }

    public function getUserInfo($token) {
        $sql = "SELECT U.id, U.name, U.email, CC.chargebee_customer_id AS self_id, CCP.chargebee_customer_id as p_id  
                FROM " . DB_PREFIX . "users AS U LEFT JOIN " . DB_PREFIX . "chargebee_customer AS CC ON U.id= CC.user_id 
                LEFT JOIN " . DB_PREFIX . "chargebee_customer AS CCP ON U.parent_id=CCP.user_id
                WHERE U.access_token='$token' ";
        return $this->_db->getOneRecord($sql);
    }

    public function updateName($token, $name) {
        $sql = "UPDATE " . DB_PREFIX . "users SET name='$name' WHERE access_token='$token'";
        return $this->_db->updateData($sql);
    }

    public function updatePassword($token, $password) {
        $sql = "UPDATE " . DB_PREFIX . "users SET password='$name' WHERE access_token='$token'";
        return $this->_db->updateData($sql);
    }

    public function getCardInfo($token) {
        $userInfo = $this->getUserInfo($token);
        $cardInfo = $this->_db->getOneRecord("SELECT * FROM " . DB_PREFIX . "user_cards WHERE user_id=" . $userInfo['id']);
        if ($cardInfo) {
            return $cardInfo;
        } else {
            return NULL;
        }
    }

    public function updateCardInfo($token, $info) {
        $userInfo = $this->getUserInfo($token);
        $chargeBeeCustomer = ($userInfo['self_id'] != NULL) ? $userInfo['self_id'] : $userInfo['p_id'];
        try {
            $savedCard = ChargeBee_Card::updateCardForCustomer($chargeBeeCustomer, $info);
            $card = $savedCard->card();
            $maskedNumber = $card->maskedNumber;
            $cardInfo = array(
                'user_id' => $userInfo['id'],
                'holder_name' => $card->firstName,
                'card_number' => $maskedNumber,
                'expiry_month' => $card->expiryMonth,
                'expiry_year' => $card->expiryYear
            );
            $exist = $this->_db->getOneRecord("SELECT * FROM " . DB_PREFIX . "user_cards WHERE user_id=" . $userInfo['id']);
            if ($exist) {
                return $this->_db->update("user_cards", $cardInfo, "user_id=" . $userInfo['id']);
            } else {
                return $this->_db->save("user_cards", $cardInfo);
            }
        } catch (Exception $ex) {
            return array('error' => TRUE, 'error_message' => $ex->getMessage());
        }
    }

    public function getBillingInfo($token) {
        $userInfo = $this->getUserInfo($token);
        $billingINfo = $this->_db->getOneRecord("SELECT * FROM " . DB_PREFIX . "billing_addresses WHERE user_id=" . $userInfo['id']);
        if ($billingINfo) {
            return $billingINfo;
        } else {
            return NULL;
        }
    }

    public function updateBillingInfo($token, $info) {
        $userInfo = $this->getUserInfo($token);
        $chargeBeeCustomer = ($userInfo['self_id'] != NULL) ? $userInfo['self_id'] : $userInfo['p_id'];
        try {
            $savedCard = ChargeBee_Customer::updateBillingInfo($chargeBeeCustomer, array('billingAddress' => $info));
            $address = $savedCard->customer()->billingAddress;
            $billingAddressInfo = array(
                'user_id' => $userInfo['id'],
                'name' => $address->firstName,
                'company' => $address->company,
                'address_one' => $address->line1,
                'address_two' => $address->line2,
                'city' => $address->city,
                'state' => $address->state,
                'zip' => $address->zip,
                'country' => $address->country,
            );
            $exist = $this->_db->getOneRecord("SELECT * FROM " . DB_PREFIX . "billing_addresses WHERE user_id=" . $userInfo['id']);
            if ($exist) {
                return $this->_db->update("billing_addresses", $billingAddressInfo, "user_id=" . $userInfo['id']);
            } else {
                return $this->_db->save("billing_addresses", $billingAddressInfo);
            }
        } catch (Exception $ex) {
            return array('error' => TRUE, 'error_message' => $ex->getMessage());
        }
    }

    public function getPlanList() {
        $sameDay = $this->_db->getAllRecords("SELECT * FROM " . DB_PREFIX . "chargebee_plan WHERE plan_type='SAME_DAY'");
        $lastMile = $this->_db->getAllRecords("SELECT * FROM " . DB_PREFIX . "chargebee_plan WHERE plan_type='LAST_MILE'");
        return array(
            'sameDay' => $sameDay,
            'lastMile' => $lastMile
        );
    }

    public function createNewSubscription($data) {
        $planInfo = $this->_db->getOneRecord("SELECT * FROM " . DB_PREFIX . "chargebee_plan WHERE plan_id='" . $r->plan_id . "'");
        $customer = $this->_db->getOneRecord("SELECT * FROM " . DB_PREFIX . "chargebee_customer WHERE user_id='" . $r->company_id . "'");
        $exist = $this->_db->getOneRecord("SELECT CS.* FROM " . DB_PREFIX . "chargebee_subscription AS CS "
                . "LEFT JOIN " . DB_PREFIX . "chargebee_plan AS P ON CS.plan_id=P.plan_id "
                . "LEFT JOIN " . DB_PREFIX . "chargebee_customer AS CC ON CS.chargebee_customer_id=CC.chargebee_customer_id "
                . " WHERE CC.user_id='" . $r->company_id . "' AND P.plan_type='" . $r->plan_type . "'");


        if ($exist) {
            ChargeBee_Subscription::update($exist['chargebee_subscription_id'], array('planId' => $r->plan_id));
            $subscriptonUpdate = array(
                'plan_id' => $r->plan_id,
                'allowed_shipment' => $planInfo['shipment_limit']
            );
        } else {
            $chargeBeeData = array(
                'planId' => $planInfo['plan_id'],
                'startDate' => time(),
                'trialEnd' => strtotime("+" . $planInfo['trial_period'] . " " . $planInfo['trial_period_unit'], time())
            );
            
            $subscriptionData=array(
                'plan_id', 
                'chargebee_subscription_id', 
                'chargebee_customer_id',
                'plan_quantity',
                'plan_unit_price',
                'start_date',
                'trial_end',
                'next_billing_date', 
                'billing_cycles', 
                'auto_collection', 
                'terms_to_charge', 
                'invoice_notes', 
                'invoice_immediately', 
                'prorata', 
                'status', 
                'payment_status', 
                'payment_counter',
                'create_date', 
                'update_date', 
                'allowed_shipment'
            );
        }
    }

}
