<?php
class Shipment_Sameday_Release extends Icargo
{
	public function __construct($param)
    {
        parent::__construct(array("email"=>$param['email'],"access_token"=>$param['access_token']));
        $this->modelObj = Shipment_Model::getInstanse();
        $this->commonModelObj  = new Common();
    }

    /*
    * Release only those shipments from route those are not delivered/collected 
    */
    public function releaseShipments($param)
    {   
        $responseArray = array();

        $shipment_ticket = json_decode($param->shipment_ticket);
        $this->modelObj->startTransaction();

        $shipmentRouteData = $this->modelObj->getShipmentRouteByShipmentRouteId($param->shipment_route_id);

        $shipments = $this->modelObj->findShipmentByShipmentTicket(implode("','", $shipment_ticket), $param->shipment_route_id);
   
        $firebaseShipmentTickets = array();
        foreach($shipments as $shipment)
        {
            array_push($firebaseShipmentTickets, $shipment["shipment_ticket"]);
        }

        $firebaseObj = new Firebase_Shipment_Withdraw_From_Route(
            array(
                "shipmet_tickets"=>$firebaseShipmentTickets,
                "driver_id"=>$shipmentRouteData["driver_id"],
                "shipment_route_id"=>$param->shipment_route_id,
                'get_drop_from'=>"shipment_ticket_after_carded"
            )
        );
        $firebaseData = $firebaseObj->getShipmentFromRoute();

        //release shipment from driver
        $error = false;
        foreach($shipments as $shipment)
        {
            $status = $this->modelObj->releaseShipment($shipment["shipment_ticket"]);

            if($status)
            {
                $status = $this->modelObj->releaseShipmentFromDriver($shipment["shipment_ticket"]);
                if(!$status)
                {
                    $error = true;
                    break;
                }
            }
            else
            {
                $error = true;
                break;
            }
        }
        if(!$error)
        {
            $status = $this->modelObj->releaseShipmentFromRoute($param->shipment_route_id);
            if($status)
            {   
                
                $action     = "Relese From Assigned Route";
                $actionsCode = 'RELEASEFROMASSIGNEDROUTE';

                foreach ($shipments as $shipment)
                    $this->commonModelObj->addShipmentlifeHistory($shipment["shipment_ticket"], $action, $shipmentRouteData["driver_id"], $param->shipment_route_id, $param->company_id, $param->warehouse_id, $actionCode, 'controller');

                $this->modelObj->commitTransaction();
                $fbData = $firebaseObj->withdrawShipments($firebaseData);

                if($fbData["jobCount"]==0)
                {
                    $completeRouteObj = new Route_Complete(array('shipment_route_id'=>$param->shipment_route_id,'company_id'=>$this->company_id,'email'=>$this->primary_email,'access_token'=>$this->access_token));
                    $completeRouteObj->saveCompletedRoute();
                }

                $responseArray = array("status"=>"success", "message"=>"Shipment release from route ".$shipmentRouteData["route_name"],"job_count"=>$fbData["jobCount"]);       
            }
            else
            {
                $this->modelObj->rollBackTransaction();
                $responseArray = array("status"=>"error", "message"=>"Shipment not release from route ".$shipmentRouteData["route_name"],"job_count"=>count($firebaseShipmentTickets));
            }
        }
        else
        {
            $this->modelObj->rollBackTransaction();
            $responseArray = array("status"=>"error", "message"=>"Shipment not release from route ".$shipmentRouteData["route_name"],"job_count"=>count($firebaseShipmentTickets));
        }
        return $responseArray;
    }
}