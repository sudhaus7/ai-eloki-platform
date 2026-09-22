<?php
declare(strict_types=1);

namespace Symfony\AI\Platform\Bridge\Eloki;

use Symfony\AI\Platform\Contract;
use Symfony\AI\Platform\ModelRouter\CatalogBasedModelRouter;
use Symfony\AI\Platform\ModelRouterInterface;
use Symfony\AI\Platform\Platform;
use Symfony\AI\Platform\Provider;
use Symfony\AI\Platform\ProviderInterface;
use Symfony\Component\HttpClient\EventSourceHttpClient;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Factory {

    /**
     * @param non-empty-string $name
     */
    public static function createProvider(
        #[\SensitiveParameter] ?string $apiKey = null,
        ?HttpClientInterface $httpClient = null,
        ?Contract $contract = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        string $name = 'elkoki',
    ): ProviderInterface {

        $httpClient = self::createHttpClient($httpClient,$apiKey);

        return new Provider(
            $name,
            [new ElokiClient($httpClient)],
            [new ElokiResultConverter()],
            new ModelCatalog($httpClient),
            $contract ?? ElokiContract::create(),
            $eventDispatcher,
        );
    }

    public static function createHttpClient(?HttpClientInterface $httpClient=null, ?string $apiKey = null): HttpClientInterface
    {
        if ($apiKey === null && isset($_ENV['ELOKI_API_KEY'])) {
            $apiKey = $_ENV['ELOKI_API_KEY'];
        }
        $httpClient = $httpClient instanceof EventSourceHttpClient ? $httpClient : new EventSourceHttpClient($httpClient);

        $defaultOptions = [];
        if (null !== $apiKey) {
            $defaultOptions['auth_bearer'] = $apiKey;
        }
        $httpClient = ScopingHttpClient::forBaseUri($httpClient, 'https://chat.elkoki.net/', $defaultOptions);
        return $httpClient;
    }

    /**
     * @param non-empty-string $name
     */
    public static function createPlatform(
        #[\SensitiveParameter] ?string $apiKey = null,
        ?HttpClientInterface $httpClient = null,
        ?Contract $contract = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        string $name = 'elkoki',
        ?ModelRouterInterface $modelRouter = null,
    ): Platform {
        return new Platform(
            [self::createProvider($apiKey, $httpClient, $contract, $eventDispatcher, $name)],
            $modelRouter ?? new CatalogBasedModelRouter(),
            $eventDispatcher,
        );
    }

}
