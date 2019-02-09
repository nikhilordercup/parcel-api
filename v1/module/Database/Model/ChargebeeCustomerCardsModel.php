<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 24-12-2018
 * Time: 11:51 AM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class ChargebeeCustomerCardsModel extends Model
{
    protected $table='chargebee_customer_card';
    protected $guarded=[];
    public $timestamps=false;
}