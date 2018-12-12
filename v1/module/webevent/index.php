<?php
class Webevent_Index extends Icargo{
    public function __construct($data)
    {
        $this->_parentObj = parent::__construct(array("email" => $data->email, "access_token" => $data->access_token));
    }

    public function registerEvent($param){
        switch($param->event_code){
            case "start-route":
                $temp_route_id = $param->shipment_route_id;

                $param->loadActionCode = "paused";
                $obj = new Process_Route($param);
                $active_route = Webevent_Model_Index::getInstance()->getDriverActiveRouteDetail(array("driver_id"=>$param->driver_id));
                foreach($active_route as $item){
                    $param->shipment_route_id = $item["shipment_route_id"];
                    $obj = new Process_Route($param);
                    $data = $obj->route_action();
                }
                $param->loadActionCode = "start-route";
                $param->action="start-route";
                $param->shipment_route_id = $temp_route_id;
                $status = Webevent_Model_Index::getInstance()->saveEvent(array("event_code"=>$param->event_code,"shipment_route_id"=>$param->shipment_route_id));
                $obj = new Process_Route($param);
                $obj->route_action();
                Consignee_Notification::_getInstance()->sendRouteStartNotification(array("shipment_route_id"=>$param->shipment_route_id,"company_id"=>$param->company_id,"driver_id"=>$param->driver_id,"trigger_code"=>$param->event_code));
                break;
        }
        return $status;
    }
}
?>