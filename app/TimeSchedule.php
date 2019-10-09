<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TimeSchedule extends Model
{
    protected $table = "time_schedule";
    public $timestamps = false;

    protected $casts = ['modified_date' => 'dateTime'];
}
