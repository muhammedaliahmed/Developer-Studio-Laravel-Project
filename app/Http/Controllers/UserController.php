<?php

namespace App\Http\Controllers;
use App\Models\account;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use App\Http\Middleware\LogRoute;
use App\Models\log;
use Exception;

class UserController extends Controller
{
    //Register User API
    function Register(Request $request){
        try{

        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:accounts',
            'password' => 'required|min:6'
        ]);

        $data=new account();
        $data->name=$request->name;
        $data->email=$request->email;
        $data->password=Hash::make($request->password);
        $data->acc_num=rand(10000,99999);
        $data->token=null;
        $data->save();
        if(!$data->save())
        {
            return "Registeration Failed Try Again";
        }
       
        return "Registered Succesfully";
        }
        catch(Exception$e){
            return$e->getMessage();
        }

    }
//Login User ID
    
    function Login(Request $request){

        try{
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $log=new log();
        $log->request=$request;



        $user=account::where('email','=',$request->email)->first();
        if($user){
            if(Hash::check($request->password,$user->password))
            {
              
               
               #Generating 32 bit token
               $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
               $charactersLength = strlen($characters);
               $randomString = '';
               for ($i = 0; $i < 32; $i++) {
                   $randomString .= $characters[rand(0, $charactersLength - 1)];
               }
                $user->token=$randomString;
                $user->save();
                $request->session()->put('LoggedUser',$user->id);
                $request->session()->put('token',$user->token);
                $log->uid=$user->id;
                $log->response="acc logged";
                $log->save();
                return"success";
               
            }
            else{
                return"pass fail";
            }


        }}
        catch(Exception$e){
            return$e->getMessage();
        }
        return"Failed";

    }

    function LogOut(){
        try{
        $log=new log();
        $log->request="Logging Out";
        $log->uid=session('LoggedUser');
        if(session()->has('LoggedUser')){
            session()->pull('LoggedUser');
            
            $log->response="acc logged out";
            $log->save();
            return"logged out";
        }}
        catch(Exception$e){
            return$e->getMessage();
        }
    }


    function SendMoney(Request $request){
        try{

        $log=new log();
        $log->request=$request;
        

        $request->validate([
            
            'acc_num_rec' => 'required',
            'amount' => 'required'
        ]);

        $sender=account::where('id','=',session('LoggedUser'))->first();
        $log->uid=$sender->id;
        if($sender->token==session('token')){
        
        $rec=account::where('acc_num','=',$request->acc_num_rec)->first();  
        if($rec==Null){
            $log->response="acc does not exists";
            $log->save();
            return"acc does not exists";
        }

        if($sender->amount<$request->amount){
            $log->response="Not enough amount in account";
            $log->save();
            return "Not enough amount in account";
        }


        $sender->amount=$sender->amount-$request->amount;
        $rec->amount=$rec->amount+$request->amount;

        $sender->save();
        $rec->save();
        if(!$sender->save()&&$rec->save())
        {
            return " Failed Try Again";
        }

        $log->response="Sent Succesfully";
        $log->save();
        return "Sent Succesfully";


        }
        $log->response="Session Expired";
        $log->save();
        return "Session Expired";
    }
    catch(Exception$e){
        return$e->getMessage();
    }



    }

    function CashOut(Request $request){

         $log=new log();
         $log->request=$request;

        $request->validate([
            'amount' => 'required'
        ]);



        try{
         $sender=account::where('id','=',session('LoggedUser'))->first();
         if($sender->token==session('token')){

            if($sender->amount<$request->amount){
                $log->response="Not enough amount in account";
                $log->save();
                return "Not enough amount in account";
            }
#Calculting number of notes
        $notes=array(5000,1000,500);
        $noteCounter=array(0,0,0);


        if($request->amount%500==0||$request->amount%1000==0||$request->amount%5000==0){
        
             
            while($request->amount!=0){

                if($request->amount>=5000){
                    $noteCounter[0]++;
                    $request->amount-=5000;
                }
                if($request->amount>=1000){
                    $noteCounter[1]++;
                    $request->amount-=1000;
                }
                    if($request->amount>=500){
                    $noteCounter[2  ]++;
                    $request->amount-=500;
                }
            }

            $sender->amount=$sender->amount-$request->amount;
            $sender->save();
            
            $log->response="Cash Withdrawn";
            $log->save();
            
            return array($notes,$noteCounter);

            

        }

           

        $log->response="Invalid amount";
        $log->save();
            return "Invalid Amount";
    }
}
catch(Exception$e){
return$e->getMessage();
}
    $log->response="Invalid Session";
    $log->save();
    return response("Invalid session");


    }


    





}
