<?php

namespace spec\Rs\VersionEye\Http;

use Http\Client\Common\HttpMethodsClient;
use Http\Client\Exception\HttpException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Rs\VersionEye\Http\CommunicationException;
use Psr\Http\Message\ResponseInterface;

class HttpPlugHttpAdapterClientSpec extends ObjectBehavior
{
    public function let(HttpMethodsClient $client)
    {
        $this->beConstructedWith($client, 'http://lolcathost/');
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Rs\VersionEye\Http\HttpPlugHttpAdapterClient');
        $this->shouldHaveType('Rs\VersionEye\Http\HttpClient');
    }

    public function it_performs_a_HTTP_request_to_the_given_url(HttpMethodsClient $client, ResponseInterface $response)
    {
        $client->send('GET', 'http://lolcathost/bar', [], null)->willReturn($response);

        $response->getBody()->willReturn('[]');

        $this->request('GET', 'bar')->shouldBeArray();
    }

    public function it_performs_a_HTTP_request_to_the_given_url_and_raises_an_error_if_failed(HttpMethodsClient $client, ResponseInterface $response, HttpException $e)
    {
        $client->send('GET', 'http://lolcathost/bar', [], null)->willThrow($e->getWrappedObject());
        $e->getResponse()->willReturn($response);

        $response->getStatusCode()->willReturn(500);
        $response->getBody()->willReturn('{"error":"foo"}');

        $this->shouldThrow(new CommunicationException('500 : foo'))->during('request', ['GET', 'bar']);
    }

    public function it_performs_a_HTTP_request_to_the_given_url_with_params(HttpMethodsClient $client, ResponseInterface $response)
    {
        $client->send('DELETE', 'http://lolcathost/bar', [], Argument::type('GuzzleHttp\Psr7\AppendStream'))->willReturn($response);

        $response->getBody()->willReturn('[]');

        $this->request('DELETE', 'bar', ['foo' => 'bar'])->shouldBeArray();
    }

    public function it_performs_a_HTTP_request_to_the_given_url_with_params_and_files(HttpMethodsClient $client, ResponseInterface $response)
    {
        $file = tempnam(sys_get_temp_dir(), 'veye');

        $client->send('DELETE', 'http://lolcathost/bar', [], Argument::type('GuzzleHttp\Psr7\AppendStream'))->willReturn($response);

        $response->getBody()->willReturn('[]');

        $this->request('DELETE', 'bar', ['foo' => 'bar', 'bazz' => $file])->shouldBeArray();

        @unlink($file);
    }
}
