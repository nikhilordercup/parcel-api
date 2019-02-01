<?php
require_once("../v1/module/notification/Signup/Courier_Signup.php");
$app->post('/login', function () use ($app) {
    require_once 'passwordHash.php';
    require_once("module/authentication/authentication.php");
    $r = json_decode($app->request->getBody());
    $r->loginType = 'controllerLogin';
    verifyRequiredParams(array('email', 'password'), $r->auth);
    $obj = new Authentication($r);
    $obj->process();
});
$app->post('/custLogin', function () use ($app) {
    require_once 'passwordHash.php';
    require_once("module/authentication/authentication.php");
    $r = json_decode($app->request->getBody());
    $r->loginType = 'custLogin';
    verifyRequiredParams(array('email', 'password'), $r->auth);
    $obj = new Authentication($r);
    $obj->process();
});

$app->post('/signUp', function () use ($app) {
    require_once 'passwordHash.php';

    $response = array();
    $r = json_decode($app->request->getBody());//print_r($r);exit;
    verifyRequiredParams(array('email', 'password'), $r->company);//,'country'
    $r = $r->company;
    $email = $r->email;
    $password = $r->password;
    $uid = $r->uid;
    if ($uid != NULL)
        $r->register_in_firebase = 1;
    $r->user_level = 2;
    $isUserExists = \v1\module\Database\Model\UsersModel::query()
        ->where('email', '=', $email)->count(['email']);
    if (!$isUserExists) {
        $r->password = passwordHash::hash($password);

        $data = [
            'name' => "",
            'contact_name' => "",
            'email' => $r->email,
            'password' => $r->password,
            'city' => "",
            'postcode' => "",
            'user_level' => 2,
            'uid' => $r->uid,
            'register_in_firebase' => $r->register_in_firebase,
            'state' => "",
            'country' => "",
            'parent_id' => 0
        ];
        $user = (\v1\module\Database\Model\UsersModel::query()->create($data)->toArray())['id'];
        if ($user != NULL) {
            \v1\module\Database\Model\CompanyDefaultRegistrationSetupModel::query()
                ->create(['company_id' => $user, 'module_code' => 'controller']);
            \v1\module\Database\Model\CompanyUsersModel::query()
                ->create(['company_id' => $user, 'warehouse_id' => 0, 'user_id' => $user]);

            $defaultConfiguration = array(
                "username" => "roopesh",
                "password" => "ROOPESH",
                "port" => "25",
                "host" => "LOCALHOST",
                "maximum_allow_drop" => "40",
                "buffer_time" => "100",
                "maxbuffertimeperdrop" => "000",
                "maxbuffertimepershipment" => "0",
                "whscanautoatrue" => "1",
                "driverscanautoatrue" => "1",
                "regularattempt" => "2",
                "phonetypeattempt" => "1",
                "round_trip" => ROUND_TRIP,
                "driving_mode" => DRIVING_MODE
            );
            \v1\module\Database\Model\ConfigurationModel::query()
                ->create([
                    "configuration_json" => json_encode($defaultConfiguration),
                    "shipment_end_number" => "00000000000",
                    "shipment_ticket_prefix" => "ICARGOS$user",
                    "parcel_end_number" => "00000000000",
                    "parcel_ticket_prefix" => "ICARGOP$user",
                    "invoice_prefix" => "ICARGOINV$user",
                    "quote_prefix" => "IQ$user",
                    "voucher_prefix" => "ICARGOV$user",
                    "voucher_end_number" => "00000",
                    "invoice_end_number" => "00000000",
                    "company_id" => $user,
                    "created_by" => "0",
                    "org_name" => "",
                    "status" => "1"
                ]);
            \v1\module\Database\Model\UserCodeModel::query()
                ->create(["id" => $user,
                    "code" => str_replace(array(" ", "-"), array("_", "_"), strtolower("")) . $user]);
            $templates = \v1\module\Database\Model\NotificationDefaultModel::all()
                ->where('type', 'LIKE', 'default')->toArray();
            $t = [];
            foreach ($templates as $template) {
                $t[] = [
                    "company_id" => $user,
                    "trigger_type" => $template["trigger_type"],
                    "trigger_code" => $template["trigger_code"],
                    "status" => $template["status"],
                    "template" => $template["template"]
                ];
            }
            if (count($t)) {
                \v1\module\Database\Model\NotificationModel::query()
                    ->insert($t);
            }


            $notificationObj = new Courier_Signup();
            $notificationObj->send($user);
            $mailer = new \v1\module\Mailer\SystemEmail();
            $mailer->sendWelcomeEmail(explode('@', $email)[0], $email);
            $mailer->sendSetUpGuideEmail(explode('@', $email)[0], $email);
            $response["status"] = "success";
            $response["message"] = "User account created successfully";
            $response["id"] = $user;

            echoResponse(200, $response);
        } else {
            $response["status"] = "error";
            $response["message"] = "Failed to create customer. Please try again";
            echoResponse(201, $response);
        }
    } else {
        $response["status"] = "error";
        $response["message"] = "An user with the provided phone or email exists!";
        echoResponse(201, $response);
    }
});

