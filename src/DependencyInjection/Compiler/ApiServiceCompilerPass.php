<?php
declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use App\Service\Api\ApiService;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ApiServiceCompilerPass implements CompilerPassInterface
{
    const SERVICE_TAG = 'jsonrpc.api-service';
    const PHP_DOC_TAG = '@ApiMethod';
    const METHOD      = 'registerMethod';

    /**
     * Register all client
     * methods signed by PHP_DOC_TAG
     * of classes tagged by SERVICE_TAG
     * as available ApiService methods and save them in ApiService $methods field
     *
     * @param ContainerBuilder $container
     *
     * @throws ReflectionException
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(ApiService::class)) {
            return;
        }
        $definition = $container->findDefinition(ApiService::class);
        $taggedServices = $container->findTaggedServiceIds(self::SERVICE_TAG);
        $locateableServices = [];
        foreach ($taggedServices as $id => $tags) {
            $reader = new ReflectionClass($id);
            $locateableServices[$reader->getName()] = new Reference($reader->getName());
            $methods = $reader->getMethods();
            foreach ($methods as $method) {
                if ($method->isPublic() && strpos($method->getDocComment(), self::PHP_DOC_TAG) !== false) {
                    $definition->addMethodCall(self::METHOD, [$reader->getName(), $method->getName()]);
                }
            }
        }

        $definition->addArgument(ServiceLocatorTagPass::register($container, $locateableServices));
        $definition->addArgument(new Reference('event_dispatcher'));
    }
}
