<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 05-02-2019
 * Time: 05:07 PM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class RouteModel extends Model
{
    protected $table='routes';
    public $timestamps=false;
    protected $guarded=[];
}