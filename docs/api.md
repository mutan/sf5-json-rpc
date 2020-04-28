## Реализация JSON-RPC API в проекте

Описание стандарта:
JSON-RPC 2.0 Specification https://www.jsonrpc.org/specification

#### config/services.yaml

1. Отмечаем, какие события будет слушать `ApiListener`.
2. Все php-классы в папке `src/ApiService` становятся сервисами и помечаются тегом `jsonrpc.api-service`. Все сервисы с эти тегом при старте приложения будут обработаны `CompilerPass`'ом.

```
# API

App\EventListener\ApiListener:
    tags:
        - {name: kernel.event_listener, event: api.request, method: onApiRequest}
        - {name: kernel.event_listener, event: api.response, method: onApiResponse}

App\ApiService\:
    resource: '../src/ApiService/*'
    tags: ["jsonrpc.api-service"]
```

#### src/Kernel.php

````
    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ApiServiceCompilerPass());
    }
````

#### src/DependencyInjection/Compiler/ApiServiceCompilerPass

CompilerPass позволяет вмешаться в процесс сбрки контейнера Symfony при старте приложения. В нашем случае инициализируется `ApiService::class` особым образом:

1. Из всех сервисов приложения выбираются сервисы, помеченные тегом `jsonrpc.api-service`.
2. Для каждого такого сервиса происходит следующее:
    * этот сервис добавляется в контейнер-локатор, сервисы из которого в дальнейшем будут доступны из `ApiService` (по-умолчанию сервисы в Symfony приватые и недоступны напрямую из контейнера)
    * при помощи рефлексии берется DocComment каждого метода этого сервиса, и если там есть тег `@ApiMethod`, этот метод будет добавлен в массив `ApiService->methods` (фактичесик это карта всех API-методов приложения).

В итоге получаем проинициализированный класс `ApiService`, в котором зарегистрированы все API-методы приложения.

#### src/Controller/ApiController

Единая входная точка для всех API-запросов: `/api/v1`.
Все запросы обрабатываются одним контроллером и методом (`ApiController->jsonRpc`), и дальше в `ApiService->handleRequest`, где и происходит вся магия:

1. Срабатывает event `api.request` (см. ApiListener ниже).
2. Запрос проверяется на соответствие стандарту JSON-RPC 2.0. Затем определяется нужный API сервис и метод, проверяется, все ли обязательные параметры содержатся в запросе. При несоответствии выбрасывается исключение с правильным кодом и сообщением, и формируется правильный ответ с ошибкой. Такие исключения НЕ попадают в Sentry.
3. Вызывается нужный API сервис и метод в нем.

   `$response->setResult(call_user_func_array([$service, $methodName], $callingParams));`

   Все API сервисы должны находиться в `src/ApiService/`. Из названия сервиса и метода в нем можно получить название запроса. Берется первое слово названия сервиса и через точку добавляется название метода. То есть для запроса `route.get` будет вызван `RouteApiService->get()`.

4. Срабатывает event `api.response` (см. ApiListener ниже).
5. Формируется ответ.

Также в `src/Service/Api/ApiService.php` используются классы-прослойки `src/Service/Api/JsonRpcRequest.php` и `src/Service/Api/JsonRpcResponce.php`. В первом происходит парсинг запроса и дополнительная валидация. Во-втором формируется ответ в соответствие со стандартом. 

#### src/EventListener/ApiListener.php

Обрабатывает event'ы, сработавшие в ApiService'е.

В onApiRequest:
1. Проверяется токен авторизации.
2. К обекту $request добавляется объект $project (найденный и авторизованный по токену).

В onApiResponse:
1. В log_jsonrpc логируются данные по запросу и ответу.

`src/Service/Api/ApiRequestEvent.php` и `src/Service/Api/ApiResponseEvent.php` - соответствующие классы Event'ов.

#### src/Service/Api/Exception

`JsonRpcParseException`, `JsonRpcInvalidRequestException`, `JsonRpcInvalidParamException`, `JsonRpcInvalidMethodException` выдают в ответе код и сообщение об ошибке в соответствие со стандартом. Все они наследуют от `JsonRpcException`, который имплементирует`JsonRpcExceptionInterface`. 