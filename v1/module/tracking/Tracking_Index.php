<?php
require_once "model/Tracking_Model_Index.php";
class Tracking_Index extends Icargo{

    private $_trackingSequnce = array(
        "PARTLYDELIVERED",
        "SDADDRESSUNACCESSEBLEORNOTFOUND",
        "SDDELIVEREDATDOOR",
        "SDADDRESSUNACCESSEBLEORNOTFOUND",
        "OUTFORDELIVERY",
        "PARTLYCOLLECTED",
        "COLLECTIONSUCCESS",
        /*"COLLECTIONFAILED",*/
        "SCADDRESSUNACCESSEBLEORNOTFOUND",
        "SCFAILEDATTEMPTCARDED",
        "ROUTESTART",
        "DRIVERACCEPTED",
        "ASSIGNTODRIVER",
        "INFORECEIVED"
    );
        
    public

    function __construct(){
        $this->modelObj = new Tracking_Model_Index();
    }

    private

    function _findLoadIdentityByShipmentTicket($shipmentTicket){
        $loadIdentity = $this->modelObj->findLoadIdentityByShipmentTicket($shipmentTicket);
        return $loadIdentity["load_identity"];
    }

    private

    function _findShipmentTicketsByLoadIdentity(){
        $items = $this->modelObj->findShipmentTicketsByLoadIdentity($this->loadIdentity);
        $temp = array();
        foreach($items as $item){
            array_push($temp, $item["shipment_ticket"]);
        }
        return $temp;
    }

    private

    function _findShipmentHistoryTicketByLoadIdentity(){
        $items = $this->modelObj->findShipmentHistoryTicketByLoadIdentity($this->loadIdentity);
        $temp = array();
        foreach($items as $item){
            $temp[$item["shipment_ticket"]] = $item["shipment_ticket"];
        }
        return array_values($temp);
    }

    private

    function _findUnAssignedShipmentTickets(){
        return array_diff($this->allShipmentTickets , $this->allShipmentHistoryTickets);
    }

    private

    function findShipmentStatusByShipmentTickets($unAssignedShipmentTickets){
        $items = $this->modelObj->findShipmentStatusByShipmentTickets($unAssignedShipmentTickets);
        $temp = array();
        foreach($items as $item){
            if($item["current_status"]=='C')
                array_push($temp, "INFORECEIVED");   
        }
        return array_unique($temp);
    }

    private

    function _findInternalCodeOfUnAssignedShipmentTickets(){
        if(count($this->unAssignedShipmentTickets)==0){
            return array();
        }
        $unAssignedShipmentTickets = implode("','", $this->unAssignedShipmentTickets);
        $currentStatus = $this->findShipmentStatusByShipmentTickets($unAssignedShipmentTickets);
        return $currentStatus;
    }

    private

    function _findInternalCodeOfAssignedShipmentTickets(){
        $items = $this->modelObj->findInternalActionCodeByLoadIdentity($this->loadIdentity);
        $temp = array();
        foreach($items as $item){
            array_push($temp, $item["action_code"]);
        }
        return $temp;
    }

    private

    function _findAllInternalCodeOfLoad(){
        return array_merge($this->unAssignedShipmentActionCode, $this->assignedShipmentActionCode);
    }

    private

    function _findLoadTrackingStatus(){
        foreach($this->_trackingSequnce as $item){
            if(in_array($item, $this->allInternalCodeOfLoad)){
                return $item;
            }
        }
        return false;
    }

    private

    function _findStatusBKP(){
        $statusCode = $this->_findLoadTrackingStatus();
        if($statusCode){
            return $this->modelObj->findStatusCodeDetail($statusCode);
        }
    }

    private

    function _findStatus($all_status){
        $statusCode = array_pop($all_status);
        return $this->modelObj->findStatusCodeDetail($statusCode);
    }

    public

    function getTrackingStatus($shipmentTicket){
        //fetch load identity by shipment ticket
        $this->loadIdentity = $this->_findLoadIdentityByShipmentTicket($shipmentTicket);

        //find all shipment ticket by load identity
        $this->allShipmentTickets = $this->_findShipmentTicketsByLoadIdentity();

        //find all shipment history ticket by load identity
        $this->allShipmentHistoryTickets = $this->_findShipmentHistoryTicketByLoadIdentity();

        //find all shipment ticket that are not found in history ticket
        $this->unAssignedShipmentTickets = $this->_findUnAssignedShipmentTickets();

        //fetch internal code from shipment
        $this->unAssignedShipmentActionCode = $this->_findInternalCodeOfUnAssignedShipmentTickets();

        //fetch record from shipment history
        $this->assignedShipmentActionCode = $this->_findInternalCodeOfAssignedShipmentTickets();

        //fetch all internal code of load
        $this->allInternalCodeOfLoad = $this->_findAllInternalCodeOfLoad();

        //$trackingStatus = $this->_findStatusBKP();

        $trackingStatus = $this->_findStatus($this->allInternalCodeOfLoad);

        print_r($this->allInternalCodeOfLoad);
        echo "Last Status :";
        print_r($trackingStatus);die;
    }
}
?>