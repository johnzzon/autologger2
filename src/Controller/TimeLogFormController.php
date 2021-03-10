<?php

namespace App\Controller;

use App\Form\TimeLogFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class TimeLogFormController extends AbstractController
{
    /**
     * @Route("/log-form", name="time_log_form")
     */
    public function index()
    {
        $form = $this->createForm(TimeLogFormType::class);
        return $this->render('time_log_form/index.html.twig', [
            'controller_name' => 'TimeLogFormController',
            'form' => $form->createView(),
        ]);
    }
}
