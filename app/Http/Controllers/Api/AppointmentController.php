<?php

namespace App\Http\Controllers\Api;

use App\Appointment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\SalonRegistration;
use App\SalonSetup;
use App\TimeSchedule;
use App\TreatmentList;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AppointmentController extends Controller
{
    // Request or Get  Prams
    public function getPerameter(Request $request){
        if( !$this->verifyToken($request->token) ){
            return $this->verifyFailed();
        }

        $data = ['salon_id' => "", 'treatment_id' => ''];
        $output = ['status' => 'success','status_type' => true,'status_code'=>200,'message'=> '','token' => 'Give here the Security Token' ,'data' => $data];
        return response()->json($output);
    }

    /**
     * Get Appointment
     */
    public function appointment(Request $request){
        try{
            if( !$this->verifyToken($request->token) ){
                return $this->verifyFailed();
            }
            // Validate the data
            $validator = Validator::make($request->data,[
                'salon_id' => ['required','numeric','min:1'],
                'treatment_id' => ['required','numeric','min:1']
            ]);

            //check validation
            if( $validator->fails() ){
                $output = ['status' => 'error','status_type' => false,'status_code'=>400,'message'=> $validator->errors()->first() ,'data' => null];
                return response()->json($output);            
            }
        
            $data = TreatmentList::where('salon_id',$request->data['salon_id'])
                ->where('id',$request->data['treatment_id'])->first();
            unset($data->treatment_picture);
            unset($data->therapist_name);

            $data->treatment_picture_path = str_replace('~','',$data->treatment_picture_path);
            $data->therapist_list = SalonRegistration::where('salon_register_id',$request->data['salon_id'])
                ->where('user_type',2)->where('therapist_status',1)->select('id as therapist_id','first_name as therapist_name')->get(); 
            
            $selon_setup = SalonSetup::find($request->data['salon_id']);
            $data->date_list = $this->loadDateSchedule($selon_setup, 0);
            $data->time_slot = $this->loadTimeSlot($selon_setup, 0, Carbon::now()->format('Y-m-d') );

            $output = ['status' => 'success','status_type' => true,'status_code'=>200,'message'=>"" ,'data' => $data];
            return response()->json($output); 

        }catch(Exception $ex){
            $output = ['status' => 'error','status_type' => false,'status_code'=>500,'message'=> 'Something went wrong' ,'data' => null];
            return response()->json($output);  
        } 

    }

    /**
     * After Select Therapist list
     * This Method will be Execute
     */
    public function selectTheratist(Request $request){
        try{
            if( !$this->verifyToken($request->token) ){
                return $this->verifyFailed();
            }

            // Validate the data
            $validator = Validator::make($request->data,[
                'salon_id' => ['required','numeric','min:1'],
            ]);

            //check validation
            if( $validator->fails() ){
                $output = ['status' => 'error','status_type' => false,'status_code'=>400,'message'=> $validator->errors()->first() ,'data' => null];
                return response()->json($output);            
            }
                   
            $selon_setup = SalonSetup::find($request->data['salon_id']);
            $data['date_list'] = $this->loadDateSchedule($selon_setup, $request->data['therapist_id']);
            $data['time_slot'] = $this->loadTimeSlot($selon_setup, $request->data['therapist_id'], Carbon::now()->format('Y-m-d') );
            
            $output = ['status' => 'success','status_type' => true,'status_code'=>200,'message'=>"" ,'data' => $data];
            return response()->json($output);

        }catch(Exception $ex){
            $output = ['status' => 'error','status_type' => false,'status_code'=>500,'message'=> 'Something went wrong' ,'data' => null];
            return response()->json($output);  
        } 
    }

    /**
     * After Select Dropdown
     * Date List This method will be called
     */
    public function selectDate(Request $request){
        try{
        
            if( !$this->verifyToken($request->token) ){
                return $this->verifyFailed();
            }

            // Validate the data
            $validator = Validator::make($request->data,[
                'salon_id' => ['required','numeric','min:1'],
                'date' => ['required','date'],
            ]);

            //check validation
            if( $validator->fails() ){
                $output = ['status' => 'error','status_type' => false,'status_code'=>400,'message'=> $validator->errors()->first() ,'data' => null];
                return response()->json($output);            
            }

            $selon_setup = SalonSetup::find($request->data['salon_id']);
            $data['time_slot'] = $this->loadTimeSlot($selon_setup, $request->data['therapist_id'], $request->data['date']);

            $output = ['status' => 'success','status_type' => true,'status_code'=>200,'message'=>"" ,'data' => $data];
            return response()->json($output);
        }catch(Exception $ex){
            $output = ['status' => 'error','status_type' => false,'status_code'=>500,'message'=> 'Something went wrong' ,'data' => null];
            return response()->json($output);  
        }

    }

    /**
     * Load Available Date List
     */
    protected function loadDateSchedule($selon_setup, $therapist_id){
        $query = TimeSchedule::where('flag','=','A');
            if($therapist_id < 1 || empty($therapist_id) ){
                $query->where('therapist_id','!=',$therapist_id);
            }else{
                $query->where('therapist_id','=',$therapist_id);
            }
            $data = $query->where('slot_date','>=', Carbon::now()->format('Y-m-d'))
            ->where('salon_register_id',$selon_setup->salon_register_id)
            ->where(function(){
                DB::raw('UPPER(TRIM(DAYNAME(T.slot_date))) NOT IN (SELECT UPPER(d.closing_day) FROM closing_day D)');
            })->select('slot_date')->groupBy('slot_date')->orderBy('slot_date')->get();
        return $data;
    }

    /**
     * Load Available Time Slot
     */
    protected function loadTimeSlot($selon_setup, $therapist_id, $date){
        if($therapist_id < 1 || empty($therapist_id) ){
            $data = DB::select("select T.therapist_id,T.flag,T.slot_date, UPPER(TRIM(DAYNAME(T.slot_date))) DAY_NAME , 
                T.slot_time from time_schedule T WHERE T.slot_date='" .$date. "' and 
                T.flag='A' and T.slot_date>='".$date."' and T.salon_register_id='" .$selon_setup->salon_register_id. "' and
                T.break_time='1' and T.therapist_id != '".$therapist_id."'  order by T.slot_time"
            );
            return $data;
        }else{
            $data = DB::select("select T.therapist_id,T.flag,T.slot_date, UPPER(TRIM(DAYNAME(T.slot_date))) DAY_NAME , 
                T.slot_time from time_schedule T WHERE T.slot_date='" .$date. "' and 
                T.flag='A' and T.slot_date>='".$date."' and T.salon_register_id='" .$selon_setup->salon_register_id. "' and
                T.break_time='1' and T.therapist_id = ".$therapist_id."  order by T.slot_time"
            );
            return $data;
        }        
    }

    /**
     * Get Confirm Appointment 
     * Prams
     */
    public function confirmAppointmentPrams(Request $request){
        if( !$this->verifyToken($request->token) ){
            return $this->verifyFailed();
        }
        $data = [
            'salon_id' => '', 'user_id' => '','therapist_name' => '', 'therapist_id' => '',
            'treatment_id' => '', 'date' => '', 'timeslot' => ''
        ];
        $output = ['status' => 'true','status_type' => true,'status_code'=>200,'message'=>'This Data fields are required To confirm Appointment', 'token' => 'Security Token Here', 'data' => $data];
        return response()->json($output); 

    }

    /**
     * Confirm Appointment
     * After Click on Fonfirm Appointment
     */

    public function confirmAppointment(Request $request){
        try{
            DB::beginTransaction();

            if( !$this->verifyToken($request->token) ){
                return $this->verifyFailed();
            }

            if( !$this->isLogin() ){
                return $this->notLogin();
            }

            // Validate the data
            $validator = Validator::make($request->data,[
                'salon_id' => ['required','numeric','min:1'],
                'treatment_id' => ['required','numeric','min:1'],
                'user_id' => ['required','numeric','min:1'],
                'date' => ['required','date'],
                'timeslot' => ['required'],
            ]);

            //check validation
            if( $validator->fails() ){
                $output = ['status' => 'error','status_type' => false,'status_code'=>400,'message'=> $validator->errors()->first() ,'data' => null];
                return response()->json($output);            
            }

            // Save Appointment Data
            $appointment = new Appointment();
            $selon_setup = SalonSetup::findOrFail($request->data['salon_id']);
            $this->makeAppoinrment($appointment, $request->data);
            $this->updateBookingStatus($request->data,$selon_setup);
            DB::commit();
            $this->sendConfirmationMail($request->data['salon_id'], $request->data);
            $output = ['status' => 'success','status_type' => true,'status_code'=>200,'message'=> 'Your Appointment Confirm Successfully' ,'data' => $_SESSION['customer']];
            return response()->json($output); 

        }catch(Exception $ex){
            DB::rollBack();
            $output = ['status' => 'error','status_type' => false,'status_code'=>500,'message'=> 'Something went wrong' ,'data' => null];
            return response()->json($output); 
        }
    }

    // Make Appointment 
    protected function makeAppoinrment($appointment, $data){
        $appointment->client_id = $data['user_id'];
        $appointment->salon_id  = $data['salon_id'];
        $appointment->therapist_name = isset($data['therapist_name'])? $data['therapist_name'] : null;
        $appointment->therapist_id = isset($data['therapist_id'])? $data['therapist_id'] : null;
        $appointment->treatment_id = $data['treatment_id'];
        $appointment->appointment_date = $data['date'];
        $appointment->appointment_time = $data['timeslot'];
        $appointment->status = '1';
        $appointment->create_date = Carbon::now();
        $appointment->save();
        return $appointment;
    }

    // Update Appointment Bookng Status
    protected function updateBookingStatus($data, $salon_setup){
        TimeSchedule::where('therapist_id', $data['therapist_id'] )
            ->where('slot_time', $data['timeslot'] )
            ->where('slot_date', $data['date'] )
            ->where('salon_register_id',$salon_setup->salon_register_id)
            ->update(['flag' => 'B','modified_date' => Carbon::now(), 'modified_by' => $_SESSION['customer']->email ]);
    }

    // Send Appointment Confirmation Mail
    protected function sendConfirmationMail($salon_id, $data){
        $salon_register = SalonRegistration::where('salon_register_id', $salon_id)
            ->select('id','email','phone_number','full_name')->get();
        $treatment = TreatmentList::findorFail($data['treatment_id']);
        
        $customer_message = 'Hi '.$_SESSION['customer']->full_name.'<br>'.
        'Thank you for booking a '.$treatment->treatment_name .' on '.$data['date'].' at '.$data['timeslot'].'.It\'s been confirmed. Thank You!';

        $this->sendMail($_SESSION['customer']->email, $customer_message);
        
        foreach($salon_register as $salon_reg){
            $salon_message = "Hi ".$salon_register->full_name.' Your customer '. $_SESSION['customer']->full_name.' booked an appointment '.$treatment->treatment_name.
            ' on '.$data['date'].' at '.$data['timeslot'].'. It\'s been confirmed. Thank You!';
            $this->sendMail($salon_reg->email,$salon_message);
        }        
    }

    // Send Mail to Customer
    protected function sendMail($email,$message){
        $receiver = [
            'customer_email' => $email,
            'messagebody' => $message
        ];
        Mail::send('email.confirmAppointment',$receiver,function($message) use ($receiver){
            $message->to($receiver['customer_email'])
              ->subject('Appointment Confirmation');
            $message->from('support@salonregister.com');
        });
    }
}
