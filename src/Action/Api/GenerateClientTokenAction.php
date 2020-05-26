<?php

namespace Payum\Braintree\Action\Api;

use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Braintree\Request\Api\GenerateClientToken;

class GenerateClientTokenAction extends BaseApiAwareAction
{
    public function execute($request)
    {
        /** @var GenerateClientToken $request */
        RequestNotSupportedException::assertSupports($this, $request);

        $requestParams = [];

        $requestCustomerId        = $request->getCustomerId();
        $requestMerchantAccountId = $request->getMerchantAccountId();

        if ($requestCustomerId !== null) {
            $requestParams['customerId'] = $requestCustomerId;
        }

        if ($requestMerchantAccountId !== null) {
            $requestParams['merchantAccountId'] = $requestMerchantAccountId;
        }

        $request->setResponse($this->api->generateClientToken($requestParams));
    }

    public function supports($request)
    {
        return $request instanceof GenerateClientToken;
    }
}
