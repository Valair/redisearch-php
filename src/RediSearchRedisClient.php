<?php

namespace Ehann\RediSearch;

use Ehann\RediSearch\Exceptions\RediSearchException;
use Ehann\RediSearch\Exceptions\UnknownIndexNameException;
use Ehann\RediSearch\Exceptions\UnknownRediSearchCommandException;
use Ehann\RediSearch\Exceptions\UnsupportedRediSearchLanguageException;
use Ehann\RedisRaw\AbstractRedisRawClient;
use Ehann\RedisRaw\RedisRawClientInterface;
use Exception;
use Psr\Log\LoggerInterface;

class RediSearchRedisClient implements RedisRawClientInterface
{
    /** @var AbstractRedisRawClient */
    protected $redis;

    public function __construct(RedisRawClientInterface $redis)
    {
        $this->redis = $redis;
    }

    public function validateRawCommandResults($payload)
    {
        $isPayloadException = $payload instanceof Exception;
        $message = $isPayloadException ? $payload->getMessage() : $payload;

        if (!is_string($message)) {
            return;
        }

        $message = strtolower($message);

        if ($message === 'unknown index name') {
            throw new UnknownIndexNameException();
        }

        if (in_array($message, ['unsupported language', 'unsupported stemmer language', 'bad argument for `language`'])) {
            throw new UnsupportedRediSearchLanguageException();
        }
        if (strpos($message, 'err unknown command \'ft.') !== false) {
            throw new UnknownRediSearchCommandException($message);
        }

        throw new RediSearchException($payload);
    }

    public function connect($hostnames = '127.0.0.1', int $port = 6379, int $db = 0, string $password = null, array $options = null): RedisRawClientInterface
    {
        $this->redis->connect($hostnames, $port, $db, $password, $options);
    }

    public function flushAll()
    {
        $this->redis->flushAll();
    }

    public function multi(bool $usePipeline = false)
    {
        return $this->redis->multi($usePipeline);
    }

    public function rawCommand(string $command, array $arguments)
    {
        $result = $this->redis->rawCommand($command, $arguments);
        if ($command !== 'FT.EXPLAIN') {
            $this->validateRawCommandResults($result);
        }

        return $result;
    }

    public function setLogger(LoggerInterface $logger): RedisRawClientInterface
    {
        return $this->redis->setLogger($logger);
    }

    public function prepareRawCommandArguments(string $command, array $arguments): array
    {
        return $this->prepareRawCommandArguments($command, $arguments);
    }
}
