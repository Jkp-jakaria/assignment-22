<?php

namespace App\Http\Controllers;

use App\Helper\JWTToken;
use App\Mail\OTPMail;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class UserController extends Controller
{
    //Page Controllers
    public function LoginPage(): View
    {
        return view('pages.auth.login-page');
    }

    public function RegistrationPage(): View
    {
        return view('pages.auth.registration-page');
    }

    public function SendOTPPage(): View
    {
        return view('pages.auth.send-otp-form');
    }

    public function VerifyOTPPage(): View
    {
        return view('pages.auth.verify-otp-form');
    }

    public function ResetPassPage(): View
    {
        return view('pages.auth.reset-pass-form');
    }

    public function ProfilePage(): View
    {
        return view('pages.dashboard.profile-page');
    }

    //API Controllers
    public function UserRegistration(Request $request)
    {
        try {
            User::create([
                'firstName' => $request->input('firstName'),
                'lastName' => $request->input('lastName'),
                'email' => $request->input('email'),
                'mobile' => $request->input('mobile'),
                'password' => $request->input('password'),
            ]);

            return response()->json([
                'status' => 'Success',
                'message' => 'User Registration Successfull',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'Failed',
                'message' => 'User Registration Failed',
            ], 200);
        }
    }

    public function UserLogin(Request $request)
    {
        $count = User::where('email', '=', $request->input('email'))->where('password', '=', $request->input('password'))->select('id')->first();

        if ($count !== null) {
            // User Login-> JWT Token Issue
            $token = JWTToken::CreateToken($request->input('email'), $count->id);

            return response()->json([
                'status' => 'Success',
                'message' => 'User Login Successfull',
            ], 200)->cookie('token', $token, 60 * 24 * 30);
        } else {
            return response()->json([
                'status' => 'Failed',
                'message' => 'Unauthorized',
            ], 200);
        }
    }

    public function SendOTPCode(Request $request)
    {
        $email = $request->input('email');
        $otp = rand(1000, 9999);
        $count = User::where('email', '=', $email)->count();

        if ($count == 1) {
            //OTP Email Address
            Mail::to($email)->send(new OTPMail($otp));

            //OTP Code Update
            User::where('email', '=', $email)->Update(['otp' => $otp]);

            return response()->json([
                'status' => 'Success',
                'message' => '4 Digit OTP Code has been send to your email !',
            ], 200);
        } else {
            return response()->json([
                'status' => 'Failed',
                'message' => 'Unauthorized',
            ], 200);
        }

    }

    public function VerifyOTP(Request $request)
    {
        $email = $request->input('email');
        $otp = $request->input('otp');
        $count = User::where('email', '=', $email)->where('otp', '=', $otp)->count();

        if ($count == 1) {
            //Database OTP Update
            User::where('email', '=', $email)->Update(['otp' => '0']);

            //Pass Reset Token Issue
            $token = JWTToken::CreateTokenForSetPassword($request->input('email'));

            return response()->json([
                'status' => 'Success',
                'message' => 'OTP Verification Successfull',
            ], 200)->cookie('token', $token, 60 * 24 * 30);
        } else {
            return response()->json([
                'status' => 'Failed',
                'message' => 'Unauthorized',
            ], 200);
        }
    }

    public function ResetPass(Request $request)
    {
        try {
            $email = $request->header('email');
            $password = $request->input('password');
            User::where('email', '=', $email)->Update(['password' => $password]);

            return response()->json([
                'status' => 'Success',
                'message' => 'Password Reset Successfull',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'Failed',
                'message' => 'Something Went Wrong',
            ], 200);
        }
    }

    public function UserLogout()
    {
        return redirect('/userLogin')->cookie('token', '', -1);
    }

    public function UserProfile(Request $request)
    {
        $email = $request->header('email');
        $user = User::where('email', '=', $email)->first();
        return response()->json([
            'status' => 'success',
            'message' => 'Request successful',
            'data' => $user,
        ], 200);
    }

    public function UpdateProfile(Request $request)
    {
        try {
            $email = $request->header('email');
            $firstName = $request->input('firstName');
            $lastName = $request->input('lastName');
            $mobile = $request->input('mobile');
            $password = $request->input('password');
            User::where('email', '=', $email)->Update([
                'firstName' => $firstName,
                'lastName' => $lastName,
                'mobile' => $mobile,
                'password' => $password,
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Request successful',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'Fail',
                'message' => 'Something went wrong',
            ], 200);
        }
    }
}
