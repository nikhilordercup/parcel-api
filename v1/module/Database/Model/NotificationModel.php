<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 04-01-2019
 * Time: 09:19 PM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class NotificationModel extends Model
{
    protected $table='notification';
    protected $guarded=[];
    public $timestamps=false;
}