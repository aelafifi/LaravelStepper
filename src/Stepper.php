<?php

namespace ElAfifi;

class Stepper
{
	public function __construct($parent)
	{
		$this->parent = $parent;
	}

	public function __invoke(...$steps)
	{
		$args = [];
		foreach ($steps as $step) {
			$ret = new NotCallable;
			if ($step instanceof StepperCall) {
				$ret = $step->call();
			} elseif (is_string($step)) {
				$ret = call_user_func([$this->parent, $step], ...$args);
				$args = [];
			} elseif (is_callable($step)) {
				$ret = call_user_func($step, ...$args);
				$args = [];
			}
			if ($ret instanceof NotCallable) {
				throw new \Exception('Not callable item in steper series');
				return;
			}
			if ($ret instanceof StepperNext) {
				$args = $ret->args;
				continue;
			}
			return $ret;
		}
	}

	public function step($func, ...$args)
	{
		return new StepperCall($this->parent, $func, $args);
	}

	public static function next(...$args)
	{
		return new StepperNext($args);
	}
}

class StepperCall
{
    public function __construct($parent, $func, $args)
    {
		$this->parent = $parent;
		$this->func = $func;
		$this->args = $args;
	}

	public function call()
	{
		if (is_string($this->func)) {
			return call_user_func([$this->parent, $this->func], ...$this->args);
		}
		if (is_callable($this->func)) {
			return call_user_func($this->func, ...$this->args);
		}
		return new NotCallable;
	}
}

class StepperNext
{
    public function __construct($args)
    {
		$this->args = $args;
	}
}

class NotCallable
{
    // 
}

?>