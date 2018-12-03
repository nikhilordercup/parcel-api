<?php
require_once('model/addressbook.php');
require_once(dirname(dirname(dirname(__FILE__))).'/postcodeanywhere/lookup.php');

class Module_Addressbook_Addressbook extends Icargo{

    public

    function __construct($data){
	    $this->_parentObj = parent::__construct(array("email"=>$data->email, "access_token"=>$data->access_token));

	}

   public

    function getAllAddresses($param)
    {
        $response = array();
		//added by kavita for search button 19march2018
		if(isset($param->country_code))
			$param->country_code = ($param->country_code=='') ? 'GB' : $param->country_code;
		else
			$param->country_code = 'GB';
	    if(isset($param->origin) && $param->origin=='api')
	    {
	          $pcaLookup = new Address_Lookup();
            $addresses = $pcaLookup->lookup($param->search_postcode,$param->country_code);
            if($addresses["status"]=="success")
            {
		            $container = json_decode(json_encode((array)$addresses['data']), TRUE);
				        $addresses = $pcaLookup->lookup($param->search_postcode,$param->country_code,$container[0]['id'][0]);
                $records = array();
                foreach($addresses["data"] as $key => $list)
                {
                    array_push($records, array(
                        "address" => $list["place"].", ".$list["street"],
                        "id" => $list["id"],
                        "street" => $list["street"]
                    ));
                }
                $response = array("status"=>"success","data"=>$records,"origin"=>"api");
                return $response;
			}
		}
		//end
        $records = Addressbook_Model::_getInstance()->searchAllAddress(array("customer_id"=>$param->customer_id,"postcode"=>$param->search_postcode));
        if(!$records)
        {
            $pcaLookup = new Address_Lookup();
            $addresses = $pcaLookup->lookup($param->search_postcode,$param->country_code);
            if($addresses["status"]=="success")
            {
		        $container = json_decode(json_encode((array)$addresses['data']), TRUE);
				$addresses = $pcaLookup->lookup($param->search_postcode,$param->country_code,$container[0]['id'][0]);
                $records = array();
                foreach($addresses["data"] as $key => $list)
                {
                    array_push($records, array(
                        "address" => $list["place"].", ".$list["street"],
                        "id" => $list["id"],
                        "street" => $list["street"]
                    ));
                    /*array_push($records, array
                        (
                         "address_line1" => $list["line1"],
                         "address_line2" => $list["line2"],
                         "postcode" => $list["postcode"],
                         "city" => $list["posttown"],
                         "state" => $list["county"],
                         "country" => $list["country_name"],
                         "id" => $key
                        )*/
                    //);
                }
                $response = array("status"=>"success","data"=>$records,"origin"=>"api");
            }
            else
            {
                $response = array("status"=>"error","message"=>$addresses["message"]);
            }
        }
        else
        {
            $response = array("status"=>"success","data"=>$records,"origin"=>"local");
        }

        return $response;
    }

    public

    function getAllAddresses13March2018($param)
    {
        $response = array();
        $records = Addressbook_Model::_getInstance()->searchAllAddress(array("customer_id"=>$param->customer_id,"postcode"=>$param->search_postcode));
        $response = array("status"=>"success","data"=>$records,"origin"=>"local");
        return $response;
    }

    public

    function searchAddressByIdBKP26march2018($param){
        if($param->address_origin=="api"){
            $pcaLookup = new Address_Lookup();
            $addresses = $pcaLookup->lookupByID((int)$param->id);
            if($addresses["status"]=="success"){
                $data = $addresses["data"][0];
                return array("status"=>"success", "data"=>array(
                    "residential_type"=>(strtolower($data["type"])=="residential") ? "yes" : "no",
                    "city"=>$data["posttown"],
                    "state"=>$data["county"],
                    "address_line1"=>$data["line1"],
                    "address_line2"=>$data["line2"],
                    "country"=>$data["country_name"]));
            }
            else{
                return array("status"=>"error", "message"=>"Searched address not found");
            }
        }
    }

	public

