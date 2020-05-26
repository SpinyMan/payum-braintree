<?php

namespace Payum\Braintree\Action\Api;

use Braintree\Customer;
use Braintree\Result\Successful;
use Braintree\Result\UsBankAccountVerification;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Model\ArrayObject;
use Payum\Braintree\Request\Api\DoSale;

class DoSaleAction extends BaseApiAwareAction
{
    public function execute($request)
    {
        /** @var DoSale $request */
        RequestNotSupportedException::assertSupports($this, $request);

        $requestParams = $this->getSaleRequestParams($request);

        $transactionResult = $this->api->sale($requestParams);

        $request->setResponse($transactionResult);
    }

    private function getSaleRequestParams($request)
    {
        /** @var ArrayObject $details */
        $details = /*ArrayObject::ensureArrayObject*/
            ($request->getModel());

        if (!$details->offsetExists('amount')) {
            throw new \Exception('Validation error');
        }

        $requestParams = new ArrayObject();

        if ($details->offsetExists('paymentMethodNonceInfo')) {
            $paymentMethodNonceInfo = $details->offsetGet('paymentMethodNonceInfo');
            if (isset($paymentMethodNonceInfo['type']) && $paymentMethodNonceInfo['type'] === 'UsBankAccount') {
                if (!$details->offsetExists('customerId') || !$details->offsetGet('customerId')) {
                    /** @var Customer $customerResult */
                    $customerResult = $this->api->createCustomer($details['customer']);
                    if (!$customerResult instanceof Customer) {
                        throw new \RuntimeException('Could not create customer');
                    }
                    $details->offsetSet('customerId', $customerResult->id);
                    /** @var Successful $paymentMethodResult */
                    $paymentMethodResult = $this->api->createPaymentMethod(
                        [
                            'customerId'         => $customerResult->id,
                            'paymentMethodNonce' => $details['paymentMethodNonce'],
                            'options'            => [
                                'usBankAccountVerificationMethod' => UsBankAccountVerification::NETWORK_CHECK,
                            ],
                        ]
                    );
                    if (!$paymentMethodResult instanceof Successful) {
                        throw new \RuntimeException('Could not create payment method');
                    }
                }
                $details->offsetUnset('paymentMethodNonce');
            }
        }

        $forwardParams = [
            'amount',
            'paymentMethodNonce',
            'paymentMethodToken',
            'creditCard',
            'billing',
            //'shipping',
            'customer',
            'customerId',
            'orderId',
        ];

        foreach ($forwardParams as $paramName) {
            if ($details->offsetExists($paramName)) {
                $requestParams[$paramName] = $details[$paramName];
            }
        }

        if ($details->offsetExists('saleOptions')) {
            $requestParams['options'] = $details['saleOptions'];
        }

        //fix error: Cannot provide both paymentMethodToken and creditCard attributes
        if ($requestParams->offsetExists('paymentMethodToken') && $requestParams->offsetExists('paymentMethodNonce')) {
            $requestParams->offsetUnset('paymentMethodNonce');
        }

        return $requestParams;
    }

    public function supports($request)
    {
        return $request instanceof DoSale && $request->getModel() instanceof \ArrayAccess;
    }
}
