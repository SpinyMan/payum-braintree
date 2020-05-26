<?php

namespace Payum\Braintree\Request\Api;

use Braintree\PaymentMethodNonce;

class FindPaymentMethodNonce
{
    private $nonceString;

    private $response;

    public function __construct(string $nonceString)
    {
        $this->nonceString = $nonceString;
    }

    public function getNonceString(): ?string
    {
        return $this->nonceString;
    }

    public function getResponse(): ?PaymentMethodNonce
    {
        return $this->response;
    }

    public function setResponse(PaymentMethodNonce $response)
    {
        $this->response = $response;
    }
}
