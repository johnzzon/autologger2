<?php

namespace App\Controller;

use App\EventManager;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FlexController extends AbstractController
{
    private const DATE_FORMAT = 'Y-m-d';

    /**
     * @Route("/flex", name="flex")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    public function index(Request $request): Response
    {
        $date_filter_start = new DateTime('2021-03-08');
        $date_filter_end = new DateTime(date(self::DATE_FORMAT));
        $date_filter_end->modify('+1 day');

        $ical_link = getenv('APP_ICAL_LINK');
        $event_manager = new EventManager($ical_link, $date_filter_start, $date_filter_end);

        return $this->render('flex/index.html.twig', [
            'controller_name' => 'FlexController',
            'weekly_duration' => $event_manager->getWeeklyTotalDuration(),
        ]);
    }
}
