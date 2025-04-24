<?php
namespace App\Service;

use Stripe\Checkout\Session;
use Stripe\Stripe;

class StripeService
{
    public function __construct(string $secretKey)
    {
        Stripe::setApiKey($secretKey);
    }

    public function createCheckoutSession(array $products, string $successUrl, string $cancelUrl): Session
    {
        return Session::create([
            'payment_method_types' => ['card'],
            'line_items'           => $products,
            'mode'                 => 'payment',
            'success_url'          => $successUrl,
            'cancel_url'           => $cancelUrl,
        ]);
    }
}
