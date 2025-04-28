<?php
namespace App\Service;

use Twilio\Rest\Client;

class TwilioServiceAlaa
{
    private $twilio;
    private $from;

    public function __construct(string $sid, string $token, string $from)
    {
        $this->twilio = new Client($sid, $token);
        $this->from   = $from;
    }

    public function sendSms(string $to, string $message): string
    {
        $message = $this->twilio->messages->create(
            $to,
            [
                'from' => $this->from,
                'body' => $message,
            ]
        );

        return $message->sid;
    }
}
