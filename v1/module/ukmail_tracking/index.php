<?php
require __DIR__ . '/vendor/autoload.php';
//$authObj = A::getInstance();

$authObj = Auth::getInstance();

$authObj->login(
  array(
    "username" => "abcd",
    "password" => "123456"
  )
);
