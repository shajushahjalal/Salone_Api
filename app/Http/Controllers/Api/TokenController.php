<?php

namespace App\Http\Controllers\Api;

use App\Appointment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TokenController extends Controller
{
    protected $App_key = "SHTUARSVEUNAHLIAOLYAJOL";

    public function getToken(Request $request){
        if( $request->app_key == $this->App_key ){
            $output = ['status' => 'success','status_code'=>200,'message'=> null,'token' => $this->get_access_token(),'data' => null];
        }else{
            $output = ['status' => 'success','status_code'=>401,'message'=> 'Invalid App Key','token' => null ];
        }        
        return response()->json( $output);
    }

    // public function getAppointment(Request $request){
    //     $data['data'] = Appointment::all();
    //     $output = ['status' => 'success','status_code'=>200,'message'=> null, 'token' => $this->get_access_token(), 'data' => $data];
    //     return response()->json( $output);
    // }
}
