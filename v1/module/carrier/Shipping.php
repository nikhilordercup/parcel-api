<<<<<<< HEAD
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
=======
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
>>>>>>> b5f5ac66cc7a31a7b7a522c82730846839a4a200
?>