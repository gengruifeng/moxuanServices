<?php
namespace MicroService;

use Throwable;

class ServiceException extends \Exception {
    public function __construct($message = "", $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
        error_log('======================Exception Start==============================');
        error_log($message) . PHP_EOL;
        error_log($this->getTraceAsString());
        error_log('======================Exception End================================');
    }
}