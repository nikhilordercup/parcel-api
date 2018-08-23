<?php
require_once "model/rest.php";
require_once "Ws_Driver_Tracking.php";
class Process_Form
{
    public $modelObj = null;
    public function __construct($params)
    {
        $this->modelObj = Shipment_Model::getInstanse();
        if (isset($params->loadActionCode)) {
            $this->loadActionCode = $params->loadActionCode;
        }
        if (isset($params->access_token)) {
            $this->access_token = $params->access_token;
        }
        if (isset($params->contact_name)) {
            $this->contact_name = $params->contact_name;
        }
        if (isset($params->driver_code)) {
            $this->driver_code = $params->driver_code;
        }
        if (isset($params->driver_id)) {
            $this->driver_id = $params->driver_id;
        }
        if (isset($params->drop_id)) {
            $this->drop_id = $params->drop_id;
        }
        if (isset($params->failed_attempt_carded_scan_info)) {
            $this->failed_attempt_carded_scan_info = $params->failed_attempt_carded_scan_info;
        }
        if (isset($params->form_code)) {
            $this->form_code = $params->form_code;
        }
        if (isset($params->latitude)) {
            if (is_numeric($params->latitude)) {
                $this->latitude = $params->latitude;
            } else {
                $this->latitude = '0.00';
            }
        }
        if (isset($params->longitude)) {
            if (is_numeric($params->longitude)) {
                $this->longitude = $params->longitude;
            } else {
                $this->longitude = '0.00';
            }
        }
        if (isset($params->offline_note)) {
            $this->offline_note = $params->offline_note;
        }
        if (isset($params->primary_email)) {
            $this->primary_email = $params->primary_email; //driver email
        }
        if (isset($params->service_msg)) {
            $this->service_message = $params->service_msg;
        }
        if (isset($params->shipment_route_id)) {
            $this->shipment_route_id = $params->shipment_route_id;
        }
        if (isset($params->shipment_ticket)) {
            $this->shipment_ticket = $params->shipment_ticket;
        }
        if (isset($params->sign)) {
            $this->sign = $params->sign;
        }
        if (isset($params->status_code)) {
            $this->status_code = $params->status_code;
        }
        if (isset($params->text)) {
            $this->text = $params->text;
        }
        if (isset($params->company_id)) {
            $this->company_id = $params->company_id;
        }
        if (isset($params->warehouse_id)) {
            $this->warehouse_id = $params->warehouse_id;
        }
        if (isset($params->form_status)) {
            $this->form_status = $params->form_status;
        }
        $this->model_rest = new Ws_Model_Rest();
    }
    private function _get_driver_by_id()
    {
        return $this->model_rest->get_user_by_id($this->driver_id);
    }
    private function _get_driver_company_warehouse()
    {
        return $this->model_rest->get_driver_company_warehouse($this->driver_id);
    }
    private function _add_driver_tacking()
    {
        $this->_driverTracking = Ws_Driver_Tracking::_getInstance();
        $this->_driverTracking->__set("driver_id", $this->driver_id);
        $this->_driverTracking->__set("route_id", $this->shipment_route_id);
        $this->_driverTracking->__set("latitude", $this->latitude);
        $this->_driverTracking->__set("longitude", $this->longitude);
        $this->_driverTracking->__set("code", $this->status_code);
        $this->_driverTracking->__set("status", "1");
        $this->_driverTracking->__set("warehouse_id", $this->warehouse_id);
        $this->_driverTracking->__set("company_id", $this->company_id);
        $this->_driverTracking->__set("source", "APP");
        $track_id = $this->_driverTracking->saveDriverTracking();
        return $track_id;
    }
    private function _delivered_shipment($ticket, $driver_id, $route_id, $driver_name, $pod_data, $latitude, $longitude, $service_msg)
    // script for grid update
    {
        $firebasedata     = array();
        $shipment_details = $this->model_rest->get_accepted_shipment_details_by_ticket($ticket);
        if (($ticket != '') && ($driver_id != '') && ($route_id != '')) {
            $shipment_details = $this->model_rest->get_accepted_shipment_details_by_ticket($ticket);
            if ($shipment_details != null and $shipment_details['current_status'] != 'D') {
                if (isset($pod_data["pod"]) and !empty($pod_data["pod"])) {
                    $podObj  = new Pod_Signatre();
                    $podPath = $podObj->saveImage($ticket, $pod_data["pod"]);
                    $this->model_rest->save("shipments_pod", array(
                        "shipment_ticket" => $ticket,
                        "driver_id" => $driver_id,
                        "pod_name" => "signature",
                        "contact_person" => $pod_data["contact_person"],
                        "latitude" => $pod_data["latitude"],
                        "longitude" => $pod_data["longitude"],
                        "value" => $podPath,
                        "comment" => $pod_data["comment"]
                    ));
                }
                if ($shipment_details['instaDispatch_loadGroupTypeCode'] == 'SAME' && $shipment_details['shipment_service_type'] == 'P') {
                    $condition = "shipment_ticket = '" . $ticket . "'";
                    $status    = $this->model_rest->update("shipment", array(
                        'actual_given_service_date' => date("Y-m-d"),
                        'actual_given_service_time' => date("H:m:s"),
                        'current_status' => 'D',
                        'driver_comment' => $service_msg,
                        'is_driverpickupfromwarehouse' => 'YES',
                        'driver_pickuptime' => date("Y-m-d H:m:s")
                    ), $condition);
                    $condition = "instaDispatch_docketNumber = '" . $shipment_details['instaDispatch_loadIdentity'] . "' AND shipment_service_type = 'D'";
                    $status    = $this->model_rest->update("shipment", array(
                        'is_driverpickupfromwarehouse' => 'YES',
                        'driver_pickuptime' => date("Y-m-d H:m:s"),
                        'action_by' => 'driver'
                    ), $condition);
                } else {
                    $condition = "shipment_ticket = '" . $ticket . "'";
                    $status    = $this->model_rest->update("shipment", array(
                        'actual_given_service_date' => date("Y-m-d"),
                        'actual_given_service_time' => date("H:m:s"),
                        'current_status' => 'D',
                        'driver_comment' => $service_msg,
                        'action_by' => 'driver'
                    ), $condition);
                }
                $condition2                                             = "shipment_route_id = '" . $shipment_details['shipment_routed_id'] . "' AND driver_id = '" . $shipment_details['assigned_driver'] . "'  AND shipment_ticket = '" . $ticket . "' AND shipment_accepted = 'YES' ";
                $status2                                                = $this->model_rest->update("driver_shipment", array(
                    'shipment_status' => 'D',
                    'is_driveraction_complete' => 'Y',
                    'service_date' => date("Y-m-d"),
                    'service_time' => date("H:m:s")
                ), $condition2);
                // check all Delivery completed than release to Driver          
                $shipment_details_after_update                          = $this->model_rest->get_accepted_shipment_details_by_ticket_after_update($ticket);
                $driverDelivered                                        = array();
                $driverDelivered['shipment_ticket']                     = $ticket;
                $driverDelivered['instaDispatch_docketNumber']          = $shipment_details_after_update['instaDispatch_docketNumber'];
                $driverDelivered['instaDispatch_loadIdentity']          = $shipment_details_after_update['instaDispatch_loadIdentity'];
                $driverDelivered['instaDispatch_jobIdentity']           = $shipment_details_after_update['instaDispatch_jobIdentity'];
                $driverDelivered['instaDispatch_objectIdentity']        = $shipment_details_after_update['instaDispatch_objectIdentity'];
                $driverDelivered['instaDispatch_objectTypeName']        = $shipment_details_after_update['instaDispatch_objectTypeName'];
                $driverDelivered['shipment_service_type']               = $shipment_details_after_update['shipment_service_type'];
                $driverDelivered['shipment_required_service_date']      = $shipment_details_after_update['shipment_required_service_date'];
                $driverDelivered['shipment_required_service_starttime'] = $shipment_details_after_update['shipment_required_service_starttime'];
                $driverDelivered['shipment_required_service_endtime']   = $shipment_details_after_update['shipment_required_service_endtime'];
                $driverDelivered['shipment_assigned_service_date']      = $shipment_details_after_update['shipment_assigned_service_date'];
                $driverDelivered['shipment_assigned_service_time']      = $shipment_details_after_update['shipment_assigned_service_time'];
                $driverDelivered['actual_given_service_date']           = $shipment_details_after_update['actual_given_service_date'];
                $driverDelivered['actual_given_service_time']           = $shipment_details_after_update['actual_given_service_time'];
                $driverDelivered['shipment_latlong']                    = $shipment_details_after_update['shipment_latlong'];
                $driverDelivered['shipment_create_date']                = $shipment_details_after_update['shipment_create_date'];
                $driverDelivered['shipment_total_attempt']              = $shipment_details_after_update['shipment_total_attempt'];
                $driverDelivered['shipment_routed_id']                  = $shipment_details_after_update['shipment_routed_id'];
                $driverDelivered['shipment_pod']                        = $shipment_details_after_update['shipment_pod'];
                $driverDelivered['assigned_driver']                     = $shipment_details_after_update['assigned_driver'];
                $driverDelivered['assigned_vehicle']                    = $shipment_details_after_update['assigned_vehicle'];
                $driverDelivered['dataof']                              = $shipment_details_after_update['dataof'];
                $driverDelivered['search_string']                       = $shipment_details_after_update['search_string'];
                $driverDeliveryid                                       = $this->model_rest->save("delivered_shipments", $driverDelivered);
                $company_id                                             = $shipment_details['company_id'];
                $warehouse_id                                           = $shipment_details['warehouse_id'];
                $gridData                                               = $this->getGridDataByTicket($company_id, $warehouse_id, $route_id, $ticket);
                $checkMoreShipmentofthisRouteDriver                     = $this->model_rest->more_shipment_exist_in_this_route_for_driver_from_operation_count($driver_id, $route_id);
                if ($checkMoreShipmentofthisRouteDriver == 0) {
                    $completeRouteObj = new Route_Complete(array(
                        'shipment_route_id' => $route_id,
                        'company_id' => $this->company_id,
                        'email' => $this->primary_email,
                        'access_token' => $this->access_token
                    ));
                    $completeRouteObj->saveCompletedRoute();
                    $firebasedata = $this->releaseRoute($driver_id, $route_id);
                }
                if ($status2) {
                    return array(
                        'message' => "Shipment($ticket) updated as (Delivered) by Driver($this->driver_name)",
                        'success' => true,
                        'status' => "success",
                        'firebase_data' => $firebasedata,
                        'grid_data' => $gridData,
                        'left' => $checkMoreShipmentofthisRouteDriver,
                        'ticket' => $ticket,
                        'timestamp' => microtime(true)
                    );
                }
            } else {
                $gridData                           = $this->getGridDataByTicket($shipment_details['company_id'], $shipment_details['warehouse_id'], $route_id, $ticket);
                $checkMoreShipmentofthisRouteDriver = $this->model_rest->more_shipment_exist_in_this_route_for_driver_from_operation_count($driver_id, $route_id);
                $currentStage                       = $this->model_rest->findShipmentCurrentStage($ticket, $driver_id, $route_id);
                if ($currentStage["action_by"] == "controller") {
                    return array(
                        'message' => "Shipment($ticket) Already Processed By Controller",
                        'success' => true,
                        'status' => "success",
                        'grid_data' => $gridData,
                        'left' => $checkMoreShipmentofthisRouteDriver,
                        'ticket' => $ticket,
                        'timestamp' => microtime(true)
                    );
                }
                return array(
                    'message' => 'Shipment already delivered',
                    'success' => true,
                    'status' => "success",
                    'grid_data' => $gridData,
                    'left' => $checkMoreShipmentofthisRouteDriver,
                    'ticket' => $ticket,
                    'timestamp' => microtime(true)
                );
            }
        } else {
            return array(
                'message' => 'No Valid Data found',
                'success' => false,
                'status' => "error"
            );
        }
    }
    private function _carded_shipment($ticket, $driver_id, $route_id, $comment, $status)
    {
        $shipmentExist = $this->model_rest->shipment_exist_by_ticket_driver($ticket, $driver_id, $route_id);
        if ($shipmentExist['exist'] == 1) {
            $getshipmentDetails = $this->model_rest->get_accepted_shipment_details_by_ticket($ticket);
            $condition          = "shipment_ticket = '" . $ticket . "'";
            $status1            = $this->model_rest->update("shipment", array(
                'actual_given_service_date' => date("Y-m-d"),
                'actual_given_service_time' => date("H:m:s"),
                'current_status' => $status,
                'driver_comment' => $comment,
                'action_by' => 'driver'
            ), $condition);
            $condition2         = "shipment_route_id = '" . $getshipmentDetails['shipment_routed_id'] . "' AND driver_id = '" . $getshipmentDetails['assigned_driver'] . "'  AND shipment_ticket = '" . $ticket . "' AND shipment_accepted = 'YES' ";
            $status2            = $this->model_rest->update("driver_shipment", array(
                'shipment_status' => 'C',
                'is_driveraction_complete' => 'N',
                'driver_comment' => $comment,
                'service_date' => date("Y-m-d"),
                'service_time' => date("H:m:s")
            ), $condition2);
            $company_id         = $getshipmentDetails['company_id'];
            $warehouse_id       = $getshipmentDetails['warehouse_id'];
            $gridData           = $this->getGridDataByTicket($company_id, $warehouse_id, $route_id, $ticket);
            if ($status2) {
                return array(
                    'message' => "Shipment($ticket) updated as (Carded) by Driver($this->driver_name)",
                    'success' => true,
                    'status' => "success",
                    'grid_data' => $gridData,
                    'ticket' => $ticket,
                    'left' => 1
                );
            }
        } else {
            $currentStage = $this->model_rest->findShipmentCurrentStage($ticket, $driver_id, $route_id);
            if ($currentStage["action_by"] == "controller") {
                return array(
                    'message' => "Shipment($ticket) Already Processed By Controller",
                    'success' => true,
                    'status' => "success"
                );
            }
            return array(
                'message' => "Sync error : Shipment($ticket) Not Found",
                'success' => false,
                'status' => "error"
            );
        }
    }
    private function authenticate_driver()
    {
        $data        = array();
        $route_exist = $this->model_rest->driver_route_exist_by_route_id($this->driver_id, $this->shipment_route_id);
        if ($route_exist['exist'] == 1) {
            $load_identity_exist = $this->model_rest->check_load_identity_exist($this->shipment_ticket);
            if ($load_identity_exist['exist'] == 1) {
                $load_identity_assign_to_driver = $this->model_rest->check_load_identity_assign_to_driver($this->shipment_ticket, $this->driver_id, $this->shipment_route_id);
                if ($load_identity_assign_to_driver['exist'] == 1) {
                    $data['message'] = 'Load found';
                    $data['success'] = true;
                    $data['status']  = "success";
                } else {
                    $data['message'] = 'Load not assign to driver';
                    $data['success'] = false;
                    $data['status']  = "error";
                }
            } else {
                $data['message'] = 'Load not found';
                $data['success'] = false;
                $data['status']  = "error";
            }
        } else {
            $data['message'] = 'Route not assigned to driver';
            $data['success'] = false;
            $data['status']  = "error";
        }
        return $data;
    }
    public function getGridDataByTicket($company_id, $warehouse_id, $route_id, $ticket)
    {
        $data = $this->modelObj->getAssignedShipmentDataByTicket($company_id, $warehouse_id, $route_id, $ticket);
        if (is_array($data) and count($data) > 0) {
            foreach ($data as $key => $value) {
                $shipmentCurrentStaus = "";
                if ($value['service_type'] == 'P') {
                    if ($value['current_status'] == 'D') {
                        $shipmentCurrentStaus = 'Collected';
                    } elseif ($value['current_status'] == 'Ca') {
                        $shipmentCurrentStaus = 'Carded';
                    } elseif ($value['current_status'] == 'O') {
                        $shipmentCurrentStaus = 'Not Collected yet';
                    } else {
                        $shipmentCurrentStaus = 'No Status';
                    }
                } elseif ($value['service_type'] == 'D') {
                    if ($value['current_status'] == 'D') {
                        $shipmentCurrentStaus = 'Delivered';
                    } elseif ($value['current_status'] == 'Ca') {
                        $shipmentCurrentStaus = 'Carded';
                    } elseif ($value['current_status'] == 'O') {
                        $shipmentCurrentStaus = 'Not Delivered yet';
                    } else {
                        $shipmentCurrentStaus = 'No Status';
                    }
                } else {
                    $shipmentCurrentStaus = 'No Status';
                }
                if ($value["service_type"] == "D") {
                    $service_type = "Delivery";
                } elseif ($value["service_type"] == "P") {
                    $service_type = "Collection";
                }
                $ticketID                     = "'" . $value['shipment_ticket'] . "'";
                $data[$key]['parcels']        = $this->modelObj->getShipmentParcels($ticketID);
                $data[$key]['current_status'] = $shipmentCurrentStaus;
                $data[$key]['service_type']   = $service_type; //$shipmentCurrentStaus;
            }
        }
        return array(
            "aaData" => $data
        );
    }

