<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 04-02-2019
 * Time: 06:50 PM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class CustomerInfoModel extends Model
{
    protected $table='customer_info';
    public $timestamps=false;
    protected $guarded=[];
}