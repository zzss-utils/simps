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

use App\Consumers\Deliver;
use Simps\Application;
use Simps\Listener;
use SplPriorityQueue;
use Swoole\Server;
use Swoole\Table;

class Tcp
{
    protected $_server;

    protected $_config;

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

        foreach ($tcpConfig['callbacks'] as $eventKey => $callbackItem) {
            [$class, $func] = $callbackItem;
            $this->_server->on($eventKey, [$class, $func]);
        }

        foreach ($tcpConfig['tables'] as $key => $value) {
            $this->_server->$key = new Table($value['size']);
            foreach ($value['columns'] as $v) {
                $this->_server->$key->column($v['name'], $v['type'], $v['size']);
            }

            $this->_server->$key->create();
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
}
