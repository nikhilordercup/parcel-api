<?php 
$app->post('/getSameDayQuotation', function() use ($app) { 
    $response = array();
    $r = verifyToken($app,$app->request->getBody()); 
    $r->endpoint = 'getSameDayQuotation';
    $obj = new Sameday((object)array('email'=>$r->email,'access_token'=>$r->access_token,'endpoint'=>$r->endpoint,'web_token'=>$r->webToken));
	$records = $obj->getSameDayQuotation($r);
    $saveWebRequest = $obj->saveWebReqResponce($r,$records,$app);
	echoResponse(200, $records);
});
$app->post('/bookSameDayQuotation', function() use ($app) { 
    $response = array();
    $r = verifyToken($app,$app->request->getBody());  
    $r->endpoint = 'bookSameDayQuotation';
    $obj = new Sameday((object)array('email'=>$r->email,'access_token'=>$r->access_token,'endpoint'=>$r->endpoint,'web_token'=>$r->webToken));
	$records = $obj->bookSameDayQuotation($r);
	$saveWebRequest = $obj->saveWebReqResponce($r,$records,$app);
    echoResponse(200, $records);
});
$app->post('/bookSameDayJob', function() use($app){ 
        $response = array();
        $r = verifyToken($app,$app->request->getBody());
        $r->endpoint = 'bookSameDayJob';
        $obj = new Sameday((object)array('email'=>$r->email,'access_token'=>$r->access_token,'endpoint'=>$r->endpoint,'web_token'=>$r->webToken));
        $records = $obj->bookSameDayJobWithoutQuotion($r);
        $saveWebRequest = $obj->saveWebReqResponce($r,$records,$app);
        echoResponse(200, $records);
});
$app->get('/executeRecurringJob', function() use($app){ 
        $response = array();
        $r = verifyTokenByPass($app,$app->request->getBody());
        $r->endpoint = 'bookedRecurringJob';
        $app->request->headers->set('Authorization',$r->token);
        $sameobj = new Sameday((object)array('email'=>$r->email,'access_token'=>$r->access_token,'endpoint'=>$r->endpoint,'web_token'=>$r->webToken));
        //$nextobj = new Nextday((object)array('email'=>$r->email,'access_token'=>$r->access_token,'endpoint'=>'bookedRecurringJob'));
        $records = $sameobj->executeSameDayRecurringJob($r);
        $saveWebRequest = $sameobj->saveWebReqResponce($r,$records,$app);
        //$records = $nextobj->executeNextDayReccuringJob($r);
        echoResponse(200, $records);
});
$app->post('/getNextDayQuotation', function() use ($app) {
        $response = array();
        $r = verifyToken($app,$app->request->getBody());
        $r->endpoint = 'getNextDayQuotation';
        $obj = new Nextday($r);
        $records = $obj->getNextDayQuotation();
        $saveWebRequest = $obj->saveWebReqResponce($r,$records,$app);
        echoResponse(200, $records);
});
$app->post('/bookNextDayJob', function() use ($app){
    $r = json_decode($app->request->getBody());
    $r->endpoint = 'bookNextDayJob';
    verifyRequest(array('access_token','company_id','warehouse_id'),$r);
    $obj = new Nextday($r);
    $response = $obj->saveBooking($r);
    $saveWebRequest = $obj->saveWebReqResponce($r,$records,$app);
    echoResponse(200, $response);
});
$app->post('/canCancelJob', function() use ($app) { 
    $response = array();
    $r = verifyToken($app,$app->request->getBody());
    $r->endpoint = 'canCancelJob';
    $obj = new Sameday((object)array('email'=>$r->email,'access_token'=>$r->access_token,'endpoint'=>$r->endpoint,'web_token'=>$r->webToken));
	$records = $obj->canCancelJob($r);
	$saveWebRequest = $obj->saveWebReqResponce($r,$records,$app);
    echoResponse(200, $records);
});
$app->post('/cancelJob', function() use ($app) { 
    $response = array();
    $r = verifyToken($app,$app->request->getBody());
    $r->endpoint = 'cancelJob';
    $obj = new Sameday((object)array('email'=>$r->email,'access_token'=>$r->access_token,'endpoint'=>$r->endpoint,'web_token'=>$r->webToken));
	$records = $obj->cancelJob($r);
	$saveWebRequest = $obj->saveWebReqResponce($r,$records,$app);
    echoResponse(200, $records);
});
$app->post('/getShipmentTracking', function() use ($app) { 
    $response = array();
    $r = verifyToken($app,$app->request->getBody());
    $r->endpoint = 'getShipmentTracking';
    $obj = new Sameday((object)array('email'=>$r->email,'access_token'=>$r->access_token,'endpoint'=>$r->endpoint,'web_token'=>$r->webToken));
	$records = $obj->getShipmentTracking($r);
	$saveWebRequest = $obj->saveWebReqResponce($r,$records,$app);
    echoResponse(200, $records);
});

/*  Globle Web services*/
$app->post('/getQuotation', function() use ($app) { 
    $response = array();
    $r = verifyToken($app,$app->request->getBody()); 
    $r->endpoint = 'getQuotation';
    $sameObj = new Sameday((object)array('email'=>$r->email,'access_token'=>$r->access_token,'endpoint'=>$r->endpoint,'web_token'=>$r->webToken));
	$sameRecords = $sameObj->getSameDayQuotation($r);
    $quotation_ref = '';
    if($sameRecords['status']=='success'){
        $quotation_ref = $sameRecords['rate']['quotation_ref'];
    }
    $nextObj = new Nextday((object)array('email'=>$r->email,'access_token'=>$r->access_token,'endpoint'=>$r->endpoint,'web_token'=>$r->webToken));
    $nextRecords = $nextObj->getNextDayQuotation($r,$quotation_ref);
    $commonObj   = new Common();
    $records = $commonObj->getMergeRecords($sameRecords,$nextRecords);
    $saveWebRequest = $sameObj->saveWebReqResponce($r,$records,$app);
	echoResponse(200, $records);
});    
?>
