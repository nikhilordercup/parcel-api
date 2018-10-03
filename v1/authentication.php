<?php
$app->post('/login', function() use ($app) {
    require_once 'passwordHash.php';
    require_once("module/authentication/authentication.php");
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('email', 'password'),$r->auth);
    $obj = new Authentication($r);
    $obj->process();
});

$app->post('/signUp', function() use ($app) {
    require_once 'passwordHash.php';
    $db = new DbHandler();

    $response = array();
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('email','name', 'password','city', 'state'),$r->company);//,'country'

    $phone = $r->company->phone;
    $email = $r->company->email;
    $password = $r->company->password;
    $uid = $r->company->uid;
    if($uid!=NULL)
        $r->company->register_in_firebase = 1;
    $r->company->user_level = 2;
    $isUserExists = $db->getOneRecord("select 1 from icargo_users where email='".$email."'");//phone='$phone' or
    if(!$isUserExists){
        $r->company->password = passwordHash::hash($password);
        $table_name = "users";

        $data = array(
            'name'=>$r->company->name,
            'contact_name'=>$r->company->contact_name,
            'phone'=>$r->company->phone,
            'email'=>$r->company->email,
            'password'=>$r->company->password,
            'address_1'=>$r->company->address_1,
            'address_2'=>$r->company->address_2,
            'city'=>$r->company->city,
            'postcode'=>$r->company->postcode,
            'user_level'=>2,
            'uid'=>$r->company->uid,
            'register_in_firebase'=>$r->company->register_in_firebase,
            'state'=>$r->company->state,
            'country'=>$r->company->country,
            'parent_id'=>0
        );
        $user = $db->save("users",$data);
        $countryInfo=$db->getOneRecord("SELECT * FROM ".DB_PREFIX."countries WHERE short_name='".$r->company->country."'" );
           

        //$user = $db->insertIntoTable($r->company, $column_names, $table_name);
        if ($user != NULL) {
            $db->save("company_default_registration_setup",array('company_id'=>$user,'module_code'=>'controller'));
            $relationData = array('company_id'=>$user,'warehouse_id'=>0,'user_id'=>$user);
            $column_names = array('company_id','warehouse_id', 'user_id');
            $relationTblEntry = $db->insertIntoTable($relationData, $column_names, DB_PREFIX."company_users");

            $db->save("configuration", array(
                    "configuration_json"=>'{"username":"roopesh","password":"ROOPESH","port":"25","host":"LOCALHOST","maximum_allow_drop":"40","buffer_time":"100","maxbuffertimeperdrop":"000","maxbuffertimepershipment":"0","whscanautoatrue":"1","driverscanautoatrue":"1","regularattempt":"2","phonetypeattempt":"1"}',
                    "shipment_end_number"=>"00000000000",
                    "shipment_ticket_prefix"=>"ICARGOS$user",
                    "parcel_end_number"=>"00000000000",
                    "parcel_ticket_prefix"=>"ICARGOP$user",
                    "invoice_prefix"=>"ICARGOINV$user",
                    "quote_prefix"=>"IQ$user",
                    "voucher_prefix"=>"ICARGOV$user",
                    "voucher_end_number"=>"00000",
                    "invoice_end_number"=>"00000000",
                    "company_id"=>$user,
                    "created_by"=>"0",
                    "org_name"=>"",
                    "status"=>"1"
                )
            );

            $db->save("user_code", array(
                    "id"=>$user,
                    "code"=>str_replace(array(" ","-"),array("_","_"), strtolower($r->company->name)).$user
                )
            );
            $basic_plan = $db->getOneRecord("select * from ".DB_PREFIX."chargebee_plan ORDER BY price DESC ");
            //register user plan
            //chargebee customer data
            $chargebee_customer_data = (object) array("billing_city"=>$r->company->city,"billing_country"=>$r->company->alpha2_code,
                "billing_first_name"=>$r->company->contact_name,"billing_last_name"=>$r->company->name,
                "billing_line1"=>$r->company->address_1,"billing_state"=>$r->company->state,
                "billing_zip"=>$r->company->postcode,"first_name"=>$r->company->name,"last_name"=>$r->company->name,
                "customer_email"=>$r->company->email,"user_id"=>$user,'phone'=>$r->company->phone,'plan_limit'=>$basic_plan['shipment_limit']);

            //chargebee customer registration
            $obj = new Module_Chargebee($chargebee_customer_data);
            $customerData = $obj->createCustomer($chargebee_customer_data);

            //chargebee associate to trial plan
            $chargebee_customer_data->customer_id = $customerData["customer_info"]["chargebee_customer_id"];
            
           
            Chargebee_Model_Chargebee::getInstanse()->
            updateBillingInfo($user, $chargebee_customer_data->customer_id);
            
            


            $chargebee_customer_data = (object) array(
                "plan_id"=>$basic_plan["plan_id"],
                "plan_quantity"=>1,
                "customer_id"=>$chargebee_customer_data->customer_id,
                "plan_unit_price"=>$basic_plan["price"],
                "start_date"=>date("Y-m-d"),
                "trial_end"=>'',
                "billing_cycles"=>$basic_plan["billing_cycle"],
                'plan_limit'=>$basic_plan['shipment_limit']
            );

            if(strtolower($basic_plan["trial_period_unit"])=="month"){
                $trial_period = 30*$basic_plan["trial_period"];
                $chargebee_customer_data->trial_end = date('Y-m-d', strtotime("+$trial_period days"));
            }
            else if(strtolower($basic_plan["trial_period_unit"])=="days"){
                $trial_period = $basic_plan["trial_period"];
                $chargebee_customer_data->trial_end = date('Y-m-d', strtotime("+$trial_period days"));
            }
            json_encode($chargebee_customer_data);
            $obj->createSubscription($chargebee_customer_data);
            // save user id to chargebee_customer_table
            $db->update("chargebee_customer", array("user_id"=>$user),"chargebee_customer_id='$chargebee_customer_data->customer_id'");

            //save user default notification templates
            $sql = "SELECT * FROM " . DB_PREFIX ."notification_default";
            $templates = $db->getALLRecordS($sql);

            foreach($templates as $template){
                $db->save("notification", array(
                    "company_id" => $user,
                    "type" => '',
                    "jobtype" => '',
                    "trigger_type" => $template["trigger_type"],
                    "trigger_code" => $template["trigger_code"],
                    "status" => $template["status"],
                    "template" => $template["template"]
                ));
            }

            $response["status"] = "success";
            $response["message"] = "User account created successfully";
            $response["id"] = $user;

            echoResponse(200, $response);
        } else {
            $response["status"] = "error";
            $response["message"] = "Failed to create customer. Please try again";
            echoResponse(201, $response);
        }
    }else{
        $response["status"] = "error";
        $response["message"] = "An user with the provided phone or email exists!";
        echoResponse(201, $response);
    }
});

$app->post('/listAllPlanForCustomerRegistration', function() use ($app){
    $db = new DbHandler();
    $planData = $db->getAllRecords("SELECT plan_id,plan_name FROM " . DB_PREFIX ."chargebee_plan WHERE status='active'");
    $countryData = $db->getAllRecords("SELECT * FROM " . DB_PREFIX ."countries");
    echoResponse(200, array("planData"=>$planData,"countryData"=>$countryData));
});