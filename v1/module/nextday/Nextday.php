<?php
final class Nextday extends Booking
{

    private $_param = array();
    protected static $_ccf = NULL;

    public function __construct($data){
        $this->_parentObj = parent::__construct(array("email" => $data->email, "access_token" => $data->access_token));
        $this->_param = $data;
    }

    /*private function _ccf(){
        if(self::$_ccf===NULL){
            self::$_ccf = new CustomerCostFactor();
        }
        return self::$_ccf;
    }

    private function _calculateCcf($data,$courier_code){
        return $this->_ccf()->calculate($data, $courier_code, $this->_param->customer_id, $this->_param->company_id);
    }*/

    private function _searchUkMail(){
        $obj = new Ukmail();
        $data = $obj->searchService($this->_param);
        return $data;
    }

    public function searchNextdayAvailableCarrier(){
        $response = array();
        $errorResponse = array();
        $results = array();

        if(isset($this->_param->carrier)){
            switch($this->_param->carrier) {
                case 'ukmail' :
                    $data = $this->_searchUkMail();
                    if(count($data)>0){
                        array_push($results, $data);
                    }
                    break;
            };
        }

        foreach($results as $item){
            if($item["status"]=="error"){
                array_push($errorResponse, array("status"=>"error", "response"=>$item["message"], "carrier_name"=>$item["carrier_name"]));

                return $errorResponse;

            }else{
                array_push($response, array(
                    $item["carrier_code"]=>array(
                        "services"=>$item["response"],
                        "carrier_info"=>(object)array(
                            "carrier_code"=>$item["carrier_code"],
                            "carrier_name"=>$item["carrier_name"],
                            "carrier_icon"=>$item["carrier_icon"],
                            "carrier_description"=>$item["carrier_description"],
                            "carrier_id"=>$item["carrier_id"],
                        )
                    )
                ));
            }
        }
        return $response;
    }
}
?>