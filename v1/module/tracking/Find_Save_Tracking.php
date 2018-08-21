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
        print_r($allTrackingCode);die;
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
        //save tracking history

        $this->modelObj->saveTrackingHistory(array(
            "shipment_ticket" => "",
            "load_identity" => $this->load_identity,
            "code" => $status["tracking_code"]
        ));
        
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
                $this->_findDeliveryShipmentStatusByLoadIdentity($load_identity);
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
                //if load type is SAME then we have to check 
                if($this->load_type=='SAME'){
                    $codeCount = $this->_findTrackingCodeCountByLoadTypeAndTrackingCode("COLLECTIONSUCCESS", $this->load_identity);
                    if($codeCount==0)
                        $this->_saveTrackingStatus(array("tracking_code"=>"COLLECTIONSUCCESS", "actions"=>"collected"));
                }
                return $this->_findDeliveryShipmentStatusByLoadIdentity($load_identity);
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
    }

    private

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
                echo 123;
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
    }

    private 

    function _findDeliveryShipmentStatusByLoadIdentity($load_identity){
        $allDeliveryShipmentCount = $this->modelObj->findAllDeliveryShipmentCountByLoadIdentity($load_identity);

        $allDeliveredShipmentCount = $this->modelObj->findDeliveredShipmentCountByLoadIdentity($load_identity);

        $allCardedShipmentCount = $this->modelObj->findCardedDeliveryShipmentCountByLoadIdentity($load_identity);

        $notDeliveredCount = $this->modelObj->findNotDeliveredShipmentCountByLoadIdentity($load_identity);

        $allDeliveryShipmentCount = $allDeliveryShipmentCount["shipment_count"];
        $allDeliveredShipmentCount = $allDeliveredShipmentCount["shipment_count"];
        $allCardedShipmentCount = $allCardedShipmentCount["shipment_count"];
        $notDeliveredCount = $notDeliveredCount["shipment_count"];

        if($allDeliveryShipmentCount > 1){
            if($allDeliveredShipmentCount > 0 and $allDeliveredShipmentCount<$allDeliveryShipmentCount){
                //partly delivered
                if($this->load_type=='SAME'){
                    $this->_saveTrackingStatus(array("tracking_code"=>"OUTFORDELIVERY", "actions"=>"out for delivery"));
                    return array("tracking_code"=>"OUTFORDELIVERY", "actions"=>"out for delivery");
                }else{
                    $this->_saveTrackingStatus(array("tracking_code"=>"PARTLYDELIVERED", "actions"=>"partly delivered"));
                    return array("tracking_code"=>"PARTLYDELIVERED", "actions"=>"partly delivered");
                }
            }
            if($allDeliveredShipmentCount == 0 and $allDeliveredShipmentCount<$allDeliveryShipmentCount){
                //out for delivery
                $this->_saveTrackingStatus(array("tracking_code"=>"OUTFORDELIVERY", "actions"=>"out for delivery"));
                return array("tracking_code"=>"OUTFORDELIVERY", "actions"=>"out for delivery");
            }
            if($allDeliveredShipmentCount > 0 and $allDeliveredShipmentCount==$allDeliveryShipmentCount){
                //delivered
                $this->_saveTrackingStatus(array("tracking_code"=>"DELIVERYSUCCESS", "actions"=>"delivered"));
                return array("tracking_code"=>"DELIVERYSUCCESS", "actions"=>"delivered");
            }
        }
        elseif($allDeliveryShipmentCount == 1){
            if($allDeliveredShipmentCount > 0){
                //delivered
                $this->_saveTrackingStatus(array("tracking_code"=>"DELIVERYSUCCESS", "actions"=>"delivered"));
                return array("tracking_code"=>"DELIVERYSUCCESS", "actions"=>"delivered");
            }

            if($allDeliveredShipmentCount==0 and $allCardedShipmentCount>0){
                //delivery carded
                $this->_saveTrackingStatus(array("tracking_code"=>"RETURNINWAREHOUSE", "actions"=>"delivered carded"));
                return array("tracking_code"=>"RETURNINWAREHOUSE", "actions"=>"delivered carded");
            }
        }
        elseif($notDeliveredCount==0){
            //out for delivery
            $this->_saveTrackingStatus(array("tracking_code"=>"OUTFORDELIVERY", "actions"=>"out for delivery"));
            return array("tracking_code"=>"OUTFORDELIVERY", "actions"=>"out for delivery");
        }else{
            return array();
        }
    }

    private

    function _findAndSaveTrackingStatus(){
        if($this->load_type=='VENDOR'){
            $this->_findDeliveryShipmentStatusByLoadIdentity($this->load_identity);
        }
        elseif($this->load_type=='NEXT'){
            $this->_findNextdayCollectionShipmentStatusByLoadIdentity($this->load_identity);
        }
        elseif($this->load_type=='SAME'){
            $this->_findSamedayCollectionShipmentStatusByLoadIdentity($this->load_identity);
        }
    }

    public 

    function saveTrackingStatus($param){echo '209';echo json_encode($param);
        //check any collection left
        $this->shipmentTickets = explode(",", $param["ticket_str"]);

        $this->formCode = isset($param["form_code"]) ? $param["form_code"] : "";

        $ticketStr = implode("','", $this->shipmentTickets);
       
        $loadIdentity = $this->modelObj->findAssignedLoadIdentityByShipmentTicket($ticketStr);

        $temp = array();
        foreach($loadIdentity as $shipment){
            $temp[$shipment["instaDispatch_loadIdentity"]]["warehouse_id"]  = $shipment["warehouse_id"];
            $temp[$shipment["instaDispatch_loadIdentity"]]["load_identity"] = $shipment["load_identity"];
            $temp[$shipment["instaDispatch_loadIdentity"]]["load_type"] = $shipment["load_type"];

            $temp[$shipment["instaDispatch_loadIdentity"]]["company_id"] = $shipment["company_id"];
            $temp[$shipment["instaDispatch_loadIdentity"]]["warehouse_id"] = $shipment["warehouse_id"];
            $temp[$shipment["instaDispatch_loadIdentity"]]["shipment_route_id"] = $shipment["shipment_route_id"];
            $temp[$shipment["instaDispatch_loadIdentity"]]["driver_id"] = $shipment["assigned_driver"];

            $temp[$shipment["instaDispatch_loadIdentity"]]["shipment_info"][] = array(
                "shipment_type"   => $shipment["load_type"],
                "shipment_ticket" => $shipment["shipment_ticket"],
                "current_status"  => $shipment["current_status"],
                "service_type"    => ($shipment["shipment_service_type"]=='P') ? 'collection' : 'delivery'
            );
        }
        $temp = array_values($temp);

        foreach($temp as $item){
            $this->shipment_info = $item["shipment_info"];
            $this->driver_id = $item["driver_id"];
            $this->shipment_route_id = $item["shipment_route_id"];
            $this->company_id = $item["company_id"];
            $this->warehouse_id = $item["warehouse_id"];
            $this->user_type = $param["user_type"].'testing';

            $this->load_identity = $item["load_identity"];
            $this->load_type = strtoupper($item["load_type"]);

            $this->_findAndSaveTrackingStatus();
        }
    }

    public 

    function saveRouteTrackingStatus($param){
        //check any collection left
        $this->shipmentTickets = explode(",", $param["ticket_str"]);

        $ticketStr = implode("','", $this->shipmentTickets);
       
        $loadIdentity = $this->modelObj->findAssignedLoadIdentityByShipmentTicket($ticketStr);

        $temp = array();
        foreach($loadIdentity as $shipment){
            $temp[$shipment["load_identity"]]["warehouse_id"]  = $shipment["warehouse_id"];
            $temp[$shipment["load_identity"]]["load_identity"] = $shipment["load_identity"];
            $temp[$shipment["load_identity"]]["load_type"] = $shipment["load_type"];

            $temp[$shipment["load_identity"]]["company_id"] = $shipment["company_id"];
            $temp[$shipment["load_identity"]]["warehouse_id"] = $shipment["warehouse_id"];
            $temp[$shipment["load_identity"]]["shipment_route_id"] = $shipment["shipment_route_id"];
            $temp[$shipment["load_identity"]]["driver_id"] = $shipment["assigned_driver"];

            $temp[$shipment["load_identity"]]["shipment_info"][] = array(
                "shipment_type"   => $shipment["load_type"],
                "shipment_ticket" => $shipment["shipment_ticket"],
                "current_status"  => $shipment["current_status"],
                "service_type"    => ($shipment["shipment_service_type"]=='P') ? 'collection' : 'delivery'
            );
        }
        $temp = array_values($temp);

        foreach($temp as $item){
            $this->shipment_info = $item["shipment_info"];
            $this->driver_id = $item["driver_id"];
            $this->shipment_route_id = $item["shipment_route_id"];
            $this->company_id = $item["company_id"];
            $this->warehouse_id = $item["warehouse_id"];
            $this->user_type = $param["user_type"].'testing';

            $this->load_identity = $item["load_identity"];
            $this->load_type = strtoupper($item["load_type"]);

            $this->_findAndSaveTrackingStatus();
        }
    }
}
?>