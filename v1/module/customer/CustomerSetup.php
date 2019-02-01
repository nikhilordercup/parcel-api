<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 24-01-2019
 * Time: 11:13 AM
 */

namespace v1\module\customer;


use Illuminate\Database\Eloquent\Builder;
use Slim\Slim;
use v1\module\Database\Model\CarrierModel;
use v1\module\Database\Model\CarrierServicesModel;
use v1\module\Database\Model\CompanyCarrierAccountsModel;
use v1\module\Database\Model\CompanyCarrierCustomersModel;
use v1\module\Database\Model\CompanyCarrierServicesModel;
use v1\module\Database\Model\CompanyCustomerServicesModel;
use v1\module\Database\Model\CompanyDefaultRegistrationSetupModel;
use v1\module\Database\Model\CompanyUsersModel;
use v1\module\Database\Model\CompanyWarehouseModel;
use v1\module\Database\Model\DriverVehicleModel;
use v1\module\Database\Model\UsersModel;
use v1\module\Database\Model\VehicleCategoryMasterModel;
use v1\module\Database\Model\VehicleModel;
use v1\module\Database\Model\WarehouseModel;

class CustomerSetup
{
    protected $_customerId;
    protected $_companyCustomerId;

    public function setupStepOne($params)
    {
        $customer = $this->setDefaultCustomer($params);
        $carrier = $this->addCarrier($params);
        $service = $this->addService($carrier, $params);
        $account = $this->addCarrierAccount($carrier, $params);
        $this->enableCarrierForCustomer($params->company_id, $account, $carrier, $customer);
        foreach ($service as $s) {
            $companyServiceId = $this->enableServicesForCompany($s, $account, $params->company_id);
            $this->enableServiceForCustomer($s, $companyServiceId, $carrier, $params->company_id, $customer);
        }
    }

    public function setDefaultCustomer($param)
    {
        if (CompanyUsersModel::query()->whereHas('userInfo', function ($q) {
                $q->where('user_level', '=', 5);
            })->where('company_id', '=', $param->company_id)->count() == 0) {
            $param->password = \passwordHash::hash('password');
            $param->user_level = 5;
            $param->register_in_firebase = 0;
            $company = UsersModel::query()->where('id', '=', $param->company_id)->first();
            $data = [
                'parent_id' => $param->company_id,
                'name' => $param->companyName,
                'contact_name' => $param->contactName,
                'phone' => $param->companyPhone,
                'email' => 'default' . $param->company_id . 'customer@gmail.com',
                'password' => $param->password,
                'address_1' => $param->address_one,
                'address_2' => $param->address_two,
                'city' => $param->companyCity,
                'postcode' => $param->companyPost,
                'state' => $param->companyState,
                'country' => $param->companyCountry,
                'user_level' => $param->user_level,
                'uid' => '',
                'register_in_firebase' => $param->register_in_firebase
            ];
            $this->_customerId = (UsersModel::query()->create($data))->id;
            $relationData = array(
                'company_id' => $param->company_id,
                'warehouse_id' => $param->warehouse_id,
                'user_id' => $this->_customerId
            );
            $this->_companyCustomerId = (CompanyUsersModel::query()->create($relationData))->id;
            return $this->_customerId;
        } else {
            return 0;
        }
    }

    public function addDriver($param,$companyInfo)
    {
        $param->company_id=$companyInfo->id;
        $companyDriver = UsersModel::query()
            ->where('email', '=', $param->email)
            ->first();
        $driverExist = CompanyUsersModel::query()->whereHas('userInfo', function ($q) {
            $q->where('user_level', '=', 4);
        })->where('company_id', '=', $param->company_id)->count();

        if (!$companyDriver && $driverExist == 0) {
            $param->password = \passwordHash::hash($param->password);
            $param->user_level = 4;
            $param->register_in_firebase = 1;
            $data = array(
                'parent_id' => $param->company_id ?? 0,
                'name' => $param->name ?? "",
                'contact_name' => $param->name ?? "",
                'phone' => $companyInfo->phone ?? "",
                'email' => $param->email ?? "",
                'password' => $param->password ?? "",
                'address_1' => $companyInfo->address_1 ?? "",
                'address_2' => $companyInfo->address_2 ?? "",
                'city' => $companyInfo->city ?? "",
                'postcode' => $companyInfo->postcode ?? "",
                'user_level' => 4,
                'uid' =>  $param->uid,
                'register_in_firebase' => 1,
                'state' => $companyInfo->state ?? "",
                'country' => $companyInfo->country ?? ""
            );
            $driver_id = (UsersModel::query()->create($data))->id;
            $warehouse=CompanyWarehouseModel::query()->where('company_id','=',$param->company_id)->first();
            if ($driver_id != NULL) {
                $relationData = array(
                    'company_id' => $param->company_id,
                    'warehouse_id' => $warehouse->warehouse_id,
                    'user_id' => $driver_id
                );
                $relationTblEntry = (CompanyUsersModel::query()->create($relationData))->id;
                DriverVehicleModel::query()
                    ->create([
                        'driver_id' => $driver_id,
                        'vehicle_id' => $this->addVehicle($param),
                        'vehicle_category_id' => $param->vehicleType
                    ]);
            }
        }
    }

