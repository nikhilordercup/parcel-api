<?php
use PhpUnitsOfMeasure\PhysicalQuantity\Length;
use PhpUnitsOfMeasure\PhysicalQuantity\Mass;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of UnitConverter
 *
 * @author perce
 */
class UnitConverter {
    //put your code here
    public function convertLenght($unit,$from,$to){
        $length=new Length($unit,$from);
        return $converted=$length->toUnit($to);
    }
    public function converWeight($unit,$from,$to){
        $weight=new Mass($unit, $from);
        return $weight->toUnit($to);
    }
    
}
