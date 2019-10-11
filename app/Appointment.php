<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $table = "appointment";
    public $timestamps = false;
    protected $casts = ['create_date' => 'dateTime'];

    // public function treatment(){
    //     return $this->belongsTo(TreatmentList::class,'treatment_id','id');
    // }

    // public function customer(){
    //     return $this->belongsTo(CustomerRegistration::class,'client_id','id');
    // }

}
