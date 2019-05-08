<?php
/**
 * rpc连接池
 * Created by PhpStorm.
 * User: zhangyubo
 * Date: 2019/3/4
 * Time: 16:27
 */
namespace Cecd\Sdk\Rpc\Client;

use Thrift\Transport\TSocket;
use Thrift\Transport\TFramedTransport;
class RpcPools
{
    protected $pools;

    private static $PoolInstance;

    private function __construct()
    {
    }

    public static function getInstance() : RpcPools
    {
        if (empty(self::$PoolInstance)) {
            self::$PoolInstance = new self;
        }
        return self::$PoolInstance;
    }
    /**
     * 放入连接池
     * @param $host
     * @param $port
     * @param $transport
     */
    public function push($host, $port, $transport)
    {
        if (!isset($this->pools[md5($host.$port)])) {
            $this->pools[md5($host . $port)] = new \SplQueue();
        }
        $this->pools[md5($host . $port)]->push($transport);
    }

    public function get($host, $port) : TFramedTransport
    {
        if (!isset($this->pools[md5($host.$port)])) {
            $this->pools[md5($host . $port)] = new \SplQueue();
        } else {
            if ($this->pools[md5($host . $port)]->count()) {
                $transport = $this->pools[md5($host . $port)]->pop();
            }
        }
        if (empty($transport)) {
            $socket = new TSocket($host, $port);
            $transport = new TFramedTransport($socket);
            $transport->open();
        }
        return $transport;
    }

    public function __destruct()
    {
        foreach ($this->pools as $pools) {
            foreach ($pools as $transport) {
                $transport->close();
            }
        }
    }
}