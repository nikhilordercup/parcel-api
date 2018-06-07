<?php
//useless file. dont do any work in this file.
class Booking_Shipment{
    private $_data = array();

    private function _setShipmentDimensions($param){

        $length = array();
        $width  = array();
        $height = array();
        $weight = array();
        $volume = array();

        $highest_length = 0;
        $highest_width  = 0;
        $highest_height = 0;

        $highest_weight = 0;
        $total_weight   = 0;
        $total_volume   = 0;

        foreach($param as $item){
            array_push($height, $item->height);
            array_push($length, $item->length);
            array_push($weight, $item->weight);
            array_push($width,  $item->width);
            array_push($volume, $item->height*$item->length*$item->width);
        }
        $highest_length = max($length);
        $highest_width  = max($width);
        $highest_height = max($height);
        $highest_weight = max($weight);

        $this->_data['shipment_total_weight']     = array_sum($weight);
        $this->_data['shipment_total_volume']     = array_sum($volume);
        $this->_data['shipment_highest_length']   = $highest_length;
        $this->_data['shipment_highest_width']    = $highest_width;
        $this->_data['shipment_highest_height']   = $highest_height;
        $this->_data['shipment_highest_weight']   = $highest_weight;
    }

    private function _generate_ticket_no($company_id){
        $record = Carrier_Model_Carrier::_getInstance()->getTicketNo($company_id);
        if($record){
            $ticket_number = $record['shipment_ticket_prefix'].str_pad($record['shipment_ticket_no'],6,0,STR_PAD_LEFT);

            $check_digit = Library::_getInstance()->generateCheckDigit($ticket_number);

            $ticket_number = "$ticket_number$check_digit";

            $status = Carrier_Model_Carrier::_getInstance()->saveLastTicketNo($company_id);

            if(Carrier_Model_Carrier::_getInstance()->testShipmentTicket($ticket_number)){
                $this->_generate_ticket_no();
            }
            return $ticket_number;
        }else{
            return false;
        }


    }

