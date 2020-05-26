<?php

namespace Payum\Braintree\Action\Api;

use Payum\Braintree\Request\Api\GenerateClientToken;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Braintree\Request\Api\FindPaymentMethodNonce;
use Braintree\Exception\NotFound;

class FindPaymentMethodNonceAction extends BaseApiAwareAction
{
    public function execute($request)
    {
        /** @var GenerateClientToken $request */
        RequestNotSupportedException::assertSupports($this, $request);

        $paymentMethodNonce = $this->api->findPaymentMethodNonce($request->getNonceString());
        $request->setResponse($paymentMethodNonce);
    }

    public function supports($request)
    {
        return $request instanceof FindPaymentMethodNonce;
    }
}
