<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\V1\Admin\ApiController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\User;
use Hash;
class ForgetPasswordController extends ApiController
{
    public function forgetPassword(Request $request){
        // dd($request);
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->messages(), 422);
        }
        $email = DB::table("password_reset_tokens")->where("email", $request->email)->first();
        if($email !== null){
            return $this->errorResponse(["email"=>["ایمیل فراموشی رمز عبور قبلا ارسال شده است."]], 422);
        }
        $token = Str::random(64);
        DB::table("password_reset_tokens")->insert([
            'email'=>$request->email,
            'token'=>$token,
            'created_at'=>Carbon::now()
        ]);
        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'ایمیل بازنشانی رمز عبور ارسال شد.',
        ], 200);
    }
    public function resetPassword(Request $request){
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'password'=>'required|confirmed|min:6'
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->messages(), 422);
        }
        $updatedData = DB::table("password_reset_tokens")->where("token", $request->token)->get();
        if(!$updatedData){
            return $this->errorResponse("اطلاعات ارسال شده نادرست است.", 422);
         }
        User::where("email", $updatedData->email)->update([
            'password'=>Hash::make($request->password)
        ]);
        DB::table("password_reset_tokens")->where("token", $request->token)->delete();

    }
}
