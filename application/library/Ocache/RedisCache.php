<?php

namespace Ocache;

use Psr\SimpleCache\CacheInterface;

class RedisCache implements CacheInterface
{
    /** @var \Redis  */
    protected $handler;
    protected $resolve;

    // 注入的redis对象不要跟程序中的redis共用一个，防止切换库或者序列化影响
    public function __construct(\Closure $closure)
    {
        $this->resolve = $closure;
    }

    // 需要操作缓存，再建立redis连接
    protected function setHandler()
    {
        if ($this->handler === null) {
            $this->handler = ($this->resolve)();
            // 不采用redis序列化选项，方便对值做出判断
//            $this->handler->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
        }
    }

    public function get($key, $default = null)
    {
        $this->setHandler();
        $value = $this->handler->get($key);
        return $value ? unserialize($value) : $default;
    }

    public function set($key, $value, $ttl = null)
    {
        $this->setHandler();
        $value = serialize($value);
        if (!$ttl || (int)$ttl < 1) {
            return $this->handler->set($key, $value);
        }
        return $this->handler->setex($key, (int)$ttl, $value);
    }

    public function delete($key)
    {
        $this->setHandler();
        return $this->handler->del($key) === 1;
    }

    public function clear()
    {
        $this->setHandler();
        return $this->handler->flushDB();
    }

    /**
     * 不要一次性取大量的key,造成redis阻塞
     * @param iterable $keys
     * @param null $default
     * @return array|iterable
     */
    public function getMultiple($keys, $default = null)
    {
        $this->setHandler();
        $values = $this->handler->mget($keys);
        $res = [];
        foreach ($values as $index => $val) {
            $res[$keys[$index]] = $val ? unserialize($val) : $default;
        }
        return $res;
    }

    public function setMultiple($values, $ttl = null)
    {
        $this->setHandler();
        if (!$ttl || (int)$ttl < 1) {
            foreach ($values as $k => $v) {
                $values[$k] = serialize($v);
            }
            return $this->handler->mset($values);
        }
        // 启用管道
        $ttl = (int)$ttl;
        $pipe = $this->handler->multi(\Redis::PIPELINE);
        foreach ($values as $k => $v) {
            $pipe->setex($k, $ttl, serialize($v));
        }
        $res = $pipe->exec();
        foreach ($res as $r) {
            if (!$r) return false;
        }
        return true;
    }

    public function deleteMultiple($keys)
    {
        $this->setHandler();
        return $this->handler->del(...$keys) == count($keys);
    }

    public function has($key)
    {
        $this->setHandler();
        return $this->handler->exists($key);
    }

    public function increment($key, $step = 1)
    {
        $this->setHandler();
        return $this->handler->incrBy($key, $step);
    }

    public function decrement($key, $step = 1)
    {
        $this->setHandler();
        return $this->handler->decrBy($key, $step);
    }
}