    public function process()
    {   
        $data               = array();
        $driver_data        = $this->_get_driver_by_id();
        $this->driver_name  = $driver_data['name'];
        $company_warehouse  = $this->_get_driver_company_warehouse();
        $this->company_id   = $company_warehouse['company_id'];
        $this->warehouse_id = $company_warehouse['warehouse_id'];

        if ($this->loadActionCode == 'processdriversuccessaction') {
            $pod_data = array(
                'contact' => $this->contact_name,
                'comment' => $this->text,
                'pod' => $this->sign,
                'contact_person' => $this->contact_name,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude
            );
            $data = $this->_delivered_shipment($this->shipment_ticket, $this->driver_id, $this->shipment_route_id, $this->driver_name, $pod_data, $this->latitude, $this->longitude, $this->service_message);
            Consignee_Notification::_getInstance()->sendShipmentCollectionDeliverNotification(array(
                "shipment_ticket" => $this->shipment_ticket,
                "company_id" => $this->company_id,
                "warehouse_id" => $this->warehouse_id,
                "trigger_code" => "successful"
            ));
            if ($data["status"] == "success") {
                $shipmentData = $this->model_rest->get_shipment_details_by_shipment_ticket($this->shipment_ticket);
                $common_obj   = new Common();
                if ($shipmentData["shipment_service_type"] == "P") {
                    $actions            = "Collection successful";
                    $internalActionCode = "COLLECTIONSUCCESS";
                } elseif ($shipmentData["shipment_service_type"] == "D") {
                    $actions            = "Delivery successful";
                    $internalActionCode = "DELIVERYSUCCESS";
                }
                $common_obj->addShipmentlifeHistory($this->shipment_ticket, $actions, $this->driver_id, $this->shipment_route_id, $this->company_id, $this->warehouse_id, $internalActionCode, 'driver');

                Find_Save_Tracking::_getInstance()->saveTrackingStatus(array("ticket_str"=>$this->shipment_ticket, "form_code"=>$this->form_code, "user_type"=>"Driver"));

                $this->_add_driver_tacking();

                if($data["left"]==0){
                    $actions            = "Route completed";
                    $internalActionCode = "ROUTECOMPLETED";
                    $common_obj->addShipmentlifeHistory($this->shipment_ticket, $actions, $this->driver_id, $this->shipment_route_id, $this->company_id, $this->warehouse_id, $internalActionCode, 'driver');
                }
                Consignee_Notification::_getInstance()->sendShipmentCollectionDeliverNotification(array(
                    "shipment_ticket" => $this->shipment_ticket,
                    "company_id" => $this->company_id,
                    "warehouse_id" => $this->warehouse_id,
                    "trigger_code" => "successful",
                    "shipment_type" => $shipmentData["instaDispatch_loadGroupTypeCode"]
                ));
            }
        } else if ($this->loadActionCode == 'processdriverfailaction') {
            $data = $this->authenticate_driver();
            if ($data['status'] == "success") {
                $common_obj = new Common();
                $data         = $this->_carded_shipment($this->shipment_ticket, $this->driver_id, $this->shipment_route_id, $this->service_message, 'Ca');
                $shipmentData = $this->model_rest->get_shipment_details_by_shipment_ticket($this->shipment_ticket);
                if ($shipmentData["shipment_service_type"] == "P") {
                    $actions            = "Collection failed";
                    $internalActionCode = "COLLECTIONFAILED";
                } elseif ($shipmentData["shipment_service_type"] == "D") {
                    $actions            = "Delivery failed";
                    $internalActionCode = "DELIVERYFAILED";
                }

                Find_Save_Tracking::_getInstance()->saveTrackingStatus(array("ticket_str"=>$this->shipment_ticket, "form_code"=>$this->form_code, "user_type"=>"Driver"));

                $this->_add_driver_tacking();

                Consignee_Notification::_getInstance()->sendShipmentCollectionDeliverNotification(array(
                    "service_message" => $this->service_message,
                    "shipment_ticket" => $this->shipment_ticket,
                    "company_id" => $this->company_id,
                    "warehouse_id" => $this->warehouse_id,
                    "trigger_code" => "failed",
                    "shipment_type" => $shipmentData["instaDispatch_loadGroupTypeCode"]
                ));
            }
        }
        return $data;
    }
    public function releaseRoute($driverid, $shipRoute_id)
    {
        $param  = array(
            'driver_id' => $driverid,
            'route_id' => $shipRoute_id
        );
        $fbObj  = new Firebase_Route_Release($param);
        $fbdata = $fbObj->getrelasedata();
    }
}