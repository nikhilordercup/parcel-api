<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 23-01-2019
 * Time: 03:44 PM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class WarehouseModel extends Model
{
    protected $table='warehouse';
    public $timestamps=false;
    protected $guarded=[];
}