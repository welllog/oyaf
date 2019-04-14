<?php

namespace Util;

class BloomFilter
{
    protected $maxSize;
    protected $errRate;
    protected $initSize;
    protected $hashes;
    protected $hashAlgos;
    protected $useBcMath = false;
    protected $filter;
    /** @var \Redis */
    protected $redis;
    protected $bucket;

    // 处理一千万的数据，大概需要28M
    public function __construct($maxSize = 1000, $errRate, $redis, $bucket = 'bloom')
    {
        $this->maxSize = $maxSize;
        $this->errRate = $errRate;
        $this->init();
        $this->redis = $redis;
        $this->bucket = $bucket;
    }

    public function initFilter()
    {
        if ($this->filter === null) {
            $this->filter = $this->redis->get($this->bucket);
            if (!$this->filter) {
                $this->filter = str_repeat("\0", ceil($this->initSize / 8));
            }
        }
    }

    protected function init()
    {
        $this->initSize = $this->calculateInitSize($this->maxSize, $this->errRate);
        $this->hashes = $this->calculateHashFunctions($this->maxSize, $this->initSize);
        $this->hashAlgos = $this->getHashAlgos();

        if ($this->hashes > $this->numHashFunctionsAvailable($this->hashAlgos)) {
            throw new \LogicException("Can't initialize filter with available hash functions");
        }
        if (!function_exists('gmp_init')) {
            if (!function_exists('bcmod')) {
                throw new \LogicException("Can't initialize filter if you don't have any of the 'gmp' or 'bcmath' extension (gmp is faster)");
            }
            $this->useBcMath = true;
        }
    }

    /**
     * Set element in the filter
     *
     * @param mixed $element
     */
    public function set($element)
    {
        $hashes = $this->hash($element);
        $this->initFilter();
        foreach ($hashes as $hash) {
            $offset = (int)floor($hash / 8);
            $bit = (int)($hash % 8);
            // ord获取字符串首字符的ascii码进行|运算，再获取新字符
            $this->filter[$offset] = chr(ord($this->filter[$offset]) | (2 ** $bit));
        }
    }
    /**
     * Is element in the hash
     *
     * @param mixed $element
     *
     * @return boolean
     *   Beware that a strict false means strict false, while a strict true
     *   means "probably with a X% probably" where X is the value you built
     *   the filter with.
     */
    public function check($element)
    {
        $hashes = $this->hash($element);
        $this->initFilter();
        foreach ($hashes as $hash) {
            $offset = (int)floor($hash / 8);
            $bit = (int)($hash % 8);
            if (!(ord($this->filter[$offset]) & (2 ** $bit))) {
                return false;
            }
        }
        return true;
    }

    private function getHashAlgos()
    {
        return hash_algos();
    }

    private function calculateInitSize($maxSize, $errRate)
    {
        return (int)ceil(($maxSize * (log($errRate)) / (log(2) ** 2)) * -1);
    }

    private function calculateHashFunctions($maxSize, $initSize)
    {
        return (int)ceil($initSize / $maxSize * log(2));
    }

    private function numHashFunctionsAvailable($hashAlgos)
    {
        $num = 0;
        foreach ($hashAlgos as $algo) {
            $num += count(unpack('J*', hash($algo, 'bloom', true)));
        }
        return $num;
    }

    private function hash($element)
    {
        $hashes = [];
        foreach ($this->hashAlgos as $algo) {
            foreach (unpack('P*', hash($algo, $element, true)) as $hash) {
                if ($this->useBcMath) {
                    $hashes[] = bcmod(sprintf("%u", $hash), $this->initSize);
                } else {
                    $hash = gmp_init(sprintf("%u", $hash));
                    $hashes[] = ($hash % $this->initSize);
                }
                if (count($hashes) >= $this->hashes) {
                    break 2;
                }
            }
        }
        return $hashes;
    }

    public function __destruct()
    {
        $this->redis->set($this->bucket, $this->filter);
    }


}