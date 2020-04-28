<?php
declare(strict_types=1);

namespace App\Service\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Stopwatch\Stopwatch;

class JsonRpcResponse
{
    const STOPWATCH_NAME = 'api';

    protected $request;
    protected $stopwatch;
    protected $error;
    protected $result;
    protected $httpResponse;

    public function __construct(JsonRpcRequest $request)
    {
        $this->request = $request;
        $this->stopwatch = new Stopwatch();
        $this->stopwatch->start(self::STOPWATCH_NAME);
    }

    public function getRequest(): JsonRpcRequest
    {
        return $this->request;
    }

    public function getStopwatch(): Stopwatch
    {
        return $this->stopwatch;
    }

    public function getDuration()
    {
        return $this->getStopwatch()->getEvent(self::STOPWATCH_NAME)->getDuration();
    }

    public function setResult($result)
    {
        $this->result = $result;
    }

    public function setError($message, $code = 0)
    {
        $this->error = [
            'code' => $code,
            'message' => $message
        ];
    }

    public function getError()
    {
        return !empty($this->error) ? $this->error : null;
    }

    public function getResult()
    {
        return !empty($this->error) ? null : $this->result;
    }

    protected function getResponseArray(): array
    {
        $result = [
            'jsonrpc' => '2.0',
            'id' => $this->getRequest() ? $this->getRequest()->getId() : null
        ];
        if (!empty($this->error)) {
            $result['error'] = $this->error;
        } else {
            $result['result'] = $this->result;
        }
        return $result;
    }

    public function generateHttpResponse()
    {
        $this->stopwatch->stop(self::STOPWATCH_NAME);
        $response = new JsonResponse($this->getResponseArray());
        $response->setEncodingOptions($response->getEncodingOptions() | JSON_UNESCAPED_UNICODE);
        $response->headers->add(['X-Api-Time' => $this->getDuration()]);
        $this->httpResponse = $response;
    }

    public function getHttpResponse(): JsonResponse
    {
        if (empty($this->httpResponse)) {
            $this->generateHttpResponse();
        }
        return $this->httpResponse;
    }
}