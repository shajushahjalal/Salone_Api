<?php

namespace App\Http\Controllers\API;

use App\CustomerRegistration;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\UserActivation;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

class CustomerController extends Controller
{

    /**
     * Customer Register 
     * Required Prams
     */
    public function create(Request $request){
        if( !$this->verifyToken($request->token) ){
            return $this->verifyFailed();
        }
        $data = [
            'email' => '', 'full_name' =>'','middle_name'=>'','last_name' =>'', 'phone' =>'',
            'contact_details' =>'','state' =>'','city' => '','postal_code'=>'','date_of_birth'=>'',
            'password' => '','subscription_email'=> '', 'subscription_phone' => ''
        ];
        $output = ['status' => 'success','status_code'=>200,'status_type' => true,'message'=> 'This fields are required for customer register', 'token' =>' Security Token Here', 'data' => $data];
        return response()->json( $output);
    }


    /**
     * Customer Register
     * Store Customer Info
     */
    public function store(Request $request){
        if( !$this->verifyToken($request->token) ){
            return $this->verifyFailed();
        }

        $validate = Validator::make($request->data,[
            'email' => ['required','unique:customer_registration'],'full_name' => ['required'],
            'middle_name' => ['required'],'last_name' =>['required'],'password' => ['required'],
            'phone'=>['required','unique:customer_registration'], 'contact_details' => ['required'],
            'state'=>['required'],'city' => ['required'],
        ]);
        if( $validate->fails() ){
            $output = ['status' => 'error','status_code'=>401,'message'=> $validate->errors()->first(),'data' => null];
            return response()->json( $output);
        }
        try{
            $customer_obj = new CustomerRegistration();
            $activation_obj = new UserActivation();
            DB::beginTransaction();          
            $customer = $customer_obj->storeCustomerData($customer_obj, $request->data);
            $user_activation = $activation_obj->generateActivationCode($customer->email);
            $this->sendMail($customer, $user_activation->ActivationCode);
            $message = "Your Account was created successfully. Please Verify your Email to active your Account";
            $output = ['status' => 'success','status_code'=>200,'status_type' => true,'message'=> $message, 'data' => null];
            DB::commit();
            return response()->json( $output);
        }catch(Exception $e){
            DB::rollBack();
            $output = ['status' => 'error','status_code'=> 500,'status_type' => false,'message'=> 'Something went wrong.'.$e->getMessage(), 'data' => null];
            return response()->json( $output);
        }
        
    }

    /**
     * Customer Login
     * Request Prams
     */

    public function requestLoginPrams(Request $request){
        if( !$this->verifyToken($request->token) ){
            return $this->verifyFailed();
        }

        $data['email'] = "";
        $data['password'] = "";
        $output = ['status' => 'success','status_type' => true,'status_code'=>200,'message'=> 'Send the data with this format with token', 'token' => 'Security Token', 'data' => $data];
        return response()->json( $output);
    }

     /**
      * Customer Login Attempt
      */
    public function login(Request $request){
        if( !$this->verifyToken($request->token) ){
            return $this->verifyFailed();
        }

        $obj = new CustomerRegistration();
        $customer = $obj->attemptLogin($request->data['email'] , $request->data['password']);
        if(!empty($customer)){
            if($customer->status == 0){
                $output = ['status' => 'pending', 'status_type' => false, 'status_code'=>200,'message'=> 'Please Verify your Email', 'data' => null];
                return response()->json( $output);
            }else{
                $_SESSION['customer'] = $customer;
                $customer->user_id = $customer->id;
                $output = ['status' => 'success','status_type' => true,'status_code'=>200,'message'=> 'Login successfully', 'data' => $customer];
                return response()->json( $output);
            }
        }else{
            $output = ['status' => 'error','status_type' => false,'status_code'=>401,'message'=> 'Invalid Email or Password', 'data' => null];
            return response()->json( $output);
        }
    }

    // Send Activation Email
    protected function sendMail($customer, $activation_code){
        $receiver = [
            'customer_email' => $customer->email,
            'messagebody' => 'Hi '.$customer->full_name.'<br>'.
            'User name: '.$customer->email.'<br>'.'Password: '.$customer->password.'<br><br>'.
            'Please click the following link to activate your account. <br>'.
            '<center> <a href="https://www.salonregister.com/CS_Activation.aspx?ActivationCode='.$activation_code.'&EmailValue='.$customer->email.'">Click here to activate your account</a> </center>'
        ];
        Mail::send('email.accountActivation',$receiver,function($message) use ($receiver){
            $message->to($receiver['customer_email'])
              ->subject('Account Activation');
            $message->from('support@salonregister.com');
        });
    }

