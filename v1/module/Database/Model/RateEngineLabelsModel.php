<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 10-01-2019
 * Time: 11:59 AM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class RateEngineLabelsModel extends Model
{
    protected $table='rateengine_labels';
    protected $guarded=[];
    public $timestamps=false;
}