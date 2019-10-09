<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $table = "appointment";
    public $timestamps = false;
    protected $casts = ['create_date' => 'dateTime'];

}
