<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/logs')]
#[IsGranted('ROLE_ADMIN')]
class ActivityLogController extends AbstractController
{
    #[Route('/', name: 'app_activity_log_index', methods: ['GET'])]
    public function index(Request $request, ActivityLogRepository $activityLogRepository): Response
    {
        $filterUser = $request->query->get('user');
        $filterAction = $request->query->get('action');
        $filterDate = $request->query->get('date');

        $qb = $activityLogRepository->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC');

        if ($filterUser) {
            $qb->andWhere('a.username LIKE :user')
               ->setParameter('user', '%' . $filterUser . '%');
        }

        if ($filterAction) {
            $qb->andWhere('a.action = :action')
               ->setParameter('action', $filterAction);
        }

        if ($filterDate) {
            $startDate = new \DateTime($filterDate);
            $endDate = (clone $startDate)->modify('+1 day');
            
            $qb->andWhere('a.createdAt >= :start')
               ->andWhere('a.createdAt < :end')
               ->setParameter('start', $startDate)
               ->setParameter('end', $endDate);
        }

        $logs = $qb->getQuery()->getResult();

        return $this->render('activity_log/index.html.twig', [
            'logs' => $logs,
            'filter_user' => $filterUser,
            'filter_action' => $filterAction,
            'filter_date' => $filterDate,
        ]);
    }

    #[Route('/clear', name: 'clear_activity_logs', methods: ['POST'])]
    public function clearLogs(EntityManagerInterface $entityManager, ActivityLogRepository $activityLogRepository): JsonResponse
    {
        try {
            // Option 1: Delete all logs using Doctrine
            $logs = $activityLogRepository->findAll();
            
            foreach ($logs as $log) {
                $entityManager->remove($log);
            }
            
            $entityManager->flush();
            
            // Option 2: If you want to use TRUNCATE (faster but bypasses events)
            // $connection = $entityManager->getConnection();
            // $connection->executeStatement('TRUNCATE TABLE activity_log');
            
            // Log this action (optional)
            $this->addFlash('success', 'All activity logs have been cleared successfully!');
            
            return $this->json([
                'success' => true,
                'message' => 'All activity logs have been cleared successfully!',
                'count' => count($logs)
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to clear activity logs: ' . $e->getMessage()
            ], 500);
        }
    }
}