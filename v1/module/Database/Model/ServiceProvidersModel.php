<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 13-02-2019
 * Time: 11:22 AM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class ServiceProvidersModel extends Model
{
    protected $table='service_providers';
    public $timestamps=false;
    protected $guarded=[];
}