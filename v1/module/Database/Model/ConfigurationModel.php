<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 18-01-2019
 * Time: 04:44 PM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class ConfigurationModel extends Model
{
    protected $table='configuration';
    public $timestamps=false;
    protected $guarded=[];
}