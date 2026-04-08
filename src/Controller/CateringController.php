<?php
// src/Controller/CateringController.php

namespace App\Controller;

use App\Entity\Catering;
use App\Form\CateringType;
use App\Repository\CateringRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/catering')]
#[IsGranted('ROLE_STAFF')]
class CateringController extends AbstractController
{
    public function __construct(private ActivityLogger $activityLogger) {}

    #[Route('/', name:'app_catering_index', methods:['GET'])]
    public function index(CateringRepository $cateringRepository): Response
    {
        $user = $this->getUser();
        $caterings = $this->isGranted('ROLE_ADMIN') 
            ? $cateringRepository->findAll() 
            : $cateringRepository->findBy(['createdBy' => $user]);

        return $this->render('catering/index.html.twig', [
            'caterings' => $caterings
        ]);
    }

    #[Route('/new', name:'app_catering_new', methods:['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $catering = new Catering();
        $form = $this->createForm(CateringType::class, $catering);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $catering->setCreatedBy($this->getUser());
            $em->persist($catering);

            // Log creation using the same EntityManager
            $this->activityLogger->logCateringCreate($this->getUser(), $catering, $em);

            $em->flush(); // commit both Catering and ActivityLog

            $this->addFlash('success', 'Catering created successfully.');
            return $this->redirectToRoute('app_catering_index');
        }

        return $this->render('catering/new.html.twig', [
            'catering' => $catering,
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}', name:'app_catering_show', methods:['GET'])]
    public function show(Catering $catering): Response
    {
        return $this->render('catering/show.html.twig', [
            'catering' => $catering
        ]);
    }

    #[Route('/{id}/edit', name:'app_catering_edit', methods:['GET','POST'])]
    public function edit(Request $request, Catering $catering, EntityManagerInterface $em): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $catering->getCreatedBy() !== $this->getUser()) {
            $this->addFlash('error', 'You can only edit your own catering records.');
            return $this->redirectToRoute('app_catering_index');
        }

        $form = $this->createForm(CateringType::class, $catering);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $catering->setUpdatedAt(new \DateTime()); // use DateTime (not DateTimeImmutable)
            
            // Log update
            $this->activityLogger->logCateringUpdate($this->getUser(), $catering, $em);

            $em->flush(); // commit both Catering update and ActivityLog

            $this->addFlash('success', 'Catering updated successfully.');
            return $this->redirectToRoute('app_catering_index');
        }

        return $this->render('catering/edit.html.twig', [
            'catering' => $catering,
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}/delete', name:'app_catering_delete', methods:['POST'])]
    public function delete(Request $request, Catering $catering, EntityManagerInterface $em): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $catering->getCreatedBy() !== $this->getUser()) {
            $this->addFlash('error', 'You can only delete your own catering records.');
            return $this->redirectToRoute('app_catering_index');
        }

        if ($this->isCsrfTokenValid('delete'.$catering->getId(), $request->request->get('_token'))) {
            // Log deletion
            $this->activityLogger->logCateringDelete($this->getUser(), $catering, $em);

            $em->remove($catering);
            $em->flush(); // commit both Catering removal and ActivityLog

            $this->addFlash('success', 'Catering deleted successfully.');
        }

        return $this->redirectToRoute('app_catering_index');
    }
}
