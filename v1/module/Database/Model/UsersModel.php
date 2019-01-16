<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 21-12-2018
 * Time: 06:08 PM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class UsersModel extends Model
{
    protected $table='users';
    protected $guarded=[];
    public $timestamps=false;
}