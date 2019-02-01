<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 29-01-2019
 * Time: 04:14 PM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class VehicleModel extends Model
{
    protected $table='vehicle';
    public $timestamps=false;
    protected $guarded=[];
}