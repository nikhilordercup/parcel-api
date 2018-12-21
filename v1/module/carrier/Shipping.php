<?php
final class Shipping{
	
	public static $_shippingObj = null;

	public static function _getInstance(){
		if(self::$_shippingObj==null){
			self::$_shippingObj = new Shipping();
		}
		return self::$_shippingObj;
	}
	
	public function generateLabel($loadIdentity){
		//search carrier from loadIdentity from shipment service table
		
		
	
	}
	
	private function _getLabel(){
		
		switch($this->carrier){
			case "UKMAIL" : 
				$obj = new Ukmail();
				$obj->getLabel($shipmentInfo,$loadIdentity);
			break;
			
			case "DHL" :
				
			
			break;
			
		}
		
		
	}

}
?>