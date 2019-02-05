<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 03-01-2019
 * Time: 05:28 PM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class SurchargesModel extends Model
{
    protected $table='surcharges';
    protected $guarded=[];
    public $timestamps=false;
}