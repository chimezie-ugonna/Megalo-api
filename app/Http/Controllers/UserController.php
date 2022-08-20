<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Custom\SendSms;

class UserController extends Controller
{
    public function sendOtp(Request $request)
    {
        $send = new SendSms();
        $send->send($request->request->get("phone_number"));
    }

    public function create(Request $request)
    {
    }

    public function read(Request $request)
    {
    }

    public function readAll()
    {
    }

    public function update(Request $request)
    {
    }

    public function delete(Request $request)
    {
    }
}
