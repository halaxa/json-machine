<?php

namespace JsonMachine;

/**
 * @param iterable $iterable
 * @return \Generator
 */
function objects($iterable)
{
    foreach ($iterable as $item) {
        yield (object) $item;
    }
}