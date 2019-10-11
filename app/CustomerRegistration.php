<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class CustomerRegistration extends Model
{
    // Table Name
    protected $table = "customer_registration";
    public $timestamps = false;

    // Casting
    protected $casts = [
        'create_date'   => 'datetime',
        'date_of_birth' => 'date',
    ];

    // Store Customer Info into Database
    public function storeCustomerData($data, $request){
        
        $data->full_name    = !empty($request['full_name'])? $request['full_name'] : $data->full_name ;
        $data->middle_name  = !empty($request['middle_name'])? $request['middle_name'] : $data->middle_name ;
        $data->last_name    = !empty($request['last_name'])? $request['last_name'] : $data->last_name ;
        $data->email        = !empty($request['email'])? $request['email'] : $data->email ;
        $data->phone        = !empty($request['phone'])? $request['phone'] : $data->phone ;
        $data->contact_details = !empty($request['contact_details'])? $request['contact_details'] : $data->contact_details ;
        $data->city         = !empty($request['city'])?  : $data->city ;
        $data->state        = !empty($request['state'])? $request['state'] : $data->state ;
        $data->postal_code  = !empty($request['postal_code'])? $request['postal_code'] : $data->postal_code ;
        $data->date_of_birth = Carbon::parse(!empty($request['date_of_birth'])? $request['date_of_birth'] : $data->date_of_birth )->format('Y-m-d');        
        $data->password     = !empty($request['password'])? $request['password'] : $data->password  ;
        $data->create_date  = Carbon::now()->format('Y-m-d H:i:s');
        $data->status       = '0';
        $data->subscription_email = !empty($request['subscription_email'])? $request['subscription_email'] : $data->subscription_email ;
        $data->subscription_phone = !empty($request['subscription_phone'])? $request['subscription_phone'] : $data->subscription_phone ;
        $data->save();
        return $data;        
    }

    // Customer Login
    public function attemptLogin($email , $password){
        $data = CustomerRegistration::where('email','=',$email)->where('password',$password)->first();
        if( !empty($data) ){
            return $data;
        }else{
            return false;
        }
    }
}
