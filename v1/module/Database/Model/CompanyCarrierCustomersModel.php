<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 28-01-2019
 * Time: 06:03 PM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class CompanyCarrierCustomersModel extends Model
{
    protected $table='courier_vs_company_vs_customer';
    public $timestamps=false;
    protected $guarded=[];
}