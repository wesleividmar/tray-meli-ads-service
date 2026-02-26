<?php

return [

    'default' => env('QUEUE_CONNECTION', 'database'),

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_QUEUE_CONNECTION'),
            'table' => env('DB_QUEUE_TABLE', 'jobs'),
            'queue' => env('DB_QUEUE', 'default'),
            'retry_after' => (int) env('DB_QUEUE_RETRY_AFTER', 90),
            'after_commit' => false,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => env('BEANSTALKD_QUEUE_HOST', 'localhost'),
            'queue' => env('BEANSTALKD_QUEUE', 'default'),
            'retry_after' => (int) env('BEANSTALKD_QUEUE_RETRY_AFTER', 90),
            'block_for' => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'default'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 90),
            'block_for' => null,
            'after_commit' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | RabbitMQ (vladimir-yuldashev/laravel-queue-rabbitmq)
        |--------------------------------------------------------------------------
        |
        | Config mais simples e robusta:
        | - usa o DEFAULT EXCHANGE (exchange vazio "")
        | - não precisa bind
        | - routing key = nome da fila
        |
        */
        'rabbitmq' => [
            'driver' => 'rabbitmq',

            'queue' => env('RABBITMQ_QUEUE', 'default'),
            'worker' => env('RABBITMQ_WORKER', 'default'),

            'connection' => PhpAmqpLib\Connection\AMQPLazyConnection::class,

            'hosts' => [
                [
                    'host' => env('RABBITMQ_HOST', '127.0.0.1'),
                    'port' => (int) env('RABBITMQ_PORT', 5672),
                    'user' => env('RABBITMQ_USER', 'guest'),
                    'password' => env('RABBITMQ_PASSWORD', 'guest'),
                    'vhost' => env('RABBITMQ_VHOST', '/'),
                ],
            ],

            'options' => [

                'exchange' => [
                    // DEFAULT EXCHANGE
                    'name' => env('RABBITMQ_EXCHANGE', ''),
                    'type' => env('RABBITMQ_EXCHANGE_TYPE', 'direct'),
                    'declare' => (bool) env('RABBITMQ_EXCHANGE_DECLARE', false),
                    'passive' => (bool) env('RABBITMQ_EXCHANGE_PASSIVE', false),
                    'durable' => (bool) env('RABBITMQ_EXCHANGE_DURABLE', true),
                    'auto_delete' => (bool) env('RABBITMQ_EXCHANGE_AUTO_DELETE', false),
                ],

                'queue' => [
                    // A fila precisa existir: vamos garantir via definitions.json + declare=true aqui.
                    'declare' => (bool) env('RABBITMQ_QUEUE_DECLARE', true),

                    // NÃO faz bind quando usa exchange "" (default exchange).
                    'bind' => (bool) env('RABBITMQ_QUEUE_BIND', false),

                    'passive' => (bool) env('RABBITMQ_QUEUE_PASSIVE', false),
                    'durable' => (bool) env('RABBITMQ_QUEUE_DURABLE', true),
                    'exclusive' => (bool) env('RABBITMQ_QUEUE_EXCLUSIVE', false),
                    'auto_delete' => (bool) env('RABBITMQ_QUEUE_AUTO_DELETE', false),

                    'arguments' => [
                        // deixe vazio (simples) — nada de DLX aqui por enquanto
                    ],
                ],
            ],
        ],

        'deferred' => [
            'driver' => 'deferred',
        ],

        'background' => [
            'driver' => 'background',
        ],

        'failover' => [
            'driver' => 'failover',
            'connections' => [
                'database',
                'deferred',
            ],
        ],

    ],

    'batching' => [
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'job_batches',
    ],

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'failed_jobs',
    ],

];