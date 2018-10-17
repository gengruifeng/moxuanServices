<?php
namespace MicroService;

use stdClass;
use Exception;
use Swoole\Coroutine;
use Throwable;
use Hprose\Future;

class Service extends \Hprose\Swoole\WebSocket\Service {
    public function __construct() {
        parent::__construct();
        $this->setCrossDomainEnabled();
    }

    public function setAllowOrigins($origins) {

    }


    public function setBeforeInvoke(callable $beforeInvoke) {
        $this->onBeforeInvoke = $beforeInvoke;
    }

    public function setAfterInvoke(callable $afterInvoke) {
        $this->onAfterInvoke = $afterInvoke;
    }

    public function setErrorHandler(callable $errorHandler) {
        $this->onSendError = $errorHandler;
    }

    /**
     * @param \Swoole\Http\Server $server
     */
    public function httpHandle($server) {
        $server->on('request', function ($request, $response) use ($server) {
            $uri = $request->server['request_uri'];
            $uriInfo = explode('?', $uri);
            if($uri != '/favicon.ico') {
                if (preg_match('#/docs#', $uri)) {
                    $uriInfo = explode('/', $uri);
                    $baseUri = '/';
                    foreach ($uriInfo as $str) {
                        if($str == 'docs') break;
                        $baseUri .= '/' . $str;
                    }
                    $baseUri .= '/docs';
                    ob_start();
                    Server::handleDocument($request, $response, $baseUri);
                    $contents = ob_get_contents();
                    ob_end_clean();
                    $response->end($contents);
                } else {
                    $uriInfo = explode('/', trim($uriInfo[0], '/'));
                    $uri = $uriInfo[sizeof($uriInfo)-1];
                    Future\co(function() use ($uri, $request, $response){
                        try {
                            $service = Register::getService($uri);
                            DataPool::initPool();
                            call_user_func_array(array($service, 'handle'), array($request, $response));
                            DataPool::freePool();
                        } catch (\Exception $e) {
                            error_log(print_r($e->getTraceAsString(), true));
                        }
                    });
                }
            }
        });
    }

    /**
     * @param \Swoole\WebSocket\Server $server
     */
    public function wsHandle($server) {
        $self = $this;
        $buffers = array();
        $connectUris = array();
        $server->on('open', function (\Swoole\WebSocket\Server $server, $request) use ($self, &$buffers, &$connectUris) {
            DataPool::initPool();
            $fd = $request->fd;
            if (isset($buffers[$fd])) {
                unset($buffers[$fd]);
            }
            $connectUris[$fd] = $request->server['request_uri'];
            $context = new stdClass();
            $context->server = $server;
            $context->fd = $fd;
            $context->userdata = new stdClass();
            try {
                $onAccept = $self->onAccept;
                if (is_callable($onAccept)) {
                    call_user_func($onAccept, $context);
                }
            }
            catch (Exception $e) { $server->close($fd); }
            catch (Throwable $e) { $server->close($fd); }
        });
        $server->on('close', function (\Swoole\WebSocket\Server $server, $fd) use ($self, &$buffers, &$connectUris) {
            if (isset($buffers[$fd])) {
                unset($buffers[$fd]);
                unset($connectUris[$fd]);
            }
            $context = new stdClass();
            $context->server = $server;
            $context->fd = $fd;
            $context->userdata = new stdClass();
            try {
                $onClose = $self->onClose;
                if (is_callable($onClose)) {
                    call_user_func($onClose, $context);
                }
            }
            catch (Exception $e) {}
            catch (Throwable $e) {}
        });
        $server->on('message', function(\Swoole\WebSocket\Server $server, $frame) use ($self, &$buffers, &$connectUris) {
            DataPool::initPool();
            $fd = $frame->fd;
            $uri = $connectUris[$fd];
            $service = Register::getService($uri);

            if (isset($buffers[$frame->fd])) {
                if ($frame->finish) {
                    $data = $buffers[$frame->fd] . $frame->data;
                    unset($buffers[$frame->fd]);
                    $service->onMessage($server, $frame->fd, $data);
                } else {
                    $buffers[$frame->fd] .= $frame->data;
                }
            }
            else {
                if ($frame->finish) {
                    $service->onMessage($server, $frame->fd, $frame->data);
                } else {
                    $buffers[$frame->fd] = $frame->data;
                }
            }
        });
    }

    public function onMessage($server, $fd, $data) {
        $id = substr($data, 0, 4);
        $request = substr($data, 4);

        $context = new stdClass();
        $context->server = $server;
        $context->fd = $fd;
        $context->id = $id;
        $context->userdata = new stdClass();
        $self = $this;

        $this->userFatalErrorHandler = function($error)
        use ($self, $server, $fd, $id, $context) {
            $self->wsPush($server, $fd, $id . $self->endError($error, $context));
        };

        $response = $this->defaultHandle($request, $context);


        $response->then(function($response) use ($self, $server, $fd, $id) {
            $self->wsPush($server, $fd, $id . $response);
        });
    }


}