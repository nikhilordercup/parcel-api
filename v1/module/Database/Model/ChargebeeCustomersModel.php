<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 24-12-2018
 * Time: 11:50 AM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class ChargebeeCustomersModel extends Model
{
    protected $table='chargebee_customer';
    public function subscription(){
        return $this->hasOne(ChargebeeSubscriptionsModel::class,'chargebee_customer_id','chargebee_customer_id')
            ->whereIn('status',['in_trial','active']);
    }
}