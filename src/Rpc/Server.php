<?php
namespace Ce\Sdk\Rpc;


use Thrift;
use Thrift\ClassLoader\ThriftClassLoader;


use Thrift\Protocol\TBinaryProtocol;
use Thrift\Transport\TPhpStream;
use Thrift\Transport\TBufferedTransport;

/**
 * Class Server.
 */
class Server
{
    /**
     * lumen-swoole version.
     */
    const VERSION = 'ce-swoole 0.1.0';
    /**
     * @var \Laravel\Lumen\Application
     */
    protected $app;
    /**
     * Default host.
     *
     * @var string
     */
    protected $host = '0.0.0.0';
    /**
     * Default port.
     *
     * @var int
     */
    protected $port = 8091;
    /**
     * Pid file.
     *
     * @var string
     */
    protected $pidFile = '';
    /**
     * Http server instance.
     *
     * @var HttpServer
     */
    protected $httpServer;
    /**
     * Http server options.
     *
     * @var array
     */
    protected $options = [];
    /**
     * Application snapshot.
     *
     * @var null
     */
    protected $appSnapshot = null;

    protected $_SERVER = [];
    /**
     * Valid swoole http server options.
     *
     * @see http://wiki.swoole.com/wiki/page/274.html
     *
     * @var array
     */
    public static $validServerOptions = [
        'reactor_num',
        'worker_num',
        'max_request',
        'max_conn',
        'task_worker_num',
        'task_ipc_mode',
        'task_max_request',
        'task_tmpdir',
        'dispatch_mode',
        'message_queue_key',
        'daemonize',
        'backlog',
        'pid_file',
        'log_file',
        'log_level',
        'heartbeat_check_interval',
        'heartbeat_idle_time',
        'open_eof_check',
        'open_eof_split',
        'package_eof',
        'open_length_check',
        'package_length_type',
        'package_max_length',
        'open_cpu_affinity',
        'cpu_affinity_ignore',
        'open_tcp_nodelay',
        'tcp_defer_accept',
        'ssl_cert_file',
        'ssl_method',
        'user',
        'group',
        'chroot',
        'pipe_buffer_size',
        'buffer_output_size',
        'socket_buffer_size',
        'enable_unsafe_event',
        'discard_timeout_request',
        'enable_reuse_port',
        'ssl_ciphers',
        'enable_delay_receive',
    ];
    /**
     * If shutdown function registered.
     *
     * @var bool
     */
    protected $shutdownFunctionRegistered = false;
    /**
     * Create a new Server instance.
     *
     * @param string $host
     * @param int    $port
     */
    public function __construct($host = '0.0.0.0', $port = 8090)
    {
        $this->host = $host;
        $this->port = $port;
    }


    /**
     * Resolve application.
     *
     * @return void
     */
    protected function resolveApplication()
    {
        if (!$this->appSnapshot) {
            $this->appSnapshot = require $this->basePath('bootstrap/app.php');

            /*$GEN_DIR = realpath(dirname(__FILE__).'/..').'/gen-php';
            $loader = new ThriftClassLoader();
            $loader->registerNamespace('Thrift', __DIR__ . '/../../lib/php/lib');
            //$loader->registerDefinition('shared', $GEN_DIR);
            $loader->registerDefinition('Base', $GEN_DIR);
            $loader->register();*/

        }
    }

    /**
     * Get the base path for the application.
     *
     * @param string|null $path
     *
     * @return string
     */
    public function basePath($path = null)
    {
        return substr(__DIR__,0, -27 ).($path ? '/'.$path : $path);
    }

    /**
     * Determine if server is running.
     *
     * @return bool
     */
    public function isRunning()
    {
        if (!file_exists($this->pidFile)) {
            return false;
        }
        $pid = file_get_contents($this->pidFile);
        return (bool) posix_getpgid($pid);
    }
    /**
     * Set http server options.
     *
     * @param array $options
     *
     * @return $this
     */
    public function options($options = [])
    {
        $this->options = array_only($options, static::$validServerOptions);
        return $this;
    }

    /**
     * Server shutdown event callback.
     */
    public function onShutdown()
    {
        unlink($this->pidFile);
    }

    protected $processor = null;
    protected $serviceName = 'RpcService';

    public function onReceive($serv, $fd, $from_id, $data)
    {
        $handler = new RpcServiceHandle();
        $processor = new \Ce\Sdk\Rpc\Base\RpcServiceProcessor($handler);

        $transport = new TBufferedTransport(new TPhpStream(TPhpStream::MODE_R | TPhpStream::MODE_W));
        $protocol = new TBinaryProtocol($transport, true, true);

        $transport->open();
        $processor->process($protocol, $protocol);
        $transport->close();
    }

    function onWorkerStart()
    {
        echo "ThriftServer Start\n";
        if (!defined('SERVER_TYPE')) {
            define('SERVER_TYPE', 1);//定义服务方式为Swoole
        }
        $this->resolveApplication();
    }

    function onConnect($server, $fd, $reactorId)
    {
        echo "新连接";
    }

    function notice($log)
    {
        echo $log."\n";
    }


    function serve()
    {
        $this->pidFile = $this->options['pid_file'];
        if ($this->isRunning()) {
            throw new \Exception('The server is already running.');
        }

        $serv = new \Swoole\Server($this->host, $this->port);
        if (!empty($this->options)) {
            $serv->set($this->options);
        }
        $serv->on('workerStart', [$this, 'onWorkerStart']);
        $serv->on('receive', [$this, 'onReceive']);
        $serv->on('connect', [$this, 'onConnect']);
        $serv->start();
    }
}