    public function setParam($param, $consignee){
        //echo "<pre>";print_r($param);print_r($consignee);die;

        $ticketNumber = $this->_generate_ticket_no($param->company_id);

        if($ticketNumber){
            //$timestamp = $param["timestamp"];
            $company_code = Carrier_Model_Carrier::_getInstance()->getCompanyCode($param->company_id);

            $latLong = Library::_getInstance()->get_lat_long_by_postcode($consignee->postcode);
            //print_r($latLong);die;
            //customer info
            $this->_data['shipment_customer_name']    = (isset($consignee->name)) ? $consignee->name : "";
            $this->_data['shipment_customer_email']   = (isset($consignee->email)) ? $consignee->email : "";
            $this->_data['shipment_customer_phone']   = (isset($consignee->phone)) ? $consignee->phone : "";

            $this->_data['shipment_address1'] = (isset($consignee->address_line1)) ? $consignee->address_line1 : "";
            $this->_data['shipment_address2'] = (isset($consignee->address_line2)) ? $consignee->address_line2 : ""; //$param["address_line2"];
            $this->_data['shipment_customer_city'] = (isset($consignee->city)) ? $consignee->city: "";
            $this->_data['shipment_postcode'] = (isset($consignee->postcode)) ? $consignee->postcode : "";
            $this->_data['shipment_customer_country'] = (isset($consignee->country)) ? $consignee->country : "";
            $this->_data['shipment_instruction'] = (isset($consignee->instruction)) ? $consignee->instruction : "";


            /*data not saved*/
            $this->_setShipmentDimensions($param->parcel);
//print_r($this->_data);die;

            $this->_data['shipment_statusName']       = "Un Attainded";
            $this->_data['shipment_shouldBookIn']     = "false";
            $this->_data['shipment_companyName']      = "";
            $this->_data['distancemiles']             = "0.00";
            $this->_data['estimatedtime']             = "00:00:00";
            /**/



            if(!isset($param->parcel_id))
                $param->parcel_id = 0;

            $this->_data['shipment_required_service_starttime']       = $param->service_starttime;
            $this->_data['shipment_required_service_endtime']         = $param->service_endtime;

            $this->_data['shipment_total_item']       = 0;//no need to save //$param["itemCount"];

            //$shipment_geo_location = $this->get_lat_long_by_postcode($param["postcode"],$param['latitude'],$param['longitude']);

            //$warehouse_id = $this->_shipment_warehouse(array("company_id"=>$param["company_id"], "postcode"=>$param["postcode"], "shipment_geo_location"=>$shipment_geo_location));
            $warehouse_id = $param->warehouse_id;

            $this->_data['instaDispatch_loadGroupTypeCode'] = strtoupper($param->loadGroupTypeCode);
            $this->_data['instaDispatch_docketNumber'] = (isset($param->docketNumber)) ? $param->docketNumber : $ticketNumber;
            $this->_data['instaDispatch_loadIdentity'] = (isset($param->loadIdentity)) ? $param->loadIdentity : $ticketNumber;
            $this->_data['instaDispatch_jobIdentity'] = (isset($param->jobIdentity)) ? $param->jobIdentity : $ticketNumber;
            $this->_data['instaDispatch_objectIdentity'] = (isset($param->objectIdentity)) ? $param->objectIdentity : $ticketNumber;
            $this->_data['instaDispatch_objectTypeName'] = (isset($param->instaDispatch_objectTypeName)) ? $param->instaDispatch_objectTypeName : "JobLoad";

            $this->_data['instaDispatch_objectTypeId'] = (isset($param->objectTypeId)) ? $param->objectTypeId : "0";
            $this->_data['instaDispatch_accountNumber'] = (isset($param->accountNumber)) ? $param->accountNumber : "0";
            $this->_data['instaDispatch_businessName'] = $company_code; //find from db by compny id
            $this->_data['instaDispatch_statusCode'] = (isset($param->statusCode)) ? $param->statusCod : "UNATTAINDED";
            $this->_data['instaDispatch_jobTypeCode'] = $param->jobTypeCode;

            $this->_data['instaDispatch_availabilityTypeCode'] = $param->availabilityTypeCode;
            $this->_data['instaDispatch_availabilityTypeName'] = $param->availabilityTypeName;
            $this->_data['instaDispatch_loadGroupTypeId']   = $param->loadGroupTypeId;
            $this->_data['instaDispatch_loadGroupTypeIcon'] = $param->loadGroupTypeIcon;
            $this->_data['instaDispatch_loadGroupTypeName'] = $param->loadGroupTypeName;
            $this->_data['instaDispatch_customerReference'] = $param->customer_reference;

            $this->_data['shipment_isDutiable'] = $param->isDutiable;
            $this->_data['error_flag'] = "0";


            $this->_data['shipment_xml_reference'] = $param->file_name;

            $this->_data['shipment_total_attempt'] = '0';

            //$this->_data['parent_id'] = (isset($param["parent_id"])) ? $param["parent_id"] : 0; no need to save

            $this->_data['shipment_pod'] = '';
            $this->_data['shipment_ticket'] = $ticketNumber;
            $this->_data['shipment_required_service_date'] = $param->service_date;
            $this->_data['current_status'] = 'C';
            $this->_data['is_shipment_routed'] = '0';
            $this->_data['is_driver_assigned'] = '0';
            $this->_data['dataof'] = $company_code;
            $this->_data['waitAndReturn'] = $param->waitAndReturn;
            $this->_data['company_id'] = $param-->company_id;
            $this->_data['warehouse_id'] = $warehouse_id;
            //$this->_data['address_id'] = $param['address_id'];
            $this->_data['shipment_service_type'] = $param->shipment_service_type;

            $this->_data['shipment_latitude'] = $latLong["latitude"];
            $this->_data['shipment_longitude'] = $latLong["longitude"];
            //$this->_data['shipment_latlong'] = $param["latitude"].','.$param["longitude"];
            $this->_data['shipment_create_date'] = date("Y-m-d", strtotime('now'));
            $this->_data['icargo_execution_order'] = $param->icargo_execution_order;
            $this->_data['shipment_executionOrder'] = $param->shipment_executionOrder;

            $data['customer_id'] = (isset($param->customer_id)) ? $param->customer_id : "0";

            $this->_data['search_string'] = $ticketNumber . ' ' . $param->instaDispatch_docketNumber . ' ' . $data->instaDispatch_objectIdentity.
                str_replace(' ', '', $param->postcode) . ' ' . $param->shipment_customer_name . ' ' . $param->shipment_required_service_date;

            $this->_data['shipment_assigned_service_date'] = (isset($param->shipment_assigned_service_date)) ? $param->shipment_assigned_service_date : "1970-01-01" ;
            $this->_data['shipment_assigned_service_time'] = (isset($param->shipment_assigned_service_time)) ? $param->shipment_assigned_service_time : "00:00:00" ;
            $this->_data["booked_by"] =  "pta nhi h";//(isset($param->userid)) ? $param->userid : "0";//logged in user id
            $this->_data["user_id"] =  (isset($param->user_id)) ? $param->user_id : "0";

            $this->_data["booking_ip"] = $_SERVER['REMOTE_ADDR'];

            $this->_data["notification_status"] = (isset($consignee->notification)) ? $consignee->notification : "0";


            //save address first then save shipment detail with address id
            //$shipmentId = $this->db->save("shipment", $data);

            if($shipmentId){
                return array('status'=>"success",'message'=>'Shipment has been added successfully', "shipment_id"=>$shipmentId);
            }else{
                return array('status'=>"error",'message'=>'Shipment has not been added successfully');
            }
        }else{
            return array('status'=>"error",'message'=>'Configuration not found');
        }
    }
}