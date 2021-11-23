<?php

namespace JsonMachine;

interface PositionAware
{
    /**
     * Returns a number of processed bytes from the beginning
     *
     * @return int
     */
    public function getPosition();
}
