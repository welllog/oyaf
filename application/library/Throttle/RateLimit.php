<?php
/**
 * Created by PhpStorm.
 * User: chentairen
 * Date: 2019/3/27
 * Time: 下午9:20
 */

namespace Throttle;

/**
 * 原子化操作基于redis的管道，如果redis为集群，则无法保证并发有效性
 * Class RateLimit
 * @package Throttle
 */
class RateLimit
{
    protected $keyPrefix = 'token_bucket:';

    protected $maxRequests;

    protected $period;

    /** @var \Redis */
    protected $store;

    /**
     * RateLimit constructor.
     * @param int $maxRequests
     * @param int $period
     * @param \Redis $cache
     */
    public function __construct(int $maxRequests, int $period, \Redis $cache)
    {
        $this->maxRequests = $maxRequests;
        $this->period = $period;
        $this->store = $cache;
    }

    /**
     * 并发安全的限流,管道用于减少io，redis集群不支持管道，因此不适用redis集群
     * @param $id
     * @param $action
     * @param int $use
     * @return bool
     */
    public function aSafeActAllow($id, $action, $use = 1)
    {
        $rate = $this->maxRequests / $this->period;

        $lockKey = $this->keyLock($id, $action);
        $timeKey = $this->keyTime($id, $action);
        $quotaKey = $this->keyQuota($id, $action);

        if ($this->store->set($lockKey, 1, ['nx', 'ex' => $this->period])) {
            $this->store->multi()
                ->set($timeKey, time(), $this->period)
                ->set($quotaKey, $this->maxRequests - $use, $this->period)
                ->exec();
            return true;
        } else {
            $now = time();
            $passTime = $now - $this->store->get($timeKey);

            $restQuota = $this->store->get($quotaKey);
            $newQuota = intval($passTime * $rate);

            $quota = $restQuota + $newQuota;
            if ($quota > $this->maxRequests) {
                $quota = $this->maxRequests;
                $newQuota = $quota - $restQuota;
            }

            $pipeline = $this->store->multi();
            $pipeline->set($timeKey, $now, $this->period);
            $pipeline->expire($lockKey, $this->period);
            if ($quota < $use) {
                ($newQuota > 0) && $pipeline->incr($quotaKey, $newQuota);
                $pipeline->expire($quotaKey, $this->period);
                $pipeline->exec();
                return false;
            } else {
                ($newQuota != $use) && $pipeline->incr($quotaKey, $newQuota - $use);
                $pipeline->expire($quotaKey, $this->period);
                $pipeline->exec();
                return true;
            }
        }
    }

    /**
     * 限流,适用于redis集群,非管道操作增加了网络io
     * @param $id
     * @param $action
     * @param int $use
     * @return bool
     */
    public function safeActAllow($id, $action, $use = 1)
    {
        $rate = $this->maxRequests / $this->period;

        $lockKey = $this->keyLock($id, $action);
        $timeKey = $this->keyTime($id, $action);
        $quotaKey = $this->keyQuota($id, $action);

        if ($this->store->set($lockKey, 1, ['nx', 'ex' => $this->period])) {
            $this->store->set($timeKey, time(), $this->period);
            $this->store->set($quotaKey, $this->maxRequests - $use, $this->period);
            return true;
        } else {
            $now = time();
            $passTime = $now - $this->store->get($timeKey);
            $this->store->set($timeKey, $now, $this->period);

            $restQuota = $this->store->get($quotaKey);
            $newQuota = intval($passTime * $rate);

            $quota = $restQuota + $newQuota;
            if ($quota > $this->maxRequests) {
                $quota = $this->maxRequests;
                $newQuota = $quota - $restQuota;
            }

            $this->store->expire($lockKey, $this->period);
            if ($quota < $use) {
                ($newQuota > 0) && $this->store->incr($quotaKey, $newQuota);
                $this->store->expire($quotaKey, $this->period);
                return false;
            } else {
                ($newQuota != $use) && $this->store->incr($quotaKey, $newQuota - $use);
                $this->store->expire($quotaKey, $this->period);
                return true;
            }
        }
    }

    /**
     * 简单限流，不能防止并发,仅适合作携带标识的动作连续非并发请求
     * @param $id
     * @param $action
     * @param int $use
     * @return bool
     */
    public function simActAllow($id, $action, $use = 1)
    {
        $rate = $this->maxRequests / $this->period;

        $timeKey = $this->keyTime($id, $action);
        $quotaKey = $this->keyQuota($id, $action);

        if (!$this->store->exists($timeKey)) {
            $this->store->set($timeKey, time(), $this->period);
            $this->store->set($quotaKey, $this->maxRequests - $use, $this->period);
            return true;
        } else {
            $now = time();
            $passTime = $now - $this->store->get($timeKey);
            $this->store->set($timeKey, $now, $this->period);

            $restQuota = $this->store->get($quotaKey);
            $newQuota = intval($passTime * $rate);

            $quota = $restQuota + $newQuota;
            $quota = ($quota > $this->maxRequests) ? $this->maxRequests : $quota;

            if ($quota < $use) {
                $this->store->set($quotaKey, $quota, $this->period);
                return false;
            } else {
                $this->store->set($quotaKey, $quota - $use, $this->period);
                return true;
            }
        }
    }

    public function purge($id, $action)
    {
        $this->store->delete($this->keyLock($id, $action),
            $this->keyTime($id, $action),
            $this->keyQuota($id, $action));
    }

    protected function keyTime($id, $action)
    {
        return $this->keyPrefix . $action . ':' . $id . ':time';
    }

    protected function keyQuota($id, $action)
    {
        return $this->keyPrefix . $action . ':' . $id . ':quota';
    }

    protected function keyLock($id, $action)
    {
        return $this->keyPrefix . $action . ':' . $id . ':lock';
    }

}