<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 17-01-2019
 * Time: 12:15 PM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class CompanyUsersModel extends Model
{
    protected $guarded=[];
    public $timestamps=false;
    protected $table='company_users';
}