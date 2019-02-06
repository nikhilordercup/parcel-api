<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 05-02-2019
 * Time: 05:09 PM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class RoutePostcodeModel extends Model
{
    protected $table='route_postcode';
    public $timestamps=false;
    protected $guarded=[];
}