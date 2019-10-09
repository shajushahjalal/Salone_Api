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
    public function customerRegister($request){
        $data = new CustomerRegistration();
        $data->full_name    = $request->full_name;
        $data->middle_name  = $request->middle_name;
        $data->last_name    = $request->last_name;
        $data->email        = $request->email;
        $data->phone        = $request->phone;
        $data->contact_details = $request->contact_details;
        $data->city         = $request->city;
        $data->state        = $request->state;
        $data->postal_code  = $request->postal_code;
        $data->date_of_birth = Carbon::parse($request->date_of_birth)->format('Y-m-d');        
        $data->password     = $request->password;
        $data->create_date  = Carbon::now()->format('Y-m-d H:i:s');
        $data->status       = '0';
        $data->subscription_email = $request->subscription_email;
        $data->subscription_phone = $request->subscription_phone;
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
