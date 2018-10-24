<?php

namespace Payum\Braintree\Request;

use Payum\Core\Request\Generic;

class Purchase extends Generic
{
    public function __construct($identity)
    {
        /** @var \Payum\Core\Payum $payum */
        $payum = app('payum');
        $model = $payum->getStorage($identity->getClass())->find($identity);

        parent::__construct($model);
    }
}
