<?php

declare(strict_types=1);
/**
 * This file is part of Simps.
 *
 * @link     https://simps.io
 * @document https://doc.simps.io
 * @license  https://github.com/simple-swoole/simps/blob/master/LICENSE
 */

namespace Simps\Server;

use Simps\Application;
use Simps\Listener;
use Simps\Route;
use Swoole\Server;

class Tcp
{
    protected $_server;

    protected $_config;

    /** @var \Simps\Route $_route */
    protected $_route;

    public function __construct()
    {
        $config = config('servers');
        $tcpConfig = $config['tcp'];
        $this->_config = $tcpConfig;
        $this->_server = new Server($tcpConfig['ip'], $tcpConfig['port'], $config['mode']);
        $this->_server->set($tcpConfig['settings']);

        if ($config['mode'] == SWOOLE_BASE) {
            $this->_server->on('managerStart', [$this, 'onManagerStart']);
        } else {
            $this->_server->on('start', [$this, 'onStart']);
        }

        $this->_server->on('workerStart', [$this, 'onWorkerStart']);
        foreach ($tcpConfig['callbacks'] as $eventKey => $callbackItem) {
            [$class, $func] = $callbackItem;
            $this->_server->on($eventKey, [$class, $func]);
        }
        $this->_server->start();
    }

    public function onStart(\Swoole\Server $server)
    {
        Application::echoSuccess("Swoole Tcp Server running：tcp://{$this->_config['ip']}:{$this->_config['port']}");
        Listener::getInstance()->listen('start', $server);
    }

    public function onManagerStart(\Swoole\Server $server)
    {
        Application::echoSuccess("Swoole Tcp Server running：tcp://{$this->_config['ip']}:{$this->_config['port']}");
        Listener::getInstance()->listen('managerStart', $server);
    }

    public function onWorkerStart(\Swoole\Server $server, int $workerId)
    {
        $this->_route = Route::getInstance();
        Listener::getInstance()->listen('workerStart', $server, $workerId);
    }
}
