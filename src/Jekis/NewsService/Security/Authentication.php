<?php

namespace Jekis\NewsService\Security;

class Authentication
{
    const HASHES_KEY = 'hashes';

    /**
     * @var \Predis\Client
     */
    private $redis;

    public function __construct(\Predis\Client $redis)
    {
        $this->redis = $redis;
        // TODO: Remove this line, it's for test purpose only!
        $this->redis->hset(self::HASHES_KEY, 'hash', 'hash');
    }

    public function authenticate($hash)
    {
        return $this->hashIsValid($hash);
    }

    /**
     * @todo use Redis
     * @param $hash
     * @return bool
     */
    private function hashIsValid($hash)
    {
        return (bool) $this->redis->hget(self::HASHES_KEY, $hash);
    }
}
