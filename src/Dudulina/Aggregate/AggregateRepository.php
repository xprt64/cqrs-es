<?php
/******************************************************************************
 * Copyright (c) 2016 Constantin Galbenu <gica.galbenu@gmail.com>             *
 ******************************************************************************/

namespace Dudulina\Aggregate;


use Dudulina\Event\EventsApplier\EventsApplierOnAggregate;
use Dudulina\Event\EventWithMetaData;
use Dudulina\EventStore;
use Dudulina\EventStore\AggregateEventStream;

class AggregateRepository
{
    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * @var EventsApplierOnAggregate
     */
    private $eventsApplier;

    /**
     * @var \SplObjectStorage
     */
    private $aggregateToEventStreamMap;

    public function __construct(
        EventStore $eventStore,
        EventsApplierOnAggregate $eventsApplier
    )
    {
        $this->eventStore = $eventStore;
        $this->eventsApplier = $eventsApplier;
        $this->aggregateToEventStreamMap = new \SplObjectStorage();
    }

    public function loadAggregate(AggregateDescriptor $aggregateDescriptor)
    {
        $aggregateClass = $aggregateDescriptor->getAggregateClass();

        $aggregate = new $aggregateClass;

        $priorEvents = $this->eventStore->loadEventsForAggregate($aggregateDescriptor);

        $this->aggregateToEventStreamMap[$aggregate] = $priorEvents;

        /** @var EventWithMetaData[] $priorEvents */
        $this->eventsApplier->applyEventsOnAggregate($aggregate, $priorEvents);

        return $aggregate;
    }

    /**
     * @param $aggregateId
     * @param $aggregate
     * @param EventWithMetaData[] $newEventsWithMeta
     * @return EventWithMetaData[] decorated events with sequence and index
     */
    public function saveAggregate($aggregateId, $aggregate, $newEventsWithMeta)
    {
        /** @var AggregateEventStream $priorEvents */
        $priorEvents = $this->aggregateToEventStreamMap[$aggregate];

        $this->eventStore->appendEventsForAggregate(
            new AggregateDescriptor($aggregateId, get_class($aggregate)), $newEventsWithMeta, $priorEvents);

        $decoratedEvents = [];

        foreach ($newEventsWithMeta as $index => $eventWithMetaData) {
            $decoratedEvents[] = $eventWithMetaData->withSequenceAndIndex($priorEvents->getSequence(), $index);
        }

        return $decoratedEvents;
    }
}