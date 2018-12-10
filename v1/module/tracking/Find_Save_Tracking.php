<?php
require_once "model/Tracking_Model_Index.php";
class Find_Save_Tracking{
    public static $obj = null;

    public $podData = array();

    public

    function __construct(){
        $this->modelObj = new Tracking_Model_Index();
        $this->commonObj = new Common();
    }

    public

    static function _getInstance(){
        if(self::$obj==null){
            self::$obj = new Find_Save_Tracking();
        }
        return self::$obj;
    }

    public

    function _findAllTrackingCode($load_identity){
        $allTrackingCode = $this->modelObj->findAllTrackingCode($load_identity);
        return $allTrackingCode;
    }

    private

    function _saveTrackingcode(){
        $allTrackingCode = $this->_findAllTrackingCode($this->load_identity);

        $latestCode = array_pop($allTrackingCode);

        $codeDetail = $this->modelObj->findStatusCodeDetail($latestCode["tracking_code"]);

        $test = $this->modelObj->saveTrackingcode(array(
            "load_identity" => $this->load_identity,
            "code" => $codeDetail["tracking_code"]
        ));

    }

    private

    function _saveTrackingPod($tracking_id){
        //save tracking pod
        if(count($this->podData)>0){
            foreach($this->podData as $podData){
                //$recordExist = $this->modelObj->findPodTrackingHistory(array("tracking_id"=>$tracking_id, "pod_id"=>$podData));
                //if($recordExist["exist"]==0){
                    $this->modelObj->saveTrackingPod(array("tracking_id"=>$tracking_id, "pod_id"=>$podData)); 
                //}   
            }
            $this->podData = array();
        }
    }

    private

    function _saveTrackingStatus($status){
        $historyInfo = $this->modelObj->findTrackingHistory(array(
            "shipment_ticket" => $status["shipment_ticket"],
            "load_identity" => $status["load_identity"],
            "code" => $status["tracking_code"]
        ));


        $historyData = array(
            "shipment_ticket" => $status["shipment_ticket"],
            "load_identity" => $status["load_identity"],
            "code" => $status["tracking_code"],
            "load_type" => $status["load_type"],
            "service_type" => $status["service_type"]
        );

        $tracking_id = 0;

        if(count($historyInfo)>0){
            //no need to update
            $historyData["create_date"] = $historyInfo["create_date"];
            $historyData["id"] = $historyInfo["id"];
            //$historyData["pod_id"] = $historyInfo["pod_id"];

            $tracking_id = $historyInfo["id"];
           
        }else{
            $tracking_id = $this->modelObj->saveTrackingHistory($historyData);
        }

        $this->_saveTrackingPod($tracking_id);
        $this->_saveTrackingcode();
    }

    private

    function _saveTrackingStatusBKP($status){

        //nishant testing
        return $this->_saveTrackingStatusNishant($status);
        if($status["shipment_ticket"]=="ICARGOS2448041"){
            $this->_saveTrackingStatusNishant($status);
        }
        //end of testing
        else{
        //find history info
        $historyInfo = $this->modelObj->findTrackingHistory(array(
            "shipment_ticket" => $status["shipment_ticket"],
            "load_identity" => $status["load_identity"],
            "code" => $status["tracking_code"]
        ));

        //delete old record
        $this->modelObj->deleteTrackingByLoadIdentityAndCode(array(
            "shipment_ticket" => $status["shipment_ticket"],
            "load_identity" => $status["load_identity"],
            "code" => $status["tracking_code"]
        ));

        $historyData = array(
            "shipment_ticket" => $status["shipment_ticket"],
            "load_identity" => $status["load_identity"],
            "code" => $status["tracking_code"],
            "load_type" => $status["load_type"],
            "service_type" => $status["service_type"]
        );

        if(count($historyInfo)>0){
            $historyData["create_date"] = $historyInfo["create_date"];
        }
            $trackingId = $this->modelObj->saveTrackingHistory($historyData);

            //save tracking pod
            if(count($this->podData)>0){
                foreach($this->podData as $podData){
                    //$recordExist = $this->modelObj->findPodTrackingHistory(array("tracking_id"=>$trackingId, "pod_id"=>$podData));
                    //if($recordExist["exist"]==0){
                        $this->modelObj->saveTrackingPod(array("tracking_id"=>$trackingId, "pod_id"=>$podData)); 
                    //}   
                }
                //$this->podData = array();
            }
       

       }


        //update tracking status
        $this->_saveTrackingcode();
    }

