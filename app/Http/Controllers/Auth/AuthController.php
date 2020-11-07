<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\Account\OTP;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller {
    public function sendOTP(Request $request) {
        // collect  mobile number
        $mobile_no = $request->mobile_no;

        // validate mobile number
        if(!preg_match('/^[0-9]{10}+$/', $mobile_no)) {
            // raise Error
            return response()->json([
              'message' => 'Invalid Mobile Number!'
            ], 403);
        }

        // check if account exists
        $user = User::firstWhere('mobile_no', $mobile_no);
        if($user) {
            $account_exists = true;
        } else {
            $account_exists = false;
        }

        // generate OTP
        $otp_number = random_int(1000, 9999);

        // store OTP in database
        $otp = new OTP;
        $otp->code = $otp_number;
        $otp->mobile_no = $mobile_no;
        $otp->save();

        // sent OTP to the mobile number
        $otp->sendToMobile();

        return response()->json([
            'message' => 'OK',
            'data' => [
              'account' => $account_exists
            ]
        ]);
    }

    //
    public function login(Request $request) {
        $mobile_no = $request->mobile_no;
        $code = $request->otp;

        // validate Mobile Number
        if(!preg_match('/^[0-9]{10}+$/', $mobile_no)) {
            // raise Error
            return response()->json([
              'message' => 'Invalid Mobile Number!'
            ], 401);
        }

        // validate OTP
        if(!preg_match('/^[0-9]{4}+$/', $code)) {
            // raise Error
            return response()->json([
              'message' => 'Invalid OTP!'
            ], 401);
        }

        // fetch OTP from table
        $otp = OTP::where('mobile_no', $mobile_no)
            ->where('code', $code)
            ->where('active', 1)
            ->first();

        // check if OTP is valid
        if(!$otp || !$otp->isValidOTPTime()) {
            // send Error
            return response()->json([
                'message' => 'Invalid OTP!'
            ], 401);
        }

        // update valid OTP
        $otp->active = 0;
        $otp->save();

        // check if account exists
        $user = User::firstWhere('mobile_no', $mobile_no);
        if(!$user) {
            // if there is not account
            // create account
            $user = new User;
            $user->mobile_no = $mobile_no;
            $user->password = bcrypt(strval(random_int(100000, 999999)));
            $user->save();
        }

        // assign user to the session
        Auth::login($user);

        // generate access token
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;

        // mark token active for next five weeks
        $token->expires_at = Carbon::now()->addWeeks(5);
        $token->save();

        // send token
        return response()->json([
            'user' => $user,
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString()
        ]);
    }
  
    public function logout(Request $request) {
        $request->user()->token()->revoke();
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function updateUser(Request $request) {
        // get user from request/session
        $user = $request->user();

        // update name
        $user->name = $request->name;

        # check is photo is sent in request
        if ($request->hasFile('photo')) {

            # check if photo is valud
            if ($request->file('photo')->isValid()) {

                # save user photo on the server
                $url = $request->photo->store('public/avatars');

                # generate photo url
                $user->photo = Storage::url($url);
            }
        }

        // save user in database
        $user->save();

        // send updated user
        return response()->json($request->user());
    }

    public function refreshToken(Request $request) {
        // get user from request/session
        $user = $request->user();

        // create a new access token
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;

        // mark validity for five weeks
        $token->expires_at = Carbon::now()->addWeeks(5);
        $token->save();

        // send token
        return response()->json([
            'user' => $user,
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString()
        ]);
    }
}