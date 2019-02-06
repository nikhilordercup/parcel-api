<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 04-01-2019
 * Time: 06:53 PM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class NotificationDefaultModel extends Model
{
    protected $table='notification_default';
    protected $guarded=[];
    public $timestamps=false;
}