    public function addVehicle($param)
    {
        $category = VehicleCategoryMasterModel::query()
            ->where('id', '=', $param->vehicleType)->first();
        $columnData = [
            'company_id' => $param->company_id,
            'category_id' => $param->vehicleType,
            'plate_no' => uniqid(),
            'model' => date('Y'),
            'brand' => uniqid(), 'color' => 'BLACK',
            'max_weight' => $category->max_weight,
            'max_width' => $category->max_width,
            'max_height' => $category->max_height,
            'max_length' => $category->max_length,
            'max_volume' => $category->max_volume];
        $id = (VehicleModel::query()->create($columnData))->id;
        return $id;
    }

    public function addCarrier($param)
    {
        $courier = [
            'name' => $param->companyName,
            'code' => strtoupper(str_replace(' ', '_', $param->companyName)),
            'icon' => '',
            'description' => '',
            'is_self' => 'YES',
            'company_id' => $param->company_id,
            'status' => 1,
            'is_apiused' => 'YES'
        ];
        return (CarrierModel::query()->create($courier))->id;
    }

    public function addService($carrierId, $params)
    {
        $sameDayService = [
            'courier_id' => $carrierId,
            'service_name' => $params->companyName,
            'service_code' => 'SAMEDAY' . strtoupper(str_replace(' ', '_', $params->companyName)),
            'service_icon' => '',
            'service_description' => '',
            'create_date' => date('Y-m-d'),
            'service_type' => 'SAMEDAY',
            'flow_type' => ''
        ];
        $nextDayService = [
            'courier_id' => $carrierId,
            'service_name' => $params->companyName,
            'service_code' => 'SAMEDAY' . strtoupper(str_replace(' ', '_', $params->companyName)),
            'service_icon' => '',
            'service_description' => '',
            'create_date' => date('Y-m-d'),
            'service_type' => 'NEXTDAY',
            'flow_type' => ''
        ];
        if (CarrierServicesModel::query()->where('courier_id', '=', $carrierId)
                ->count() == 0) {
            $sameDayId = (CarrierServicesModel::query()->create($sameDayService))->id;
            $nextDayId = (CarrierServicesModel::query()->create($nextDayService))->id;
            return [$sameDayId, $nextDayId];
        } else {
            return [];
        }
    }

    public function addCarrierAccount($carrierId, $params)
    {
        $accountInfo = [
            'courier_id' => $carrierId,
            'company_id' => $params->company_id,
            'account_number' => 'blank',
            'username' => 'blank',
            'password' => 'blank',
            'create_date' => date('Y-m-d'),
            'company_ccf_operator_service' => 'FLAT',
            'company_ccf_operator_surcharge' => 'FLAT',
            'address_id' => '0',
            'status' => '1'
        ];
        return (CompanyCarrierAccountsModel::query()->create($accountInfo))->id;
    }

    public function enableServicesForCompany($serviceId, $carrierAccountId, $companyId)
    {
        $data = [
            'service_id' => $serviceId,
            'courier_id' => $carrierAccountId,
            'company_id' => $companyId,
            'create_date' => date('Y-m-d')
        ];
        return (CompanyCarrierServicesModel::query()->create($data))->id;
    }

    public function enableCarrierForCustomer($companyId, $account, $carrierId, $customerId)
    {
        $data = [
            'company_id' => $companyId,
            'company_courier_account_id' => $account,
            'courier_id' => $carrierId,
            'customer_id' => $customerId,
            'create_date' => date('Y-m-d'),
            'status' => 1
        ];
        return (CompanyCarrierCustomersModel::query()->create($data))->id;
    }

    public function enableServiceForCustomer($serviceId, $companyService, $carrierId, $companyId, $companyCustomerId)
    {
        $data = [
            'service_id' => $serviceId,
            'company_service_id' => $companyService,//Id from CompanyCarrierServicesModel
            'courier_id' => $carrierId,
            'company_id' => $companyId,
            'company_customer_id' => $companyCustomerId,//Id from Company
            'create_date' => date('Y-m-d'),
            'status' => 1
        ];
        return (CompanyCustomerServicesModel::query()->create($data))->id;
    }

    /**
     * @param $app Slim
     */
    public static function initRoutes($app)
    {
        $app->post('/getVehicleMasterCategories',function ()use ($app){
           $categories=VehicleCategoryMasterModel::query()->where('status','=',1)
               ->get();
           echoResponse(200,['status'=>'success','message'=>$categories]);
        });
        $app->post('/createOnBoardDriver',function ()use ($app){
            $r = json_decode($app->request->getBody());
            $company=UsersModel::query()->where('id','=',$r->company_id)->first();
            (new CustomerSetup())->addDriver($r->driver,$company);
            echoResponse(200,['status'=>'success','message'=>'Driver created successfully']);
        });
        $app->post('/getWarehouseCount',function ()use ($app){
            $r = json_decode($app->request->getBody());
            $company=CompanyWarehouseModel::query()->where('company_id','=',$r->company_id)->count();
            echoResponse(200,['status'=>'success','message'=>$company]);
        });
    }

}