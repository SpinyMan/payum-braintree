<?php

namespace Payum\Braintree\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\Model\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Payum;
use Payum\Core\Request\RenderTemplate;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Reply\HttpResponse;
use Payum\Braintree\Request\ObtainPaymentMethodNonce;
use Payum\Braintree\Request\Api\GenerateClientToken;

class ObtainPaymentMethodNonceAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    private   $templateName;

    private   $config;

    protected $cardholderAuthenticationRequired;

    public function __construct(ArrayObject $config)
    {
        $this->config                           = $config;
        $this->templateName                     = $this->config['payum.template.obtain_payment_method_nonce'];
        $this->cardholderAuthenticationRequired = true;
    }

    public function setCardholderAuthenticationRequired($value)
    {
        $this->cardholderAuthenticationRequired = $value;
    }

    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = /*ArrayObject::ensureArrayObject*/
            ($request->getModel());

        if ($details->offsetExists('paymentMethodNonce')) {
            $request->setResponse($details['paymentMethodNonce']);

            return;
        }

        $this->gateway->execute($clientHttpRequest = new GetHttpRequest());

        if (array_key_exists('payment_method_nonce', $clientHttpRequest->request)) {
            $paymentMethodNonce = $clientHttpRequest->request['payment_method_nonce'];
            $request->setResponse($paymentMethodNonce);

            return;
        }

        if (!$details->offsetExists('clientToken')) {
            $this->generateClientToken($details);
        }

        if (!$details->offsetExists('clientToken')) {
            throw new \RuntimeException('Validation error');
        }

        $this->gateway->execute(
            $template = new RenderTemplate(
                $this->templateName, [
                'env'                            => $this->config->offsetExists('sandbox') && $this->config->offsetGet('sandbox') ? 'sandbox' : 'production',
                'formAction'                     => $clientHttpRequest->uri,
                'clientToken'                    => $details['clientToken'],
                'amount'                         => $details['amount'],
                'currency'                       => $details['currency'] ?? 'USD',
                'google_pay_merchant_id'         => $this->config->offsetExists('google_pay_merchant_id') ? $this->config->offsetGet('google_pay_merchant_id') : '',
                'details'                        => $details,
                'obtainCardholderAuthentication' => $this->cardholderAuthenticationRequired,
            ]
            )
        );

        throw new HttpResponse($template->getResult());
    }

    protected function generateClientToken($details)
    {
        $request = new GenerateClientToken();

        $this->gateway->execute($request);

        $details['clientToken'] = $request->getResponse();
    }

    public function supports($request)
    {
        return $request instanceof ObtainPaymentMethodNonce && $request->getModel() instanceof \ArrayAccess;
    }
}
