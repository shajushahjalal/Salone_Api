<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Session;

class TokenVerification
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if( !isset($request->token) || $request->token != $_SESSION['token'] ){
            $output = ['status' =>'success','status_code'=>401,'message'=>'Security Token is Not Match','data' => null];
            return response()->json($output);
        }else{
            return $next($request);
        }
        // if( !isset($request->token) || $request->token != Session::get('token') ){
        //     $output = ['status' =>'success','status_code'=>401,'message'=>'Security Token is Not Match','data' => null];
        //     return response()->json($output);
        // }else{
        //     return $next($request);
        // }
             
    }
}
