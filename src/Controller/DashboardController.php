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

    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        $featuredProducts = $this->productRepository->findBy([], ['createdAt' => 'DESC'], 8);
        
        return $this->render('home.html.twig', [
            'products' => $featuredProducts,
        ]);
    }

    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('about.html.twig');
    }

    #[Route('/services', name: 'app_services')]
    public function services(): Response
    {
        return $this->render('services.html.twig');
    }

    #[Route('/products', name: 'app_products')]
    public function products(): Response
    {
        $allProducts = $this->productRepository->findBy([], ['createdAt' => 'DESC']);
        
        return $this->render('products.html.twig', [
            'products' => $allProducts,
        ]);
    }

    #[Route('/team', name: 'app_team')]
    public function team(): Response
    {
        return $this->render('team.html.twig');
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('contact.html.twig');
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(Request $request): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->adminDashboard();
        }

        if ($this->isGranted('ROLE_STAFF')) {
            return $this->staffDashboard($request);
        }

        return $this->redirectToRoute('app_home');
    }

    private function adminDashboard(): Response
    {
        $totalUsers = $this->userRepository->count([]);
        $allUsers = $this->userRepository->findAll();
        $totalStaff = count(array_filter($allUsers, fn($u) => in_array('ROLE_STAFF', $u->getRoles())));
        $totalProducts = $this->productRepository->count([]);
        $totalCaterings = $this->cateringRepository->count([]);
        $recentLogs = $this->activityLogRepository->findBy([], ['createdAt' => 'DESC'], 20);
        
        // Debug: Check if logs exist
        // Uncomment this line to see if logs are being fetched
        // dd($recentLogs);

        return $this->render('dashboard/admin.html.twig', [
            'total_users' => $totalUsers,
            'total_staff' => $totalStaff,
            'total_products' => $totalProducts,
            'total_caterings' => $totalCaterings,
            'recent_logs' => $recentLogs,
            'all_users' => $allUsers,
        ]);
    }

    private function staffDashboard(Request $request): Response
    {
        $user = $this->getUser();
        $search = $request->query->get('search');

        $productQuery = $this->productRepository->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC');

        if ($search) {
            $productQuery->andWhere('p.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $allProducts = $productQuery->getQuery()->getResult();
        $totalProducts = count($allProducts);

        $cateringQuery = $this->cateringRepository->createQueryBuilder('c')
            ->where('c.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('c.createdAt', 'DESC');

        $allCaterings = $cateringQuery->getQuery()->getResult();

        return $this->render('dashboard/staff.html.twig', [
            'my_products_count' => $totalProducts,
            'my_products' => $allProducts,
            'my_caterings_count' => count($allCaterings),
            'my_caterings' => $allCaterings,
            'search_query' => $search,
        ]);
    }
}