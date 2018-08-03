<?php
/**
 * Created by PhpStorm.
 * User: perce
 * Date: 22/01/2018
 * Time: 04:42 PM
 */

class ConfigurationManager
{
    private $_db;

    /**
     * ConfigurationManager constructor.
     */
    public function __construct()
    {
        $this->_db=new DbHandler();
    }
    public function addConfigurationType(){

    }
    public function addConfiguration($companyId,$configData){
        $configData=addslashes($configData);
        $sql="INSERT INTO ".DB_PREFIX."system_configuration (configuration_type,company_id,config_data)".
            " VALUES ('APP',$companyId,'$configData')";
        //exit($sql);
        return $this->_db->executeQuery($sql);
    }
    public function updateConfiguration($companyId,$configData){
        $configData=addslashes($configData);
        $exist=$this->listConfiguration($companyId);
        if(is_null($exist)){
            return $this->addConfiguration($companyId,$configData);
        }
        $sql="UPDATE ".DB_PREFIX."system_configuration SET config_data='$configData' WHERE ".
            " company_id=$companyId AND configuration_type='APP'";
        return $this->_db->updateData($sql);
    }
    public function deleteConfiguration($typeId,$companyId){
        $sql="DELETE ".DB_PREFIX."system_configuration WHERE ".
            " company_id=$companyId AND configuration_type_id=$typeId";
        return $this->_db->delete($sql);
    }
    public function listConfiguration($companyId){
        $sql="SELECT * FROM ".DB_PREFIX."system_configuration WHERE  company_id=$companyId AND configuration_type='APP'";
        return $this->_db->getOneRecord($sql);
    }
    public function listAllConfiguration($companyId){
        $sql="SELECT * FROM ".DB_PREFIX."system_configuration WHERE  company_id=$companyId";
        return $this->_db->getAllRecords($sql);
    }
    public function getDefaultData(){
        return json_decode(stripcslashes('{"server_response_time":5,"http_timeout":120000,"loader_timeout":120000,"asynchronous_data_timeout":1500,"current_location_request_time":1000,"poll_current_location_timeout":10000,"save_gps_location":"false","system_messages":{"route_accept_error":{"error_1":{"title":"Errro","template":"Please wait while a route just accepted is in process"},"error_2":{"title":"Success","template":"Route accepted successfully"}},"route_pause_error":{"error_1":{"title":"Success","template":"Route paused successfully"},"error_2":{"title":"Error","template":"Route paused error"}},"route_assigned":{"error_1":{"title":"Start Route","template":"Please complete or pause the current route to start a new route."},"error_2":{"title":"Error","template":"Please wait while a route just started is in process"},"error_3":{"title":"Scanning Incomplete","template":"\'Scanning of few parcels on this route is remaining.Please complete the scanning of all parcels or get approval from warehouse before starting the route."},"error_4":{"title":"Authentication Code","template":"Please enter six digit passcode to continue"},"error_5":{"title":"Success","template":"Route started"}},"background_route_assigned":{"error_1":{"title":"Route Assigned","template":"New route has been assigned to you."}},"move_to_background":{"error_1":{"title":"Background Mode","template":"IDriver is running in background"}},"auth_error":{"error_1":{"title":"Token Mismathed","template":"Your login token mismatched"}},"signature_error":{"error_1":{"title":"Error","template":"Please provide signature first"}},"deadlock_messages":{"error_1":{"title":"Sever Error","template":"Please accept the route again."}},"shipment_withdraw":{"error_1":{"title":"Shipment Withdraw","template":"Shipment has been withdrawn by the controller."}},"route_withdraw":{"error_1":{"title":"Shipment Withdraw","template":"Route has been withdrawn by the controller."}},"scan_error_messages":{"error_1":{"title":"Alert","template":"Barcode Mismatch."},"error_2":{"title":"Load Scan","template":"Parcel already scanned."},"error_3":{"title":"Load Scan","template":"Route : __route_name__ <br>__scan_count__ of __parcel_count__ scan successful"},"error_4":{"title":"Load Scan","template":"All parcels of this route has been scanned successfully"},"error_5":{"title":"Load Scan","template":"The parcel you trying to scan is belonging to unaccepted route. Please accept the route first"},"error_6":{"title":"Load Scan","template":"Parcel not found"}},"consignee_error_messages":{"error_1":{"title":"Server Error","template":"Due to server issue, we are unable to fetch consignee information."}},"confirm_navigation_messages":{"error_1":{"title":"Navigate to new job","template":"Are you sure you want to pause the current job and navigate to a new job?"}},"end_route":{"error_1":{"title":"End Route","template":"You still have __dynamic_msg__, are you sure to end the route?"}},"plugin_error_messages":{"geolocation_error":{"error_1":{"title":"Geolocation error","template":"It seems your geo location setting is not enable."}},"scanner_error":{"error_1":{"title":"Scanner error","template":"Camera is not enabled to scan the barcode."}}},"server_request_offline_message":"User is in offline mode","get_location_not_found":"geo-location-not-found"},"system_media":{"audio":{"record_found":"media\/arpeggio.mp3","record_not_found":"media\/beep.mp3"}},"map_events":{"button_control":{"process_shipments":"true","make_current_button":"false"}}}'));
    }

}