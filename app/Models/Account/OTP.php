<?php

namespace App\Models\Account;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class OTP extends Model
{
    use HasFactory;

    protected $table = 'otp';

    public function isValidOTPTime() {
        // valid only for 10 minutes
        $timenow = Carbon::now();
        return $timenow->diffInMinutes($this->created_at)  < 10;
    }

    /**
     * Sends request for OTP
     */
    public function sendToMobile() {
        return false;
        // MSG19 server request
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.msg91.com/api/v5/flow/",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "",
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTPHEADER => array(
                "authkey: EnterYourAuthKey",
                "content-type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
    }
}
