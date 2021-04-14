<?php

namespace App\Controller;

use DateTime;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\Issue\Worklog;
use JiraRestApi\JiraException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TimeLogController extends AbstractController
{
    /**
     * @Route("/log", name="time_log")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    public function index(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $jira_issue = $request->query->get('jira');
        $fibery_moment = $request->query->get('fibery');
        $duration = $request->query->get('duration');
        if (!$duration) {
            throw new RuntimeException('Missing required parameters.');
        }

        // Log to Jira.
        if ($jira_issue) {
            try {
                $worklog = new Worklog();

                $worklog->setStarted(new DateTime())
                    ->setTimeSpent($duration);

                $issue_service = new IssueService();

                $issue_service->addWorklog($jira_issue, $worklog);
                $this->addFlash('success', "Worklog created for $jira_issue.");
            } catch (JiraException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        // Log to Fibery.
        if ($fibery_moment) {
            $this->addFlash('success', "Tidlogg created for #$fibery_moment.");
        }

        // Log to Harvest.
        // TODO: Harvest API implementation.
        // $harvest = new \BestIt\Harvest\Client(getenv('HARVEST_SERVER_URL'), getenv('HARVEST_USERNAME'), getenv('HARVEST_PASSWORD'));

        // Get all users.
        // $projects = $harvest->timesheet()->create();
        // dd($projects);

        return $this->render('time_log/index.html.twig', [
            'controller_name' => 'TimeLogController',
        ]);
    }
}