    /**
     * Update Customer Info
     */
    public function update(Request $request){
        if( !$this->verifyToken($request->token) ){
            return $this->verifyFailed();
        }

        $validate = Validator::make($request->data,[
            'user_id' => ['required','min:1'], 'full_name' => ['required'], 'middle_name' => ['required'], 'last_name' => ['required'],
            'phone'=>['required'], 'contact_details' => ['required'], 'state'=>['required'], 'city' => ['required']
        ]);

        if( $validate->fails() ){
            $output = ['status' => 'error','status_code'=>401,'status_type' => false, 'message'=> $validate->errors()->first(),'data' => null];
            return response()->json( $output);
        }
        try{
            $request_data = $request->data;
            $request_data['email'] = null;
            $request_data['password'] = null;            
            $customer = CustomerRegistration::find($request_data['user_id']); 

            if(!empty($customer)){
                $data = $customer->storeCustomerData($customer, $request_data);
                $data->user_id = $customer->id;
                $output = ['status' => 'success','status_code'=>200,'status_type' => true, 'message'=> 'update successfully','data' => $data];
                return response()->json( $output);
            }else{
                $output = ['status' => 'error','status_code'=>401,'status_type' => false, 'message'=> 'Customer is not found','data' => null];
                return response()->json( $output);
            }

        }catch(Exception $e){
            $output = ['status' => 'error','status_code'=>500,'status_type' => true, 'message'=> 'Something went wrong'.$e->getMessage(),'data' => null];
            return response()->json( $output);
        }        
    }

    /**
     * Password Update
     */
    public function updatePassword(Request $request){
        if( !$this->verifyToken($request->token) ){
            return $this->verifyFailed();
        }

        $validate = Validator::make($request->data,[
            'user_id' => ['required','min:1'], 'password' => ['required']
        ]);

        if( $validate->fails() ){
            $output = ['status' => 'error','status_code'=>401,'status_type' =>false, 'message'=> $validate->errors()->first(),'data' => null];
            return response()->json( $output);
        }

        try{
            $request_data = $request->data;
            $request_data['email'] = null;

            $customer = CustomerRegistration::find($request_data['user_id']); 
            $data = $customer->storeCustomerData($customer, $request_data);
            $data->user_id = $customer->id;
            $output = ['status' => 'success','status_code'=>200,'status_type' => true, 'message'=> 'update successfully','data' => $data];
            return response()->json( $output);
        }catch(Exception $e){
            $output = ['status' => 'error','status_code'=>500,'status_type' => false,'message'=> 'Something went wrong','data' => null];
            return response()->json( $output);
        }
    }

    /**
     * Forgot Password 
     * Request
     */
    public function forgotPassword(Request $request){
        if( !$this->verifyToken($request->token) ){
            return $this->verifyFailed();
        }

        $validate = Validator::make($request->data,[
            'email' => ['required','email']
        ]);

        if( $validate->fails() ){
            $output = ['status' => 'error','status_code'=>401,'status_type' =>false,'message'=> $validate->errors()->first(),'data' => null];
            return response()->json( $output);
        }
        try{
            $customer = CustomerRegistration::where('email', '=', $request->data['email'])->first();
            if(!empty($customer)){
                $this->forgotPasswordMail($customer);
                $output = ['status' => 'success','status_code'=>200,'status_type' =>true, 'message'=>'please check your email.we send your password on your email','data' => null];
                return response()->json( $output);
            }else{
                $output = ['status' => 'error','status_code'=>401,'status_type' =>false,'message'=> 'your email is not found','data' => null];
                return response()->json( $output);
            }
        }catch(Exception $ex){
            $output = ['status' => 'error','status_code'=>500,'status_type' =>false,'message'=> 'Something went wrong','data' => null];
            return response()->json( $output);
        }

    }

       // Send Activation Email
       protected function forgotPasswordMail($customer){
        $receiver = [
            'customer_email' => $customer->email,
            'messagebody' => 'Hi '.$customer->full_name.'<br>'.
            'User name: '.$customer->email.'<br>'.'Password: '.$customer->password.'<br><br>'.
            'Thank you for connect with us.<br>'
            
        ];
        Mail::send('email.accountActivation',$receiver,function($message) use ($receiver){
            $message->to($receiver['customer_email'])
              ->subject('Password Recovery');
            $message->from('support@salonregister.com');
        });
    }

}
