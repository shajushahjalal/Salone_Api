<?php
namespace App\Http\Controllers\Component;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

trait Token
{
    protected $next_access_token;
    protected $access_token = "SHTUARSVEUNAHLIAOLYAJOL";
    
    protected function generateAccessToken(){
        //$this->access_token = Str::random(30);
        //Session::put('token',$this->access_token);
        $_SESSION['token'] = $this->access_token;
    }


    protected function get_next_access_token(){
        $this->next_access_token = $this->generateAccessToken();
        return $this->next_access_token;
    }

    protected function get_access_token(){
        $this->generateAccessToken();
        return $this->access_token;
    }

    protected function verifyToken($token){
        if( $token == $this->access_token ){
            return true;            
        }else{
            return false;
        }
    }

    protected function verifyFailed(){
        $output = ['status' =>'error','status_type' => false,'status_code'=>401,'message'=>'Security Token is Not Match','data' => null];
        return response()->json($output);
    }

}