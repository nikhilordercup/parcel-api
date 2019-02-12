<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 07-02-2019
 * Time: 07:50 PM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class UserAddressModel extends Model
{
    protected $table='user_address';
    public $timestamps=false;
    protected $guarded=[];
}