<?php

namespace Pnoexz;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Remember that every worker has to have declare(ticks=...) in the file it
 * resides on, as this setting is limited to the scope of the file.
 */
abstract class AbstractWorker
{
    use LoggerAwareTrait;

    /**
     * @var bool
     */
    protected $terminated = false;

    /**
     * @var ?LoggerInterface
     */
    protected $logger;

    /**
     * Number of seconds the worker should wait to perform the next job.
     *
     * @var int
     */
    protected $sleep = 1;

    /**
     * Determines whether or not the worker should move to the next iteration
     * or simply exit.
     *
     * @return bool
     */
    protected function shouldRun(): bool
    {
        return !$this->terminated;
    }

    /**
     * Gracfully terminates this worker.
     */
    protected function terminate()
    {
        $this->terminated = true;
    }

    /**
     * Wrapper for terminating due to signals.
     */
    protected function onTerminateSignalCaught()
    {
        if ($this->logger instanceof LoggerInterface) {
            $this->logger->debug('Caught signal');
        }
        $this->terminate();
    }

    /**
     * This function gets run on every tick and is useful for debugging purposes
     * if a tick handler is registered using the following function
     * register_tick_function([$this, 'tick']);
     */
    public function tick()
    {
        if ($this->logger instanceof LoggerInterface) {
            $this->logger->debug('tick');
        }
    }

    /**
     * @throws \Exception
     */
    protected function attachSignalHandler()
    {
        if (!function_exists('pcntl_signal')) {
            throw new CantListenToSignalsException();
        }
        pcntl_signal(SIGTERM, [$this, 'onTerminateSignalCaught']);
        pcntl_signal(SIGINT, [$this, 'onTerminateSignalCaught']);

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->debug('Handler for signals attached');
        }
    }

    /**
     * Increases the sleep time to determine how slowly the next iteration
     * should be. This should be used when our worker didn't find any work.
     */
    protected function increaseSleep()
    {
        $this->sleep = min(64, max(1, $this->sleep * 2));
    }

    /**
     * Resets the sleep time back to 0.
     */
    protected function resetSleep()
    {
        $this->sleep = 0;
    }
}
