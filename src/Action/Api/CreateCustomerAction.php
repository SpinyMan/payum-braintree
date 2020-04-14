<?php
namespace Payum\Braintree\Action\Api;

use Payum\Braintree\Request\Api\CreateCustomer;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Braintree\Request\Api\DoSale;

/**
 * Class CreateCustomerAction
 *
 * @package Payum\Braintree\Action\Api
 *
 * @property \Payum\Braintree\Api $api
 */
class CreateCustomerAction extends BaseApiAwareAction
{
    /**
     * {@inheritDoc}
     *
     * @throws \Payum\Core\Exception\LogicException if the token not set in the instruction.
     */
    public function execute($request)
    {
        /** @var $request DoSale */
        RequestNotSupportedException::assertSupports($this, $request);

        $requestParams = $this->getRequestParams($request);

        if (array_key_exists('customerId', $requestParams)) {
            $customerId = $requestParams['customerId'];
            unset($requestParams['customerId']);
            if ($customerId) {
                try {
                    $transactionResult = $this->api->updateCustomer($customerId, $requestParams + [
                            'options' => [
                                'updateExisting' => true
                            ]
                        ]);
                    $request->setResponse($transactionResult);
                    return;
                } catch (\Exception $e) {
                }
            }
        }

        $transactionResult = $this->api->createCustomer($requestParams);

        $request->setResponse($transactionResult);
    }

    private function getRequestParams($request)
    {
        $details = /*ArrayObject::ensureArrayObject*/($request->getModel());

        if (! $details->offsetExists('paymentMethodNonce')) {
            throw new \Exception('Validation error');
        }

        $params = [];
        foreach (['paymentMethodNonce', 'customerId', 'firstName', 'lastName', 'email', 'phone', 'creditCard'] as $key)  {
            if (! $details->offsetExists($key)) continue;

            $params[$key] = $details->offsetGet($key);
        }

        return $params;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return $request instanceof CreateCustomer && $request->getModel() instanceof \ArrayAccess;
    }
}
