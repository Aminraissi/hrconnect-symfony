<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class PaypalService
{
    private string $clientId;
    private string $clientSecret;
    private string $apiUrl;
    private HttpClientInterface $client;

    public function __construct(
        string $clientId,
        string $clientSecret,
        string $apiUrl,
        HttpClientInterface $client
    ) {
        $this->clientId     = $clientId;
        $this->clientSecret = $clientSecret;
        $this->apiUrl       = $apiUrl;
        $this->client       = $client;
    }

    private function getAccessToken(): string
    {
        $response = $this->client->request('POST', $this->apiUrl . '/v1/oauth2/token', [
            'auth_basic' => [$this->clientId, $this->clientSecret],
            'body'       => ['grant_type' => 'client_credentials'],
        ]);

        $data = $response->toArray();

        return $data['access_token'];
    }

    public function createOrder($price, string $successUrl, string $cancelUrl): string
    {
        $accessToken = $this->getAccessToken();

        $response = $this->client->request('POST', $this->apiUrl . '/v2/checkout/orders', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type'  => 'application/json',
            ],
            'json'    => [
                'intent'              => 'CAPTURE',
                'purchase_units'      => [[
                    'amount' => [
                        'currency_code' => 'USD',
                        'value'         => $price,
                    ],
                ]],
                'application_context' => [
                    'return_url' => $successUrl,
                    'cancel_url' => $cancelUrl,
                ],
            ],
        ]);

        $order = $response->toArray();
        foreach ($order['links'] as $link) {
            if ($link['rel'] === 'approve') {
                return $link['href']; // redirect here
            }
        }

        throw new \RuntimeException('No approval URL found.');
    }
}
