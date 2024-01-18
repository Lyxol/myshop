<?php

namespace App\Controller\Api;

use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{

    public function __construct(
        private ProductRepository $productRepository,
        private EntityManagerInterface $entityManagerInterface
    ) {
    }
//>Doesn't need  token
    #[Route('/api/product', name: 'app_api_all_product')]
    public function getAllProducts(
        #[MapQueryParameter] bool $available = true
    ): JsonResponse {
        $products = ($available)
            ? $this->productRepository->findAllAvailableProducts()
            : $this->productRepository->findAll();
        return $this->json([
            'products' => $products,
        ]);
    }

    #[Route('/api/product/{id}', name: 'app_api_one_product')]
    public function getOneProduct(int $id)
    {
        if ($this->productRepository->findOneById($id) === null)
            return $this->json(["error" => "This product doesn't exist"], 404);
        return $this->json($this->productRepository->findOneById($id));
    }
//<
}
