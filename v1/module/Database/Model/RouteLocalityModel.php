<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 05-02-2019
 * Time: 05:08 PM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class RouteLocalityModel extends Model
{
    protected $table='route_locality';
    public $timestamps=false;
    protected $guarded=[];
}