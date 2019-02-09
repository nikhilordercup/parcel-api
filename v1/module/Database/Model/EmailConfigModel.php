<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 04-01-2019
 * Time: 12:24 PM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class EmailConfigModel extends Model
{
    protected $table='email_config';
    protected $guarded=[];
    public $timestamps=false;
}