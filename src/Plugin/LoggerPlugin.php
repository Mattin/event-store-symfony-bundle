<?php

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\Plugin;

use Prooph\Common\Event\ActionEvent;
use Prooph\EventStore\ActionEventEmitterEventStore;
use Prooph\EventStore\Plugin\Plugin;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LoggerPlugin implements Plugin
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $eventStoreListener;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
        $this->eventStoreListener = [];
    }

    public function attachToEventStore(ActionEventEmitterEventStore $eventStore): void
    {
        foreach ($eventStore::ALL_EVENTS as $eventStoreEvent) {
            $this->eventStoreListener[] = $eventStore->attach($eventStoreEvent, function (ActionEvent $event) use ($eventStoreEvent, $eventStore) {
                $context = [];
                if ($event->getParam('stream', null) !== null) {
                    $context['stream_name'] = (string)$event->getParam('stream')->streamName();
                }
                if (isset($context['stream_name'])) {
                    $this->logger->info(sprintf('Event store action "%s" for stream "%s"', $event->getName(), $context['stream_name']), $context);
                } else {
                    $this->logger->info(sprintf('Event store action "%s"', $event->getName()));
                }

            }, 1000);
        }
    }

    public function detachFromEventStore(ActionEventEmitterEventStore $eventStore): void
    {
        foreach ($this->eventStoreListener as $listener) {
            $eventStore->detach($listener);
        }
    }
}
