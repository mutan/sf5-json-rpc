<?php
declare(strict_types=1);

namespace App\Service\Api;

use Symfony\Contracts\EventDispatcher\Event;

/*
 * The api.request event is dispatched each time ... TODO
 */
final class ApiRequestEvent extends Event
{
    public const NAME = 'api.request';

    protected $request;

    public function __construct(JsonRpcRequest $request)
    {
        $this->request = $request;
    }

    public function getRequest()
    {
        return $this->request;
    }
}