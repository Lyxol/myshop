<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

class ProductController extends AbstractController
{

    public function __construct(
        private ProductRepository $productRepository,
        private EntityManagerInterface $entityManagerInterface
    ) {
    }
    //>Doesn't need  token
    //available => show all product if false | show all product with stock > 0 if true
    #[Route('/api/product', name: 'app_api_all_product', methods: ['GET'])]
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

    #[Route('/api/product/{id}', name: 'app_api_one_product', methods: ['GET'])]
    public function getOneProduct(int $id) : JsonResponse
    {
        if ($this->productRepository->findOneById($id) === null)
            return $this->json(["error" => "This product doesn't exist"], 404);
        return $this->json($this->productRepository->findOneById($id));
    }
    //<
    //>Need Admin role
    #[Route('/api/product', name: "app_api_create_product", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: "Unauthorized")]
    public function createProduct(Request $request, SerializerInterface $serializer) : JsonResponse
    {
        $product = $serializer->deserialize($request->getContent(), Product::class, 'json');
        if ($this->productRepository->findOneByName($product->getName()) !== null)
            return $this->json(["error" => "this product already exist"], 409);
        $this->entityManagerInterface->persist($product);
        $this->entityManagerInterface->flush();
        return $this->json($product);
    }

    #[Route('/api/product/{id}', name: "app_api_delete_product", methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: "Unauthorized")]
    public function deleteProduct(int $id) : JsonResponse
    {

        $product = $this->productRepository->findOneById($id);
        if ($product === null)
            return $this->json(["error" => "product not found"], 404);
        $this->entityManagerInterface->remove($product);
        $this->entityManagerInterface->flush();
        return $this->json($product);
    }
    //<
}
