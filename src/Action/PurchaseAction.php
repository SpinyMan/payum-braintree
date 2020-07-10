<?php

namespace Payum\Braintree\Action;

use Payum\Braintree\Request\Purchase;
use Payum\Core\Action\ActionInterface;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Model\ArrayObject;
use Payum\Core\Payum;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\RuntimeException;
use Payum\Braintree\Request\ObtainPaymentMethodNonce;

//use Payum\Braintree\Request\ObtainCardholderAuthentication;
use Payum\Braintree\Request\Api\FindPaymentMethodNonce;
use Payum\Braintree\Request\Api\DoSale;
use Payum\Braintree\Reply\Api\PaymentMethodNonceArray;
use Payum\Braintree\Reply\Api\TransactionResultArray;
use Braintree\Transaction;
use Payum\Core\Request\GetHumanStatus;

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

    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var ArrayObject $details */
        $details = /*ArrayObject::ensureArrayObject*/
            ($request->getModel());

        if ($details->offsetExists('status')) {
            return;
        }

        try {
            $this->obtainPaymentMethodNonce($details);

            //$this->obtainCardholderAuthentication($details);
            $this->doSaleTransaction($details);
            $this->resolveStatus($details);

            $nonceExists = ($details->offsetExists('paymentMethodNonce') && $details->offsetExists('paymentMethodNonceInfo')) || $details->offsetExists('customerId');
            if (!$nonceExists
                || !$details->offsetExists('sale')
                || !$details->offsetExists('status')) {
                throw new \RuntimeException('Validation error');
            }
        } catch (RuntimeException $exception) {
            $details->offsetSet('status', GetHumanStatus::STATUS_FAILED);
            $details->offsetSet('status_reason', $exception->getMessage());
        }

        //update storage
        /** @var Payum $payum */
        $payum = app('payum');
        $payum->getStorage($details)->update($details);
    }

    protected function obtainPaymentMethodNonce(ArrayObject $details)
    {
        if ($details->offsetExists('paymentMethodNonce') || $details->offsetExists('customerId')) {
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

    protected function findPaymentMethodNonceInfo(ArrayObject $details)
    {
        $this->gateway->execute($request = new FindPaymentMethodNonce($details['paymentMethodNonce']));

        $paymentMethodInfo = $request->getResponse();

        if ($paymentMethodInfo === null) {
            throw new RuntimeException('payment_method_nonce not found');
        }

        $details['paymentMethodNonceInfo'] = PaymentMethodNonceArray::toArray($paymentMethodInfo);
    }

    protected function doSaleTransaction(ArrayObject $details)
    {
        if ($details->offsetExists('sale')) {
            return;
        }

        $saleOptions = [];

        if (!$this->isPreAuthorized($details)) {
            $saleOptions['submitForSettlement'] = true;
        }

        if ($details->offsetExists('paymentMethodNonce') /*&& !$this->isPreAuthorized($details)*/) {
            $saleOptions['threeDSecure'] = [
                'required' => $this->cardholderAuthenticationRequired,
            ];
        }

        if ($saleOptions) {
            $details->offsetSet('saleOptions', $saleOptions);
        }

        /** @see https://developers.braintreepayments.com/reference/request/transaction/sale/php */
        $this->gateway->execute($request = new DoSale($details));
        $transaction = $request->getResponse();

        $details['sale'] = TransactionResultArray::toArray($transaction);
    }

    /*protected function createCustomer($details)
    {
        $request = new CreateCustomer($details);
        $this->gateway->execute($request);
        $customer = $request->getResponse();
        $details['customer'] = $customer->jsonSerialize();
    }*/

    protected function resolveStatus(ArrayObject $details): void
    {
        $sale = $details->offsetGet('sale');

        if (isset($sale['success']) && $sale['success']) {
            switch ($sale['transaction']['status']) {
                case Transaction::AUTHORIZED:
                case Transaction::AUTHORIZING:
                    $details->offsetSet('status', GetHumanStatus::STATUS_AUTHORIZED);
                    break;

                case Transaction::SUBMITTED_FOR_SETTLEMENT:
                case Transaction::SETTLING:
                case Transaction::SETTLED:
                case Transaction::SETTLEMENT_PENDING:
                case Transaction::SETTLEMENT_CONFIRMED:
                    $details->offsetSet('status', GetHumanStatus::STATUS_CAPTURED);
                    break;

                case Transaction::FAILED:
                case Transaction::GATEWAY_REJECTED:
                case Transaction::PROCESSOR_DECLINED:
                case Transaction::SETTLEMENT_DECLINED:
                    $details->offsetSet('status', GetHumanStatus::STATUS_FAILED);
                    break;

                case Transaction::VOIDED:
                    $details->offsetSet('status', GetHumanStatus::STATUS_CANCELED);
                    break;

                case Transaction::AUTHORIZATION_EXPIRED:
                    $details->offsetSet('status', GetHumanStatus::STATUS_EXPIRED);
                    break;
            }
        } else {
            $details->offsetSet('status', GetHumanStatus::STATUS_FAILED);
        }
    }

    protected function isPreAuthorized(ArrayObject $details): bool
    {
        return $details->offsetExists('pre-authorized') && $details->offsetGet('pre-authorized');
    }

    public function supports($request)
    {
        return $request instanceof Purchase && $request->getModel() instanceof \ArrayAccess;
    }
}
