<?php
/******************************************************************************
 * Copyright (c) 2016 Constantin Galbenu <gica.galbenu@gmail.com>             *
 ******************************************************************************/

namespace Dudulina\ReadModel;

use Dudulina\Event\EventWithMetaData;
use Dudulina\EventStore;
use Dudulina\ProgressReporting\TaskProgressCalculator;
use Dudulina\ProgressReporting\TaskProgressReporter;
use Dudulina\ReadModel\ReadModelEventApplier\ReadModelReflector;
use Psr\Log\LoggerInterface;

class ReadModelRecreator
{

    /**
     * @var EventStore
     */
    private $eventStore;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var TaskProgressReporter|null
     */
    private $taskProgressReporter;
    /**
     * @var ReadModelEventApplier
     */
    private $readModelEventApplier;
    /**
     * @var ReadModelReflector
     */
    private $readModelReflector;

    public function __construct(
        EventStore $eventStore,
        LoggerInterface $logger,
        ReadModelEventApplier $readModelEventApplier,
        ReadModelReflector $readModelReflector
    )
    {
        $this->eventStore = $eventStore;
        $this->logger = $logger;
        $this->readModelEventApplier = $readModelEventApplier;
        $this->readModelReflector = $readModelReflector;
    }

    public function setTaskProgressReporter(?TaskProgressReporter $taskProgressReporter)
    {
        $this->taskProgressReporter = $taskProgressReporter;
    }

    public function recreateRead(ReadModelInterface $readModel)
    {
        $eventClasses = $this->readModelReflector->getEventClassesFromReadModel($readModel);

        $this->logger->info(print_r($eventClasses, true));
        $this->logger->info('loading events...');

        $allEvents = $this->eventStore->loadEventsByClassNames($eventClasses);

        $this->logger->info('applying events...');

        $taskProgress = null;

        if ($this->taskProgressReporter) {
            $taskProgress = new TaskProgressCalculator(count($allEvents));
        }

        foreach ($allEvents as $eventWithMetadata) {
            /** @var EventWithMetaData $eventWithMetadata */
            $this->readModelEventApplier->applyEventOnlyOnce($readModel, $eventWithMetadata);
            if ($this->taskProgressReporter) {
                $taskProgress->increment();
                $this->taskProgressReporter->reportProgressUpdate($taskProgress->getStep(), $taskProgress->getTotalSteps(), $taskProgress->calculateSpeed(), $taskProgress->calculateEta());
            }
        }
    }

    /**
     * @param ReadModelInterface $readModel
     * @param string $afterTimestap only the events strictly after this timestamp are applied
     * @return string The last timestamp processed
     */
    public function pollAndApplyEvents(ReadModelInterface $readModel, string $afterTimestap = null)
    {
        $eventClasses = $this->readModelReflector->getEventClassesFromReadModel($readModel);

        $allEvents = $this->eventStore->loadEventsByClassNames($eventClasses);

        if ($afterTimestap) {
            $allEvents->afterSequence($afterTimestap);
        }

        foreach ($allEvents as $eventWithMetadata) {
            /** @var EventWithMetaData $eventWithMetadata */
            $this->readModelEventApplier->applyEventOnlyOnce($readModel, $eventWithMetadata);
            $afterTimestap = $eventWithMetadata->getMetaData()->getTimestamp();
        }
        return $afterTimestap;
    }
}