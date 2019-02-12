<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 23-01-2019
 * Time: 04:22 PM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class CompanyWarehouseModel extends Model
{
    protected $table='company_warehouse';
    public $timestamps=false;
    protected $guarded=[];
    public function warehouse(){
        return $this->hasOne(WarehouseModel::class,'id','warehouse_id');
    }
}