<?php

namespace Rs\VersionEye\Http;

use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\MultipartStream;
use Http\Client\Common\HttpMethodsClient;
use Http\Client\Exception\HttpException as PlugException;
use Http\Client\HttpClient as PlugClient;
use Http\Discovery\StreamFactoryDiscovery;
use Psr\Http\Message\StreamInterface;

/**
 * HttpPlugHttpAdapterClient.
 *
 * @author Robert Schönthal <robert.schoenthal@gmail.com>
 */
class HttpPlugHttpAdapterClient implements HttpClient
{
    /**
     * @var HttpMethodsClient
     */
    private $adapter;

    private $url;

    /**
     * @param PlugClient $adapter
     * @param string     $url
     */
    public function __construct(PlugClient $adapter, $url)
    {
        $this->adapter = $adapter;
        $this->url     = $url;
    }

    /**
     * {@inheritdoc}
     */
    public function request($method, $path, array $params = [])
    {
        list($params, $files) = $this->splitParams($params);

        try {
            $body     = $this->createBody($params, $files);
            $response = $this->adapter->send($method, $this->url . $path, [], $body);

            return json_decode($response->getBody(), true);
        } catch (PlugException $e) {
            throw $this->buildRequestError($e);
        }
    }

    /**
     * splits arguments into parameters and files (if any).
     *
     * @param array $params
     *
     * @return array
     */
    private function splitParams(array $params)
    {
        $parameters = [];
        $files      = [];

        foreach ($params as $name => $value) {
            if (is_readable($value)) { //file
                $files[$name] = $value;
            } else {
                $parameters[$name] = $value;
            }
        }

        return [$parameters, $files];
    }

    /**
     * builds the error exception.
     *
     * @param PlugException $e
     *
     * @return CommunicationException
     */
    private function buildRequestError(PlugException $e)
    {
        $data    = $e->getResponse() ? json_decode($e->getResponse()->getBody(), true) : ['error' => $e->getMessage()];
        $message = isset($data['error']) ? $data['error'] : 'Server Error';
        $status  = $e->getResponse() ? $e->getResponse()->getStatusCode() : 500;

        return new CommunicationException(sprintf('%s : %s', $status, $message));
    }

    /**
     * @param array $params
     * @param array $files
     *
     * @return StreamInterface|null
     */
    private function createBody(array $params, array $files)
    {
        $streams = [];

        foreach ($params as $k => $v) {
            $streams[] = ['name' => $k, 'contents' => $v];
        }

        foreach ($files as $k => $file) {
            $streams[] = ['name' => $k, 'contents' => new LazyOpenStream($file, 'r'), 'filename' => $file];
        }

        return count($streams) ? StreamFactoryDiscovery::find()->createStream(new MultipartStream($streams)) : null;
    }
}
