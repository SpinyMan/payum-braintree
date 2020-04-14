<?php
namespace Payum\Braintree;

use Payum\Braintree\Action\Api\CreateCustomerAction;
use Payum\Braintree\Action\Api\DoRefundAction;
use Payum\Braintree\Action\Api\DoSyncAction;
use Payum\Braintree\Action\CaptureAction;
use Payum\Braintree\Action\ConvertPaymentAction;
use Payum\Braintree\Action\ObtainPaymentMethodNonceAction;
use Payum\Braintree\Action\ObtainCardholderAuthenticationAction;
use Payum\Braintree\Action\PurchaseAction;
use Payum\Braintree\Action\StatusAction;
use Payum\Braintree\Action\Api\GenerateClientTokenAction;
use Payum\Braintree\Action\Api\FindPaymentMethodNonceAction;
use Payum\Braintree\Action\Api\DoSaleAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

class BraintreeGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $ui = $config['ui'];
        $config->defaults([

            'payum.factory_name' => 'braintree',
            'payum.factory_title' => 'braintree',

            'payum.template.obtain_payment_method_nonce' => "@PayumBraintree/Action/obtain_payment_method_nonce_{$ui}.html.twig",
            'payum.template.obtain_cardholder_authentication' => '@PayumBraintree/Action/obtain_cardholder_authentication.html.twig',

            'payum.action.capture' => new CaptureAction(),

            'payum.action.purchase' => function(ArrayObject $config) {
                $action = new PurchaseAction();
                $action->setCardholderAuthenticationRequired($config['cardholderAuthenticationRequired']);

                return $action;
            },

            'payum.action.convert_payment' => new ConvertPaymentAction(),

            'payum.action.obtain_payment_method_nonce' => function(ArrayObject $config) {
                $action = new ObtainPaymentMethodNonceAction($config);
                $action->setCardholderAuthenticationRequired($config['cardholderAuthenticationRequired']);

                return $action;
            },

            /*'payum.action.obtain_cardholder_authentication' => function(ArrayObject $config) {
                return new ObtainCardholderAuthenticationAction($config['payum.template.obtain_cardholder_authentication']);
            },*/

            'payum.action.status' => new StatusAction(),

            'payum.action.api.generate_client_token' => new GenerateClientTokenAction(),
            'payum.action.api.find_payment_method_nonce' => new FindPaymentMethodNonceAction(),
            'payum.action.api.do_sale' => new DoSaleAction(),
            'payum.action.api.do_refund' => new DoRefundAction(),
            'payum.action.api.do_sync' => new DoSyncAction(),
            'payum.action.api.create_customer' => new CreateCustomerAction(),

            'cardholderAuthenticationRequired' => true
        ]);

        if (! $config['payum.default_options']) {
            $config['payum.default_options'] = [
                'sandbox' => true,
                'storeInVault' => null,
                'storeInVaultOnSuccess' => null,
                'storeShippingAddressInVault' => null,
                'addBillingAddressToPaymentMethod' => null
            ];
        }
        $config->defaults($config['payum.default_options']);

        if (false == $config['payum.api']) {
            $config['payum.required_options'] = [];

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Api((array) $config, $config['payum.http_client'], $config['httplug.message_factory']);
            };
        }

        $payumPaths = $config['payum.paths'];
        $payumPaths['PayumBraintree'] = __DIR__ . '/Resources/views';
        $config['payum.paths'] = $payumPaths;
    }
}
