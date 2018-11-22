<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of GridConfiguration
 *
 * @author perce_qzotijf
 */
class GridConfiguration {

    private $_db;
    private $_app;
    private $_requestParams;
    private $_table='user_grid_states';
    /**
     * FormConfiguration constructor.
     */
    private function __construct($app) {
        $this->_db = new DbHandler();
        $this->_app = $app;
        $this->_requestParams=json_decode($this->_app->request->getBody());
    }

    public static function initRoutes($app) {        
        
        $app->post('/saveGridState', function() use ($app) { 
            $self = new GridConfiguration($app);
            $r = json_decode($app->request->getBody());	
            verifyRequiredParams(array('access_token'), $r);            
            $user=(array)$self->fetchUserInfo($r->access_token);
            $self->editConfiguration($user['id'], $r->grid_slug, json_encode($r->grid_state));
            echoResponse(200, array('result'=>'success','message'=>'Configuration Saved Successfully.'));
        });
        $app->post('/fetchGridState', function() use ($app) {  
            $self = new GridConfiguration($app);
            $r = json_decode($app->request->getBody());	
            verifyRequiredParams(array('access_token'), $r);            
            $user=(array)$self->fetchUserInfo($r->access_token);
            $gridConfig=$self->fetchConfiguration($user['id'], $r->grid_slug);
            echoResponse(200, array('grid_state'=> json_decode(stripcslashes($gridConfig['grid_state'])),'result'=>'success'));
        });
    }

    public function setSaveGridConfiRoute() {
        
    }

    public function addConfiguration($userId, $gridSlug, $gridState) {
        $configData = addslashes($gridState);
        //$extraData=addslashes($extraData);
        $sql = "INSERT INTO " . DB_PREFIX . $this->_table . " (user_id,grid_slug,grid_state)" .
                " VALUES ('$userId','$gridSlug','$gridState')";
        return $this->_db->executeQuery($sql);
    }

    public function editConfiguration($userId, $gridSlug, $gridState) {
        $exist = $this->fetchConfiguration($userId, $gridSlug);
        if (is_null($exist)) {
            return $this->addConfiguration($userId, $gridSlug, $gridState);
        }
        $configData = addslashes($gridState);
        $sql = "UPDATE " . DB_PREFIX . $this->_table . " SET grid_state='$configData' WHERE " .
                " user_id=$userId AND grid_slug='$gridSlug'";
        return $this->_db->updateData($sql);
    }

    public function fetchConfiguration($userId, $gridSlug) {
        $sql = "SELECT * FROM " . DB_PREFIX . $this->_table . " WHERE  user_id=$userId AND grid_slug='$gridSlug'";
        return $this->_db->getOneRecord($sql);
    }

    public function deleteConfiguration($userId, $gridSlug) {
        $sql = "DELETE FROM " . DB_PREFIX . $this->_table . " WHERE " .
                " user_id=$userId AND grid_slug='$gridSlug'";
        return $this->_db->delete($sql);
    }
    
    public function fetchUserInfo($accessToken) {
        $sql = "SELECT * FROM " . DB_PREFIX . "users WHERE  access_token='$accessToken' ";
        return $this->_db->getOneRecord($sql);
    }

}