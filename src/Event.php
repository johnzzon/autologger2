<?php

namespace App;

use DateInterval;
use DateTime;
use DateTimeInterface;
use Sabre\VObject\Component\VEvent;

class Event
{

    /**
     * The summary of the event.
     *
     * @var string
     */
    protected $summary;

    /**
     * The start date of the event.
     *
     * @var DateTimeInterface
     */
    protected $startDate;

    /**
     * The end date of the event.
     *
     * @var DateTimeInterface
     */
    protected $endDate;

    /**
     * The project code.
     *
     * @var string
     */
    protected $project;

    /**
     * The Jira issue identifier.
     *
     * @var string
     */
    protected $jiraIssue;

    /**
     * The Fibery moment identifier.
     *
     * @var string
     */
    protected $fiberyMoment;

    /**
     * The UUID for this event.
     *
     * @var string
     */
    protected $uuid;

    /**
     * Event constructor.
     *
     * @param VEvent $vevent
     *
     * @throws \Exception
     */
    public function __construct(VEvent $vevent)
    {
        $this->uuid = $vevent->UID->__toString();
        $this->summary = $vevent->SUMMARY->__toString();
        $this->project = $this->parseProject();
        $this->jiraIssue = $this->parseJiraIssue();
        $this->fiberyMoment = $this->parseFiberyMoment();
        $this->startDate = new DateTime($vevent->DTSTART);
        $this->endDate = new DateTime($vevent->DTEND);
    }

    /**
     * Get UUID.
     *
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @return string
     */
    public function getSummary(): string
    {
        return $this->summary;
    }

    /**
     * @return string
     */
    public function getShortSummary(): string
    {
       return preg_replace('/^(?<project>[A-Z\s]*): /', '', $this->summary);
    }

    /**
     * @return string
     */
    public function getSummaryMessage(): string
    {
       return preg_replace('/^#\d+\s/', '', $this->getShortSummary());
    }

    /**
     * @return string
     */
    private function parseProject(): string
    {
        if (preg_match('/^(?<project>[A-Z\s]*):/', $this->summary, $matches)) {
            return $matches['project'];
        }
        return FALSE;
    }

    public function getProject(): string
    {
        return $this->project;
    }

    /**
     * @return \DateInterval
     */
    public function getDurationInterval(): DateInterval
    {
        return $this->startDate->diff($this->endDate);
    }

    /**
     * Get event duration formatted.
     *
     * @param string $format
     *
     * @return string
     */
    public function getDurationFormatted($format = '%h:%I'): string
    {
        $interval = $this->startDate->diff($this->endDate);
        return $interval->format($format);
    }

    /**
     * Get event duration formatted.
     *
     * @param string $format
     *
     * @return string
     */
    public function getDurationHours(): string
    {
        $interval = $this->startDate->diff($this->endDate);
        $hours = $interval->h;
        $minutes = $interval->i;
        return $hours + ($minutes / 60);
    }

    /**
     * Get the Jira issue URL.
     *
     * @return bool|string
     */
    public function getJiraUrl()
    {
        if ($this->isJiraIssue()) {
            if ($this->project === 'GU') {
                return 'https://jiragu.atlassian.net/browse/' . $this->jiraIssue;
            }
            return 'https://kodamera.atlassian.net/browse/'. $this->jiraIssue;
        }
        return FALSE;
    }

    /**
     * Get the Jira issue URL.
     *
     * @return bool|string
     */
    public function getFiberyUrl()
    {
        if ($this->isFiberyMoment()) {
            return 'https://kodamera.fibery.io/Projekthantering/Moment/'. $this->fiberyMoment;
        }
        return FALSE;
    }

    /**
     * Returns a Jira issue identifier or FALSE if not a Jira issue.
     *
     * @return bool|string
     */
    protected function parseJiraIssue()
    {
        if (preg_match('/(?<jira>[A-Z\d]+-[\d]+)/', $this->summary, $matches)) {
            return $matches['jira'];
        }
        return FALSE;
    }

    /**
     * Returns a Fibery moment identifier or FALSE if not a Fibery moment.
     *
     * @return bool|string
     */
    protected function parseFiberyMoment()
    {
        if (preg_match('/#(?<fibery>[\d]+)/', $this->summary, $matches)) {
            return $matches['fibery'];
        }
        return FALSE;
    }

    /**
     * Returns whether this task is a Jira issue or not.
     *
     * @return bool
     */
    public function isJiraIssue(): bool
    {
        return (bool)$this->jiraIssue;
    }

    /**
     * Returns whether this task is a Fibery moment or not.
     *
     * @return bool
     */
    public function isFiberyMoment(): bool
    {
        return (bool)$this->fiberyMoment;
    }


    /**
     * Add duration to this event.
     *
     * @param DateInterval $interval
     *   The duration to add in the form of a DateInterval.
     */
    public function addDuration(DateInterval $interval): void
    {
        $this->endDate->add($interval);
    }

    /**
     * @return string
     */
    public function getJiraIssue(): string
    {
        return $this->jiraIssue;
    }

    /**
     * @return string
     */
    public function getFiberyMoment(): string
    {
        return $this->fiberyMoment;
    }

}
