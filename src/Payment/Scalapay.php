<?php

namespace Webkul\Scalapay\Payment;

use Illuminate\Support\Facades\Storage;
use Webkul\Payment\Payment\Payment;

class Scalapay extends Payment
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $code  = 'scalapay';

    public function getRedirectUrl(): string
    {
        return route('scalapay.process');
    }

    /**
     * Returns payment method image
     */
    public function getImage(): string
    {
        $url = $this->getConfigData('image');

        return $url ? Storage::url($url) : bagisto_asset('images/money-transfer.png', 'shop');
    }
}