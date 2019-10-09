<?php

namespace App\Http\Controllers\API;

use App\CustomerRegistration;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\UserActivation;
use Exception;
use Fideloper\Proxy\TrustedProxyServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

class CustomerController extends Controller
{
    /**
     * Store Customer Info
     */
    public function store(Request $request){
        if( !$this->verifyToken($request->token) ){
            return $this->verifyFailed();
        }

        $validate = Validator::make($request->data,[
            'email' => ['required','unique:customer_registration'],'full_name' => ['required'],
            'last_name'=>['required'],'password' => ['required'],'phone'=>['unique:required'],
            'contact_details' => ['required'],'state'=>['required'],'city' => ['required']
        ]);
        if( $validate->fails() ){
            $output = ['status' => 'error','status_code'=>401,'message'=> $validate->errors()->first(),'data' => null];
            return response()->json( $output);
        }
        try{
            $customer_obj = new CustomerRegistration();
            $activation_obj = new UserActivation();
            DB::beginTransaction();            
            $customer = $customer_obj->customerRegister($request);
            $user_activation = $activation_obj->generateActivationCode($customer->email);
            $this->sendMail($customer, $user_activation->ActivationCode);
            $message = "Your Account was created successfully. Please Verify your Email to active your Account";
            $output = ['status' => 'success','status_code'=>200,'message'=> $message, 'token' => $this->get_access_token(), 'data' => null];
            DB::commit();
            return response()->json( $output);
        }catch(Exception $ex){
            DB::rollBack();
            $output = ['status' => 'error','status_code'=>401,'message'=> 'Something went wrong', 'data' => null];
            return response()->json( $output);
        }
        
    }

    /**
     * Customer Login
     */

     // Request Login Prams
    public function requestLoginPrams(Request $request){
        if( !$this->verifyToken($request->token) ){
            return $this->verifyFailed();
        }

        $data['email'] = "";
        $data['password'] = "";
        $output = ['status' => 'success','status_type' => true,'status_code'=>200,'message'=> 'Send the data with this format with token', 'token' => 'Security Token', 'data' => $data];
        return response()->json( $output);
    }

     // Attempt to Login 
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
                $output = ['status' => 'success','status_type' => true,'status_code'=>200,'message'=> 'Login successfully', 'data' => $customer];
                return response()->json( $output);
            }
        }else{
            $output = ['status' => 'error','status_type' => false,'status_code'=>401,'message'=> 'Invalid Email or Password', 'data' => null];
            return response()->json( $output);
        }
    }

    /**
     * Send Activation Email
     */
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

}
