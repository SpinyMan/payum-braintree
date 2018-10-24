# Payum_Braintree

A Payum extension for Braintree gateway integration. Based on 
https://github.com/nfq-eta/payum-braintree

## Configuration

Register a gateway factory to the payum's builder and create a gateway:

```php
<?php

use Payum\Core\PayumBuilder;

$defaultConfig = [];

$payum = (new PayumBuilder)
    ->addGatewayFactory('braintree', new Payum\Braintree\BraintreeGatewayFactory($defaultConfig))

    ->addGateway('braintree', [
        'factory' => 'braintree',
        'sandbox' => true,
        'merchantId' => '123123',
        'publicKey' => '999999',
        'privateKey' => '777888',
    ])

    ->getPayum()
;
```

Using the gateway:

prepare.php
```php
<?php

/** @var \Payum\Core\Payum $payum */
$payum = app('payum');

$storage = $payum->getStorage(\Payum\Core\Model\ArrayObject::class);
$payment = $storage->create();
$payment['order_id'] = 123;
$payment['amount'] = 12.00;
$storage->update($payment);

$captureToken = $payum->getTokenFactory()->createCaptureToken('braintree', $payment, 'done.php');

return redirect($captureToken->getTargetUrl());
```

done.php
```php
/** @var Payum $payum */
$payum = app('payum');
/** @var \Payum\Core\Model\Token $token */
$token = $payum->getHttpRequestVerifier()->verify($request);
$gateway = $payum->getGateway($token->getGatewayName());

/** @var \Payum\Core\Storage\IdentityInterface $identity **/
$identity = $token->getDetails();
$model = $payum->getStorage($identity->getClass())->find($identity);
$gateway->execute($status = new GetHumanStatus($model));

/** @var \Payum\Core\Request\GetHumanStatus $status */

// using shortcut
if ($status->isNew() || $status->isCaptured() || $status->isAuthorized()) {
	// success
} elseif ($status->isPending()) {
	// most likely success, but you have to wait for a push notification.
} elseif ($status->isFailed() || $status->isCanceled()) {
	// the payment has failed or user canceled it.
}