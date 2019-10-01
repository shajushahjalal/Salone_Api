<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\SalonRegistration;
use App\SalonSetup;
use Exception;
use Illuminate\Support\Facades\DB;

class ApiDataController extends Controller
{
    // Get All Selon Data
    public function getAllSelon(){
        try{
            $data = [];
            $salons = SalonSetup::where('salon_status',1)->get();
            foreach($salons as $salon){                
                $salon->salon_logo = base64_encode($salon->salon_logo);
                $salon->owner_picture = null;
                $salon->branding_picture1 = null;
                $salon->branding_picture2 = null;
                $data[$salon->id] = $salon;
            }
            $output = ['status' => 'success','status_code'=>200,'message'=> null, 'token' => $this->get_access_token(), 'data' => $data];
            return response()->json( $output);
        }catch(Exception $ex){
            $output = [
                'status' => 'error','status_code'=>500,'message'=> 'Something went Wrong. Try again Later', 
                'token' => $this->get_access_token(),'data' => null
            ];
            return response()->json( $output);
        }
    }

    /*
    public function getSalonDetails($salon_id){
        try{
            $salonDetails = DB::table('salon_setup as ST')
                ->leftjoin('salon_registration as SR','SR.id','=','ST.salon_register_id')
                ->leftjoin('treatment_list as TL','TL.salon_id','=','ST.id')
                ->where('ST.id','=',$salon_id)
                ->select('TL.id as treatment_id','TL.treatment_price','TL.treatment_duration','TL.brand_treatment','TL.price_offer_courses',
                'ST.id as salon_setup_id','ST.contact_details','ST.city','ST.state','ST.postal_code','ST.opening_days_hours','ST.opening_time','ST.closing_time',
                'SR.phone_number','SR.email','SR.full_name as salon_name','ST.branding_picture1','TL.treatment_picture')
                ->get();
            foreach($salonDetails as $salon){
                $salon->treatment_picture = base64_encode($salon->treatment_picture);
                $salon->branding_picture1 = base64_encode($salon->branding_picture1);
            }
            $output = ['status' => 'success','status_code'=>200,'message'=> null, 'token' => $this->get_access_token(), 'data' => $salonDetails];
            return response()->json( $output);
        }catch(Exception $ex){
            $output = [
                'status' => 'error','status_code'=>500,'message'=> 'Something went Wrong. Try again Later', 
                'token' => $this->get_access_token(),'data' => null
            ];
            return response()->json( $output);
        }
    }
	*/

    public function getSalonDetails($salon_id){
        try{
            $salonDetails = DB::table('salon_setup as ST')
                ->leftjoin('salon_registration as SR','SR.id','=','ST.salon_register_id')
                ->where('ST.id','=',$salon_id)
                ->select('ST.salon_register_id','ST.id as salon_setup_id','ST.contact_details','ST.city','ST.state','ST.postal_code','ST.opening_days_hours','ST.opening_time','ST.closing_time',
                'SR.phone_number','SR.email','SR.full_name as salon_name','ST.branding_picture1')
                ->first();
            $salonDetails->branding_picture1 = base64_encode($salonDetails->branding_picture1);
            $treatmentList = DB::select("Select treatment_list.id,treatment_list.salon_id,treatment_list.salon_name,treatment_list.treatment_name,treatment_list.treatment_description,treatment_list.treatment_duration,treatment_list.unsuitable_for,treatment_list.patch_test,treatment_list.treatment_price,treatment_list.brand_treatment,treatment_list.price_offer_courses,salon_setup.salon_logo from treatment_list INNER JOIN salon_setup ON salon_setup.salon_register_id =treatment_list.salon_id where salon_id=".$salon_id);
            foreach($treatmentList as $list){
                $list->salon_logo = base64_encode( $list->salon_logo);
            }
            $salonDetails->treatment_list = $treatmentList;
            $output = ['status' => 'success','status_code'=>200,'message'=> null, 'token' => $this->get_access_token(), 'data' => $salonDetails];
            return response()->json( $output);
        }catch(Exception $ex){
            $output = [
                'status' => 'error','status_code'=>500,'message'=> 'Something went Wrong. Try again Later', 
                'token' => $this->get_access_token(),'data' => null
            ];
            return response()->json( $output);
        }
    }

}
