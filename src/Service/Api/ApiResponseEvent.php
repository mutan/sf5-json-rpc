<?php
declare(strict_types=1);

namespace App\Service\Api;

use Symfony\Contracts\EventDispatcher\Event;

/*
 * The api.response event is dispatched each time ... TODO
 */

final class ApiResponseEvent extends Event
{
    public const NAME = 'api.response';

    protected $response;

    public function __construct(JsonRpcResponse $response)
    {
        $this->response = $response;
    }

    public function getResponse()
    {
        return $this->response;
    }
}
