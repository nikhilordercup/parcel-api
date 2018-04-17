<?php
class Booking extends Icargo
{
    public function __construct($data){return true;
        $this->_parentObj = parent::__construct(array("email" => $data["email"], "access_token" => $data["access_token"]));
    }

    public function saveNextDayBooking($param){echo "<pre>";print_r($param);die;
        $obj = new Booking_Shipment();
        $param->loadGroupTypeCode = "NEXT";
        $param->availabilityTypeCode = "UNKN";
        $param->availabilityTypeName = "Unknown";
        $param->file_name = "";
        $param->loadGroupTypeName = "NEXT";
        $param->isDutiable = "false";
        $param->jobTypeName = "Collection";
        $param->jobTypeCode = "COLL";
        $param->shipment_service_type = "P";
        $param->icargo_execution_order = "1";
        $param->shipment_executionOrder = "1";
        $param->loadGroupTypeId = "0";
        $param->instaDispatch_loadGroupTypeIcon = "";
        $param->shipment_pod = "";
        $param->waitAndReturn = "false";
        $param->warehouse_id = "92";  //fix
        $param->customer_id = $param->customer->id;
        $param->user_id = $param->user->id;
        $param->notification = "pta nhi h";

        $obj->setParam($param, $param->collection);
    }

}
?>