    function searchAddressById($param){
        if($param->address_origin=="api"){
            $pcaLookup = new Address_Lookup();
            //$addresses = $pcaLookup->lookupByID((int)$param->id);
			$addresses = $pcaLookup->lookupByID("$param->id");
            if($addresses["status"]=="success"){
                $data = $addresses["data"][0];
                return array("status"=>"success", "data"=>array(
                    "residential_type"=>(strtolower($data["type"])=="residential") ? "yes" : "no",
                    "city"=>$data["posttown"],
                    "state"=>$data["county"],
                    "address_line1"=>$data["line1"],
                    "address_line2"=>$data["line2"],
                    "country"=>$data["country_name"]),"origin"=>"api");
            }
            else{
                return array("status"=>"error", "message"=>"Searched address not found");
            }
        }else{
			$addresses = Addressbook_Model::_getInstance()->searchAddressByAddressId(array("address_id"=>$param->id));
			return array("status"=>"success", "data"=>array(
                "name"=>$addresses["first_name"],
                "phone"=>$addresses["contact_no"],
                "email"=>$addresses["contact_email"],
                "city"=>$addresses["city"],
				"residential_type"=>(strtolower($addresses["address_type"])=="residential") ? "yes" : "no",
				"state"=>$addresses["state"],
				"address_line1"=>$addresses["address_line1"],
				"address_line2"=>$addresses["address_line2"],
				"postcode"=>$addresses["postcode"],
				"company_name"=>$addresses["company_name"],
				"country"=>$addresses["country"]),"origin"=>"local");
		}
    }

	public

    function getAllAddressesTest($param)
    {
	    $records = array();
        $response = array();
        $addresses = Addressbook_Model::_getInstance()->searchAllAddress(array("customer_id"=>$param->customer_id,"postcode"=>$param->search_postcode));
        if(count($addresses)>0){
			foreach($addresses as $key => $list)
                {
					array_push($records, array(
						"address" => $list["address_line1"].", ".$list["address_line2"],
						"id" => $list["id"],
						"street" => $list["city"]
					));
                }
		}else{

		}
        $response = array("status"=>"success","data"=>$records,"origin"=>"local");

        return $response;
    }

	public

    function getAllDefaultWarehouseAddressBySearchKey($param){
        $records = Addressbook_Model::_getInstance()->searchAllDefaultWarehouseAddress($param->customer_id,$param->search_postcode);
        $response = array("status"=>"success","data"=>$records,"origin"=>"local");
        return $response;
    }

	public function checkChangedAddress($param){
		$addressBookParam = (Object) array(
			"address_1" => (isset($param->address_line1)) ? $param->address_line1 : "",
			"address_2" => (isset($param->address_line2)) ? $param->address_line2 : "",
			"postcode"  => (isset($param->postcode)) ? $param->postcode : "",
			"city"      => (isset($param->city)) ? $param->city : "",
			"state"     => (isset($param->state)) ? $param->state : "",
			"country"   => (isset($param->country)) ? $param->country : "",
			"name"      => (isset($param->name)) ? $param->name : "",
			"email"     => (isset($param->address_email)) ? $param->address_email : "",
			"company_id"=> (isset($param->company_name)) ? $param->company_name : ""
		);

		$commonObj = new Common();
		$addressBookStr = $commonObj->getAddressBookSearchString($addressBookParam);
		$record = Addressbook_Model::_getInstance()->searchAddressByAddressStringAndAddressId($param->address_id, $addressBookStr);

		if($record){
			$response = array("address_status"=>"not changed");
		   //echo "record found";
		}else{
		   $response = array("address_status"=>"changed");
		   //echo "record not found";
		}
		return $response;
	}

	public function getAllAddressesFromAddressBook(){
		$records = Addressbook_Model::_getInstance()->getAllAddressesFromAddressBook();
		foreach($records as $record){
			$data = strtolower(preg_replace('/\s+/','',$record['search_string']));
			$update = Addressbook_Model::_getInstance()->updateAddressById($record['id'],$data);
			print_r($update);
		}
	}

}
?>
