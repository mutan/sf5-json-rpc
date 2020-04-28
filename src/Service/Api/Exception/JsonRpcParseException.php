<?php
declare(strict_types=1);

namespace App\Service\Api\Exception;

class JsonRpcParseException extends JsonRpcException
{
    const CODE = -32700;

    public function __construct($message = 'Parse error', $code = self::CODE, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}