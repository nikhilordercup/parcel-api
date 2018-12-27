<?php
class Module_Shipment_Tracking
{
    private $_shipmentStatus = array("COLLECTIONSUCCESS"=>"Collected","DELIVERYSUCCESS"=>"Delivered");

    private $_shipmentMessage = array("COLLECTIONSUCCESS"=>"Collected successfully","DELIVERYSUCCESS"=>"Delivered successfully");

    private

    function getMarkerIcons(){
        $libObj = new Library();
        return array("driver"=>$libObj->get_api_url()."assets/icons/vehicle.png","COLLECTIONSUCCESS"=>$libObj->get_api_url()."assets/icons/green-marker.png","DELIVERYSUCCESS"=>$libObj->get_api_url()."assets/icons/green-marker.png");
    }

    public

    function getTracking($param)
    {
        $warehouseId = 0;
        $markerIcons = $this->getMarkerIcons();
        $markers = array();
        $warehouseInfo = array();

        $items = Shipment_Model::getInstanse()->getShipmentTracking($param->identity);
        if(count($items)>0){
            foreach($items as $key=>$item){
                $items[$key]["status_code"] = $this->_shipmentStatus[$item["status_code"]];

                $pod = Shipment_Model::getInstanse()->getShipmentPod($item["shipment_ticket"]);
                $items[$key]["pod"] = $pod["pod"];

                array_push($markers, array(
                    "icon"=>$markerIcons[$item["status_code"]], 
                    "message"=>$this->_shipmentMessage[$item["status_code"]], 
                    "label"=>$item["execution_order"],
                    "latitude"=>$item["latitude"],
                    "longitude"=>$item["longitude"],
                    ));

                $warehouseId = $item["warehouse_id"];
                $driverId = $item["driver_id"];
                $createDate = date("Y-m-d", strtotime(str_replace("/","-",$item["create_date"])));

                unset($items[$key]["warehouse_id"]);
                unset($items[$key]["driver_id"]);
                unset($items[$key]["latitude"]);
                unset($items[$key]["longitude"]);
                unset($items[$key]["execution_order"]);
            }

            $warehouseInfo = Shipment_Model::getInstanse()->getWarehouseInfo($warehouseId);

            $driverCurrentLocation = Shipment_Model::getInstanse()->getDriverCurrentLocation($driverId, $createDate);
            array_push($markers, array(
                "icon"=>$markerIcons["driver"], 
                "message"=>"", 
                "label"=>"D",
                "latitude"=>$driverCurrentLocation["latitude"],
                "longitude"=>$driverCurrentLocation["longitude"],
                )
            );
        }
        return array("warehouse_info"=>$warehouseInfo, "shipment_info"=>$items, "markers_info"=>$markers);
    }
}