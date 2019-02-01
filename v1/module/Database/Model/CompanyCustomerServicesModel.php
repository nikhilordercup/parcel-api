<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 28-01-2019
 * Time: 06:49 PM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class CompanyCustomerServicesModel extends Model
{
    protected $table='company_vs_customer_vs_services';
    public $timestamps=false;
    protected $guarded=[];
}