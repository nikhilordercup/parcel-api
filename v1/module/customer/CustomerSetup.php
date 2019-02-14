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
use v1\module\Database\Model\AddressBookModel;
use v1\module\Database\Model\CarrierModel;
use v1\module\Database\Model\CarrierServicesModel;
use v1\module\Database\Model\CompanyCarrierAccountsModel;
use v1\module\Database\Model\CompanyCarrierCustomersModel;
use v1\module\Database\Model\CompanyCarrierServicesModel;
use v1\module\Database\Model\CompanyCustomerServicesModel;
use v1\module\Database\Model\CompanyDefaultRegistrationSetupModel;
use v1\module\Database\Model\CompanyUsersModel;
use v1\module\Database\Model\CompanyWarehouseModel;
use v1\module\Database\Model\CountriesModel;
use v1\module\Database\Model\CustomerInfoModel;
use v1\module\Database\Model\DriverVehicleModel;
use v1\module\Database\Model\RouteLocalityModel;
use v1\module\Database\Model\RouteModel;
use v1\module\Database\Model\RoutePostcodeModel;
use v1\module\Database\Model\ServiceFlowTypeModel;
use v1\module\Database\Model\UserAddressModel;
use v1\module\Database\Model\UsersModel;
use v1\module\Database\Model\VehicleCategoryMasterModel;
use v1\module\Database\Model\VehicleModel;
use v1\module\Database\Model\WarehouseModel;

/**
 * @property \Illuminate\Database\Eloquent\Model|null|object|static _warehouse
 */
class CustomerSetup
{
    protected $_customerId;
    protected $_companyCustomerId;

    /**
     * @param $params
     */
    public function setupStepOne($params)
    {
        $this->_warehouse = CompanyWarehouseModel::query()->with('warehouse')->where('company_id', '=', $params->company_id)->first();
        $customer = $this->setDefaultCustomer($params);
        $carrier = $this->addCarrier($params);
        $account = $this->addCarrierAccount($carrier, $params);
        $service = $this->addService($carrier, $params, $account, $customer);
        $this->enableCarrierForCustomer($params->company_id, $account, $carrier, $customer);
        $this->setupRoute($params);
        $this->addCustomerAddress($params, $customer);
    }

