<?php
class Idriver_Auth
{
    public function __construct($params)
    {
        $this->email    = $params['email'];
        $this->password = $params['password'];
        
        $this->db = new DbHandler();
    }
    
    private function _setAccessToken($v)
    {
        $this->_access_token = base64_encode(rand() . "-" . uniqid() . "-$v");
    }
    
    private function _getAccessToken()
    {
        return $this->_access_token;
    }
    
    private function _process()
    {
        $user = $this->db->getOneRecord("SELECT UT.`id`,UT.`name`,UT.`password`,UT.`email`,UT.`user_level`,UT.`create_date`,ULT.`user_type`, `UT`.`uid`, ULT.`code` FROM " . DB_PREFIX . "users as UT INNER JOIN " . DB_PREFIX . "user_level as ULT ON UT.`user_level` = ULT.`id` WHERE UT.`email`='" . $this->email . "' AND UT.`email_verified`=1");
        return $user;
    }
    
    public function getDriverDetails($data)
    {
        //$driver_pictures                         = json_decode($data['driver_picture']);
        $detail                                    = array();
        $detail["lastAccessTime"]                  = '';
        $detail["geoLocationBrodcastingFrequency"] = 30000; //($data['maximum_tracking_time'] * 60000);
        $detail["code"]                            = $data['id'];
        $detail["viewTypeCode"]                    = "ASSIGNL";
        $detail["personName"]                      = $data['name'];
        $detail["picture"]                         = 'drivers/'; // . $driver_pictures[0];
        $detail['user_id']                         = $data['id'];
        $detail['email']                           = $data['email'];
        return $detail;
    }
    
    private function _getVehicleDetailsByVehicleId($driver_id)
    {
        $vehicle                 = $this->db->getOneRecord("SELECT * FROM " . DB_PREFIX . "vehicle AS VT INNER JOIN " . DB_PREFIX . "driver_vehicle AS DVT ON DVT.vehicle_id = VT.id WHERE DVT.driver_id = '$driver_id'");
        $data                    = array();
        $data["maxWeight"]       = (float) $vehicle['max_weight'];
        $data["maxLength"]       = (float) $vehicle['max_length'];
        $data["maxWidth"]        = (float) $vehicle['max_width'];
        $data["maxGirth"]        = (float) $vehicle['max_height'];
        $data["code"]            = $vehicle['plate_no'];
        $data["vehicleTypeName"] = $vehicle['model'];
        
        
        $data["maxDistance"]     = '';
        $data["carbonFootprint"] = '';
        return $data;
    }
    
    private function _get_user_warehouse($user_id)
    {
        $sql    = "SELECT T3.*, T2.company_id AS company_id FROM " . DB_PREFIX . "users AS T1 INNER JOIN " . DB_PREFIX . "company_users AS T2 ON T2.user_id=T1.id INNER JOIN " . DB_PREFIX . "warehouse AS T3 ON T3.ID=T2.warehouse_id WHERE T1.id=$user_id";
        $record = $this->db->getRowRecord($sql);
        return $record;
    }
    
    private function _getAppConfiguration($company_id)
    {
        $sql    = "SELECT config_data FROM " . DB_PREFIX . "system_configuration AS T1 WHERE T1.configuration_type='APP' AND T1.company_id='$company_id'";
        //return $this->db->getRowRecord($sql);
        $data = json_decode('{"test_mode":"false","server_response_time":5,"http_timeout":120000,"loader_timeout":10000,"asynchronous_data_timeout":1500,"current_location_request_time":1000,"pull_current_location_timeout":60000,"save_gps_location":true,"system_messages":{"route_accept_error":{"error_1":{"title":"Error","template":"Please wait while a route just accepted is in process"},"error_2":{"title":"Success","template":"Route accepted successfully"}},"route_pause_error":{"error_1":{"title":"Success","template":"Route paused successfully"},"error_2":{"title":"Error","template":"Route paused error"}},"route_assigned":{"error_1":{"title":"Route Assigned","template":"A new route has been assigned to you."},"error_2":{"title":"Shipment Assigned","template":"New shipment(s) has been assigned to __route_name__. Please reoptimize the route again"}},"background_route_assigned":{"error_1":{"title":"Route Assigned","template":"New route has been assigned to you."}},"route_optimization_error":{"error_1":{"title":"Error","template":"Route not optimized."}},"move_to_background":{"error_1":{"title":"Background Mode","template":"IDriver is running in background"}},"route_start_error":{"error_1":{"title":"Start Route","template":"Please complete or pause the current route to start a new route."},"error_2":{"title":"Error","template":"Please wait while a route just started is in process"},"error_3":{"title":"Scanning Incomplete","template":"Scanning of few parcels on this route is remaining.Please complete the scanning of all parcels or get approval from warehouse before starting the route"},"error_4":{"title":"Authentication Code","template":"Please enter six digit passcode to continue"},"error_5":{"title":"Success","template":"Route started"},"error_6":{"title":"Error","template":"Route not started."}},"auth_error":{"error_1":{"title":"Token Mismathed","template":"Your login token mismatched"}},"signature_error":{"error_1":{"title":"Error","template":"Please provide signature first"}},"deadlock_messages":{"error_1":{"title":"Server Error","template":"Please accept the route again."}},"shipment_withdraw":{"error_1":{"title":"Shipment Withdraw","template":"Shipment has been withdrawn by the controller."}},"route_withdraw":{"error_1":{"title":"Shipment Withdraw","template":"Route has been withdrawn by the controller."}},"scan_error_messages":{"error_1":{"title":"Alert","template":"Barcode Mismatch."},"error_2":{"title":"Load Scan","template":"Parcel already scanned."},"error_3":{"title":"Load Scan","template":"Route : __route_name__ <br>__scan_count__ of __parcel_count__ scan successful"},"error_4":{"title":"Load Scan","template":"All parcels of this route has been scanned successfully"},"error_5":{"title":"Load Scan","template":"The parcel you trying to scan is belonging to unaccepted route. Please accept the route first"},"error_6":{"title":"Load Scan","template":"Parcel not found"},"error_7":{"title":"Error","template":"Piece Identity is required"}},"consignee_error_messages":{"error_1":{"title":"Server Error","template":"Due to server issue, we are unable to fetch consignee information."}},"confirm_navigation_messages":{"error_1":{"title":"Navigate to new job","template":"Are you sure you want to pause the current job and navigate to a new job?"}},"end_route":{"error_1":{"title":"End Route","template":"You still have __dynamic_msg__, are you sure to end the route?"}},"server_request_offline_message":"User is in offline mode","get_location_not_found":"geo-location-not-found"},"plugin_messages":{"geolocation_error":{"error_1":{"title":"Geolocation error","template":"It seems your geo location setting is not enable."}},"scanner_error":{"error_1":{"title":"Scanner error","template":"Camera is not enabled to scan the barcode."}}}}',true);
       
        return array("config_data" => base64_encode(json_encode($data)));


        //return array("config_data"=>);
    }
    
