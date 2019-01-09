<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 04-01-2019
 * Time: 03:42 PM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class CountriesModel extends Model
{
    protected $table='countries';
    protected $guarded=[];
    public $timestamps=false;
}