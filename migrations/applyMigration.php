<?php
require_once './MigrationFileManager.php';
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
if(!empty($_POST)){
    $mfm=new MigrationFileManager;
    $mfm->applyMigration($_POST['file_name']);
}else{
    exit(json_encode(array('success'=>FALSE,'message'=>'Invalid request.')));
}

