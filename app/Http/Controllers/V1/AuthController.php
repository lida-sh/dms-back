<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\V1\Admin\ApiController;
use App\User;

use Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class AuthController extends ApiController
{
    
    public function login(Request $request)
    {   
        // dd($request->all());
        $validator = Validator::make($request->all(), [
            'username' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->messages(), 422);
        }
        if (Auth::attempt(['email' => $request->username, 'password' => $request->password])) {
            $user = Auth::user();
            // $client = new Client([
                // 'verify' => false, // غیرفعال کردن بررسی گواهی SSL
                // 'headers' => [
                //     'Content-Type' => 'application/json',  // اضافه کردن هدر
                // ]
            // ]);
            $response = Http::post('http://dms-back.test/oauth/token', [
                    'grant_type' => 'password',
                    'client_id' => $request->client_id,
                    'client_secret' => $request->client_secret,
                    'username' => $request->username,
                    'password' => $request->password,
                    'scope' => ''
            ]);
            
            // $user['token'] = $response->getBody();
            $body = $response->getBody();
            $data = json_decode($body, true); // تبدیل به آرایه
            $data['identity'] = $user;
            // $this->successResponse($data, 200);
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'User has been logged successfully.',
                'data' => $data
            ], 200);
        } else {
            return $this->errorResponse("نام کاربری یا رمز عبور اشتباه است.", 401);
            // return response()->json([
            //     'success' => true,
            //     'statusCode' => 401,
            //     'message' => 'نام کاربری یا رمز عبور اشتباه است.',
            //     'errors' => 'نام کاربری یا رمز عبور اشتباه است.',
            // ], 401);
        }
        
    }
    public function refreshToken(Request $request): JsonResponse
    {
        
        $response = Http::asForm()->post('http://dms-back.test/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $request->refresh_token,
            'client_id' => env('PASSPORT_PASSWORD_CLIENT_ID'),
            'client_secret' => env('PASSPORT_PASSWORD_SECRET'),
            'scope' => '',
        ]);
        $body = $response->getBody();
        
        $data = json_decode($body, true);

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Refreshed token.',
            'data' => $data,
        ], 200);
    }
    public function logout(): JsonResponse
    {
        
    
        Auth::user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Logged out successfully.',
        ], 200);
    }
    public function me(): JsonResponse
    {
        // dd("me");
        $user = auth()->user();
        // $this->successResponse($user, 200);
        return response()->json($user, 200);
    }
}