    private

    function _findTrackingCodeCountByLoadTypeAndTrackingCode($code, $load_identity){
        $codeCount = $this->modelObj->findTrackingCodeCountByLoadTypeAndTrackingCode($code, $load_identity);
        return $codeCount["tracking_code_count"];
    }

    private

    function _findSamedayCollectionShipmentStatusByLoadIdentity($load_identity){
        if($this->shipment_info["service_type"]=='collection'){
            $trackingCode = ($this->shipment_info["current_status"]=="D") ? "COLLECTIONSUCCESS" : "COLLECTIONAWAITED";
            $action = ($this->shipment_info["current_status"]=="D") ? "collected" : "collection awaited";
            $this->_saveTrackingStatus(array("load_type"=>$this->shipment_info["load_type"],"service_type"=>$this->shipment_info["service_type"],"load_identity"=>$this->shipment_info["load_identity"],"shipment_ticket"=>$this->shipment_info["shipment_ticket"], "tracking_code"=>$trackingCode, "actions"=>$action));
        }
        $this->_findDeliveryShipmentStatusByLoadIdentity($load_identity);
    }

    private

    function _findDeliveryShipmentStatusByLoadIdentity($load_identity){
        if($this->shipment_info["service_type"]=='delivery'){
            $collectionSuccessCount = $this->modelObj->findCollectionSuccessCountByLoadIdentity($this->shipment_info["load_identity"]);
            if($collectionSuccessCount["num_rows"]>0){
                if($this->shipment_info["current_status"]=="Ca"){
                    $trackingCode = "DELIVERY_CARDED";
                }
                elseif($this->shipment_info["current_status"]=="D"){
                    $trackingCode = "DELIVERYSUCCESS";
                }
                else{
                    $trackingCode = "OUTFORDELIVERY";
                }
                $action = ($this->shipment_info["current_status"]=="D") ? "delivered" : "out for delivery";
                $this->_saveTrackingStatus(array("load_type"=>$this->shipment_info["load_type"],"service_type"=>$this->shipment_info["service_type"],"load_identity"=>$this->shipment_info["load_identity"],"shipment_ticket"=>$this->shipment_info["shipment_ticket"],"tracking_code"=>$trackingCode, "actions"=>$action));
            }
        }
    }

    private

    function _findAndSaveTrackingStatus(){
        if($this->load_type=='VENDOR'){
            $this->_findDeliveryShipmentStatusByLoadIdentity($this->load_identity);
        }
        elseif($this->load_type=='NEXT'){
            $this->_findSamedayCollectionShipmentStatusByLoadIdentity($this->load_identity);
        }
        elseif($this->load_type=='SAME'){
            $this->_findSamedayCollectionShipmentStatusByLoadIdentity($this->load_identity);
        }
    }

    public

    function saveTrackingStatus($param){
        //check any collection left
        if(isset($param["pod_status"])){
            $this->podData = $param["pod_status"];
        }

        $this->shipmentTickets = explode(",", $param["ticket_str"]);

        $this->formCode = isset($param["form_code"]) ? $param["form_code"] : "";

        $ticketStr = implode("','", $this->shipmentTickets);

        $loadIdentity = $this->modelObj->findAssignedLoadIdentityByShipmentTicket($ticketStr);

        //current_status<>'D'
        $temp = array();
        foreach($loadIdentity as $shipment){
            $shipmentInfo = $this->modelObj->findShipmentlByLoadIdentity($shipment["load_identity"]);

            $this->load_identity = $shipment["load_identity"];
            foreach($shipmentInfo as $item){
                $this->shipment_info = array(
                    "shipment_type"   => $item["instaDispatch_loadGroupTypeCode"],
                    "shipment_ticket" => $item["shipment_ticket"],
                    "current_status"  => $item["current_status"],
                    "service_type"    => ($item["shipment_service_type"]=='P') ? 'collection' : 'delivery',
                    "load_type"       => strtoupper($item["instaDispatch_loadGroupTypeCode"]),
                    "load_identity"   => $item["instaDispatch_loadIdentity"]
                );
                $this->load_type = strtoupper($item["instaDispatch_loadGroupTypeCode"]);



                $this->_findAndSaveTrackingStatus();
            }
        }
    }

