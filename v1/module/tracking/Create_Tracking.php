<?php
require_once "model/Tracking_Model_Index.php";
class Create_Tracking extends Icargo{
    public $loadIdentity = null;

    private $statusArray = array(
        "pre_transit" => "INFO_RECEIVED",
        "in_transit" => "IN_TRANSIT",
        "out_for_delivery" => "OUTFORDELIVERY",
        "delivered" => "DELIVERED"
    );

    public

    function __construct(){
        $this->modelObj = new Tracking_Model_Index();
        $this->commonObj = new Common();

        $this->apiInfo = array("dev"=>
            array("api_key"=>"8VfzGXyF0idIP1exSAlabQ"
            )
        );
    }

    private

    function _findTrackingCodeAndString(){
        $code = $this->statusArray[$this->trackingData->status];
        $data = $this->modelObj->findTrackingInfoByCode($code);
        if(count($data)>0)
            return array("action"=>$data["name"], "action_code"=>$data["code"]);
        return array("action"=>$this->trackingData->status_detail, "action_code"=>$this->trackingData->status);
    }

    private

    function _updateShipmentService($tracking_code){
        $tracking_code = 0;
        $this->loadIdentity = null;
        $loadIdentity = $this->modelObj->findLoadIdentityByTrackingNo($tracking_code);

        if(isset($loadIdentity["load_identity"])){
            $this->loadIdentity = $loadIdentity["load_identity"];
            $this->modelObj->updateTracking($this->loadIdentity, $this->statusArray[$this->trackingData->status]);
        }
    }

    private

    function _saveShipmentLifeHistory(){
        $shipmentData = $this->modelObj->findShipmentlByLoadIdentity($this->loadIdentity);
        if(count($shipmentData)>0){
            foreach($shipmentData as $data){
                $icargoTrackingData = $this->_findTrackingCodeAndString();
                $this->commonObj->addShipmentlifeHistory($data["shipment_ticket"], $icargoTrackingData["action"], $data["assigned_driver"], $data["shipment_routed_id"], $data["company_id"], $data["warehouse_id"], $icargoTrackingData["action_code"], 'easypost');
            }
        }
    }

    private

    function _saveShipmentTracking(){
        $data = array(
            "tracking_id" => $this->trackingData->id,  
            "object" => $this->trackingData->object,  
            "mode" => $this->trackingData->mode,  
            "tracking_code" => $this->trackingData->tracking_code,  
            "code" => $this->statusArray[$this->trackingData->status],  
            "status_detail" => $this->trackingData->status_detail,  
            "created_at" => $this->trackingData->created_at,  
            "updated_at" => $this->trackingData->updated_at,

            "signed_by" => $this->trackingData->signed_by,  
            "weight" => $this->trackingData->weight,  
            "est_delivery_date" => $this->trackingData->est_delivery_date,  
            //"shipment_id" => $this->trackingData->shipment_id,
            "load_identity" => $this->trackingData->shipment_id,
            "carrier" => $this->trackingData->carrier,
            "finalized" => (empty($this->trackingData->finalized)) ? 0 : $this->trackingData->finalized,
            "is_return" => $this->trackingData->is_return, 
            "public_url" => $this->trackingData->public_url,
            "user_id" => $this->trackingData->user_id,
            "event_id" => $this->trackingData->id,
            "origin" => 'easypost',
            "api_string" => json_encode($this->trackingData)
        );

        $temp = $this->modelObj->findTrackingById($this->trackingData->shipment_id, $this->statusArray[$this->trackingData->status], $this->trackingData->id, $this->trackingData->tracking_code, $this->trackingData->carrier, 'easypost');

        if($temp["num_count"]==0)
            $this->modelObj->saveTracking($data);
    }

    private

    function _saveTrackingDetail(){
        foreach($this->trackingData->tracking_details as $details){
            $temp = $this->modelObj->findTrackingDetail($details->status_detail, $details->datetime);
            if($temp["tracking_code_count"]==0){
                $data = array(
                    "object" => $details->object,
                    "message" => $details->message,
                    "description" => $details->description,
                    "status" => $details->status,
                    "status_detail" => $details->status_detail,
                    "datetime" => $details->datetime,
                    "source" => $details->source,
                    "carrier_code" => $details->carrier_code,
                    "city" => $details->city,
                    "state" => $details->state,
                    "country" => $details->country,
                    "zip" => $details->zip,
                    "tracking_id" => $this->trackingData->result->id,
                    "origin" => 'easypost'
                );
                $this->modelObj->saveTrackingDetail($data);
            }
        }
    }

    private function _saveTrackingCarrierDetail(){
        $data = array(
            "object" => $this->trackingData->carrier_detail->object,
            "service" => $this->trackingData->carrier_detail->service,
            "container_type" => $this->trackingData->carrier_detail->container_type,
            "est_delivery_date_local" => $this->trackingData->carrier_detail->est_delivery_date_local,
            "est_delivery_time_local" => $this->trackingData->carrier_detail->est_delivery_time_local,
            "origin_location" => $this->trackingData->carrier_detail->origin_location,
            "origin_location_city" => $this->trackingData->carrier_detail->origin_tracking_location->city,
            "origin_location_state" => $this->trackingData->carrier_detail->origin_tracking_location->state,
            "origin_location_country" => $this->trackingData->carrier_detail->origin_tracking_location->country,
            "origin_location_zip" => $this->trackingData->carrier_detail->origin_tracking_location->zip,
            "destination_location" => $this->trackingData->carrier_detail->destination_location,
            "destination_location_city" => $this->trackingData->carrier_detail->destination_tracking_location->city,
            "destination_location_state" => $this->trackingData->carrier_detail->destination_tracking_location->state,
            "destination_location_country" => $this->trackingData->carrier_detail->destination_tracking_location->country,
            "destination_location_zip" => $this->trackingData->carrier_detail->destination_tracking_location->zip,
            "guaranteed_delivery_date" => $this->trackingData->carrier_detail->guaranteed_delivery_date,
            "alternate_identifier" => $this->trackingData->carrier_detail->alternate_identifier,
            "initial_delivery_attempt" => $this->trackingData->carrier_detail->initial_delivery_attempt,
            "tracking_id" => $this->trackingData->id,
            "origin" => 'easypost'
        );

        $temp = $this->modelObj->findTrackingCarrierDetail($this->trackingData->result->id);
        if($temp["num_count"]==0)
            $this->modelObj->saveTrackingCarrierDetail($data);
        else
            $this->modelObj->updateTrackingCarrierDetail($data, $this->trackingData->result->id);
    }

    public

    function createTracking($tracking_code, $carrier){
        \EasyPost\EasyPost::setApiKey($this->apiInfo[ENV]["api_key"]);
        $this->trackingData = \EasyPost\Tracker::create(array('tracking_code' => $tracking_code, 'carrier' => $carrier));

        $this->_updateShipmentService($tracking_code);
        $this->_saveShipmentLifeHistory();
        $this->_saveShipmentTracking();
        $this->_saveTrackingCarrierDetail();
        $this->_saveTrackingDetail();
    }
}
?>