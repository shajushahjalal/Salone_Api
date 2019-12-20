<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\DB;

class SaloneController extends Controller
{
    /**
     * Get All 
     * Salon List
     */
    public function getAllSelon(Request $request){
        if( !$this->verifyToken($request->token) ){
            return $this->verifyFailed();
        }
        try{
            $data = [];
            $salons = DB::table('salon_setup')
                    ->leftjoin('salon_registration','salon_setup.salon_register_id','=','salon_registration.id')
                    ->leftjoin('closing_day','closing_day.salon_register_id','=','salon_setup.salon_register_id')
                    ->where('salon_setup.salon_status','=',1)
                    ->where('salon_registration.salon_status','=',1)
                    ->select('salon_setup.*','salon_registration.full_name as salon_name','closing_day')->get();
            foreach($salons as $salon){                
                $salon->salon_logo = $salon->salon_logo_path;
                $salon->salon_id = $salon->id;
                $salon->owner_picture = null;
                $salon->branding_picture1 = null;
                $salon->branding_picture2 = null;
                array_push($data, $salon);                            
            } 
            $output = ['status' => 'success','status_type' => true,'status_code'=>200,'message'=> null, 'data' => $data];
            return response()->json( $output);
        }catch(Exception $ex){
            $output = [
                'status' => 'error','status_type' => false,'status_code'=>500,'message'=> 'Something went Wrong. Try again Later', 
                'token' => $this->get_access_token(),'data' => null
            ];
            return response()->json( $output);
        }
    }

    /**
     * For Get Salon Details 
     * Required parms
     */ 
    public function requestSalonDetailsPrams(Request $request){
        if( !$this->verifyToken($request->token) ){
            return $this->verifyFailed();
        }
        $data['salon_id'] = "";
        $output = ['status' => 'success','status_type' => false,'status_code'=>200,'message'=> 'Send the data with this format with token', 'data' => $data];
        return response()->json( $output);
    }

    /** 
     * Get Salone Details
     * Information
     */ 
    public function getSalonDetails(Request $request){

        if( !$this->verifyToken($request->token) ){
            return $this->verifyFailed();
        }
        try{
            $salon_id = $request->data['salon_id'];
            $salonDetails = DB::table('salon_setup as ST')
                ->leftjoin('salon_registration as SR','SR.id','=','ST.salon_register_id')
                ->leftjoin('closing_day','closing_day.salon_register_id','=','SR.id')
                ->where('ST.id','=',$salon_id)
                ->select('ST.salon_register_id','ST.id as salon_setup_id','ST.contact_details','ST.city','ST.state','ST.postal_code','ST.opening_days_hours','ST.opening_time','ST.closing_time',
                'SR.phone_number','SR.email','SR.full_name as salon_name','ST.salon_logo_path','closing_day')
                ->first();
            $treatmentList = DB::select("Select treatment_list.id,treatment_list.salon_id,treatment_list.salon_name,treatment_list.treatment_name,treatment_list.treatment_picture_path,treatment_list.treatment_description,treatment_list.treatment_duration,treatment_list.unsuitable_for,treatment_list.patch_test,treatment_list.treatment_price,treatment_list.brand_treatment,treatment_list.price_offer_courses,salon_setup.salon_logo_path from treatment_list INNER JOIN salon_setup ON salon_setup.salon_register_id =treatment_list.salon_id where salon_id=".$salon_id);
            foreach($treatmentList as $item){
                $item->treatment_picture_path = str_replace('~','',$item->treatment_picture_path);
            }
            $salonDetails->treatment_list = $treatmentList;
            $output = ['status' => 'success','status_type' => true,'status_code'=>200,'message'=> null, 'data' => $salonDetails];
            return response()->json( $output);
        }catch(Exception $ex){
            $output = [
                'status' => 'error','status_type' => false,'status_code'=>500,'message'=> 'Something went Wrong. Try again Later', 'data' => null];
            return response()->json( $output);
        }
    }

    /**
     * Search Salon
     */
    public function searchSalon(Request $request){
        try{
            if( !$this->verifyToken($request->token) ){
                return $this->verifyFailed();
            }
            $data = DB::table('salon_setup as ST')
                ->leftjoin('salon_registration as SR','ST.salon_register_id','=','SR.id')
                ->leftjoin('closing_day','closing_day.salon_register_id','=','ST.salon_register_id')
                ->where('SR.salon_status','=', 1)
                ->where(function($query)use($request){
                    $query->where('SR.full_name','like','%'.$request->search.'%')
                    ->orWhere('ST.city','like','%'.$request->search.'%')
                    ->orWhere('ST.state','like','%'.$request->search.'%')
                    ->orWhere('ST.postal_code','like','%'.$request->search.'%');
                })
                ->select('ST.*','SR.full_name as salon_name','closing_day')->get();
            foreach($data as $salon){                
                $salon->salon_logo = $salon->salon_logo_path;
                $salon->salon_id = $salon->id;
                $salon->owner_picture = null;
                $salon->branding_picture1 = null;
                $salon->branding_picture2 = null;                           
            }
            
            $output = ['status' => 'success','status_type' => true,'status_code'=>200,'message'=> null, 'data' => $data];
            return response()->json( $output);

        }catch(Exception $e){
            $output = ['status' => 'error','status_type' => false,'status_code'=>500,'message'=> 'Something went Wrong. Try again Later', 'data' => null];
            return response()->json( $output);
        }
    }
}
