<?php
declare(strict_types=1);

namespace App\Service\Api\Exception;

class JsonRpcInvalidMethodException extends JsonRpcException
{
    const CODE = -32601;

    public function __construct($message = 'Method not found', $code = self::CODE, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}