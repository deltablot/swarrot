<?php

namespace Swarrot\Processor\Stack;

use Swarrot\Broker\Message;
use Swarrot\Processor\ConfigurableInterface;
use Swarrot\Processor\InitializableInterface;
use Swarrot\Processor\ProcessorInterface;
use Swarrot\Processor\SleepyInterface;
use Swarrot\Processor\TerminableInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StackedProcessor implements ConfigurableInterface, InitializableInterface, TerminableInterface, SleepyInterface
{
    /**
     * @var mixed
     */
    protected $processor;

    /**
     * @var array
     */
    protected $middlewares;

    /**
     * @param mixed $processor
     */
    public function __construct($processor, array $middlewares)
    {
        $this->processor = $processor;
        $this->middlewares = $middlewares;
    }

    /**
     * setDefaultOptions.
     */
    public function setDefaultOptions(OptionsResolver $resolver)
    {
        foreach ($this->middlewares as $middleware) {
            if ($middleware instanceof ConfigurableInterface) {
                $middleware->setDefaultOptions($resolver);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        foreach ($this->middlewares as $middleware) {
            if ($middleware instanceof InitializableInterface) {
                $middleware->initialize($options);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(Message $message, array $options)
    {
        if ($this->processor instanceof ProcessorInterface) {
            return $this->processor->process($message, $options);
        } elseif (is_callable($this->processor)) {
            $processor = $this->processor;

            return $processor($message, $options);
        } else {
            throw new \InvalidArgumentException('Processor MUST implement ProcessorInterface or be a valid callable.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function terminate(array $options)
    {
        foreach ($this->middlewares as $middleware) {
            if ($middleware instanceof TerminableInterface) {
                $middleware->terminate($options);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sleep(array $options)
    {
        foreach ($this->middlewares as $middleware) {
            if ($middleware instanceof SleepyInterface) {
                if (false === $middleware->sleep($options)) {
                    return false;
                }
            }
        }

        return true;
    }
}