    public function authenticate()
    {
        $response = array();
        
        $user = $this->_process();
        if ($user != null) {
            if (true) {
                $access_token       = $this->_setAccessToken($user['id']);
                $access_token       = $this->_getAccessToken();
                $tokenUpdateSuccess = $this->db->updateAccessTokenById($access_token, $user['id']);
                
                if ($tokenUpdateSuccess) {
                    
                    
                    if ($user['user_type'] = 'driver') {
                        
                        $vehicle_detail = $this->_getVehicleDetailsByVehicleId($user['id']);
                        if ($vehicle_detail['vehicleTypeName'] != null) {
                            $warehouse_data = $this->_get_user_warehouse($user['id']);
                            
                            $appConfiguration = $this->_getAppConfiguration($warehouse_data['company_id']);
                           // print_r($appConfiguration);die;
                            if (count($appConfiguration) > 0) {
                                //$appConfiguration["config_data"] = json_decode($appConfiguration["config_data"], true);

                                $formObj = new Default_Form();

                                $response = array(
                                    "success"=>true,
                                    "message"=>"Authenticated successfully",
                                    "title"=>"User Authenication",
                                    "accessToken"=>$access_token,

                                    "isStaff"=>"driver",
                                    "vehicle"=>$vehicle_detail,
                                    "user_detail"=>$this->getDriverDetails($user),


                                    "company_id"=>$warehouse_data['company_id'],
                                    "uid"=>$user['uid'],
                                    "password"=>$this->password,

                                    "warehouse" => array(
                                        "warehouse_id" => $warehouse_data['id'],
                                        "warehouse_name" => $warehouse_data['name'],
                                        "warehouse_postcode" => $warehouse_data['postcode'],
                                        "warehouse_form" => base64_encode($formObj->getForm())
                                    ),
                                    "system_configuration"=>$appConfiguration["config_data"]

                                );
                                /*$response['success']     = true;
                                $response['message']     = 'Authenticated successfully';
                                $response['title']       = 'User Authenication';
                                $response['accessToken'] = $access_token;


                                $response['isStaff']     = 'driver';
                                $response['vehicle']     = $vehicle_detail;
                                $response['user_detail'] = $this->getDriverDetails($user);


                                $response['company_id']  = $warehouse_data['company_id'];
                                $response['uid']         = $user['uid'];
                                $response['password']    = $this->password;




                                $response['warehouse']   = array(
                                    "warehouse_id" => $warehouse_data['id'],
                                    "warehouse_name" => $warehouse_data['name'],
                                    "warehouse_postcode" => $warehouse_data['postcode'],
                                    "warehouse_form" => base64_encode($formObj->getForm()),
                                    "system_configuration" => $appConfiguration["config_data"]
                                );*/
                            } else {
                                $response['success'] = false;
                                $response['message'] = 'Login failed! App configuration not found';
                                
                            }
                        } else {
                            $response['success'] = false;
                            $response['message'] = 'Login failed! Vehicle not assigned';
                        }
                    }
                } else {
                    $response['success'] = false;
                    $response['message'] = 'Authentication Failure!';
                }
            } else {
                $response['success'] = false;
                $response['message'] = 'Login failed. Incorrect credentials!';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'No such user is registered!';
        }
        return $response;
    }
}
?>