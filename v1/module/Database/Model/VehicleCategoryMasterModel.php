<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 29-01-2019
 * Time: 06:09 PM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class VehicleCategoryMasterModel extends Model
{
    protected $table='vehicle_category_master';
    public $timestamps=false;
    protected $guarded=[];
}