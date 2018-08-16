<?php
interface CarrierInterface{
	public function addCarrier($carrierInfo);
	public function getCarrierId($carrierName);
	public function getCarrierList();
}