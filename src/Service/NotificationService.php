<?php
namespace App\Service;

class NotificationService
{

    private string $appId;
    private string $appKey;

    public function __construct(
        string $appId,
        string $appKey,
    ) {
        $this->appId  = $appId;
        $this->appKey = $appKey;
    }

    public function send(String $title, String $description)
    {
        $data = [
            'app_id'            => $this->appId,
            'included_segments' => ['All'],
            'headings'          => ['en' => $title],
            'contents'          => ['en' => $description],
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://onesignal.com/api/v1/notifications');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Basic ' . $this->appKey,
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}
