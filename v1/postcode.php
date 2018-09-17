<?php

class Postcode {

	private function is_valid_uk_postcode($p) { 
		$postcodeRegEx = "/[A-Z]{1,2}[0-9]{1,2} ?[0-9][A-Z]{2}/i";
		return preg_match($postcodeRegEx, $p);
	}
	
	private function format_uk_postcode($p) { return $p; 
		if ($this->is_valid_uk_postcode($p)) { 
			$postcodeRegEx = "/(^[A-Z]{1,2}[0-9]{1,2})([0-9][A-Z]{2}$)/i";
			return preg_replace($postcodeRegEx,"$1 $2", $p); 
		} else {
			return false;
		}
	}
	
	private function is_valid_postcode($p,$countryCode) { 
		$postcodeRegEx = $this->country_specific_reg_exp($countryCode);
		return preg_match($postcodeRegEx, $p);
	}

	private function format_postcode($p,$countryCode) {
		if ($this->is_valid_postcode($p,$countryCode)) {
			$postcodeRegEx = $this->country_specific_reg_exp($countryCode);
			return array($p,$postcodeRegEx);
			//return array(preg_replace($postcodeRegEx,"$1 $2", $p),$postcodeRegEx); 
		} else {
			return false;
		}
	}
	
	public function validate($p,$countryCode){
		return $this->format_postcode($p,$countryCode);
		
	}
	
	private function country_specific_reg_exp($countryCode){
		$ZIPREG=array(
			"US"=>"/(^\d{5}$)|(^\d{5}-\d{4}$)/",
			"UK"=>"/^(GIR|[A-Z]\d[A-Z\d]??|[A-Z]{2}\d[A-Z\d]??)[ ]??(\d[A-Z]{2})$/",
			"GB"=>"/^(GIR|[A-Z]\d[A-Z\d]??|[A-Z]{2}\d[A-Z\d]??)[ ]??(\d[A-Z]{2})$/",
			"DE"=>"/\b((?:0[1-46-9]\d{3})|(?:[1-357-9]\d{4})|(?:[4][0-24-9]\d{3})|(?:[6][013-9]\d{3}))\b/",
			"CA"=>"/^([ABCEGHJKLMNPRSTVXY]\d[ABCEGHJKLMNPRSTVWXYZ])\ {0,1}(\d[ABCEGHJKLMNPRSTVWXYZ]\d)$/",
			"FR"=>"/^(F-)?((2[A|B])|[0-9]{2})[0-9]{3}$/",
			"IT"=>"/^(V-|I-)?[0-9]{5}$/",
			"AU"=>"/^(0[289][0-9]{2})|([1345689][0-9]{3})|(2[0-8][0-9]{2})|(290[0-9])|(291[0-4])|(7[0-4][0-9]{2})|(7[8-9][0-9]{2})$/",
			"NL"=>"/^[1-9][0-9]{3}\s?([a-zA-Z]{2})?$/",
			"ES"=>"/^([1-9]{2}|[0-9][1-9]|[1-9][0-9])[0-9]{3}$/",
			"DK"=>"/^([D|d][K|k]( |-))?[1-9]{1}[0-9]{3}$/",
			"SE"=>"/^(s-|S-){0,1}[0-9]{3}\s?[0-9]{2}$/",
			"BE"=>"/^[1-9]{1}[0-9]{3}$/",
			"IN"=>"/^\d{6}$/"
		);
		return $ZIPREG[$countryCode];
	}

}
?>