$app->post('/listAllPlanForCustomerRegistration', function () use ($app) {
    $db = new DbHandler();
    $planData = $db->getAllRecords("SELECT plan_id,plan_name FROM " . DB_PREFIX . "chargebee_plan WHERE status='active'");
    $countryData = $db->getAllRecords("SELECT * FROM " . DB_PREFIX . "countries");
    echoResponse(200, array("planData" => $planData, "countryData" => $countryData));
});
$app->post('/addAddressStep', function () use ($app) {
    $r = json_decode($app->request->getBody());
    //Address Update Start
    \v1\module\Database\Model\UsersModel::query()->where('email', '=', $r->email)
        ->update(['name' => $r->companyName,
            'contact_name' => $r->contactName,
            'phone' => $r->companyPhone,
            'address_1' => $r->address_one,
            'address_2' => $r->address_two,
            'city' => $r->companyCity,
            'postcode' => $r->companyPost,
            'country' => $r->companyCountry]);
    //Address Update End

    $user = \v1\module\Database\Model\UsersModel::query()->get()
        ->where('email', '=', $r->email)->first();

    $user = $user->id;
    if (\v1\module\Database\Model\ChargebeeCustomersModel::query()
            ->where('user_id', '=', $user)->count() == 0) {
        //Register User with Chargebee Start
        $chargebee_customer_data = (object)array("billing_city" => $r->companyCity, "billing_country" => $r->companyCountry,
            "billing_first_name" => $r->contactName, "billing_last_name" => $r->contactName,
            "billing_line1" => $r->address_one, "billing_state" => $r->companyState,
            "billing_zip" => $r->companyPost, "first_name" => $r->contactName, "last_name" => "",
            "customer_email" => $r->email, 'user_id' => $user, 'phone' => $r->companyPhone, 'plan_limit' => 0);

        //chargebee customer registration
        $obj = new \v1\module\chargebee\ChargebeeHelper($chargebee_customer_data);
        $customerData = $obj->createCustomer($chargebee_customer_data);
        //Register User with Chargebee End

        //Subscribe Trail for Basic plan Start
        $chargebee_customer_data->customer_id = $customerData["customer_info"]["chargebee_customer_id"];

        \v1\module\chargebee\model\ChargebeeModel::getInstanse()->
        updateBillingInfo($user, $chargebee_customer_data->customer_id);

        $plan = \v1\module\Database\Model\ChargebeePlansModel::all()
            ->where('plan_type', '=', 'SAME_DAY')
            ->where('status', '=', 'active')->first();

        $basic_plan = json_decode(json_encode($plan), true);
        $chargebee_subscription_data = (object)array(
            "plan_id" => $basic_plan["plan_id"],
            "plan_quantity" => 1,
            "customer_id" => $chargebee_customer_data->customer_id,
            "plan_unit_price" => $basic_plan["price"],
            "start_date" => date("Y-m-d"),
            "billing_cycles" => $basic_plan["billing_cycle"],
            'plan_limit' => $basic_plan['shipment_limit']
        );

        if (strtolower($basic_plan["trial_period_unit"]) == "month") {
            $trial_period = 30 * $basic_plan["trial_period"];
            $chargebee_subscription_data->trial_end = date('Y-m-d', strtotime("+$trial_period days"));
        } else if (strtolower($basic_plan["trial_period_unit"]) == "days") {
            $trial_period = $basic_plan["trial_period"];
            $chargebee_subscription_data->trial_end = date('Y-m-d', strtotime("+$trial_period days"));
        }
        $obj->createSubscription($chargebee_subscription_data);
        \v1\module\Database\Model\ChargebeeCustomersModel::query()
            ->where('chargebee_customer_id', '=', $chargebee_customer_data->customer_id)
            ->update(['user_id' => $user]);

        $mailer = new \v1\module\Mailer\SystemEmail();
        $mailer->sendSignUpTrailEmail($r->contactName, $r->email,$basic_plan,$chargebee_subscription_data);
    }
    //Subscribe Trial part END
    /************************Create Warehouse*******************/
    if (\v1\module\Database\Model\CompanyWarehouseModel::query()
            ->where('company_id', '=', $user)->count() == 0) {
        $insertData = ["phone" => $r->companyPhone, "name" => $r->companyName,
            "email" => $r->email, "postcode" => $r->companyPost,
            "address_1" => $r->address_one, "address_2" => $r->address_two,
            "city" => $r->companyCity, "state" => $r->companyState,
            "country" => $r->companyCountry, "latitude" => $r->lat,
            "longitude" => $r->lang];
        $warehouse_id = (\v1\module\Database\Model\WarehouseModel::query()->create($insertData))->id;
        if ($warehouse_id != NULL) {
            $relationData = array('company_id' => $r->company_id, 'warehouse_id' => $warehouse_id);
            $relationTblEntry = (\v1\module\Database\Model\CompanyWarehouseModel::query()->create($relationData))->id;
            if ($relationTblEntry != NULL) {
                    $setupData = array('company_id' => $r->company_id, 'module_code' => 'warehouse');
                    $column_names = array('company_id', 'module_code');
                    \v1\module\Database\Model\CompanyDefaultRegistrationSetupModel::query()
                        ->where('company_id', '=', $r->company_id)
                        ->where('module_code', '=', 'warehouse')->delete();
                    \v1\module\Database\Model\CompanyDefaultRegistrationSetupModel::query()
                        ->create($setupData);
            }
            $r->warehouse_id=$warehouse_id;
        }
    }
    (new \v1\module\customer\CustomerSetup())->setupStepOne($r);
    $company=\v1\module\Database\Model\UsersModel::query()->with('role')
        ->where('email','=',$r->email)->first()->toArray();
    /************************End Warehouse**********************/
    echoResponse(200, $company);
});
//$app->post('/getSubscriptionInfo',function ()use ($app){
//    $r = json_decode($app->request->getBody());
//    $company=\v1\module\Database\Model\ChargebeeCustomersModel::query()
//        ->with('subscription')
//        ->where('user_id','=',$r->company_id)
//    ->first();
//    echoResponse(200,$company);
//});

$app->post('/loadInfo', function () use ($app) {
    $r = json_decode($app->request->getBody());
    $r->loginType = 'controllerLogin';
    verifyRequiredParams(array('email', 'password'), $r->auth);
    $u=\v1\module\Database\Model\UsersModel::query()
        ->where('email',$r->auth->email)
        ->first()->toArray();
    $u['status']="success";
    $u['message']="Logged in successfully.";
    echoResponse(200, $u);
});