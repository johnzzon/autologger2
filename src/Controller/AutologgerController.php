<?php

namespace App\Controller;

use App\EventManager;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AutologgerController extends AbstractController
{
    protected const DATE_FORMAT = 'Y-m-d';

    /**
     * @Route("/", name="autologger")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    public function index(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $date = $request->query->get('date') ?: date(self::DATE_FORMAT);
        $date_filter_start = new DateTime($date);
        $date_filter_end = new DateTime($date);
        $date_filter_end->modify('+1 day');

        $yesterday = new DateTime($date);
        $yesterday->modify('-1 day');
        $tomorrow = new DateTime($date);
        $tomorrow->modify('+1 day');

        $ical_link = getenv('APP_ICAL_LINK');
        $event_manager = new EventManager($ical_link, $date_filter_start, $date_filter_end);
        $events = $event_manager->getAggregatedEvents();

        return $this->render('autologger/index.html.twig', [
            'controller_name' => 'AutologgerController',
            'events' => $events,
            'date' => $date,
            'yesterday_link' => $this->generateUrl('autologger', ['date' => $yesterday->format(self::DATE_FORMAT)]),
            'tomorrow_link' => $this->generateUrl('autologger', ['date' => $tomorrow->format(self::DATE_FORMAT)]),
            'total_duration' => $event_manager->getTotalDuration(),
        ]);
    }
}
