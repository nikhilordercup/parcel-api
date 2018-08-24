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
	
	public function validate($p){
		return $this->format_uk_postcode($p);
		
	}
}
?>