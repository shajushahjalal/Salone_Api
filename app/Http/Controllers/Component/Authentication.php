<?php
namespace App\Http\Controllers\Component;

trait Authentication{

    /**
     * check customer 
     * login
     */
    protected function isLogin(){
        if( !empty($_SESSION['customer']) ){
            return true;
        }else{
            return false;
        }
    }

    /**
     * Redirect if not Login
     */
    protected function notLogin(){
        $output = ['status' =>'error','status_type' => false,'status_code'=>401,'message'=>'Customer login required','data' => null];
        return response()->json($output);
    }

    /**
     * Customer Logout
     */
    protected function logout(){
        $_SESSION['customer'] = null;
        return true;
    }
}