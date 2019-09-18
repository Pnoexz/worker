<?php

namespace Pnoexz;

class CantListenToSignalsException extends \Exception
{
    /**
     * @var string
     */
    public $message = 'Module "pcntl" is not enabled';
}
