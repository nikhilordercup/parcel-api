<?php
require_once 'Idriver_Auth.php';
require_once 'Process_Route.php';
require_once 'Process_Form.php';
require_once 'Optimize_Route.php';
require_once 'Logout.php';
require_once 'Ws_Credential_Info.php';
class Idriver{
    public function __construct()
    {
    }
    public function services($params)
    {
        if(isset($params->service))
        {
            switch($params->service)
            {
                case 'authenticateglobal' :
                    verifyRequiredParams(array('username', 'password'), $params);
                    return $this->_authenticate($params);
                break;
                case 'processdriveraction' :
                    verifyRequiredParams(array('accessToken', 'primary_email', 'latitude', 'longitude'), $params);
                    return $this->_process_driver_action($params);
                break;
                case 'route/accepted' :
                    return $this->_route_accepted($params);
                break;
                case 'cancel-route' :
                    verifyRequiredParams(array('user_id', 'cancel_reason', 'accessToken', 'primary_email', 'latitude', 'longitude'), $params);
                    return $this->_process_cancel_request($params);
                break;
                case 'start-route' :
                    verifyRequiredParams(array('driver_id', 'accessToken', 'shipment_route_id', 'latitude', 'longitude'), $params);
                    return $this->_process_start_route($params);
                break;
                case 'processdriversuccessaction' :
                    return $this->_process_route_form($params);
                break;
                case 'processdriverfailaction' :
                    return $this->_process_route_form($params);
                break;
                case 'route/optimize-route' :
                    return $this->_process_route_optimization($params);
                break;
                case 'scan/onc-collected' :
                    return $this->_save_load_scan_status($params);
                break;
                case 'route/gps-location' :
                    return $this->_save_driver_gps_location($params);
                break;
                case 'route-paused' :
                    return $this->_route_paused($params);
                break;
                case 'logout' :
                    return $this->_logout($params);
                break;
                case 'save/user-credential-info' :
                    return $this->_saveCredentialInfo($params);
            }   
        }
    }
    private function _authenticate($params)
    {
        $obj = new Idriver_Auth(array('email'=>$params->username, 'password'=>$params->password));
        $data = $obj->authenticate();
        return $data;
    }
    private function _route_accepted($params)
    {
        $obj = new Process_Route($params);
        $data = $obj->route_action();
        return $data;
    }
     private function _route_paused($params)
    {  
        $params->loadActionCode = 'PAUSED';
        $obj = new Process_Route($params);
        $data = $obj->route_action();
        return $data;
    }
    private function _process_driver_action($params)
    {
        $obj = new Process_Route($params);
        $data = $obj->route_action();
        return $data;
    }
    private function _process_cancel_request($params)
    {
        $params->id = $params->user_id;
        $obj = new Process_Route($params);
        $data = $obj->route_action();
        return $data;   
    }
    private function _process_start_route($params)
    {
        $params->id = $params->driver_id;
        $params->loadActionCode = $params->service;
        $obj = new Process_Route($params);
        $data = $obj->route_action();
        return $data;   
    }
    private function _process_route_form($params)
    {
        $params->id = $params->driver_id;
        $params->loadActionCode = $params->service;
        $obj = new Process_Form($params);
        $data = $obj->process();
        return $data;
    }
    private function _process_route_optimization($params)
    { 
        $obj = new Optimize_Route($params);
        $data = $obj->optimize();
        return $data;
    }
    private function _save_load_scan_status($params)
    {
           return $params;
    }
    private function _save_driver_gps_location($params)
    {
        $params->loadActionCode = 'SAVE-GPS-LOCATION';
        $obj = new Process_Route($params);
        $data = $obj->route_action();
        return array("status"=>"success", "message"=>"gps location captured");
    }
    private function _logout($params)
    {
        $obj = new Driver_Logout($params);
        $obj->clearAccessToken();
    }
    private function _saveCredentialInfo($params)
    {
        $obj = new Ws_Credential_Info();
        $obj->saveCredentialInfo(array("device_token_id"=>$params->device_token_id, "user_code"=>$params->user_code, "company_id"=>$params->company_id));
        return array("status"=>"success", "message"=>"device token captured");
    }
}
?>