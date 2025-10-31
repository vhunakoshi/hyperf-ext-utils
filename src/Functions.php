<?php

namespace Vhunakoshi\Utils;


use Hyperf\Utils\Arr;
use Hyperf\Amqp\Producer;
use Hyperf\Utils\Codec\Json;
use Psr\SimpleCache\CacheInterface;
use Hyperf\Utils\ApplicationContext;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Amqp\Message\ProducerMessageInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\Paginator\LengthAwarePaginator;

if (! function_exists('dispatch')) {
    function dispatch($event, int $priority = 1)
    {
        $eventDispatcher = \Vhunakoshi\Utils\container()->get(EventDispatcherInterface::class);
        $eventDispatcher->dispatch($event, $priority);
    }
}

if (! function_exists('validate')) {
    function validate(array $data, array $rules, array $messages = [], array $customAttributes = [])
    {   
        $validator = \Vhunakoshi\Utils\container()->get(ValidatorFactoryInterface::class);
        return $validator->make($data, $rules, $messages, $customAttributes);
    }
}

if (! function_exists('request')) {
    function request(): RequestInterface
    {
        return \Vhunakoshi\Utils\container()->get(RequestInterface::class);
    }
}

if (! function_exists('response')) {
    function response($data, int $code = 0, array $meta = []): ResponseInterface
    {
        $response = ApplicationContext::getContainer()->get(ResponseInterface::class);
        
        $payload = [
            'error' => $code
        ];

        if(is_string($data)) {
            $payload['message'] = $data;
            $data = null;
        }

        if ($data || is_array($data)) {
            $payload['data'] = $data;
        }

        if ($meta) {
            $payload['meta'] = $meta;
        }

        if($data instanceof \Hyperf\Paginator\LengthAwarePaginator) {
            $payload['meta'] = Arr::except($data->toArray(), [
                'data',
                'first_page_url',
                'last_page_url',
                'prev_page_url',
                'next_page_url',
            ]);
        }

        if($data instanceof \Hyperf\Resource\Json\AnonymousResourceCollection || $data instanceof \Hyperf\Resource\Json\ResourceCollection){
            $paginated = $data->resource->toArray();
            $payload['meta'] = Arr::except($paginated, [
                'data',
                'first_page_url',
                'last_page_url',
                'prev_page_url',
                'next_page_url',
            ]);
        }

        $payload = Json::encode($payload);
        
        return $response
                ->withStatus(200)
                ->withHeader('content-type', 'application/json')
                ->withBody(new SwooleStream($payload));
    }
}

if(! function_exists('paginated')) {

    function paginated(LengthAwarePaginator $collection) {

        return Arr::except($collection->toArray(), [
            'data',
            'first_page_url',
            'last_page_url',
            'prev_page_url',
            'next_page_url',
        ]);
    }
}

if (! function_exists('cache')) {
    function cache()
    {
        return \Vhunakoshi\Utils\container()->get(CacheInterface::class);
    }
}

if (! function_exists('container')) {
    function container()
    {
        if (! ApplicationContext::hasContainer()) {
            throw new \RuntimeException('The application context lacks the container.');
        }

        return ApplicationContext::getContainer();
    }
}

if (! function_exists('publish')) {
    function publish(ProducerMessageInterface $message, $delay = 0)
    {
        $producer = \Vhunakoshi\Utils\container()->get(Producer::class);
        return $producer->produce($message);
    }
}
