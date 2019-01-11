<?php

namespace v1\module\UserNotes;

class NotesApi
{
    private $_db;
    private $_app;
    private $_requestParams;

    private function __construct($app)
    {
        $this->_app = $app;
        $this->_requestParams = json_decode($this->_app->request->getBody());
    }

    public static function UserNotesApi($app){

        $app->post('/saveUserNotes', function () use ($app){
            $r = json_decode($app->request->getBody());
            $obj = new UserNotes($r);
            $responce = $obj->insertUserNotes($r);
            echoResponse(200, $responce);
        });

        $app->post('/getUserNotes', function () use ($app){
            $r = json_decode($app->request->getBody());
            $obj = new UserNotes($r);
            $responce_notes = $obj->getAllUserNotesByJobIdentity($r);
            echoResponse(200, $responce_notes);
        });

    }
}