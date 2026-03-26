<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Reservation;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/events')]
class EventController extends AbstractController
{
    #[Route('/', name: 'app_event_index')]
    public function index(EventRepository $eventRepository): Response
    {
        $events = $eventRepository->findAllOrderedByDate();

        return $this->render('event/index.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/{id}', name: 'app_event_show', requirements: ['id' => '\d+'])]
    public function show(Event $event): Response
    {
        return $this->render('event/show.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/{id}/reserve', name: 'app_event_reserve', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function reserve(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        if ($event->isFull()) {
            $this->addFlash('error', 'Désolé, cet événement est complet.');
            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        $error = null;
        if ($request->isMethod('POST')) {
            $name  = trim($request->request->get('name', ''));
            $email = trim($request->request->get('email', ''));
            $phone = trim($request->request->get('phone', ''));

            if (empty($name) || empty($email) || empty($phone)) {
                $error = 'Tous les champs sont obligatoires.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Adresse email invalide.';
            } else {
                $reservation = new Reservation();
                $reservation->setEvent($event);
                $reservation->setName($name);
                $reservation->setEmail($email);
                $reservation->setPhone($phone);

                if ($this->getUser()) {
                    $reservation->setUser($this->getUser());
                }

                $em->persist($reservation);
                $em->flush();

                return $this->render('reservation/confirmation.html.twig', [
                    'reservation' => $reservation,
                    'event' => $event,
                ]);
            }
        }

        return $this->render('event/reserve.html.twig', [
            'event' => $event,
            'error' => $error,
        ]);
    }
}
