<?php
declare(strict_types=1);

namespace App\Service\Api;

use App\Service\Api\Exception\JsonRpcExceptionInterface;
use App\Service\Api\Exception\JsonRpcInvalidMethodException;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * JSON-RPC 2.0 Specification https://www.jsonrpc.org/specification
 */
class ApiService
{
    const API_CLASS_NAME_SUFFIX = 'ApiService';

    /** Array of all registered api methods */
    private $methods = [];

    private $dispatcher;
    private $locator;

    public function __construct(ContainerInterface $locator, EventDispatcherInterface $dispatcher)
    {
        $this->locator = $locator;
        $this->dispatcher = $dispatcher;
    }

    public function handleRequest(Request $httpRequest, LoggerInterface $logger): JsonResponse
    {
        $request = new JsonRpcRequest;
        $response = new JsonRpcResponse($request);
        try {
            $request->parseRequest($httpRequest);
            $this->dispatcher->dispatch(new ApiRequestEvent($request), ApiRequestEvent::NAME);
            if (!$request->getMethod() || empty($this->methods[$request->getMethod()])) {
                throw new JsonRpcInvalidMethodException('Method not found');
            }
            $service = $this->locator->get($this->methods[$request->getMethod()]['service']);
            $methodName = $this->methods[$request->getMethod()]['method'];
            $requestParams = $request->getParams();
            $requestParamId = 0;
            $callingParams = [];
            $reader = new ReflectionMethod($service, $methodName);
            foreach ($reader->getParameters() as $i => $param) {
                if ($param->getClass()) {
                    if ($param->getClass()->getName() == JsonRpcRequest::class
                        || $param->getClass()->isSubclassOf(JsonRpcRequest::class)) {
                        $callingParams[$i] = $request;
                    } elseif ($param->getClass()->getName() == JsonRpcResponse::class
                        || $param->getClass()->isSubclassOf(JsonRpcResponse::class)) {
                        $callingParams[$i] = $response;
                    } elseif ($request->getObject($param->getClass()->getName())) {
                        $callingParams[$i] = $request->getObject($param->getClass()->getName());
                    } else {
                        throw new JsonRpcInvalidMethodException('Method definition is incorrect');
                    }
                } else {
                    $key = $request->isAssociative() ? $param->getName() : $requestParamId;
                    if (array_key_exists($key, $requestParams)) {
                        $callingParams[$i] = $requestParams[$key];
                        $requestParamId++;
                    } elseif (!$param->isOptional()) {
                        throw new JsonRpcInvalidMethodException('Undefined parameter "' . $key . '"');
                    }
                }
            }
            if ($requestParamId > 0 && isset($requestParams[$requestParamId])) {
                throw new JsonRpcInvalidMethodException('Too many parameters');
            }
            $response->setResult(call_user_func_array([$service, $methodName], $callingParams));
        } catch (JsonRpcExceptionInterface $e) {
            $response->setError($e->getMessage(), $e->getCode());
        } catch (Exception $e) {
            $logger->error($e->getMessage(), ['exception' => $e]);
            $response->setError('Internal error: ' . $e->getMessage(), -32603);
        }
        $this->dispatcher->dispatch(new ApiResponseEvent($response), ApiResponseEvent::NAME);
        return $response->getHttpResponse();
    }

    public function registerMethod($className, $methodName)
    {
        $classNameShort = $this->camelToSnakeCase(
            str_replace(
                self::API_CLASS_NAME_SUFFIX,
                '',
                substr($className, strrpos($className, '\\') + 1)
            )
        );
        $this->methods[$classNameShort . '.' . $methodName] = [
            'service' => $className,
            'method' => $methodName
        ];
    }

    function snakeToCamelCase($string, $firstUpper = false)
    {
        $str = str_replace('_', '', ucwords($string, '_'));
        if (!$firstUpper) {
            $str = lcfirst($str);
        }
        return $str;
    }

    function camelToSnakeCase($string)
    {
        preg_match_all('#([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)#', $string, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }
}