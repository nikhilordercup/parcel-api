<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 24-01-2019
 * Time: 05:03 PM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class DriverVehicleModel extends Model
{
    protected $table='driver_vehicle';
    protected $guarded=[];
    public $timestamps=false;
}