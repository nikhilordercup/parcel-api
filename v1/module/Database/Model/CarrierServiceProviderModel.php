<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 13-02-2019
 * Time: 11:28 AM
 */

namespace v1\module\Database\Model;


use Illuminate\Database\Eloquent\Model;

class CarrierServiceProviderModel extends Model
{
    protected $table='carrier_service_provider';
    public $timestamps=false;
    protected $guarded=[];
    public function carrier(){
        return $this->belongsTo(CarrierModel::class,'carrier_id','id');
    }
    public function provider(){
        return $this->belongsTo(ServiceProvidersModel::class,'provider_id','id');
    }
    public function endpoint(){
        return $this->belongsTo(ServiceProvidersModel::class,'provider_id','id');
    }
}