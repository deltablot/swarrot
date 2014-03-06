<?php

namespace Swarrot;

use Swarrot\Broker\MessageProviderInterface;
use Swarrot\Broker\Message;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Swarrot\Processor\ConfigurableInterface;
use Swarrot\Processor\InitializableInterface;
use Swarrot\Processor\TerminableInterface;

class Consumer
{
    protected $messageProvider;
    protected $processor;
    protected $optionsResolver;

    public function __construct(MessageProviderInterface $messageProvider, $processor, OptionsResolverInterface $optionsResolver = null)
    {
        $this->messageProvider = $messageProvider;
        $this->processor       = $processor;
        $this->optionsResolver = $optionsResolver ?: new OptionsResolver();
    }

    /**
     * consume
     *
     * @param array $options Parameters sent to the processor
     *
     * @return void
     */
    public function consume(array $options = array())
    {
        $this->optionsResolver->setDefaults(array(
            'poll_interval' => 50000
        ));

        $processor = $this->processor;

        if ($processor instanceof ConfigurableInterface) {
            $processor->setDefaultOptions($this->optionsResolver);
        }

        $options = $this->optionsResolver->resolve($options);

        if ($processor instanceof InitializableInterface) {
            $processor->initialize($options);
        }

        while (true) {
            while (null !== $message = $this->messageProvider->get()) {
                if (false === $processor($message, $options)) {
                    break 2;
                }
            }

            usleep($options['poll_interval']);
        }

        if ($processor instanceof TerminableInterface) {
            $processor->terminate($options);
        }
    }

    public function getMessageProvider()
    {
        return $this->messageProvider;
    }

    public function setMessageProvider(MessageProviderInterface $messageProvider)
    {
        $this->messageProvider = $messageProvider;
    }

    public function getProcessor()
    {
        return $this->processor;
    }

    public function setProcessor($processor)
    {
        $this->processor = $processor;
    }

    public function getOptionsResolver()
    {
        return $this->optionsResolver;
    }

    public function setOptionsResolver(OptionsResolverInterface $optionsResolver)
    {
        $this->optionsResolver = $optionsResolver;
    }
}