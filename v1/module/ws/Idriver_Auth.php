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
        $sql    = "SELECT config_data AS app_configuration FROM " . DB_PREFIX . "system_configuration AS T1 WHERE T1.configuration_type='APP' AND T1.company_id='$company_id'";
        $data = $this->db->getRowRecord($sql);
        return array("config_data"=>base64_encode(json_encode(json_decode($data["app_configuration"]))));
    }

    private function _getAppFormConfiguration($company_id)
    {
        $sql    = "SELECT config_data AS form_configuration FROM " . DB_PREFIX . "system_configuration AS T1 WHERE T1.configuration_type='APP_FORM' AND T1.company_id='$company_id'";
        return $this->db->getRowRecord($sql);
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
                            if (count($appConfiguration) > 0) {
                                $formData = $this->_getAppFormConfiguration($warehouse_data['company_id']);
                                if(count($formData)==0){
                                    $formObj = new Default_Form();
                                    $formData = $formObj->getForm();
                                }else{
                                    $formData = $formData["form_configuration"];
                                }

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
                                        "warehouse_postcode" => $warehouse_data['postcode']
                                    ),
                                    "system_configuration"=>$appConfiguration["config_data"],
                                    "form_json"=>$formData

                                );
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
