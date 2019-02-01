<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 28-01-2019
 * Time: 05:00 PM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class CarrierServicesModel extends Model
{
    protected $table='courier_vs_services';
    public $timestamps=false;
    protected $guarded=[];

}