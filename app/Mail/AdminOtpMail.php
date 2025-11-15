<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;
    public string $name;
    public string $role;
    public string $email;

    public function __construct(string $otp, string $name, string $role, string $email)
    {
        $this->otp   = $otp;
        $this->name  = $name;
        $this->role  = $role;
        $this->email = $email;
    }

    public function build()
    {
        return $this
            ->subject('GenRev – New Registration OTP')
            ->view('emails.admin-otp');
    }
}
