<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 18-01-2019
 * Time: 04:48 PM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class CompanyDefaultRegistrationSetupModel extends Model
{
    protected $table='company_default_registration_setup';
    public $timestamps=false;
    protected $guarded=[];
}