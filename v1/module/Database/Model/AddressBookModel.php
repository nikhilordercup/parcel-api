<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 07-02-2019
 * Time: 07:49 PM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class AddressBookModel extends Model
{
    protected $table='address_book';
    public $timestamps=false;
    protected $guarded=[];
}