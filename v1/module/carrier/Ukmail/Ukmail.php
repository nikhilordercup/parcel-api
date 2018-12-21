<?php
require_once "CarrierInterface.php";
final class Ukmail extends Carrier implements CarrierInterface{

    

	//get to and from for address(common so will be in common class)
	//get shipdate
	//get credential
	//get package
	//get currency
	//carrier
	//service code
	public function getLabel($shipmentInfo,$loadIdentity){
		//check validation
		
		
	}
	
	private function validate($data){
		$error = array();
		//call validation function from validation class
		if(!Ukmail_Validation::_getInstance()->firstName('first_name')){
			$error['first_name'] = Ukmail_Validation::_getInstance()->errorMsg;
		}
		if(!Ukmail_Validation::_getInstance()->lastName('last_name')){
			$error['last_name'] = Ukmail_Validation::_getInstance()->errorMsg;
		}
	}
	
	}
?>