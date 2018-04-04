<?php

// popup message will be populated for basic account setup




/*$app->get('/dashboard/getAllAssignedRecords', function() {
    $db = new DbHandler();//DST.shipment_accepted  = 'Pending' AND 
	$sql = "SELECT DST.*, ST.* FROM ".DB_PREFIX."driver_shipment AS DST INNER JOIN ".DB_PREFIX."shipment AS ST ON ST.shipment_ticket = DST.shipment_ticket WHERE ST.current_status  = 'O'";
	echo json_encode(array("assigned_records"=>$db->getAllRecords($sql)),true);
});
$app->get('/dashboard/getAllActiveDrivers', function() {
    $db = new DbHandler();
	$sql = "SELECT DST.*, ST.* FROM ".DB_PREFIX."driver_shipment AS DST INNER JOIN ".DB_PREFIX."shipment AS ST ON ST.shipment_ticket = DST.shipment_ticket WHERE ST.current_status  = 'O'";
	echo json_encode(array("assigned_records"=>$db->getAllRecords($sql)),true);
});

$app->get('/dashboard/getAllInactiveDrivers', function() {
    $db = new DbHandler();
	$sql = "SELECT DST.*, ST.* FROM ".DB_PREFIX."driver_shipment AS DST INNER JOIN ".DB_PREFIX."shipment AS ST ON ST.shipment_ticket = DST.shipment_ticket WHERE ST.current_status  = 'O'";
	echo json_encode(array("assigned_records"=>$db->getAllRecords($sql)),true);
});

$app->get('/dashboard/getAllDrivers', function() {
    $db = new DbHandler();
	$sql = "SELECT DST.*, ST.* FROM ".DB_PREFIX."driver_shipment AS DST INNER JOIN ".DB_PREFIX."shipment AS ST ON ST.shipment_ticket = DST.shipment_ticket WHERE ST.current_status  = 'O'";
	echo json_encode(array("assigned_records"=>$db->getAllRecords($sql)),true);
});*/
?>