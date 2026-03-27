<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Reservation;
use App\Repository\EventRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin')]
class AdminController extends AbstractController
{
    // ─── Dashboard 
    #[Route('/', name: 'admin_dashboard')]
    public function dashboard(EventRepository $eventRepository, ReservationRepository $reservationRepository): Response
    {
        $events = $eventRepository->findAllOrderedByDate();
        $totalReservations = count($reservationRepository->findAll());

        return $this->render('admin/dashboard.html.twig', [
            'events' => $events,
            'totalReservations' => $totalReservations,
            'totalEvents' => count($events),
        ]);
    }

    // ─── Events list 
    #[Route('/events', name: 'admin_events')]
    public function events(EventRepository $eventRepository): Response
    {
        return $this->render('admin/events.html.twig', [
            'events' => $eventRepository->findAllOrderedByDate(),
        ]);
    }

    // ─── Create event 
    #[Route('/events/new', name: 'admin_event_new', methods: ['GET', 'POST'])]
    public function newEvent(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $error = null;

        if ($request->isMethod('POST')) {
            [$event, $error] = $this->buildEventFromRequest($request, $em, $slugger);
            if (!$error) {
                $em->persist($event);
                $em->flush();
                $this->addFlash('success', 'Événement créé avec succès !');
                return $this->redirectToRoute('admin_events');
            }
        }

        return $this->render('admin/event_form.html.twig', [
            'event' => null,
            'error' => $error,
            'action' => 'create',
        ]);
    }

    // ─── Edit event 
    #[Route('/events/{id}/edit', name: 'admin_event_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function editEvent(
        Event $event,
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $error = null;

        if ($request->isMethod('POST')) {
            [$updatedEvent, $error] = $this->buildEventFromRequest($request, $em, $slugger, $event);
            if (!$error) {
                $updatedEvent->setUpdatedAt(new \DateTimeImmutable());
                $em->flush();
                $this->addFlash('success', 'Événement modifié avec succès !');
                return $this->redirectToRoute('admin_events');
            }
        }

        return $this->render('admin/event_form.html.twig', [
            'event' => $event,
            'error' => $error,
            'action' => 'edit',
        ]);
    }

    // ─── Delete event 
    #[Route('/events/{id}/delete', name: 'admin_event_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteEvent(Event $event, EntityManagerInterface $em): Response
    {
        if ($event->getImage()) {
            $imagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/events/' . $event->getImage();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        $em->remove($event);
        $em->flush();
        $this->addFlash('success', 'Événement supprimé.');
        return $this->redirectToRoute('admin_events');
    }

    // ─── Reservations for an event 
    #[Route('/events/{id}/reservations', name: 'admin_event_reservations', requirements: ['id' => '\d+'])]
    public function eventReservations(Event $event, ReservationRepository $reservationRepository): Response
    {
        return $this->render('admin/reservations.html.twig', [
            'event' => $event,
            'reservations' => $reservationRepository->findByEvent($event),
        ]);
    }

    // ─── All reservations 
    #[Route('/reservations', name: 'admin_reservations')]
    public function allReservations(ReservationRepository $reservationRepository): Response
    {
        return $this->render('admin/all_reservations.html.twig', [
            'reservations' => $reservationRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    // ─── Delete reservation 
    #[Route('/reservations/{id}/delete', name: 'admin_reservation_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteReservation(Reservation $reservation, EntityManagerInterface $em): Response
    {
        $eventId = $reservation->getEvent()->getId();
        $em->remove($reservation);
        $em->flush();
        $this->addFlash('success', 'Réservation supprimée.');
        return $this->redirectToRoute('admin_event_reservations', ['id' => $eventId]);
    }

    // ─── Helper 
    private function buildEventFromRequest(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        ?Event $event = null
    ): array {
        $event = $event ?? new Event();
        $error = null;

        $title       = trim($request->request->get('title', ''));
        $description = trim($request->request->get('description', ''));
        $dateStr     = $request->request->get('date', '');
        $location    = trim($request->request->get('location', ''));
        $seats       = (int) $request->request->get('seats', 0);

        if (empty($title) || empty($description) || empty($dateStr) || empty($location) || $seats <= 0) {
            return [$event, 'Tous les champs sont obligatoires et les places doivent être > 0.'];
        }

        try {
            $date = new \DateTimeImmutable($dateStr);
        } catch (\Exception) {
            return [$event, 'Date invalide.'];
        }

        $event->setTitle($title)
              ->setDescription($description)
              ->setDate($date)
              ->setLocation($location)
              ->setSeats($seats);

        // Handle image upload
        $imageFile = $request->files->get('image');
        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/events',
                    $newFilename
                );
                // Delete old image if exists
                if ($event->getImage()) {
                    $oldImage = $this->getParameter('kernel.project_dir') . '/public/uploads/events/' . $event->getImage();
                    if (file_exists($oldImage)) unlink($oldImage);
                }
                $event->setImage($newFilename);
            } catch (FileException $e) {
                $error = 'Erreur lors de l\'upload de l\'image.';
            }
        }

        return [$event, $error];
    }
}
