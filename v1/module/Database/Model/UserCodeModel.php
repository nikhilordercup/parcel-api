<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 18-01-2019
 * Time: 05:02 PM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class UserCodeModel extends Model
{
    protected $table='user_code';
    public $timestamps=false;
    protected $guarded=[];
}