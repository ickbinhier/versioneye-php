<?php

namespace Rs\VersionEye\Http;

use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\MultipartStream;
use Http\Client\Common\HttpMethodsClient;
use Http\Client\Exception\HttpException as PlugException;
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
     * @param HttpMethodsClient $adapter
     * @param string               $url
     */
    public function __construct(HttpMethodsClient $adapter, $url)
    {
        $this->adapter = $adapter;
        $this->url     = $url;
    }

    /**
     * {@inheritdoc}
     */
    public function request($method, $url, array $params = [])
    {
        list($params, $files) = $this->fixParams($params);

        try {
            $body = $this->createBody($params, $files);

            var_dump($body);die;
            $response = $this->adapter->send($method, $this->url . $url, [], $body);

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
    private function fixParams(array $params)
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
     * @return StreamInterface|null
     */
    private function createBody(array $params, array $files)
    {
        $streams = [];

        if ($params) {
            foreach ($params as $k => $v) {
                $streams[] = ['name' => $k, 'contents' => $v];
            }
        }

        foreach ($files as $k => $file) {
            $streams[] = ['name' => 'file', 'contents' => new LazyOpenStream($file, 'r'), 'filename' => $file];
        }


        return 0 < count($streams) ? new MultipartStream($streams) : null;
    }
}
