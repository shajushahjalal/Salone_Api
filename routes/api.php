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

    /**
     * Salone List &
     * Salone Details
     */
    Route::post('get/all_salons','Api\SaloneController@getAllSelon');
    Route::get('get/salons_details','Api\SaloneController@requestSalonDetailsPrams');
    Route::post('get/salons_details','Api\SaloneController@getSalonDetails');

    Route::get('search-salon','Api\SaloneController@searchSalon');
    Route::post('search-salon','Api\SaloneController@searchSalon');

    /**
     * Customer Registration &
     *  Login
     */
    Route::get('customer/login','Api\CustomerController@requestLoginPrams');    
    Route::post('customer/login','Api\CustomerController@login');
    Route::get('customer/register','Api\CustomerController@create');
    Route::post('customer/register','Api\CustomerController@store');
    Route::post('customer/update','Api\CustomerController@update');
    Route::post('customer/password-update','Api\CustomerController@updatePassword');
    Route::post('customer/forgot-password','Api\CustomerController@forgotPassword');

    /**
     * Appointment 
     */
    Route::get('appointment','Api\AppointmentController@getPerameter');
    Route::post('appointment','Api\AppointmentController@appointment');
    Route::post('appointment/select-therapist','Api\AppointmentController@selectTheratist');
    Route::post('appointment/select-date','Api\AppointmentController@selectDate');
    Route::get('appointment/confirm','Api\AppointmentController@confirmAppointmentPrams');
    Route::post('appointment/confirm','Api\AppointmentController@confirmAppointment');
    Route::post('appointment/load','Api\AppointmentController@loadAppoinement');
    Route::post('appointment/history','Api\AppointmentController@appoinementHistory');
    Route::post('appointment/cancel','Api\AppointmentController@cancelAppointment');
