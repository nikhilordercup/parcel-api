<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 17-01-2019
 * Time: 12:31 PM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class UserLevelsModel extends Model
{
    protected $table='user_model';
    public $timestamps=false;
    protected $guarded=[];

}