<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::get('get_token','Api\TokenController@getToken');

Route::middleware(['TokenVerification'])->group(function(){
    Route::post('get/appointment','Api\TokenController@getAppointment');
    Route::post('get/all_salons','Api\ApiDataController@getAllSelon');
    Route::post('get/{salon_id}/salons_details','Api\ApiDataController@getSalonDetails');
});