    public

    function saveRouteTrackingStatus($param){
        $this->shipmentTickets = explode(",", $param["ticket_str"]);

        $ticketStr = implode("','", $this->shipmentTickets);

        $loadIdentity = $this->modelObj->findAssignedLoadIdentityByShipmentTicket($ticketStr);
        foreach($loadIdentity as $shipment){
            $shipmentInfo = $this->modelObj->findShipmentlByLoadIdentity($shipment["load_identity"]);
            $this->load_identity = $shipment["load_identity"];
            foreach($shipmentInfo as $item){
                $this->shipment_info = array(
                    "shipment_type"   => $item["instaDispatch_loadGroupTypeCode"],
                    "shipment_ticket" => $item["shipment_ticket"],
                    "current_status"  => $item["current_status"],
                    "service_type"    => ($item["shipment_service_type"]=='P') ? 'collection' : 'delivery',
                    "load_type"       => strtoupper($item["instaDispatch_loadGroupTypeCode"]),
                    "load_identity"   => $item["instaDispatch_loadIdentity"]
                );
                $this->load_type = strtoupper($item["instaDispatch_loadGroupTypeCode"]);
                $this->_findAndSaveTrackingStatus();
            }
        }
    }

    /*public

    function saveRouteTrackingStatusBKP($param){
        //check any collection left
        $this->shipmentTickets = explode(",", $param["ticket_str"]);

        $ticketStr = implode("','", $this->shipmentTickets);

        $loadIdentity = $this->modelObj->findAssignedLoadIdentityByShipmentTicket($ticketStr);

        $temp = array();
        foreach($loadIdentity as $shipment){
            $shipmentInfo = $this->modelObj->findShipmentlByLoadIdentity($shipment["load_identity"]);

            $temp[$shipment["load_identity"]]["warehouse_id"]  = $shipment["warehouse_id"];
            $temp[$shipment["load_identity"]]["load_identity"] = $shipment["load_identity"];
            $temp[$shipment["load_identity"]]["load_type"] = $shipment["load_type"];

            $temp[$shipment["load_identity"]]["company_id"] = $shipment["company_id"];
            $temp[$shipment["load_identity"]]["warehouse_id"] = $shipment["warehouse_id"];
            $temp[$shipment["load_identity"]]["shipment_route_id"] = $shipment["shipment_route_id"];
            $temp[$shipment["load_identity"]]["driver_id"] = $shipment["assigned_driver"];

            $temp[$shipment["load_identity"]]["shipment_info"] = array();

            foreach($shipmentInfo as $item){
                $temp[$shipment["load_identity"]]["shipment_info"][] = array(
                    "shipment_type"   => $item["load_type"],
                    "shipment_ticket" => $item["shipment_ticket"],
                    "current_status"  => $item["current_status"],
                    "service_type"    => ($item["shipment_service_type"]=='P') ? 'collection' : 'delivery'
                );
            }
        }
        $temp = array_values($temp);

        foreach($temp as $item){
            $this->shipment_info = $item["shipment_info"];
            $this->driver_id = $item["driver_id"];
            $this->shipment_route_id = $item["shipment_route_id"];
            $this->company_id = $item["company_id"];
            $this->warehouse_id = $item["warehouse_id"];
            $this->user_type = $param["user_type"];
            $this->load_identity = $item["load_identity"];
            $this->load_type = strtoupper($item["load_type"]);
            $this->_findAndSaveTrackingStatus();
        }
    }*/
}
?>