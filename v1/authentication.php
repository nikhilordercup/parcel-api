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
    $r=$r->company;
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
            $mailer->sendWelcomeEmail(explode('@',$email)[0],$email);
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
        ->update(['name' => $r->name,
            'contact_name' => $r->contact_name,
            'phone' => $r->phone,
            'password' => $r->password,
            'address_1' => $r->address_1,
            'address_2' => $r->address_2,
            'city' => $r->city,
            'postcode' => $r->postcode,
            'country' => $r->country,
            'parent_id' => 0]);
    //Address Update End

    //Register User with Chargebee Start
    $chargebee_customer_data = (object)array("billing_city" => $r->city, "billing_country" => $r->alpha2_code,
        "billing_first_name" => $r->contact_name, "billing_last_name" => $r->name,
        "billing_line1" => $r->address_1, "billing_state" => $r->state,
        "billing_zip" => $r->postcode, "first_name" => $r->name, "last_name" => $r->name,
        "customer_email" => $r->email, "user_id" => $user, 'phone' => $r->phone, 'plan_limit' => 0);

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
    //Subscribe Trial part END

});