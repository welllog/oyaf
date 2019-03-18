<?php

namespace Ocache;

use Psr\SimpleCache\CacheInterface;

class FileCache implements CacheInterface
{
    protected $default_ttl;
    protected $file_mode;
    protected $cache_path;
    // gc概率,设为负数则不自动gc,可在定时脚本中调用垃圾回收
    protected $gcProbability = 10;

    public function __construct($cache_path, $default_ttl, $file_mode = 0775)
    {
        $this->default_ttl = $default_ttl;
        $this->file_mode = $file_mode;
        if (!file_exists($cache_path)) {
            $this->mkdir($cache_path); // ensure that the parent path exists
        }
        $path = realpath($cache_path);
        if ($path === false) {
            throw new \Exception("cache path does not exist: {$cache_path}");
        }
        if (!is_writable($path . DIRECTORY_SEPARATOR)) {
            throw new \Exception("cache path is not writable: {$cache_path}");
        }
        $this->cache_path = $path;
    }
    
    public function get($key, $default = null)
    {
        $path = $this->getPath($key);

        $expires_at = @filemtime($path);

        if ($expires_at === false) {
            return $default; // file not found
        }

        if (time() >= $expires_at) {
            @unlink($path); // file expired

            return $default;
        }

        $data = @file_get_contents($path);

        if ($data === false) {
            return $default; // race condition: file not found
        }

        if ($data === 'b:0;') {
            return false; // because we can't otherwise distinguish a FALSE return-value from unserialize()
        }

        $value = @unserialize($data);

        if ($value === false) {
            return $default; // unserialize() failed
        }

        return $value;
    }

    public function set($key, $value, $ttl = null)
    {
        $this->gc();

        $path = $this->getPath($key);

        $dir = dirname($path);

        if (!file_exists($dir)) {
            // ensure that the parent path exists:
            $this->mkdir($dir);
        }

        $temp_path = $this->cache_path . DIRECTORY_SEPARATOR . uniqid('', true);

        if (is_int($ttl)) {
            $expires_at = time() + $ttl;
        } elseif ($ttl instanceof DateInterval) {
            $expires_at = date_create_from_format("U", time())->add($ttl)->getTimestamp();
        } elseif ($ttl === null) {
            $expires_at = time() + $this->default_ttl;
        } else {
            throw new \Exception("invalid TTL: " . print_r($ttl, true));
        }

        if (false === @file_put_contents($temp_path, serialize($value))) {
            return false;
        }

//        if (false === @chmod($temp_path, $this->file_mode)) {
//            return false;
//        }

        // 保证原子性
        if (@touch($temp_path, $expires_at) && @rename($temp_path, $path)) {
            return true;
        }

        @unlink($temp_path);

        return false;
    }

    public function getMultiple($keys, $default = null)
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->get($key) ?: $default;
        }

        return $values;
    }

    public function setMultiple($values, $ttl = null)
    {
        $ok = true;

        foreach ($values as $key => $value) {
            $ok = $this->set($key, $value, $ttl) && $ok;
        }

        return $ok;
    }

    public function delete($key)
    {
        return @unlink($this->getPath($key));
    }

    public function deleteMultiple($keys)
    {
        $ok = true;
        foreach ($keys as $key) {
            $ok = $this->delete($key) && $ok;
        }
        return $ok;
    }

    public function clear()
    {
        $success = true;

        $paths = $this->listPaths();

        foreach ($paths as $path) {
            if (!unlink($path)) {
                $success = false;
            }
        }

        return $success;
    }

    public function has($key)
    {
        return $this->get($key, $this) !== $this;
    }

    public function increment($key, $step = 1)
    {
        $path = $this->getPath($key);

        $dir = dirname($path);

        if (!file_exists($dir)) {
            $this->mkdir($dir); // ensure that the parent path exists
        }

        $lock_path = $dir . DIRECTORY_SEPARATOR . ".lock"; // allows max. 256 client locks at one time

        $lock_handle = fopen($lock_path, "w");

        flock($lock_handle, LOCK_EX);

        $value = $this->get($key, 0) + $step;

        $ok = $this->set($key, $value);

        flock($lock_handle, LOCK_UN);

        return $ok ? $value : false;
    }

    public function decrement($key, $step = 1)
    {
        return $this->increment($key, -$step);
    }

    public function cleanExpired()
    {
        $now = time();

        $paths = $this->listPaths();

        foreach ($paths as $path) {
            if ($now > filemtime($path)) {
                @unlink($path);
            }
        }
    }

    protected function gc($force = false)
    {
        if ($force || random_int(0, 1000000) < $this->gcProbability) {
            $this->cleanExpired();
        }
    }

    protected function getPath($key)
    {
        $hash = hash("sha256", $key);

        return $this->cache_path
            . DIRECTORY_SEPARATOR
            . strtoupper($hash[0])
            . DIRECTORY_SEPARATOR
            . strtoupper($hash[1])
            . DIRECTORY_SEPARATOR
            . substr($hash, 2);
    }

    protected function mkdir($path)
    {
        if (!file_exists($path)) {
            return mkdir($path, 0777, true);
        }
    }

    protected function listPaths()
    {
        $iterator = new \RecursiveDirectoryIterator(
            $this->cache_path,
            \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS
        );

        $iterator = new \RecursiveIteratorIterator($iterator);

        foreach ($iterator as $path) {
            if (is_dir($path)) {
                continue; // ignore directories
            }

            yield $path;
        }
    }
}