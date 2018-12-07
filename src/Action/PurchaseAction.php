<?php

namespace Payum\Braintree\Action;

use Payum\Braintree\Request\Api\CreateCustomer;
use Payum\Braintree\Request\Purchase;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Authorize;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\RuntimeException;
use Payum\Braintree\Request\ObtainPaymentMethodNonce;
//use Payum\Braintree\Request\ObtainCardholderAuthentication;
use Payum\Braintree\Request\Api\FindPaymentMethodNonce;
use Payum\Braintree\Request\Api\DoSale;
use Payum\Braintree\Reply\Api\PaymentMethodNonceArray;
use Payum\Braintree\Reply\Api\TransactionResultArray;
use Braintree\Transaction;

class PurchaseAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    protected $cardholderAuthenticationRequired;

    public function __construct()
    {
        $this->cardholderAuthenticationRequired = true;
    }

    public function setCardholderAuthenticationRequired($value)
    {
        $this->cardholderAuthenticationRequired = $value;
    }

    /**
     * {@inheritDoc}
     *
     * @param Authorize $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var \Payum\Core\Model\ArrayObject $details */
        $details = /*ArrayObject::ensureArrayObject*/($request->getModel());

        if ($details->offsetExists('status')) {
            return;
        }

        try {
            $this->obtainPaymentMethodNonce($details);

            if ($details->offsetExists('pre-authorized') && $details->offsetGet('pre-authorized') && ! $details->offsetExists('customer')) {
                $this->createCustomer($details);
            } else {
                //$this->obtainCardholderAuthentication($details);

                $this->doSaleTransaction($details);

                $this->resolveStatus($details);

                if (! $details->offsetExists('paymentMethodNonce') || ! $details->offsetExists('paymentMethodNonceInfo') || ! $details->offsetExists('sale') || ! $details->offsetExists('status')) {
                    throw new \Exception('Validation error');
                }
            }
        }
        catch(RuntimeException $exception) {

            $details['status'] = 'failed';
            $details['status_reason'] = $exception->getMessage();
        }

        //update storage
        /** @var \Payum\Core\Payum $payum */
        $payum = app('payum');
        $payum->getStorage($details)->update($details);
    }

    protected function obtainPaymentMethodNonce($details)
    {
        if ($details->offsetExists('paymentMethodNonce')) {
            return;
        }

        $request = new ObtainPaymentMethodNonce($details);
        $this->gateway->execute($request);

        $paymentMethodNonce = $request->getResponse();

        $details['paymentMethodNonce'] = $paymentMethodNonce;

        $this->findPaymentMethodNonceInfo($details);
    }

    /*protected function obtainCardholderAuthentication($details)
    {
        $paymentMethodNonceInfo = $details['paymentMethodNonceInfo'];

        $isNotRequired = true !== $this->cardholderAuthenticationRequired;
        $isNotCreditCardType = 'CreditCard' !== $paymentMethodNonceInfo['type'];
        $has3DSecureInfo = !empty($paymentMethodNonceInfo['threeDSecureInfo']);

        if ($isNotRequired || $isNotCreditCardType || $has3DSecureInfo) {
            return;
        }

        $this->gateway->execute($request = new ObtainCardholderAuthentication($details));

        $paymentMethodNonce = $request->getResponse();

        $details['paymentMethodNonce'] = $paymentMethodNonce;

        $this->findPaymentMethodNonceInfo($details);
    }*/

    protected function findPaymentMethodNonceInfo($details)
    {
        $this->gateway->execute($request = new FindPaymentMethodNonce($details['paymentMethodNonce']));

        $paymentMethodInfo = $request->getResponse();

        if (null === $paymentMethodInfo) {
            throw new RuntimeException('payment_method_nonce not found');
        }

        $details['paymentMethodNonceInfo'] = PaymentMethodNonceArray::toArray($paymentMethodInfo);
    }

    protected function doSaleTransaction($details)
    {
        if ($details->offsetExists('sale')) {
            return;
        }

        $saleOptions = [
            'submitForSettlement' => true
        ];

        if ($details->offsetExists('paymentMethodNonce') && ! $details->offsetExists('pre-authorized')) {
            $saleOptions['threeDSecure'] = [
                'required' => $this->cardholderAuthenticationRequired
            ];
        }

        $details['saleOptions'] = $saleOptions;

        $this->gateway->execute($request = new DoSale($details));

        $transaction = $request->getResponse();

        $details['sale'] = TransactionResultArray::toArray($transaction);
    }

    protected function createCustomer($details)
    {
        $request = new CreateCustomer($details);
        $this->gateway->execute($request);
        /** @var \Braintree\Customer $customer */
        $customer = $request->getResponse();
        $details['customer'] = $customer->jsonSerialize();
    }

    /**
     * @param \Payum\Core\Model\ArrayObject $details
     */
    protected function resolveStatus($details)
    {
        $sale = $details->offsetGet('sale');

        if (array_get($sale, 'success')) {

            switch($sale['transaction']['status']) {

                case Transaction::AUTHORIZED:
                case Transaction::AUTHORIZING:

                    $details->offsetSet('status', 'authorized');
                    break;

                case Transaction::SUBMITTED_FOR_SETTLEMENT:
                case Transaction::SETTLING:
                case Transaction::SETTLED:
                case Transaction::SETTLEMENT_PENDING:
                case Transaction::SETTLEMENT_CONFIRMED:

                    $details->offsetSet('status', 'captured');
                    break;
            }
        }
        else {
            $details->offsetSet('status', 'failed');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return $request instanceof Purchase && $request->getModel() instanceof \ArrayAccess;
    }
}
