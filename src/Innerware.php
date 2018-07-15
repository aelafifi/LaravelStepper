<?php

namespace HA;

trait Innerware
{
    private $currentRound = null;

    protected function innerwares(...$innerwares)
    {
        $this->currentRound = new InnerwareRound($this, $innerwares);
        $response = $this->currentRound->run();
        $this->currentRound = null;
        return $response;
    }

	protected function resolve($data)
    {
        $this->currentRound &&
            $this->currentRound->resolve($data);
    }

    protected function reject($code, $data)
    {
        $this->currentRound &&
            $this->currentRound->reject($code, $data);
    }

    protected function requires(...$innerwares)
    {
        $this->currentRound &&
            $this->currentRound->requires(...$innerwares);
    }
}

?>