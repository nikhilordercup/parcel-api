<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 28-01-2019
 * Time: 01:07 PM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class CarrierModel extends Model
{
    protected $table='courier';
    public $timestamps=false;
    protected $guarded=[];
}