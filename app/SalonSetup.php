<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalonSetup extends Model
{
    //
    protected $table = "salon_setup";
    public $timestamps = false;

    // public function salonRegister(){
    //     return $this->belongsTo(SalonRegistration::class,'id','salon_register_id');
    // }
}
