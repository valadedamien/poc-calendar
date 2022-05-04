<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\EventsType;
use App\Repository\EventsRepository;
use App\Services\ClientService;
use App\Services\EventsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(name: 'app_calendar_')]
class CalendarController extends AbstractController
{
    /**
     * Listing des events en bdd.
     */
    #[Route('/', name: 'list')]
    public function listAction(EventsRepository $eventsRepository, Request $request, ClientService $clientService): Response
    {
        if ($request->get('code')) {
            $clientService->writeNewToken($request->get('code'));
        }

        $events = $eventsRepository->findAll();

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'events' => $events,
        ]);
    }

    /**
     * Créer un event.
     */
    #[Route('/create', name: 'create')]
    public function createAction(Request $request, EventsService $eventsService): Response|RedirectResponse
    {
        $form = $this->createForm(EventsType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $start = $data['dateStart']->format('Y-m-d');
            $end = $data['dateEnd']->format('Y-m-d');

            $eventsService->insertEvent($start, $end, $data['summary']);

            $this->redirectToRoute('app_calendar_list');
        }

        return $this->render('home/create.html.twig', [
            'controller_name' => 'HomeController',
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime définitivement l'event.
     */
    #[Route('/{idEvent}/delete', name: 'delete')]
    public function deleteAction(string $idEvent, EventsService $eventsService): RedirectResponse
    {
        $eventsService->deleteEvent($idEvent);

        return $this->redirectToRoute('app_calendar_list');
    }

    /**
     * Update la couleur de l'event à vert.
     */
    #[Route('/{idEvent}/validate', name: 'update_validate')]
    public function updateValidateEvent(string $idEvent, EventsService $eventsService): RedirectResponse
    {
        $eventsService->updateEvent($idEvent, EventsService::COLOR_ID['green']);

        return $this->redirectToRoute('app_calendar_list');
    }

    /**
     * Update la couleur de l'event à rouge.
     */
    #[Route('/{idEvent}/cancel', name: 'update_cancel')]
    public function updateCancelEvent(string $idEvent, EventsService $eventsService): RedirectResponse
    {
        $eventsService->updateEvent($idEvent, EventsService::COLOR_ID['light_red']);

        return $this->redirectToRoute('app_calendar_list');
    }
}
