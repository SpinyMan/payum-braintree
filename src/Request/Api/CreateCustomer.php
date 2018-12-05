<?php
namespace Payum\Braintree\Request\Api;

use Payum\Core\Request\Generic;

class CreateCustomer extends Generic
{
    protected $response;

    public function getResponse()
    {
        return $this->response;
    }
    
    public function setResponse($value)
    {
        $this->response = $value;
    }
}
