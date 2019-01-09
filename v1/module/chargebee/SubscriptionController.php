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
     * @param \Slim\Slim $app
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

        $app->post('/updateSubscriptionInfo', function() use ($app) {
            $self = new SubscriptionController($app);
            $r = json_decode($app->request->getBody());
            verifyRequiredParams(array('access_token'), $r);
            $data = $self->createNewSubscription($r);
            echoResponse(200, array('result' => 'success', 'message' => $data));
        });

        $app->post('/getPurchaseHistory', function() use ($app) {
            $self = new SubscriptionController($app);
            $r = json_decode($app->request->getBody());
            verifyRequiredParams(array('access_token'), $r);
            $data = $self->getPurchaseHistory($r->company_id);
            echoResponse(200, array('result' => 'success', 'message' => $data));
        });

        $app->post('/paymentFailHook', function() use ($app) {
            $self = new SubscriptionController($app);
            $data = $self->paymentFailHook();
            echoResponse(200, array('result' => 'success', 'message' => $data));
        });
        $app->post('/trialEndHook', function() use ($app) {
            $self = new SubscriptionController($app);
            $data = $self->trialEndHook();
            echoResponse(200, array('result' => 'success', 'message' => $data));
        });

        $app->post('/paymentHook', function() use ($app) {
            $self = new SubscriptionController($app);
            $data = $self->paymentHook();
            echoResponse(200, array('result' => 'success', 'message' => $data));
        });
        $app->post('/cancel-subscription',function ()use ($app){
            $r = json_decode($app->request->getBody());
            $model=new \v1\module\chargebee\model\ChargebeeModel();
            $d=$model->getSubscription($r->company_id,$r->plan_type);
            if($d){
                /**
                 * @var $m \v1\module\Database\Model\ChargebeeSubscriptionsModel
                 */
                $m=$d->subscription;
                $helper=new \v1\module\chargebee\ChargebeeHelper([]);
                $helper->cancelSubscription($m->chargebee_subscription_id);
                $m->status='subscription_cancelled';
                $m->save();
            }
            echoResponse(200,['result'=>'success','message'=>'Subscription canceled successfully.']);
        });
        $app->get('/send/mail',function ()use ($app){
           $m=new \v1\module\Mailer\SystemEmail();
           $m->sendWelcomeEmail();
        });
    }
    public function getSubscriptionInfo($company_id) {
        $sql = "SELECT CS.*, U.id, P.plan_type FROM " . DB_PREFIX . "chargebee_subscription AS CS "
            . "LEFT JOIN " . DB_PREFIX . "chargebee_customer AS CC ON 
                CS.chargebee_customer_id = CC.chargebee_customer_id " .
            "LEFT JOIN " . DB_PREFIX . "users AS U ON CC.user_id=U.id " .
            "LEFT JOIN " . DB_PREFIX . "chargebee_plan AS P ON CS.plan_id=P.plan_id " .
            " WHERE U.id=$company_id AND CS.status IN ('in_trial','active')";
        return $this->_db->getAllRecords($sql);
    }

    public function getShipmentCounts($company_id) {
        $date=date('Y-m-');
        $sql = "SELECT instaDispatch_loadGroupTypeCode as shipment_type,COUNT(*) as shipment_count FROM `" . DB_PREFIX . "shipment`"
            . " WHERE company_id=$company_id "
            . " AND shipment_create_date LIKE '$date%' "
            . "GROUP BY instaDispatch_loadGroupTypeCode";
        return $this->_db->getAllRecords($sql);
    }
    public function getPlanInfo($company_id) {
        $sql = "SELECT CS.*, U.id, P.plan_type FROM " . DB_PREFIX . "chargebee_subscription AS CS LEFT JOIN " . DB_PREFIX . "chargebee_customer AS CC ON 
                CS.chargebee_customer_id = CC.chargebee_customer_id 
                LEFT JOIN " . DB_PREFIX . "users AS U ON CC.user_id=U.id "
            ." LEFT JOIN " . DB_PREFIX . "chargebee_plan AS P ON CS.plan_id=P.plan_id " .
            "WHERE U.id=$company_id AND CS.status IN ('in_trial','active')";
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
        $sql = "UPDATE " . DB_PREFIX . "users SET password='$password' WHERE access_token='$token'";
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
            return array('error' => TRUE, 'error_message' => $ex->getMessage().'At:'.$ex->getLine());
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
    public function createNewSubscription($r) {
        try{
            $planInfo = \v1\module\Database\Model\ChargebeePlansModel::all()
            ->where('plan_id','=',$r->plan_id)->first();
            $customer =\v1\module\Database\Model\ChargebeeCustomersModel::all()
            ->where('user_id','=',$r->company_id)->first();
            if(!$customer){
                $this->registerExistingUserToChargeBee($r->company_id);
                $customer =\v1\module\Database\Model\ChargebeeCustomersModel::all()
                    ->where('user_id','=',$r->company_id)->first();
            }
            $exist = $this->_db->getOneRecord("SELECT CS.* FROM " . DB_PREFIX . "chargebee_subscription AS CS "
                . "LEFT JOIN " . DB_PREFIX . "chargebee_plan AS P ON CS.plan_id=P.plan_id "
                . "LEFT JOIN " . DB_PREFIX . "chargebee_customer AS CC ON CS.chargebee_customer_id=CC.chargebee_customer_id "
                . " WHERE CC.user_id='" . $r->company_id . "' AND P.plan_type='" . $r->plan_type . "' "
                . " AND CS.status IN ('in_trial','active')");
            if ($exist) {
                $result = ChargeBee_Subscription::update($exist['chargebee_subscription_id'], array('planId' => $r->plan_id,'trialEnd'=>0));
                $result = $result->subscription();
                return $this->_db->update("chargebee_subscription", array('status'=>'active','update_date'=>date('Y-m-d H:i:s')), " id=".$exist['id'] );
            } else {
                $result = ChargeBee_Subscription::createForCustomer($customer->chargebee_customer_id,
                    array('planId'=>$r->plan_id,'trialEnd'=>0));
                $result = $result->subscription();

            }
            $subscriptionData=array(
                'plan_id'=>$r->plan_id,
                'chargebee_subscription_id'=>$result->id,
                'chargebee_customer_id'=>$customer['chargebee_customer_id'],
                'plan_quantity'=>1,
                'plan_unit_price'=>$planInfo['price'],
                'start_date'=>date('Y-m-d H:i:s',$result->startedAt),
                'trial_end'=>date('Y-m-d H:i:s',$result->trialEnd),
                'next_billing_date'=>date('Y-m-d H:i:s', $result->nextBillingAt),
                'billing_cycles'=>0,
                'auto_collection'=>FALSE,
                'terms_to_charge'=>0,
                'invoice_notes'=>'',
                'invoice_immediately'=>'',
                'prorata'=>'0',
                'status'=>$result->status,
                'payment_status'=>'0',
                'payment_counter'=>0,
                'create_date'=>date('Y-m-d H:i:s'),
                'update_date'=>date('Y-m-d H:i:s'),
                'allowed_shipment'=>$planInfo['shipment_limit']
            );

            return $this->_db->save('chargebee_subscription', $subscriptionData);
        } catch (Exception $ex){
            return array('error'=>TRUE,'message'=>$ex->getMessage().'At :'.$ex->getLine());
        }

    }
    public function registerExistingUserToChargeBee($userId){
        $company=\v1\module\Database\Model\UsersModel::all()
            ->where('id','=',$userId)
            ->where('user_level','=',2)->first();
        $chargebee_customer_data = (object) ["billing_city"=>$company->city,
            "billing_country"=>$company->alpha2_code,
            "billing_first_name"=>$company->contact_name,
            "billing_last_name"=>$company->name,
            "billing_line1"=>$company->address_1,
            "billing_state"=>$company->state,
            "billing_zip"=>$company->postcode,
            "first_name"=>$company->name,
            "last_name"=>$company->name,
            "customer_email"=>$company->email,
            "user_id"=>$userId,
            'phone'=>$company->phone,
            'plan_limit'=>0];
        $helper=new \v1\module\chargebee\ChargebeeHelper($company);
        return $helper->createCustomer($chargebee_customer_data);
    }
    public function getPurchaseHistory($company_id){
        $sql = "SELECT CS.*, U.id, P.plan_name, P.price FROM " . DB_PREFIX . "chargebee_subscription AS CS "
            . "LEFT JOIN " . DB_PREFIX . "chargebee_customer AS CC ON 
                CS.chargebee_customer_id = CC.chargebee_customer_id " .
            "LEFT JOIN " . DB_PREFIX . "users AS U ON CC.user_id=U.id " .
            "LEFT JOIN " . DB_PREFIX . "chargebee_plan AS P ON CS.plan_id=P.plan_id " .
            " WHERE U.id=$company_id ";
        return $this->_db->getAllRecords($sql);
    }

    public function paymentFailHook(){
        $content = file_get_contents('php://input');
        try{
            $event = ChargeBee_Event::deserialize($content);
            $subscription=$event->content()->subscription();
            $customer=$event->content()->customer();
            $data=array(
                'subscription_id'=>$subscription->id,
                'customer_id'=>$customer->id
            );
            $this->_db->save('payment_failure', $data);
            $subscriptionRow=$this->_db->getOneRecord("SELECT * FROM " . DB_PREFIX . "chargebee_subscription "
                . "WHERE chargebee_subscription_id ='".$subscription->id."' ORDER BY id DESC ");
            if($subscriptionRow){
                $this->_db->update("chargebee_subscription", array('status'=>'payment_failed',
                    'next_billing_date'=>date('Y-m-d H:i:s', $subscription->nextBillingAt)),"id=".$subscriptionRow['id']);
            }
            echo "success";exit;
        } catch (Exception $ex){
            exit($ex->getMessage());
        }
    }


    public function trialEndHook(){
        $content = file_get_contents('php://input');
        try{
            $event = ChargeBee_Event::deserialize($content);
            $subscription=$event->content()->subscription();
            $customer=$event->content()->customer();
            $data=array(
                'subscription_id'=>$subscription->id,
                'customer_id'=>$customer->id
            );
            $this->_db->save('payment_failure', $data);
            $subscriptionRow=$this->_db->getOneRecord("SELECT * FROM " . DB_PREFIX . "chargebee_subscription "
                . "WHERE chargebee_subscription_id ='".$subscription->id."' ORDER BY id DESC ");
            if($subscriptionRow){
                $this->_db->update("chargebee_subscription", array('status'=>'active',
                    'next_billing_date'=>date('Y-m-d H:i:s', $subscription->nextBillingAt)),"id=".$subscriptionRow['id']);
            }
            echo "success";exit;
        } catch (Exception $ex){
            exit($ex->getMessage());
        }
    }

    public function paymentHook(){
        $content = file_get_contents('php://input');
        try{
            $event = ChargeBee_Event::deserialize($content);
            $subscription=$event->content()->subscription();
            $customer=$event->content()->customer();
            $data=array(
                'subscription_id'=>$subscription->id,
                'customer_id'=>$customer->id
            );
            // $this->_db->save('payment_failure', $data);
            $subscriptionRow=$this->_db->getOneRecord("SELECT * FROM " . DB_PREFIX . "chargebee_subscription "
                . "WHERE chargebee_subscription_id ='".$subscription->id."' ORDER BY id DESC ");
            if($subscriptionRow){
                $this->_db->update("chargebee_subscription", array('status'=>'active',
                    'next_billing_date'=>date('Y-m-d H:i:s', $subscription->nextBillingAt)),"id=".$subscriptionRow['id']);
            }
            echo "success";exit;
        } catch (Exception $ex){
            exit($ex->getMessage());
        }
    }
}