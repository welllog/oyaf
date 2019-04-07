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
    protected $empty;
    protected $filter;

    public function __construct($maxSize = 1000, $errRate)
    {
        $this->maxSize = $maxSize;
        $this->errRate = $errRate;
        $this->init();
        $this->filter = str_repeat("\0", ceil($this->initSize / 8));
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
        if (!is_scalar($element)) {
            $element = serialize($element);
        }
        $hashes = $this->hash($element);
        foreach ($hashes as $hash) {
            $offset = (int)floor($hash / 8);
            $bit = (int)($hash % 8);
            $this->filter[$offset] = chr(ord($this->filter[$offset]) | (2 ** $bit));
        }
        $this->empty = false;
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
        if (!is_scalar($element)) {
            $element = serialize($element);
        }
        $hashes = $this->hash($element);
        foreach ($hashes as $hash) {
            $offset = (int)floor($hash / 8);
            $bit = (int)($hash % 8);
            if (!(ord($this->filter[$offset]) & (2 ** $bit))) {
                return false;
            }
        }
        return true;
    }
    /**
     * Is this instance empty
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return $this->empty;
    }
    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return implode(',', [$this->maxSize, $this->errRate, base64_encode($this->filter)]);
    }
    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list($this->maxSize, $this->errRate, $this->filter) = explode(',', $serialized, 3);
        $this->filter = base64_decode($this->filter);
        $this->init();
        $this->empty = false;
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
}