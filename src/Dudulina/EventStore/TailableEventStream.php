<?php
/**
 * Copyright (c) 2018 Constantin Galbenu <xprt64@gmail.com>
 */

namespace Dudulina\EventStore;


interface TailableEventStream
{
    /**
     * @param callable $callback function(EventWithMetadata)
     * @param string[] $eventClasses
     * @param EventSequence $afterSequence Opaque sequence; it makes sense only to the event store implementation
     */
    public function tail(callable $callback, $eventClasses, EventSequence $afterSequence): void;
}