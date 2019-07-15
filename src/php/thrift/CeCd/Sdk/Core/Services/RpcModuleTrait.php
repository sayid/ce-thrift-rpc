<?php
/**
 * Created by PhpStorm.
 * User: zhangyubo
 * Date: 2019/4/30
 * Time: 15:21
 */

namespace Thrift\CeCd\Sdk\Core\Services;

use Thrift\CeCd\Sdk\Core\Client\Rpc;

trait RpcModuleTrait
{
    private $host;

    private $port;

    private $rpcObj;

    private $clientInterceptor;//拦截器

    private $actionType;


    /**
     * 检查服务器，返回ok标示正常
     * @return mixed|string
     * @throws \Thrift\Exception\TException
     */
    public function ping()
    {
        $rpc = new Rpc("ping", $this);
        return $rpc->callRpc("ping", "ping", []);
    }

    /**
     * 魔术方法自动调用rpc类
     * @param $class
     * @return Rpc
     */
    public function __get($class)
    {
        $class_load = $this->getClass($class);
        $rpc = new Rpc($class_load, $this);
        $this->rpcObj = $rpc;
        return $this->rpcObj;
    }

    /**
     * @return mixed
     */
    public function getHost() : string
    {
        if (!empty($this->host)) {
            return $this->host;
        } else {
            $host = GEnv($this->config_key . ".host");
            return $host;
        }

    }

    /**
     * @return mixed
     */
    public function getPort(): int
    {
        if (!empty($this->port)) {
            return $this->port;
        } else {
            $host = GEnv($this->config_key . ".port");
            return $host;
        }
    }

    public function setHost(string $host): RpcModuleIf
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @param int $port
     */
    public function setPort(int $port): RpcModuleIf
    {
        $this->port = $port;
        return $this;
    }

    public function getServiceId(): int
    {
        return $this->serviceId;
    }

    public function getLang(): string
    {
        return $this->lang;
    }

    public function getConfigKey(): string
    {
        return $this->config_key;
    }

    public function getClientInterceptor(): string
    {
        return $this->clientInterceptor;
    }

    public function getClass($property)
    {
        $class = get_class($this);
        if (isset($this->rpcs)) {
            foreach ($this->rpcs as $rpc) {
                if (strpos($rpc, $property)) {
                    return $rpc;
                }
            }
        }
        throw new \Exception($class . "'s property " . $property . " is not found");
    }

    /**
     * 设置同步/异步请求 默认同步
     * @param int $actionType
     * @return $this
     */
    public function setMode(int $actionType = 0) : RpcModuleIf
    {
        $this->actionType = $actionType;
        return $this;
    }

    public function getActionMode() : int
    {
        return $this->actionType;
    }
}

