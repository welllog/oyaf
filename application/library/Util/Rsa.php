<?php

namespace Util;

class Rsa
{
    /**
     * 私钥
     * @var
     */
    private $_privKey;

    /**
     * 公钥
     * @var
     */
    private $_pubKey;

    /**
     * 保存文件地址
     * @var
     */
    private $_keyPath;

    /**
     * openssl配置文件路径
     * @var string
     */
//    private $_config = '/usr/local/etc/openssl/openssl.cnf';

    /**
     * 密钥长度
     * 可选1024, 2048(支付宝所用密钥长度), 4096
     * @var array
     */
    private $_length = 2048;

    /**
     * 指定密钥文件地址
     * Rsa constructor.
     * @param $path
     * @throws \Exception
     */
    public function __construct($path)
    {
        if (empty($path) || !is_dir($path)) {
            throw new \Exception('请指定密钥文件地址目录');
        }
        $this->_keyPath = $path;
    }

    /**
     * 创建公钥和私钥
     */
    public function createKey()
    {
        $config = [
//            "config" => $this->_config,
            "digest_alg" => "sha512",
            "private_key_bits" => $this->_length,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];
        // 生成私钥
        $rsa = openssl_pkey_new($config);
        openssl_pkey_export($rsa, $privKey, NULL, $config);
        file_put_contents($this->_keyPath . DIRECTORY_SEPARATOR . 'priv.key', $privKey);
        $this->_privKey = openssl_pkey_get_public($privKey);
        // 生成公钥
        $rsaPri = openssl_pkey_get_details($rsa);
        $pubKey = $rsaPri['key'];
        file_put_contents($this->_keyPath . DIRECTORY_SEPARATOR . 'pub.key', $pubKey);
        $this->_pubKey = openssl_pkey_get_public($pubKey);
    }

    /**
     * 设置私钥
     * @return bool
     */
    public function setupPrivKey()
    {
        if (is_resource($this->_privKey)) {
            return true;
        }
        $file = $this->_keyPath . DIRECTORY_SEPARATOR . 'priv.key';
        $privKey = file_get_contents($file);
        $this->_privKey = openssl_pkey_get_private($privKey);
        return true;
    }

    /**
     * 设置公钥
     * @return bool
     */
    public function setupPubKey()
    {
        if (is_resource($this->_pubKey)) {
            return true;
        }
        $file = $this->_keyPath . DIRECTORY_SEPARATOR . 'pub.key';
        $pubKey = file_get_contents($file);
        $this->_pubKey = openssl_pkey_get_public($pubKey);
        return true;
    }

    /**
     * 用私钥加密
     * @param $data
     * @return null|string
     */
    public function privEncrypt($data)
    {
        if (!is_string($data)) {
            return null;
        }
        $this->setupPrivKey();
        $result = openssl_private_encrypt($data, $encrypted, $this->_privKey);
        if ($result) {
            return urlencode(base64_encode($encrypted));
        }
        return null;
    }

    /**
     * 私钥解密
     * @param $encrypted
     * @return null
     */
    public function privDecrypt($encrypted)
    {
        if (!is_string($encrypted)) {
            return null;
        }
        $this->setupPrivKey();
        $encrypted = base64_decode(urldecode($encrypted));
        $result = openssl_private_decrypt($encrypted, $decrypted, $this->_privKey);
        if ($result) {
            return $decrypted;
        }
        return null;
    }

    /**
     * 公钥加密
     * @param $data
     * @return null|string
     */
    public function pubEncrypt($data)
    {
        if (!is_string($data)) {
            return null;
        }
        $this->setupPubKey();
        $result = openssl_public_encrypt($data, $encrypted, $this->_pubKey);
        if ($result) {
            return urlencode(base64_encode($encrypted));
        }
        return null;
    }

    /**
     * 公钥解密
     * @param $crypted
     * @return null
     */
    public function pubDecrypt($crypted)
    {
        if (!is_string($crypted)) {
            return null;
        }
        $this->setupPubKey();
        $crypted = base64_decode(urldecode($crypted));
        $result = openssl_public_decrypt($crypted, $decrypted, $this->_pubKey);
        if ($result) {
            return $decrypted;
        }
        return null;
    }

    /**
     * 加签(采用sha256)
     * @param string $data
     * @return null|string
     */
    public function createSign($data = '')
    {
        if (!is_string($data)) {
            return null;
        }
        $this->setupPrivKey();
        return openssl_sign(
            $data,
            $sign,
            $this->_privKey,
            OPENSSL_ALGO_SHA256      // 若openssl版本过老,可改为OPENSSL_ALGO_SHA1
        ) ? urlencode(base64_encode($sign)) : null;

    }

    /**
     * 验签(采用sha256)
     * @param string $data
     * @param string $sign
     * @return bool
     */
    public function verifySign($data = '', $sign = '')
    {
        if (!is_string($sign) || !is_string($sign)) {
            return false;
        }
        $this->setupPubKey();
        return (bool)openssl_verify(
            $data,
            base64_decode(urldecode($sign)),
            $this->_pubKey,
            OPENSSL_ALGO_SHA256  // 若openssl版本过老,可改为OPENSSL_ALGO_SHA1
        );

    }

    /**
     * __destruct
     *
     */
    public function __destruct()
    {
        @fclose($this->_privKey);
        @fclose($this->_pubKey);
    }

}


