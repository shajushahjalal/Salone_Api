<?php
namespace App\Http\Controllers\Component;

use Session;
use Illuminate\Support\Str;

trait Token
{
    protected $access_token;
    protected $next_access_token;
    
    protected function generateAccessToken(){
        $this->access_token = Str::random(30);
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

    protected function verifyToken($request){
        if( $request->token != $_SESSION['token'] ){
            $output = ['status' =>'success','status_code'=>401,'message'=>'Security Token is Not Match','data' => null];
            return response()->json($output);
        }
    }
}