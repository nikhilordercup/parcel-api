<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CustomFilterConfiguration
 *
 * @author perce_qzotijf
 */
class CustomFilterConfiguration {
    //put your code here
    private $_db;
    private $_app;
    private $_requestParams;
    private $_table='custom_filter_config';

    /**
     * FormConfiguration constructor.
     */
    private function __construct($app) {
        $this->_db = new DbHandler();
        $this->_app = $app;
        $this->_requestParams=json_decode($this->_app->request->getBody());
    }

    public static function initRoutes($app) {        
        
        $app->post('/saveFilterConfig', function() use ($app) { 
            $self = new CustomFilterConfiguration($app);
            $r = json_decode($app->request->getBody());	
            verifyRequiredParams(array('access_token'), $r);            
            $user=(array)$self->fetchUserInfo($r->access_token);
            $self->editConfiguration($user['id'],$r->filter_config->filter_name, $r->filter_slug, json_encode($r->filter_config));
            $filterConfig=$self->fetchAllConfiguration($user['id']);
            foreach ($filterConfig as $k=>$v)$filterConfig[$k]['filter_config']= json_decode (stripcslashes ($v['filter_config']));
            echoResponse(200, array('result'=>'success','message'=>'Configuration Saved Successfully.','filters'=>$filterConfig));
        });
        $app->post('/fetchFilterConfig', function() use ($app) {  
            $self = new CustomFilterConfiguration($app);
            $r = json_decode($app->request->getBody());	
            verifyRequiredParams(array('access_token'), $r);            
            $user=(array)$self->fetchUserInfo($r->access_token);
            $gridConfig=$self->fetchConfiguration($user['id'], $r->filter_slug);
            echoResponse(200, array('filter_config'=> json_decode(stripcslashes($gridConfig['filter_config'])),'result'=>'success'));
        });
        $app->post('/fetchAllFilter', function() use ($app) {  
            $self = new CustomFilterConfiguration($app);
            $r = json_decode($app->request->getBody());	
            verifyRequiredParams(array('access_token'), $r);            
            $user=(array)$self->fetchUserInfo($r->access_token);
            $filterConfig=$self->fetchAllConfiguration($user['id']);
            foreach ($filterConfig as $k=>$v)$filterConfig[$k]['filter_config']= json_decode (stripcslashes ($v['filter_config']));
            echoResponse(200, array('filters'=> $filterConfig,'result'=>'success'));
        });
        $app->post('/deleteFilter', function() use ($app) {  
            $self = new CustomFilterConfiguration($app);
            $r = json_decode($app->request->getBody());	
            verifyRequiredParams(array('access_token'), $r);            
            $user=(array)$self->fetchUserInfo($r->access_token);
            $self->deleteConfiguration($user['id'],$r->filter_config->filter_slug,$r->filter_config->filter_name);
            $filterConfig=$self->fetchAllConfiguration($user['id']);
            foreach ($filterConfig as $k=>$v)$filterConfig[$k]['filter_config']= json_decode (stripcslashes ($v['filter_config']));
            echoResponse(200, array('filters'=> $filterConfig,'result'=>'success'));
        });
    }

    

    public function addConfiguration($userId, $filter_name, $filter_slug, $filter_config) {
        $configData = addslashes($filter_config);
        $sql = "INSERT INTO " . DB_PREFIX . $this->_table . " (user_id,filter_name,filter_slug,filter_config)" .
                " VALUES ('$userId', '$filter_name','$filter_slug','$configData')";
        return $this->_db->executeQuery($sql);
    }

    public function editConfiguration($userId, $filter_name, $filter_slug, $filter_config) {
        $exist = $this->fetchConfiguration($userId, $filter_slug,$filter_name);
        if (is_null($exist)) {
            return $this->addConfiguration($userId, $filter_name, $filter_slug, $filter_config);
        }
        $configData = addslashes($filter_config);
        $sql = "UPDATE " . DB_PREFIX . $this->_table . " SET filter_config='$filter_config' WHERE " .
                " user_id=$userId AND filter_slug='$filter_slug'";
        return $this->_db->updateData($sql);
    }

    public function fetchConfiguration($userId, $filter_slug, $filter_name) {
        $sql = "SELECT * FROM " . DB_PREFIX . $this->_table . " WHERE  user_id=$userId AND filter_slug='$filter_slug' AND filter_name='$filter_name'";
        return $this->_db->getOneRecord($sql);
    }
    
    public function fetchAllConfiguration($userId) {
        $sql = "SELECT * FROM " . DB_PREFIX . $this->_table . " WHERE  user_id=$userId";
        return $this->_db->getAllRecords($sql);
    }

    public function deleteConfiguration($userId, $filter_slug, $filter_name) {
        $sql = "DELETE FROM " . DB_PREFIX . $this->_table . " WHERE " .
                " user_id=$userId AND filter_slug='$filter_slug' AND filter_name='$filter_name'";
        return $this->_db->delete($sql);
    }
    
    public function fetchUserInfo($accessToken) {
        $sql = "SELECT * FROM " . DB_PREFIX . "users WHERE  access_token='$accessToken' ";
        return $this->_db->getOneRecord($sql);
    }
}