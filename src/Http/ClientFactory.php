<?php

namespace Rs\VersionEye\Http;

use Http\Client\Common\HttpMethodsClient;
use Http\Client\Curl\Client;
use Http\Client\Plugin\AuthenticationPlugin;
use Http\Client\Plugin\DecoderPlugin;
use Http\Client\Plugin\ErrorPlugin;
use Http\Client\Plugin\PluginClient;
use Http\Client\Plugin\RedirectPlugin;
use Http\Client\Plugin\RetryPlugin;
use Http\Message\Authentication\QueryParam;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Http\Message\StreamFactory\GuzzleStreamFactory;
use Http\Client\Exception as PlugException;

/**
 * Factory for creating Http Client
 *
 * @author Robert Schönthal <robert.schoenthal@gmail.com>
 */
class ClientFactory
{
    /**
     * @param string $url
     * @param string $token
     * @return HttpPlugHttpAdapterClient
     */
    public static function create($url, $token)
    {
        $client = self::createPlugClient($token);

        return new HttpPlugHttpAdapterClient($client, $url);
    }

    /**
     * @param string $token
     * @return HttpMethodsClient
     */
    private static function createPlugClient($token)
    {
        $messageFactory = new GuzzleMessageFactory();

        $baseClient = new Client($messageFactory, new GuzzleStreamFactory(), [
            CURLOPT_TIMEOUT => 30,
        ]);

        $plugins = [
            new RedirectPlugin(),
            new RetryPlugin(5),
            new DecoderPlugin(),
            new ErrorPlugin(),
        ];

        if ($token) {
            $plugins[] = new AuthenticationPlugin(new QueryParam([
                'api_key' => $token
            ]));
        }

        $pluginClient = new PluginClient($baseClient, $plugins);

        return new HttpMethodsClient($pluginClient, $messageFactory);
    }
}