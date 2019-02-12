<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 28-01-2019
 * Time: 05:49 PM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class CompanyCarrierServicesModel extends Model
{
    protected $table='courier_vs_services_vs_company';
    public $timestamps=false;
    protected $guarded=[];
}