    /**
     * @param $param
     * @return int|mixed
     */
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
                'name' => $param->contactName,
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
                'register_in_firebase' => $param->register_in_firebase,
                'is_default' => 1
            ];
            $this->_customerId = (UsersModel::query()->create($data))->id;
            $relationData = array(
                'company_id' => $param->company_id,
                'warehouse_id' => $param->warehouse_id,
                'user_id' => $this->_customerId
            );
            $this->_companyCustomerId = (CompanyUsersModel::query()->create($relationData))->id;
            $customerInfo = [
                'user_id' => $this->_customerId, 'billing_full_name' => $param->contactName,
                'billing_address_1' => $param->address_one, 'billing_address_2' => $param->address_two,
                'billing_postcode' => $param->companyPost, 'billing_city' => $param->companyCity,
                'billing_state' => $param->companyState, 'billing_country' => $param->companyCountry,
                'billing_phone' => $param->companyPhone, 'webapi_token' => '', 'ccf_operator_service' => 'PERCENTAGE',
                'ccf_operator_surcharge' => 'PERCENTAGE',
                'customer_type' => 'PREPAID', 'available_credit' => 500
            ];
            CustomerInfoModel::query()->create($customerInfo);
            return $this->_customerId;
        } else {
            return 0;
        }
    }

    /**
     * @param $param
     * @param $companyInfo
     */
    public function addDriver($param, $companyInfo)
    {
        $this->_warehouse = CompanyWarehouseModel::query()->where('company_id', '=', $companyInfo->id)->first();

        $param->company_id = $companyInfo->id;
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
                'uid' => $param->uid,
                'register_in_firebase' => 1,
                'state' => $companyInfo->state ?? "",
                'country' => $companyInfo->country ?? ""
            );
            $driver_id = (UsersModel::query()->create($data))->id;

            if ($driver_id != NULL) {
                $relationData = array(
                    'company_id' => $param->company_id,
                    'warehouse_id' => $this->_warehouse->warehouse_id,
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

    /**
     * @param $param
     * @return mixed
     */
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

    /**
     * @param $param
     * @return mixed
     */
    public function addCarrier($param)
    {
        $courier = [
            'name' => $param->companyName,
            'code' => strtoupper(str_replace(' ', '_', $param->companyName)),
            'icon' => '',
            'description' => '',
            'is_self' => 'YES',
            'company_id' => $param->company_id,
            'created_by' => $param->company_id,
            'status' => 1,
            'is_apiused' => 'YES'
        ];
        return (CarrierModel::query()->create($courier))->id;
    }

    /**
     * @param $carrierId
     * @param $params
     * @param $account
     * @param $customer
     * @return array
     */
    public function addService($carrierId, $params, $account, $customer)
    {
        $sameDayService = [
            'courier_id' => $carrierId,
            'service_name' => $params->companyName,
            'service_code' => 'SAMEDAY' . strtoupper(str_replace(' ', '_', $params->companyName)),
            'service_icon' => '',
            'service_description' => '',
            'create_date' => date('Y-m-d'),
            'service_type' => 'SAMEDAY',
            'flow_type' => 'domestic'
        ];
        $sameDayId = (CarrierServicesModel::query()->create($sameDayService))->id;
        $companyServiceId = $this->enableServicesForCompany($sameDayId, $account, $params->company_id);
        $this->enableServiceForCustomer($sameDayId, $companyServiceId, $carrierId, $params->company_id, $customer, $account);
        ServiceFlowTypeModel::query()->create([
            'service_id' => $sameDayId,
            'account_number' => 'blank',
            'flow_type' => 'Domestic'
        ]);
        $nextDayService = [
            'courier_id' => $carrierId,
            'service_name' => $params->companyName,
            'service_code' => 'NEXTDAY' . strtoupper(str_replace(' ', '_', $params->companyName)),
            'service_icon' => '',
            'service_description' => '',
            'create_date' => date('Y-m-d'),
            'service_type' => 'NEXTDAY',
            'flow_type' => 'domestic'
        ];

        $nextDayId = (CarrierServicesModel::query()->create($nextDayService))->id;
        $companyServiceId = $this->enableServicesForCompany($nextDayId, $account, $params->company_id);
        $this->enableServiceForCustomer($nextDayId, $companyServiceId, $carrierId, $params->company_id, $customer, $account);
        ServiceFlowTypeModel::query()->create([
            'service_id' => $nextDayId,
            'account_number' => 'blank',
            'flow_type' => 'Domestic'
        ]);
        return [0 => $sameDayId, 1 => $nextDayId];
    }

    /**
     * @param $carrierId
     * @param $params
     * @return mixed
     */
    public function addCarrierAccount($carrierId, $params)
    {
        $accountInfo = [
            'courier_id' => $carrierId,
            'company_id' => $params->company_id,
            'account_number' => 'blank',
            'username' => 'blank',
            'password' => 'blank',
            'create_date' => date('Y-m-d'),
            'company_ccf_operator_service' => 'PERCENTAGE',
            'company_ccf_operator_surcharge' => 'PERCENTAGE',
            'address_id' => $params->warehouse_id,//Warehouse ID
            'is_internal' => 1,
            'status' => '1'
        ];
        return (CompanyCarrierAccountsModel::query()->create($accountInfo))->id;
    }

    /**
     * @param $serviceId
     * @param $carrierAccountId
     * @param $companyId
     * @return mixed
     */
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

    /**
     * @param $companyId
     * @param $account
     * @param $carrierId
     * @param $customerId
     * @return mixed
     */
    public function enableCarrierForCustomer($companyId, $account, $carrierId, $customerId)
    {
        $data = [
            'company_id' => $companyId,
            'company_courier_account_id' => $account,
            'account_number' => 'blank',
            'courier_id' => $carrierId,
            'customer_id' => $customerId,
            'create_date' => date('Y-m-d'),
            'company_ccf_operator_service' => 'PERCENTAGE',
            'company_ccf_operator_surcharge' => 'PERCENTAGE',
            'status' => 1
        ];
        return (CompanyCarrierCustomersModel::query()->create($data))->id;
    }

    /**
     * @param $serviceId
     * @param $companyService
     * @param $carrierId
     * @param $companyId
     * @param $companyCustomerId
     * @param $account
     * @return mixed
     */
    public function enableServiceForCustomer($serviceId, $companyService, $carrierId, $companyId, $companyCustomerId, $account)
    {
        $data = [
            'service_id' => $serviceId,
            'company_service_id' => $companyService,//Id from CompanyCarrierServicesModel
            'courier_id' => $account,//ID from courier_vs_company
            'company_id' => $companyId,
            'company_customer_id' => $companyCustomerId,//Id from Company
            'create_date' => date('Y-m-d'),
            'ccf_operator' => 'PERCENTAGE',
            'create_by' => $companyId,
            'status' => 1
        ];
        return (CompanyCustomerServicesModel::query()->create($data))->id;
    }

    /**
     * @param $params
     */
    public function setupRoute($params)
    {
        $route = [
            'name' => $params->companyName,
            'company_id' => $params->company_id,
            'warehouse_id' => $params->warehouse_id,
            'create_date' => date('Y-m-d'),
            'allowed_drops' => 30
        ];
        $route = (RouteModel::query()->create($route))->id;
        $locality = [
            'route_id' => $route,
            'locality' => $params->companyState,
            'city' => $params->companyCity
        ];
        RouteLocalityModel::query()->create($locality);
        $rPost = [
            'postcode' => $params->companyPost,
            'route_id' => $route,
            'company_id' => $params->company_id,
            'warehouse_id' => $params->warehouse_id,
        ];
        RoutePostcodeModel::query()->create($rPost);
    }

    /**
     * @param $param
     * @param $customer
     */
    public function addCustomerAddress($param, $customer)
    {
        $country = CountriesModel::query()
            ->where('alpha2_code', '=', $param->companyCountry)
            ->orWhere('alpha3_code', '=', $param->companyCountry)
            ->first();
        $billingAddress = [
            'customer_id' => $customer, 'first_name' => $param->contactName,
            'last_name' => '', 'contact_no' => $param->companyPhone,
            'contact_email' => $param->email, 'address_line1' => $param->address_one,
            'address_line2' => $param->address_two, 'postcode' => $param->companyPost,
            'city' => $param->companyCity, 'state' => $param->companyState, 'country' => $country->short_name,
            'iso_code' => $country->alpha3_code, 'latitude' => $this->_warehouse->warehouse->latitude,
            'longitude' => $this->_warehouse->warehouse->longitude,
            'company_name' => $param->companyName, 'search_string' => '',
            'is_default_address' => 'N', 'is_warehouse' => 'N',
            'version_id' => 'version_1',
            'billing_address' => 'Y', 'name' => $param->contactName,
            'email' => $param->email, 'phone' => $param->companyPhone,
            'pickup_address' => 'N', 'country_id' => $country->id
        ];
        $billingId = (AddressBookModel::query()->create($billingAddress))->id;
        $userAdd = [
            'user_id' => $customer,
            'address_id' => $billingId,
            'default_address' => 'N',
            'pickup_address' => 0,
            'billing_address' => 1,
            'warehouse_address' => 'N'
        ];
        UserAddressModel::query()->create($userAdd);
        $pickupAddress = [
            'customer_id' => $customer, 'first_name' => $param->contactName,
            'last_name' => '', 'contact_no' => $param->companyPhone,
            'contact_email' => $param->email, 'address_line1' => $param->address_one,
            'address_line2' => $param->address_two, 'postcode' => $param->companyPost,
            'city' => $param->companyCity, 'state' => $param->companyState, 'country' => $country->short_name,
            'iso_code' => $country->alpha3_code, 'latitude' => $this->_warehouse->warehouse->latitude,
            'longitude' => $this->_warehouse->warehouse->longitude,
            'company_name' => $param->companyName, 'search_string' => '',
            'is_default_address' => 'N', 'is_warehouse' => 'N',
            'version_id' => 'version_1',
            'billing_address' => 'N', 'name' => $param->contactName,
            'email' => $param->email, 'phone' => $param->companyPhone,
            'pickup_address' => 'Y', 'country_id' => $country->id
        ];
        $pickupId = (AddressBookModel::query()->create($pickupAddress))->id;
        $userAdd = [
            'user_id' => $customer,
            'address_id' => $pickupId,
            'default_address' => 'Y',
            'pickup_address' => 1,
            'billing_address' => 0,
            'warehouse_address' => 'N'
        ];
        UserAddressModel::query()->create($userAdd);
    }

    /**
     * @param $app Slim
     */
    public static function initRoutes($app)
    {
        $app->post('/getVehicleMasterCategories', function () use ($app) {
            $categories = VehicleCategoryMasterModel::query()->where('status', '=', 1)
                ->get();
            echoResponse(200, ['status' => 'success', 'message' => $categories]);
        });
        $app->post('/createOnBoardDriver', function () use ($app) {
            $r = json_decode($app->request->getBody());
            $company = UsersModel::query()->where('id', '=', $r->company_id)->first();
            (new CustomerSetup())->addDriver($r->driver, $company);
            echoResponse(200, ['status' => 'success', 'message' => 'Driver created successfully']);
        });
        $app->post('/getWarehouseCount', function () use ($app) {
            $r = json_decode($app->request->getBody());
            $company = CompanyWarehouseModel::query()->where('company_id', '=', $r->company_id)->count();
            echoResponse(200, ['status' => 'success', 'message' => $company]);
        });
        $app->get('/checkIt', function () use ($app) {
            $u = \v1\module\Database\Model\UsersModel::query()
                ->with('companyWarehouse', 'companyWarehouse.warehouse')
                ->where('email', 'abc11@gmail.com')
                ->first()->toArray();
            echo '<pre>';
            print_r($u);
            exit;
        });
    }

}