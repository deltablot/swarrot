<?php

namespace Swarrot\Processor\Stack;

class Builder
{
    /**
     * @var \SplStack
     */
    private $specs;

    public function __construct()
    {
        $this->specs = new \SplStack();
    }

    /**
     * @return self
     *
     * @throws \InvalidArgumentException Missing argument(s) when calling unshift
     */
    public function unshift()
    {
        if (0 === func_num_args()) {
            throw new \InvalidArgumentException('Missing argument(s) when calling unshift');
        }

        $spec = func_get_args();
        $this->specs->unshift($spec);

        return $this;
    }

    /**
     * @return self
     *
     * @throws \InvalidArgumentException Missing argument(s) when calling push
     */
    public function push()
    {
        if (0 === func_num_args()) {
            throw new \InvalidArgumentException('Missing argument(s) when calling push');
        }

        $spec = func_get_args();
        $this->specs->push($spec);

        return $this;
    }

    /**
     * @param mixed $processor
     *
     * @return StackedProcessor
     */
    public function resolve($processor)
    {
        $middlewares = [$processor];

        foreach ($this->specs as $spec) {
            $args = $spec;
            $firstArg = array_shift($args);

            if (is_callable($firstArg)) {
                $processor = $firstArg($processor);
            } else {
                $kernelClass = $firstArg;
                array_unshift($args, $processor);

                $reflection = new \ReflectionClass($kernelClass);
                $processor = $reflection->newInstanceArgs($args);
            }

            array_unshift($middlewares, $processor);
        }

        return new StackedProcessor($processor, $middlewares);
    }
}
