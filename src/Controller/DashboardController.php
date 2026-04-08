<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\ProductRepository;
use App\Repository\CateringRepository;
use App\Repository\ActivityLogRepository;
use App\Service\ActivityLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    public function __construct(
        private ActivityLogger $activityLogger,
        private UserRepository $userRepository,
        private ProductRepository $productRepository,
        private CateringRepository $cateringRepository,
        private ActivityLogRepository $activityLogRepository
    ) {}

    #[Route('/', name: 'app_dashboard')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $this->activityLogger->logLogin($user);

        // ADMIN DASHBOARD
        if ($this->isGranted('ROLE_ADMIN')) {
            $totalUsers = $this->userRepository->count([]);
            $users = $this->userRepository->findAll();
            $totalStaff = count(array_filter($users, fn($u) => in_array('ROLE_STAFF', $u->getRoles())));
            $totalProducts = $this->productRepository->createQueryBuilder('p')
                ->select('COUNT(p.id)')
                ->getQuery()
                ->getSingleScalarResult();
            $totalCaterings = $this->cateringRepository->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->getQuery()
                ->getSingleScalarResult();

            $recentLogs = $this->activityLogRepository->findBy([], ['createdAt' => 'DESC'], 20);

            return $this->render('dashboard/admin.html.twig', [
                'total_users' => $totalUsers,
                'total_staff' => $totalStaff,
                'total_products' => $totalProducts,
                'total_caterings' => $totalCaterings,
                'recent_logs' => $recentLogs,
            ]);
        }

        // STAFF DASHBOARD
        if ($this->isGranted('ROLE_STAFF')) {
            $search = $request->query->get('search');

            // Products
            $qb = $this->productRepository->createQueryBuilder('p')
                ->orderBy('p.createdAt', 'DESC');

            if ($search) {
                $qb->andWhere('p.name LIKE :search')
                   ->setParameter('search', '%'.$search.'%');
            }

            $all_products = $qb->getQuery()->getResult();
            $totalProducts = $this->productRepository->createQueryBuilder('p')
                ->select('COUNT(p.id)')
                ->getQuery()
                ->getSingleScalarResult();

            // Caterings
            $cateringQb = $this->cateringRepository->createQueryBuilder('c')
                ->where('c.createdBy = :user')
                ->setParameter('user', $user)
                ->orderBy('c.createdAt', 'DESC');

            $all_caterings = $cateringQb->getQuery()->getResult();
            $totalCaterings = count($all_caterings);

            return $this->render('dashboard/staff.html.twig', [
                'my_products_count' => $totalProducts,
                'my_products' => $all_products,
                'my_caterings_count' => $totalCaterings,
                'my_caterings' => $all_caterings,
            ]);
        }

        return $this->redirectToRoute('app_login');
    }
}
