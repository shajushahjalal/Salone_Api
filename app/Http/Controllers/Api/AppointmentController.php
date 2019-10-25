<?php

namespace App\Http\Controllers\Api;

use App\Appointment;
use App\CustomerRegistration;
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
use Mail;

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
        $date = Carbon::parse($date)->format('Y-m-d');
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
            $customer = CustomerRegistration::findOrFail($request->data['user_id']);
            $this->makeAppoinrment($appointment, $request->data);
            $this->updateBookingStatus($request->data,$selon_setup,$customer);
            DB::commit();
            $message = "Your Appointment Confirm Successfully.";
            try{
                $this->sendConfirmationMail($request->data['salon_id'], $request->data, $customer,'Appointment Confirmation');
            }catch(Exception $e){
                $message .= ' But Mail is not sent due to server problem';
            }
            $output = ['status' => 'success','status_type' => true,'status_code'=>200,'message'=> $message ,'data' => null];
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
        if(isset($data['therapist_name'])){
            $appointment->therapist_name = $data['therapist_name'];
        }else{
            $datas = SalonRegistration::find($data['therapist_id']);
            $appointment->therapist_name = $datas->first_name; 
        }
        
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
    protected function updateBookingStatus($data, $salon_setup, $customer){        
        TimeSchedule::where('therapist_id', $data['therapist_id'] )
            ->where('slot_time', $data['timeslot'] )
            ->where('slot_date', $data['date'] )
            ->where('salon_register_id',$salon_setup->salon_register_id)
            ->update(['flag' => 'B','modified_date' => Carbon::now(), 'modified_by' => $customer->id ]);
    }

    // Send Appointment Confirmation Mail
    protected function sendConfirmationMail($salon_id, $data,$customer, $sublect){
        $salon_register = SalonRegistration::where('salon_register_id','=',$salon_id)
            ->select('id','email','phone_number','full_name')->get();
        $treatment = TreatmentList::findorFail($data['treatment_id']);
        
        $customer_message = 'Hi '.$customer->full_name.'<br>'.
        'Thank you for booking a '.$treatment->treatment_name .' on '.$data['date'].' at '.$data['timeslot'].'.It\'s been confirmed. Thank You!';

        $this->sendMail($customer->email, $customer_message, $sublect);
        
        foreach($salon_register as $salon_reg){
            $salon_message = "Hi ".$salon_reg->full_name.' Your customer '. $customer->full_name.' booked an appointment '.$treatment->treatment_name.
            ' on '.$data['date'].' at '.$data['timeslot'].'. It\'s been confirmed. Thank You!';
            $this->sendMail($salon_reg->email,$salon_message, $sublect);
        }        
    }

    // Send Mail to Customer
    protected function sendMail($email,$message,$sublect){
        $receiver = [
            'customer_email' => $email,
            'messagebody' => $message
        ];
        Mail::send('email.confirmAppointment',$receiver,function($message) use ($receiver,$sublect){
            $message->to($receiver['customer_email'])
              ->subject($sublect);
            $message->from('support@salonregister.com');
        });
    }


    /**
     * Load Appointments
     */
    public function loadAppoinement(Request $request){
        try{
            if( !$this->verifyToken($request->token) ){
                return $this->verifyFailed();
            }
            // Validate the data
            $validator = Validator::make($request->data,[
                'user_id' => ['required','numeric','min:1'],
            ]);
            
            //check validation
            if( $validator->fails() ){
                $output = ['status' => 'error','status_type' => false,'status_code'=>400,'message'=> $validator->errors()->first() ,'data' => null];
                return response()->json($output);            
            }
            $data = $this->getAllAppointment( $request->data['user_id'],isset($request->data['date'])?$request->data['date']:null, isset($request->data['to_date'])?$request->data['to_date']:null, isset($request->data['from_date'])?$request->data['from_date']:null );
    
            $output = ['status' => 'success','status_type' => true,'status_code'=>200,'message'=> '' ,'data' => $data];
            return response()->json($output); 

        }catch(Exception $e){
            $output = ['status' => 'error','status_type' => false,'status_code'=>500,'message'=> 'Something went wrong' ,'data' => null];
            return response()->json($output); 
        }
        
    }

    /**
     * Load Appointment History
     * Load All Appointments
     */
    public function appoinementHistory(Request $request){
        try{
            if( !$this->verifyToken($request->token) ){
                return $this->verifyFailed();
            }
            // Validate the data
            $validator = Validator::make($request->data,[
                'user_id' => ['required','numeric','min:1'],
            ]);
            
            //check validation
            if( $validator->fails() ){
                $output = ['status' => 'error','status_type' => false,'status_code'=>400,'message'=> $validator->errors()->first() ,'data' => null];
                return response()->json($output);            
            }

            
            $data = $this->getAllAppointment( $request->data['user_id']);
            $output = ['status' => 'success','status_type' => true,'status_code'=>200,'message'=> '' ,'data' => $data];
            return response()->json($output); 

        }catch(Exception $ex){
            $output = ['status' => 'error','status_type' => false,'status_code'=>500,'message'=> 'Something went wrong' ,'data' => null];
            return response()->json($output); 
        }
    }

    // Get All Appointment Data
    protected function getAllAppointment($customer_id,$date = null, $to_date = null, $form_date = null){
        //dd( $to_date,$form_date);
        if(!empty($date)){
            $date = Carbon::parse($date)->format('Y-m-d');
        }
        if( !empty($to_date) ){
            $to_date = Carbon::parse($to_date)->format('Y-m-d');
        }
        if( !empty($form_date) ){
            $form_date = Carbon::parse($form_date)->format('Y-m-d');
        }

        $query = DB::table('appointment as APT')
            ->leftjoin('customer_registration as CR','CR.id','=','APT.client_id')
            ->leftjoin('treatment_list as TL','TL.id','=','APT.treatment_id')
            ->leftjoin('salon_setup as ST','ST.id','=','APT.salon_id')
            ->leftjoin('salon_registration as SR','SR.id','=','ST.salon_register_id')
            ->where('CR.id','=', $customer_id)->where('APT.status',1);
        if( !empty($to_date) && !empty($form_date) ){
            if($to_date < $form_date){
                $query->whereBetween('APT.appointment_date',[$to_date, $form_date]);
            }else{
                $query->whereBetween('APT.appointment_date',[$form_date,$to_date]);
            }            
        }elseif(!empty($date)){
            $query->where('APT.appointment_date', '>=', $date);
        }
        else{
            //$query->where('APT.appointment_date', '<', Carbon::now()->format('Y-m-d'));
        }

        $data = $query->select('APT.id as appointment_id','APT.therapist_name','APT.status','APT.time_schedule_id',
            'TL.treatment_name','TL.id as treatment_id','APT.appointment_date','appointment_time','APT.client_id as customer_id',
            'CR.full_name','CR.email','CR.phone','CR.contact_details','CR.city','CR.postal_code','APT.salon_id',
            'APT.status','APT.create_date','TL.treatment_picture_path','SR.full_name as salon_name','SR.email as salon_email','SR.phone_number as salon_phone_number',
            'ST.contact_details as salon_address','ST.city as salon_city','ST.state as salon_state','ST.postal_code as salon_postal_code')
            ->orderBy('appointment_date','DESC')->get();
        foreach($data as $item){
            $item->treatment_picture_path = str_replace('~','',$item->treatment_picture_path);
        }
        return $data;
    }

    /**
     * Cancel Appointment
     */
    public function cancelAppointment(Request $request){ 
        try{
            if( !$this->verifyToken($request->token) ){
                return $this->verifyFailed();
            }
            // Validate the data
            $validator = Validator::make($request->data,[
                'user_id' => ['required','numeric','min:1'],
                'appointment_id' =>  ['required','numeric','min:1'],
            ]);
            
            //check validation
            if( $validator->fails() ){
                $output = ['status' => 'error','status_type' => false,'status_code'=>400,'message'=> $validator->errors()->first() ,'data' => null];
                return response()->json($output);            
            }
            DB::beginTransaction();

            $appointment = Appointment::find($request->data['appointment_id']);
            $appointment->status = '0';
            $appointment->save();
            TimeSchedule::where('therapist_id', '=' ,$appointment->therapist_id)
                ->where('slot_date', '=', $appointment->appointment_date)
                ->where('slot_time','=', $appointment->appointment_time)
                ->update(['flag' => 'A','modified_date' => Carbon::now(), 'modified_by' => $request->data['user_id'] ]);
            DB::commit();
            $msssage = "Appointment Cancel Successfully.";

            try{
                $customer = CustomerRegistration::find($request->data['user_id']);
                $this->sendCancelMail($appointment, $customer, 'Cancel Appointment');
            }catch(Exception $e){
                $msssage .= 'But mail is not sent';
            }
            $output = ['status' => 'success','status_type' => true,'status_code'=>200,'message'=> $msssage ,'data' => null];
            return response()->json($output);
        }catch(Exception $e){
            $output = ['status' => 'error','status_type' => false,'status_code'=>500,'message'=> 'Something went wrong' ,'data' => null];
            return response()->json($output);
        }
    }


    /**
     * Change Appointment
     */
    public function changeAppointment(Request $request){
        try{
            if( !$this->verifyToken($request->token) ){
                return $this->verifyFailed();
            }

            // Validate the data
            $validator = Validator::make($request->data,[
                'salon_id' => ['required','numeric','min:1'],
                'treatment_id' => ['required','numeric','min:1'],
                'user_id' => ['required','numeric','min:1'],
                'appointment_id' =>  ['required','numeric','min:1'],
                'date' => ['required','date'],
                'timeslot' => ['required'],
            ]);
            
            //check validation
            if( $validator->fails() ){
                $output = ['status' => 'error','status_type' => false,'status_code'=>400,'message'=> $validator->errors()->first() ,'data' => null];
                return response()->json($output);            
            }

            DB::beginTransaction();

            $appointment = Appointment::find($request->data['appointment_id']);
            TimeSchedule::where('therapist_id', '=' ,$appointment->therapist_id)
                ->where('slot_date', '=', $appointment->appointment_date)
                ->where('slot_time','=', $appointment->appointment_time)
                ->update(['flag' => 'A','modified_date' => Carbon::now(), 'modified_by' => $request->data['user_id'] ]);
            
            $appointment->treatment_id = $request->data['treatment_id'];
            $appointment->appointment_date = $request->data['date'];
            $appointment->appointment_time = $request->data['timeslot'];
            $appointment->modified_date = Carbon::now();
            $appointment->modified_by = $request->data['user_id'];
            $appointment->therapist_name = isset($request->data['therapist_name'])? $request->data['therapist_name'] : null;
            $appointment->therapist_id = isset($request->data['therapist_id'])? $request->data['therapist_id'] : null;
            $appointment->save();
            $appointment->appointment_id = $appointment->id;
            $selon_setup = SalonSetup::findOrFail($request->data['salon_id']);
            $customer = CustomerRegistration::findOrFail($request->data['user_id']);
            $this->updateBookingStatus($request->data,$selon_setup,$customer);
            DB::commit();

            $message = 'Appointment Change Successfully.';
            try{
                $this->sendChangeAppointmentlMail($appointment, $customer, 'Change Appointment');
            }catch(Exception $e){
                $message .='But mail is not sent';
            }

            $output = ['status' => 'success','status_type' => true,'status_code'=>200,'message'=> $message ,'data' => $appointment ];
            return response()->json($output);

        }catch(Exception $ex){
            DB::rollBack();
            $output = ['status' => 'error','status_type' => false,'status_code'=>500,'message'=> 'Something went wrong' ,'data' => null];
            return response()->json($output);
        }
    }


    // send Appointment Cancel Mail
    protected function sendCancelMail($appointment, $customer, $sublect){
        $salon_register = SalonRegistration::where('salon_register_id','=', $appointment->salon_id)
            ->select('id','email','phone_number','full_name')->get();
        
        $customer_message = 'Hi '.$customer->full_name.'<br>'.' Your Appointment Date: '.$appointment->appointment_date.
        ', at :'.$appointment->appointment_time. ' been cancel Successfully. Thank You!';

        $this->sendMail($customer->email, $customer_message, $sublect);
        
        foreach($salon_register as $salon_reg){
            $salon_message = "Hi ".$salon_reg->full_name.' Your customer '. $customer->full_name.' Canceled his Appoinement on : '.
            $appointment->appointment_date.' at '.$appointment->appointment_time;
            $this->sendMail($salon_reg->email,$salon_message, $sublect);
        }        
    }

    // send Appointment Cancel Mail
    protected function sendChangeAppointmentlMail($appointment, $customer, $sublect){
        $salon_register = SalonRegistration::where('salon_register_id','=', $appointment->salon_id)
            ->select('id','email','phone_number','full_name')->get();
        $treatment = TreatmentList::findorFail($appointment->treatment_id);
        
        $customer_message = 'Hi '.$customer->full_name.'<br>'.'Your booking has been changed'.
        'Thank you for booking a '.$treatment->treatment_name .' on '.$appointment->appointment_date.' at '.$appointment->appointment_time.'.It\'s been confirmed. Thank You!';

        $this->sendMail($customer->email, $customer_message, $sublect);
        
        foreach($salon_register as $salon_reg){
            $salon_message = "Hi ".$salon_reg->full_name.' Your customer '. $customer->full_name.' changed his appointment. New booked appoinment on '.$treatment->treatment_name.
            ' on '.$appointment->appointment_date.' at '.$appointment->appointment_time.'. It\'s been confirmed. Thank You!';
            $this->sendMail($salon_reg->email,$salon_message, $sublect);
        }        
    }
}
