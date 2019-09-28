<?php

namespace App\Http\Middleware;

use Closure;

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
        if( $request->token != $_SESSION['token'] ){
            $output = ['status' =>'success','status_code'=>401,'message'=>'Security Token is Not Match','data' => null];
            return response()->json($output);
        }else{
            return $next($request);
        }        
    }
}
