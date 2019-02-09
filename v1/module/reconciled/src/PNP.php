<?php 
//namespace DHL\DHL {
   class PNP extends Singleton{
    public function getName(){
        return 'I am PNP Class !!';
    }
    public function validHeader($header){
      $headerColumn = array(
                                str_replace(' ','',strtolower('Invoice No')),
                                str_replace(' ','',strtolower('Invoice Date')),
                                str_replace(' ','',strtolower('Company Name')),
                                str_replace(' ','',strtolower('Account Number')),
                                str_replace(' ','',strtolower('DHL Product Description')),
                                str_replace(' ','',strtolower('Nett Charge')),
                                str_replace(' ','',strtolower('Weight')),
                                str_replace(' ','',strtolower('Tax Amount')),
                                str_replace(' ','',strtolower('AWB Number')),
                                str_replace(' ','',strtolower('Shipper Reference')),
                                str_replace(' ','',strtolower('Shipment Date'))
                            );    	
        $flag = array();
        foreach($headerColumn as $key=>$vals){
            if(!in_array($vals,$header)){
                $flag[] = false;
            }
        }
        if(count($flag)>0){
            return false;
        }else{
           return true; 
        }
     }
} 
//}
?>
