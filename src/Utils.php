<?php

namespace Vhunakoshi\Utils;

use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class Utils
{
    public static function validate(array $data, array $rules, array $messages = [], array $customAttributes = [])
    {
        $validator = container()->get(ValidatorFactoryInterface::class);

        return $validator->make($data, $rules, $messages, $customAttributes);
    }

    public static function dispatch($event, int $priority = 1)
    {
        $eventDispatcher = container()->get(EventDispatcherInterface::class);
        $eventDispatcher->dispatch($event, $priority);
    }

    public static function response($data, int $code = 0, array $meta = []): ResponseInterface
    {
        $response = container()->get(ResponseInterface::class);
        $message = null;
        $payload = [
            'error' => $code
        ];

        if (is_string($data)) {
            $payload['message'] = $data;
            $data = null;
        }

        if ($data || is_array($data)) {
            $payload['data'] = $data;
        }

        if ($meta) {
            $payload['meta'] = $meta;
        }

        $payload = Json::encode($payload);

        return $response
                ->withStatus(200)
                ->withHeader('content-type', 'application/json')
                ->withBody(new SwooleStream($payload));
    }
}