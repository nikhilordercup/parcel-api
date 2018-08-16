<?php
interface ShipmentInterface{
	public function initShipmentInfo($shipmentInfo);
	public function addFromAddress();
	public function addToAddress();
	public function addReturnAddress();
	public function addShipmentAccount();
	public function addParcel();
	public function getRateList();
	public function getRateId();
	public function getAccountToUse();
	public function getRates();
	public function addShipment();
	public function buyShipment();
}