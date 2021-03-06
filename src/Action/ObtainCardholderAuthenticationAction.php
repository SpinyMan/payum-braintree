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
use Payum\Braintree\Request\ObtainCardholderAuthentication;
use Payum\Braintree\Request\FindPaymentMethodNonce;
use Payum\Braintree\Request\Api\GenerateClientToken;

class ObtainCardholderAuthenticationAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    private $templateName;

    public function __construct($templateName)
    {
        $this->templateName = $templateName;
    }

    /**
     * {@inheritDoc}
     *
     * @param ObtainCardholderAuthentication $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var \Payum\Core\Model\ArrayObject $details */
        $details = /*ArrayObject::ensureArrayObject*/($request->getModel());

        if ( ! $details->offsetExists('paymentMethodNonce')
            || ! $details->offsetExists('paymentMethodNonceInfo')) {
            throw new \Exception('Validation error');
        }

        $paymentMethodNonceInfo = $details['paymentMethodNonceInfo'];

        if (array_key_exists('threeDSecureInfo', $paymentMethodNonceInfo) && array_key_exists('status', $paymentMethodNonceInfo['threeDSecureInfo'])) {
            return;
        }

        $this->gateway->execute($clientHttpRequest = new GetHttpRequest());

        if ('POST' == $clientHttpRequest->method && array_key_exists('threeDSecure_payment_method_nonce', $clientHttpRequest->request)) {
            $paymentMethodNonce = $clientHttpRequest->request['threeDSecure_payment_method_nonce'];
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
            'formAction' => $clientHttpRequest->uri,
            'clientToken' => $details['clientToken'],
            'amount' => $details['amount'],
            'creditCard' => $details['paymentMethodNonce'],
            'details' => $details
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
        return
            $request instanceof ObtainCardholderAuthentication &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
