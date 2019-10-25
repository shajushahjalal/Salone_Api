<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Image;

class CustomerRegistration extends Model
{
    // Table Name
    protected $table = "customer_registration";
    public $timestamps = false;

    // Casting
    protected $casts = [
        'create_date'   => 'datetime',
        'date_of_birth' => 'date',
    ];

    // Store Customer Info into Database
    public function storeCustomerData($data, $request){
        $data->full_name    = !empty($request['full_name'])? $request['full_name'] : $data->full_name ;
        $data->middle_name  = array_key_exists('middle_name',$request)?$request['middle_name']:$data->middle_name;
        $data->last_name    = !empty($request['last_name'])? $request['last_name'] : $data->last_name ;
        $data->email        = !empty($request['email'])? $request['email'] : $data->email ;
        $data->phone        = !empty($request['phone'])? $request['phone'] : $data->phone ;
        $data->contact_details = !empty($request['contact_details'])? $request['contact_details'] : $data->contact_details ;
        $data->city         = !empty($request['city'])? $request['city'] : $data->city ;
        $data->state        = !empty($request['state'])? $request['state'] : $data->state ;
        $data->postal_code  = !empty($request['postal_code'])? $request['postal_code'] : $data->postal_code ;
        $data->date_of_birth = Carbon::parse(!empty($request['date_of_birth'])? $request['date_of_birth'] : $data->date_of_birth )->format('Y-m-d');        
        $data->password     = !empty($request['password'])? $request['password'] : $data->password  ;
        $data->create_date  = Carbon::now()->format('Y-m-d H:i:s');

        $dir = "../admin.salonregister.com/upload/customer_profile/"; 
        $data->profile_picture_path = $this->UploadImage($request,'profile_picture_path',$dir, $data->profile_picture_path);
        
        $data->status       = empty($data->status)?'0':$data->status;
        
        $data->subscription_email = array_key_exists('subscription_email',$request)?$request['subscription_email']:$data->subscription_email;
        $data->subscription_phone = array_key_exists('subscription_phone',$request)?$request['subscription_phone']:$data->subscription_phone;
        $data->save();
        return $data;        
    }

    // Customer Login
    public function attemptLogin($email , $password){
        $data = CustomerRegistration::where('email','=',$email)->where('password',$password)->first();
        if( !empty($data) ){
            return $data;
        }else{
            return false;
        }
    }

    // Upload Image
    protected function UploadImage($request,$fileName,$dir,$oldFile){
        if( empty($request[$fileName]) ){            
            if(!empty($oldFile)){
                $this->RemoveFile('../admin.salonregister.com/'.$oldFile);
            }
            return '';
        }
        $this->CheckDir($dir);
        if(!empty($oldFile)){
            $this->RemoveFile('../admin.salonregister.com/'.$oldFile);
        }        
        
        $filename = 'profilePic_'.Carbon::now()->format('Ymd').'_'.time().'.png';
        $path = $dir.$filename;
        Image::make($request[$fileName])->save($path);
        
        return 'upload/customer_profile/'.$filename;
    }

    /*
     * ---------------------------------------------
     * Check the Derectory If exists or Not
     * ---------------------------------------------
     */
    protected function CheckDir($dir){
        if(!is_dir($dir)){
                mkdir($dir,0777,true);
        }
    }
    
    /*
     * ---------------------------------------------
     * Check the file If exists then Delete the file
     * ---------------------------------------------
     */
    protected function RemoveFile($filePath) {
        if(file_exists($filePath)){
            unlink($filePath);
        }
    }
}
