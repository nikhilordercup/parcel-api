<?php
require_once "model/Tracking_Model_Index.php";
class Find_Save_Tracking{
    public static $obj = null;
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

    function _saveTrackingStatus($status){
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

        $this->modelObj->saveTrackingHistory($historyData);
        
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

    /*private

    function _findNextdayCollectionShipmentStatusByLoadIdentity($load_identity){
        $allCollectionShipmentCount = $this->modelObj->findAllCollectionShipmentCountByLoadIdentity($load_identity);

        $allCollectedShipmentCount = $this->modelObj->findCollectedShipmentCountByLoadIdentity($load_identity);

        $allCardedShipmentCount = $this->modelObj->findCardedCollectedShipmentCountByLoadIdentity($load_identity);

        $notCollectedCount = $this->modelObj->findNotCollectedShipmentCountByLoadIdentity($load_identity);

        $allCollectionShipmentCount = $allCollectionShipmentCount["shipment_count"];
        $allCollectedShipmentCount  = $allCollectedShipmentCount["shipment_count"];
        $allCardedShipmentCount     = $allCardedShipmentCount["shipment_count"];
        $notCollectedCount          = $notCollectedCount["shipment_count"];

        if($allCollectionShipmentCount > 1){
            if($allCollectedShipmentCount > 0 and $allCollectedShipmentCount<$allCollectionShipmentCount){
                //partly collected
                $this->_saveTrackingStatus(array("tracking_code"=>"PARTLYCOLLECTED", "actions"=>"partly collected"));
                return array("tracking_code"=>"PARTLYCOLLECTED", "actions"=>"partly collected");
            }
            if($allCollectedShipmentCount > 0 and $allCollectedShipmentCount==$allCollectionShipmentCount){
                //need to collected
                $this->_saveTrackingStatus(array("tracking_code"=>"COLLECTIONSUCCESS", "actions"=>"collected"));
                return array("tracking_code"=>"COLLECTIONSUCCESS", "actions"=>"collected");
            }
            if($notCollectedCount==0){
                //awaiting collection
                $this->_saveTrackingStatus(array("tracking_code"=>"COLLECTIONAWAITED", "actions"=>"collection awaited"));
                return array("tracking_code"=>"COLLECTIONAWAITED", "actions"=>"collection awaited");
            }
        }
        elseif($allCollectionShipmentCount == 1){
            if($allCollectedShipmentCount > 0){
                //collected
                $codeCount = $this->_findTrackingCodeCountByLoadTypeAndTrackingCode("COLLECTIONSUCCESS", $this->load_identity);
                if($codeCount==0)
                    $this->_saveTrackingStatus(array("tracking_code"=>"COLLECTIONSUCCESS", "actions"=>"collected"));
            }

            if($allCollectedShipmentCount==0 and $allCardedShipmentCount>0){
                //collection carded
                
                $this->_saveTrackingStatus(array("tracking_code"=>"RETURNINWAREHOUSE", "actions"=>"collection carded"));
                return array("tracking_code"=>"RETURNINWAREHOUSE", "actions"=>"collection carded");
            }
            if($notCollectedCount==$allCollectionShipmentCount){
                //awaiting collection
                $this->_saveTrackingStatus(array("tracking_code"=>"COLLECTIONAWAITED", "actions"=>"collection awaited"));
                return array("tracking_code"=>"COLLECTIONAWAITED", "actions"=>"collection awaited");
            }
        }
        elseif($notCollectedCount==$allCollectionShipmentCount){
            //awaiting collection
            $this->_saveTrackingStatus(array("tracking_code"=>"COLLECTIONAWAITED", "actions"=>"collection awaited"));
            return array("tracking_code"=>"COLLECTIONAWAITED", "actions"=>"collection awaited");
        }else{
            return array();
        }
    }*/

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
                //$trackingCode = ($this->shipment_info["current_status"]=="D") ? "DELIVERYSUCCESS" : "OUTFORDELIVERY";
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
            //$this->_findNextdayCollectionShipmentStatusByLoadIdentity($this->load_identity);
            $this->_findSamedayCollectionShipmentStatusByLoadIdentity($this->load_identity);
        }
        elseif($this->load_type=='SAME'){
            $this->_findSamedayCollectionShipmentStatusByLoadIdentity($this->load_identity);
        }
    }

    public 

    function saveTrackingStatus($param){
        //check any collection left
        $this->shipmentTickets = explode(",", $param["ticket_str"]);

        $this->formCode = isset($param["form_code"]) ? $param["form_code"] : "";

        $ticketStr = implode("','", $this->shipmentTickets);

        $loadIdentity = $this->modelObj->findAssignedLoadIdentityByShipmentTicket($ticketStr);

        //current_status<>'D' 
        $temp = array();
        foreach($loadIdentity as $shipment){
            $shipmentInfo = $this->modelObj->findShipmentlByLoadIdentity($shipment["load_identity"]);

            //start of testing
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


            //end of testing

            /*$temp[$shipment["load_identity"]]["warehouse_id"]  = $shipment["warehouse_id"];
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
            }*/
        }
        /*$temp = array_values($temp);

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

        }*/
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

    public 

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

            /*$temp[$shipment["load_identity"]]["shipment_info"][] = array(
                //"shipment_type"   => $shipment["load_type"],
                "shipment_ticket" => $shipment["shipment_ticket"],
                "current_status"  => $shipment["current_status"],
                "service_type"    => ($shipment["shipment_service_type"]=='P') ? 'collection' : 'delivery'
            );*/
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
    }
}
?>
