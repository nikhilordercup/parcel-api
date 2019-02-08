<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 08-02-2019
 * Time: 03:21 PM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class ServiceFlowTypeModel extends Model
{
    protected $table='service_flow_type';
    public $timestamps=false;
    protected $guarded=[];
}