<?php
require_once "model/Tracking_Model_Index.php";
class Create_Tracking extends Icargo{
    //public $loadIdentity = null;



    private $statusArray = array(
        "pre_transit" => "INFO_RECEIVED",
        "in_transit" => "IN_TRANSIT",
        "out_for_delivery" => "OUTFORDELIVERY",
        "delivered" => "DELIVERYSUCCESS",
		"failure"  => "INFO_RECEIVED",
		"unknown"  => "INFO_RECEIVED"
    );

    public

    function __construct(){
        $this->modelObj = new Tracking_Model_Index();
        $this->commonObj = new Common();

        $this->apiInfo = array(
            "dev"  => array("api_key"=>"8VfzGXyF0idIP1exSAlabQ"),
            "live" => array("api_key"=>"8VfzGXyF0idIP1exSAlabQ")
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

    function _updateShipmentService($tracking_code, $tracking_data){
        $this->loadIdentity = null;
        $loadIdentity = $this->modelObj->findLoadIdentityByTrackingNo($tracking_code);

        if(isset($loadIdentity["load_identity"])){
            $this->loadIdentity = $loadIdentity["load_identity"];
            $this->modelObj->updateTracking($this->loadIdentity, $this->statusArray[$tracking_data->status]);
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

    function _findShipmentTicketByTrackingCodeAndLoadIdentity($load_ldentity, $code){
        $shipmentData = $this->modelObj->findDeliveryShipmentlByLoadIdentity($load_ldentity);
        return $shipmentData["shipment_ticket"];
    }

    private

    function _savePod($shipment_ticket, $created_at, $tracking_id){
        
        $data = array(
            "shipment_ticket" => $shipment_ticket,
            "pod_name" => 'signature',
            "comment" => 'text',
            "contact_person" => $this->trackingData->signed_by,
            "create_date" => $created_at,
            "tracking_id" => $tracking_id,
            "value" => ""
        );

        $temp = $this->modelObj->findPodByTrackingId($tracking_id);

        if($temp["num_count"]>0){
            $this->modelObj->updatePod($data, $tracking_id);
        }else{
            $this->modelObj->savePod($data);  
        }
    }

    private

    function _saveShipmentTracking(){
        $data = array(
            "tracking_id" => $this->trackingData->id,
            "object" => $this->trackingData->object,
            "mode" => $this->trackingData->mode,
            "tracking_code" => $this->trackingData->tracking_code,
            //"code" => $this->statusArray[$this->trackingData->status],
            "status_detail" => $this->trackingData->status_detail,
            "created_at" => $this->trackingData->created_at,
            "updated_at" => $this->trackingData->updated_at,

            "signed_by" => $this->trackingData->signed_by,
            "weight" => $this->trackingData->weight,
            "est_delivery_date" => $this->trackingData->est_delivery_date,
            //"shipment_id" => $this->trackingData->shipment_id,
            "load_identity" => $this->loadIdentity,
            "carrier" => $this->trackingData->carrier,
            "finalized" => (empty($this->trackingData->finalized)) ? 0 : $this->trackingData->finalized,
            "is_return" => $this->trackingData->is_return,
            "public_url" => $this->trackingData->public_url,
            "user_id" => $this->trackingData->user_id,
            "event_id" => $this->trackingData->id,
            "origin" => 'easypost',
            "api_string" => json_encode($this->trackingData)
        );

        foreach($this->trackingData->tracking_details as $details){
            $date = new DateTime($details->datetime);
            $data["create_date"] = $date->format('Y-m-d H:i:s');

            $data["code"] = $this->statusArray[$details->status];
            $data["shipment_ticket"] = $this->_findShipmentTicketByTrackingCodeAndLoadIdentity($this->loadIdentity, $data["code"]);
           
            $temp = $this->modelObj->findTrackingById($this->loadIdentity, $data["code"], $this->trackingData->id, $this->trackingData->tracking_code, $this->trackingData->carrier, 'easypost');

            if(count((array)$temp)==0)
                $tracking_id = $this->modelObj->saveTracking($data);
            else{
                $tracking_id = $temp["id"];
                $this->modelObj->updateTrackingHistory($data, $tracking_id);
            }

            if($data["code"]=='DELIVERYSUCCESS'){
                $this->_savePod($data["shipment_ticket"], $data["create_date"], $tracking_id);
            }   
        }
    }

    private

    function _saveTrackingDetail(){
		if(isset($this->trackingData->tracking_details)){
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
						"tracking_id" => $this->trackingData->id,
						"origin" => 'easypost'
					);
					$this->modelObj->saveTrackingDetail($data);
				}
			}
		}
    }

    private function _saveTrackingCarrierDetail(){
		if(isset($this->trackingData->carrier_detail)){

				$data = array(
				"object" => $this->trackingData->carrier_detail->object,
				"service" => $this->trackingData->carrier_detail->service,
				"container_type" => $this->trackingData->carrier_detail->container_type,
				"est_delivery_date_local" => $this->trackingData->carrier_detail->est_delivery_date_local,
				"est_delivery_time_local" => $this->trackingData->carrier_detail->est_delivery_time_local,
				"origin_location" => $this->trackingData->carrier_detail->origin_location,
				"origin_location_city" => (isset($this->trackingData->carrier_detail->origin_tracking_location)) ? $this->trackingData->carrier_detail->origin_tracking_location->city : "",
				"origin_location_state" => (isset($this->trackingData->carrier_detail->origin_tracking_location)) ? $this->trackingData->carrier_detail->origin_tracking_location->state : "",
				"origin_location_country" => (isset($this->trackingData->carrier_detail->origin_tracking_location)) ? $this->trackingData->carrier_detail->origin_tracking_location->country : "",
				"origin_location_zip" => (isset($this->trackingData->carrier_detail->origin_tracking_location)) ? $this->trackingData->carrier_detail->origin_tracking_location->zip : "",
				"destination_location" => $this->trackingData->carrier_detail->destination_location,
				"destination_location_city" => (isset($this->trackingData->carrier_detail->destination_tracking_location)) ? $this->trackingData->carrier_detail->destination_tracking_location->city : "",
				"destination_location_state" => (isset($this->trackingData->carrier_detail->destination_tracking_location)) ? $this->trackingData->carrier_detail->destination_tracking_location->state : "",
				"destination_location_country" => (isset($this->trackingData->carrier_detail->destination_tracking_location)) ? $this->trackingData->carrier_detail->destination_tracking_location->country : "",
				"destination_location_zip" => (isset($this->trackingData->carrier_detail->destination_tracking_location)) ? $this->trackingData->carrier_detail->destination_tracking_location->zip : "",
				"guaranteed_delivery_date" => $this->trackingData->carrier_detail->guaranteed_delivery_date,
				"alternate_identifier" => $this->trackingData->carrier_detail->alternate_identifier,
				"initial_delivery_attempt" => $this->trackingData->carrier_detail->initial_delivery_attempt,
				"tracking_id" => $this->trackingData->id,
				"origin" => 'easypost'
			);


            $temp = $this->modelObj->findTrackingCarrierDetail($this->trackingData->id);
            if($temp["num_count"]==0)
              $this->modelObj->saveTrackingCarrierDetail($data);
            else
              $this->modelObj->updateTrackingCarrierDetail($data, $this->trackingData->id);



		}
    }

    private

    function _createTracking($tracking_code, $carrier){
        \EasyPost\EasyPost::setApiKey($this->apiInfo[ENV]["api_key"]);
        $this->trackingData = \EasyPost\Tracker::create(array('tracking_code' => $tracking_code, 'carrier' => $carrier));

        $this->_updateShipmentService($tracking_code, $this->trackingData);
        $this->_saveShipmentLifeHistory();
        $this->_saveShipmentTracking();
        $this->_saveTrackingCarrierDetail();
        $this->_saveTrackingDetail();
    }

    public

    function createTracking($tracking_code, $carrier_code){
        $carrier_code = strtolower($carrier_code);
        switch($carrier_code){
            case "dhl";
                $this->_createTracking($tracking_code, "DHLExpress");
            break;
        }
    }

    private function _saveCronDhlTracking($items, $counter=-1){
        $counter++;
        if($counter < $this->itemCount){
            $tracking_code = (int)$items[$counter]["tracking_number"];
            $carrier_code = $items[$counter]["carrier_code"];

            if($tracking_code>0){
                $this->createTracking($tracking_code, $carrier_code);
            }
            return $this->_saveCronDhlTracking($items, $counter);
        }else{

        }
    }

    public function saveDhlTracking(){
        $items = $this->modelObj->getDhlTrackingId();

        if(is_array($items) && count($items)){
            $this->itemCount = count($items);
            $this->_saveCronDhlTracking($items);
        }
    }
}
?>
