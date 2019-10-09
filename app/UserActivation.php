<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserActivation extends Model
{
    //
    protected $table = "useractivation";
    public $timestamps = false;

    public function generateActivationCode($email){
        $data = new UserActivation();
        $data->email = $email;
        $data->ActivationCode = rand(100001,999999);
        $data->save();
        return $data;
    }
}
