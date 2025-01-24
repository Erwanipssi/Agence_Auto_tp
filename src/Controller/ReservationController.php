<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Comment;
use App\Entity\Vehicule;
use App\Entity\VehiculeType;
use App\Form\ReservationType;
use App\Form\CommentType;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reservation')]
final class ReservationController extends AbstractController
{
    #[Route(name: 'app_reservation_index', methods: ['GET'])]
    public function index(ReservationRepository $reservationRepository): Response
    {
        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservationRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ReservationRepository $reservationRepository): Response
    {
        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $vehicule = $reservation->getVehicule();
            $dateDebut = $reservation->getDateDebut();
            $dateFin = $reservation->getDateFin();
        
            if ($reservationRepository->isVehiculeReserved($vehicule->getId(), $dateDebut, $dateFin)) {
                $this->addFlash('error', 'Le véhicule est déjà réservé pour cette période.');
                return $this->redirectToRoute('app_reservation_new');
            }
        
            $diff = $dateDebut->diff($dateFin)->days;
            $prixTotal = $vehicule->getPrix() * $diff;
        
            if ($prixTotal > 400) {
                $prixTotal *= 0.9; 
            }
        
            $reservation->setPrix($prixTotal);
        
            $vehicule->setStatut(true);
        
            $entityManager->persist($reservation);
            $entityManager->flush();
        
            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }
        
    
        return $this->render('reservation/new.html.twig', [
            'reservation' => $reservation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_show', methods: ['GET', 'POST'])]
    public function show(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $comment = new Comment();
        $commentForm = $this->createForm(CommentType::class, $comment);
        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $comment->setReservation($reservation);
            $comment->setUser($this->getUser());
            $comment->setCreatedAt(new \DateTime());

            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Votre commentaire a été ajouté avec succès.');

            return $this->redirectToRoute('app_reservation_show', ['id' => $reservation->getId()]);
        }

        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
            'commentForm' => $commentForm->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if (new \DateTime() >= $reservation->getDateDebut()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier cette réservation car elle a déjà commencé ou est en cours.');
            return $this->redirectToRoute('app_reservation_show', ['id' => $reservation->getId()]);
        }
    
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
    
            $this->addFlash('success', 'Réservation modifiée avec succès.');
    
            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }
    
        return $this->render('reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/create/{vehiculeId}', name: 'app_reservation_create', methods: ['GET', 'POST'])]
    public function create(int $vehiculeId, EntityManagerInterface $entityManager): Response
    {
        $vehicule = $entityManager->getRepository(Vehicule::class)->find($vehiculeId);
    
        if (!$vehicule) {
            throw $this->createNotFoundException('Véhicule non trouvé.');
        }
    
        if ($vehicule->getStatut()) {
            $this->addFlash('error', 'Ce véhicule est déjà réservé.');
            return $this->redirectToRoute('app_vehicule_show', ['id' => $vehiculeId]);
        }
    
        $user = $this->getUser();
        $reservationRepository = $entityManager->getRepository(Reservation::class);
        $existingReservation = $reservationRepository->findOneBy([
            'vehicule' => $vehicule,
            'user' => $user,
        ]);
    
        if ($existingReservation) {
            return $this->redirectToRoute('app_reservation_show', ['id' => $existingReservation->getId()]);
        }
    
        $reservation = new Reservation();
        $reservation->setVehicule($vehicule);
        $reservation->setUser($user);
        $reservation->setDateDebut(new \DateTime());
        $reservation->setDateFin((new \DateTime())->modify('+1 day'));
        $reservation->setPrix($vehicule->getPrix());
    
        $vehicule->setStatut(true);
    
        $entityManager->persist($reservation);
        $entityManager->flush();
    
        $this->addFlash('success', 'Réservation créée avec succès.');
    
        return $this->redirectToRoute('app_reservation_show', ['id' => $reservation->getId()]);
    }
    
    
    
    #[Route('/{id}', name: 'app_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $reservation->getId(), $request->request->get('_token'))) {
            $vehicule = $reservation->getVehicule();
    
            $vehicule->setStatut(false);
    
            $entityManager->remove($reservation);
            $entityManager->flush();
    
            $this->addFlash('success', 'Réservation supprimée avec succès.');
        }
    
        return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }
    
}
