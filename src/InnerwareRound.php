<?php

namespace HA;

class InnerwareRound
{
    const VISITED_CARES_ABOUT_ARGUMENTS = true;

    private $resolved = false;
    private $response = null;
    private $visited = [];
    private $controller;
    private $innerwares;
    private $running = false;

	public function __construct($controller, $innerwares) {
        $this->controller = $controller;
        $this->innerwares = $innerwares;
    }

    public function run()
    {
        if ($this->running) {
            return;
        }
        $this->running = true;
        $this->nextInnerware();
        return $this->response;
    }

    private function nextInnerware($custom = null)
    {
        if ($this->resolved || (!$custom && empty($this->innerwares))) {
            return;
        }

        $innerware = $custom ?:
            $innerware = array_shift($this->innerwares);

        if (null === $innerware) {
            return $custom ? null : $this->nextInnerware();
        }

        if (is_string($innerware)) {
            $this->applyStringInnerware($innerware);
            return $custom ? null : $this->nextInnerware();
        }

        if (is_callable($innerware)) {
            $this->applyCallableInnerware($innerware);
            return $custom ? null : $this->nextInnerware();
        }

        throw new Exception("Innerware must be string or callable");
    }

    private function normalizeArgument($value)
    {
        try {
            return eval("return $value;");
        } catch (Exception $e) {
            return $value;
        }
    }

    private function applyStringInnerware($innerware)
    {
        if (static::VISITED_CARES_ABOUT_ARGUMENTS) {
            if (in_array($innerware, $this->visited)) {
                return $this->nextInnerware();
            }
            $this->visited[] = $innerware;
        }

        @list($methodName, $args) = explode(":", $innerware, 2);

        if (!static::VISITED_CARES_ABOUT_ARGUMENTS) {
            if (in_array($methodName, $this->visited)) {
                return $this->nextInnerware();
            }
            $this->visited[] = $methodName;
        }

        $methodName = "handle" . str_replace(" ", "", ucwords(str_replace("_", " ", $methodName)));

        $args = $args ? explode(",", $args) : [];
        $args = array_map([$this, "normalizeArgument"], $args);

        call_user_func([$this->controller, $methodName], ...$args);
    }

    private function applyCallableInnerware($innerware)
    {
        call_user_func($innerware);
    }

    public function requires(...$innerwares)
    {
        foreach ($innerwares as $innerware) {
            if (!$this->resolved) {
                $this->nextInnerware($innerware);
            }
        }
    }

    public function resolve($data)
    {
        if ($this->resolved) {
            return;
        }

        $this->response = response()->json($data);
        $this->resolved = true;
    }

    public function reject($code, $data)
    {
        if ($this->resolved) {
            return;
        }

        $this->response = response()->json($data, $code);
        $this->resolved = true;
    }
}

?>