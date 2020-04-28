<?php
declare(strict_types=1);

namespace App\Service\Api\Exception;

class JsonRpcInvalidRequestException extends JsonRpcException
{
    const CODE = -32600;

    public function __construct($message = 'Invalid Request', $code = self::CODE, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}