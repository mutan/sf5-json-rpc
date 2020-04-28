<?php
declare(strict_types=1);

namespace App\Service\Api\Exception;

class JsonRpcInvalidParamException extends JsonRpcException
{
    const CODE = -32602;

    public function __construct($message = 'Invalid params', $code = self::CODE, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}