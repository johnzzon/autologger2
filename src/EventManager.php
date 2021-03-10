<?php

namespace App;

use DateTime;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Reader;
use function DeepCopy\deep_copy;

class EventManager
{

    /**
     * @var Event[]
     */
    protected $events = [];

    /**
     * Event constructor.
     *
     * @param string $calendar_link
     *   A link to a calendar.
     * @param DateTime $date_start
     *   Filter events from this date.
     * @param DateTime $date_end
     *   Filter events to this date.
     *
     * @throws \Exception
     */
    public function __construct(string $calendar_link, DateTime $date_start, DateTime $date_end)
    {
        $vcalendar = Reader::read(
            fopen($calendar_link,'rb')
        );

        foreach ($vcalendar->VEVENT as $vevent) {
            /** @var Vevent $vevent */
            if (!$vevent->isInTimeRange($date_start, $date_end)) {
                continue;
            }
            $event = new Event($vevent);
            $this->events[] = $event;
        }
    }

    /**
     * Get events.
     *
     * @return Event[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * Get aggregated events.
     *
     * This method adds multiple events of same project and summary as one, with their durations combined.
     *
     * @return Event[]
     *
     * @throws \Exception
     */
    public function getAggregatedEvents(): array
    {
        /** @var Event[] $aggregated_events */
        $aggregated_events = [];

        foreach ($this->getEvents() as $event) {
            foreach ($aggregated_events as &$aggregated_event) {
                $is_same_project = $aggregated_event->getProject() === $event->getProject();
                $is_same_summary = $aggregated_event->getSummary() === $event->getSummary();
                if ($is_same_project && $is_same_summary) {
                    // We've already added this task, append duration to old event and skip.
                    $aggregated_event->addDuration($event->getDurationInterval());
                    continue 2;
                }
            }

            // We need to deep clone as to not mess up the original events.
            $event_clone = deep_copy($event);
            $aggregated_events[] = $event_clone;
        }

        return $aggregated_events;
    }

    /**
     * Get total duration of the day.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getTotalDuration(): string
    {
        $zero = new DateTime('00:00');
        $total_duration = clone $zero;
        foreach ($this->getEvents() as $event) {
            $total_duration->add($event->getDurationInterval());
        }
        return $zero->diff($total_duration)->format('%h:%I');
    }

}
