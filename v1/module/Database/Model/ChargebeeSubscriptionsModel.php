<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 24-12-2018
 * Time: 11:54 AM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class ChargebeeSubscriptionsModel extends Model
{
    protected $table='chargebee_subscription';
    protected $guarded=[];
    public $timestamps=false;
    public function customer(){
        return $this->belongsTo(ChargebeeCustomersModel::class,'chargebee_customer_id','chargebee_customer_id');
    }
}