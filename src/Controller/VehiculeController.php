<?php

namespace App\Controller;

use App\Entity\Vehicule;
use App\Form\VehiculeType;
use App\Entity\Reservation;
use App\Form\ReservationType;

use App\Repository\VehiculeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/vehicule')]
final class VehiculeController extends AbstractController
{
    #[Route(name: 'app_vehicule_index', methods: ['GET'])]
    public function index(Request $request, VehiculeRepository $vehiculeRepository): Response
    {
        $criteria = [];

        if ($marque = $request->query->get('marque')) {
            $criteria['marque'] = $marque;
        }

        if ($modele = $request->query->get('modele')) {
            $criteria['modele'] = $modele;
        }

        if (($statut = $request->query->get('statut')) !== null) {
            $criteria['statut'] = (bool) $statut;
        }

        $vehicules = $vehiculeRepository->  FindBy($criteria);

        return $this->render('vehicule/index.html.twig', [
            'vehicules' => $vehicules
        ]);
    }
    
    

    #[Route('/new', name: 'app_vehicule_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $vehicule = new Vehicule();
        $form = $this->createForm(VehiculeType::class, $vehicule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($vehicule);
            $entityManager->flush();

            return $this->redirectToRoute('app_vehicule_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vehicule/new.html.twig', [
            'vehicule' => $vehicule,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_vehicule_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $entityManager): Response
    {
        $vehicule = $entityManager->getRepository(Vehicule::class)->find($id);
    
        if (!$vehicule) {
            throw $this->createNotFoundException('Véhicule non trouvé.');
        }
    
        $reservations = $vehicule->getReservations();
    
        $user = $this->getUser();
        $userReservation = null;
        foreach ($reservations as $reservation) {
            if ($reservation->getUser() === $user) {
                $userReservation = $reservation;
                break;
            }
        }
    
        $comments = [];
        foreach ($reservations as $reservation) {
            foreach ($reservation->getComments() as $comment) {
                $comments[] = $comment;
            }
        }
    
        return $this->render('vehicule/show.html.twig', [
            'vehicule' => $vehicule,
            'comments' => $comments,
            'reservation' => $userReservation, 
        ]);
    }
    
    #[Route('/{id}/reserve', name: 'app_vehicule_reserve', methods: ['GET', 'POST'])]
public function reserve(int $id, Request $request, EntityManagerInterface $entityManager): Response
{
    $vehicule = $entityManager->getRepository(Vehicule::class)->find($id);

    if (!$vehicule) {
        throw $this->createNotFoundException('Véhicule non trouvé.');
    }

    if ($vehicule->getStatut()) {
        $this->addFlash('error', 'Ce véhicule est déjà réservé.');
        return $this->redirectToRoute('app_vehicule_show', ['id' => $id]);
    }

    $reservation = new Reservation();
    $reservation->setVehicule($vehicule);
    $reservation->setUser($this->getUser());

    $form = $this->createForm(ReservationType::class, $reservation);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $dateDebut = $reservation->getDateDebut();
        $dateFin = $reservation->getDateFin();

        if ($dateDebut >= $dateFin) {
            $this->addFlash('error', 'La date de début doit être inférieure à la date de fin.');
            return $this->redirectToRoute('app_vehicule_reserve', ['id' => $id]);
        }

        $diff = $dateDebut->diff($dateFin)->days;
        $prixTotal = $vehicule->getPrix() * $diff;
        $reservation->setPrix($prixTotal);

        $vehicule->setStatut(true);

        $entityManager->persist($reservation);
        $entityManager->flush();

        $this->addFlash('success', 'Réservation créée avec succès.');

        return $this->redirectToRoute('app_reservation_show', ['id' => $reservation->getId()]);
    }

    return $this->render('vehicule/reserve.html.twig', [
        'vehicule' => $vehicule,
        'form' => $form->createView(),
    ]);
}

    
    
    #[Route('/{id}/edit', name: 'app_vehicule_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Vehicule $vehicule, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(VehiculeType::class, $vehicule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_vehicule_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vehicule/edit.html.twig', [
            'vehicule' => $vehicule,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_vehicule_delete', methods: ['POST'])]
    public function delete(Request $request, Vehicule $vehicule, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$vehicule->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($vehicule);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_vehicule_index', [], Response::HTTP_SEE_OTHER);
    }
}
