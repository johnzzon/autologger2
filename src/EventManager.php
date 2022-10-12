<?php

namespace App;

use Cassandra\Date;
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
            fopen($calendar_link, 'rb')
        );

        if (empty($vcalendar->VEVENT)) {
            return;
        }

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

        uasort($aggregated_events, static function (Event $a, Event $b) {
            return $a->getSummary() > $b->getSummary();
        });

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
        $interval = $zero->diff($total_duration);
        $hours = $interval->h;
        $minutes = $interval->i;

        return $hours + ($minutes / 60);
    }

    /**
     * Get total duration of the day.
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getWeeklyTotalDuration(): array
    {
        $weeks = $this->getWeeklyEvents();
        $weeks = array_reverse($weeks);
        $week_durations = [];

        foreach ($weeks as $week => $events) {
            $zero = new DateTime('00:00');
            $total_duration = clone $zero;

            foreach ($events as $event) {
                $total_duration->add($event->getDurationInterval());
            }
            $interval = $zero->diff($total_duration);
            $days = $interval->d;
            $hours = $interval->h;
            $minutes = $interval->i;
            $week_durations[$week] = number_format(($days * 24) + $hours + ($minutes / 60), 2);
        }

        return $week_durations;
    }

    public function getWeeklyEvents(): array
    {
        $weeks = [];

        foreach ($this->getEvents() as $event) {
            $year_and_week = $event->getDate()->format('Y, \vW');
            $year = $event->getDate()->format('Y');
            $week = $event->getDate()->format('W');
            $week_date = new DateTime();
            $week_date->setISODate($year, $week);
            $weeks[$week_date->format('Y-m-d')][] = $event;
        }

        return array_reverse($weeks);
    }

}
