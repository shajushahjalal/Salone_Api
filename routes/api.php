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

/**
 * Authentication Token
 * After Verify APP Key
*/ 
Route::get('get_token','Api\TokenController@getToken');


    //Route::post('get/appointment','Api\TokenController@getAppointment');

    /**
     * Salone List &
     * Salone Details
     */
    Route::post('get/all_salons','Api\SaloneController@getAllSelon');
    Route::get('get/salons_details','Api\SaloneController@requestSalonDetailsPrams');
    Route::post('get/salons_details','Api\SaloneController@getSalonDetails');

    /**
     * Customer Registration &
     *  Login
     */
    Route::get('customer/login','Api\CustomerController@requestLoginPrams');    
    Route::post('customer/login','Api\CustomerController@login');

    Route::post('customer/register','Api\CustomerController@store');

    /**
     * Appointment 
     */
    Route::get('appointment','Api\AppointmentController@getPerameter');
    Route::post('appointment','Api\AppointmentController@appointment');
    Route::post('appointment/select-therapist','Api\AppointmentController@selectTheratist');
    Route::post('appointment/select-date','Api\AppointmentController@selectDate');
    Route::get('appointment/confirm','Api\AppointmentController@confirmAppointmentPrams');
    Route::post('appointment/confirm','Api\AppointmentController@confirmAppointment');
