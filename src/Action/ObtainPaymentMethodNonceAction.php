<?php
namespace Payum\Braintree\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\RenderTemplate;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Reply\HttpResponse;
use Payum\Braintree\Request\ObtainPaymentMethodNonce;
use Payum\Braintree\Request\Api\GenerateClientToken;

class ObtainPaymentMethodNonceAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    private $templateName;

    private $config;

    protected $cardholderAuthenticationRequired;

    public function __construct(ArrayObject $config)
    {
        $this->config = $config;
        $this->templateName = $this->config['payum.template.obtain_payment_method_nonce'];
        $this->cardholderAuthenticationRequired = true;
    }

    public function setCardholderAuthenticationRequired($value)
    {
        $this->cardholderAuthenticationRequired = $value;
    }

    /**
     * {@inheritDoc}
     *
     * @param ObtainPaymentMethodNonce $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = /*ArrayObject::ensureArrayObject*/($request->getModel());
        
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

        if (false == $details->offsetExists('clientToken')) {
            $this->generateClientToken($details);
        }

        if ( ! $details->offsetExists('clientToken')) {
            throw new \Exception('Validation error');
        }

        //update storage
        /** @var \Payum\Core\Payum $payum */
        $payum = app('payum');
        $payum->getStorage($details)->update($details);

        $this->gateway->execute($template = new RenderTemplate($this->templateName, [
            'env' => $this->config->offsetGet('sandbox') ? 'sandbox' : 'production',
            'formAction' => $clientHttpRequest->uri,
            'clientToken' => $details['clientToken'],
            'amount' => $details['amount'],
            'currency' => $details['currency'] ?? 'USD',
            'google_pay_merchant_id' => env('GOOGLE_PAY_MERCHANT_ID'),
            'details' => $details,
            'obtainCardholderAuthentication' => $this->cardholderAuthenticationRequired
        ]));

        throw new HttpResponse($template->getResult());
    }

    protected function generateClientToken($details)
    {
        $request = new GenerateClientToken();

        $this->gateway->execute($request);

        $details['clientToken'] = $request->getResponse();
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return $request instanceof ObtainPaymentMethodNonce && $request->getModel() instanceof \ArrayAccess;
    }
}
