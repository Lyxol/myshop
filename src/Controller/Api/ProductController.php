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
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
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
    public function getOneProduct(Product $product = null): JsonResponse
    {
        if ($product === null)
            return $this->json([
                "code" => JsonResponse::HTTP_NOT_FOUND,
                "message" => "This product does not exist"
            ], JsonResponse::HTTP_NOT_FOUND);
        return $this->json($product);
    }
    //<
    //>Need Admin role
    #[Route('/api/product', name: "app_api_create_product", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: "Unauthorized")]
    public function createProduct(Request $request, SerializerInterface $serializer): JsonResponse
    {
        try {
            $product = $serializer->deserialize($request->getContent(), Product::class, 'json');
        } catch (\Throwable $th) {
            return $this->json([
                "code" => JsonResponse::HTTP_NOT_ACCEPTABLE,
                "message" => $th->getMessage()
            ], JsonResponse::HTTP_NOT_ACCEPTABLE);
        }
        if ($this->productRepository->findOneByName($product->getName()) !== null)
            return $this->json([
                "code" => JsonResponse::HTTP_CONFLICT,
                "message" => "this product already exist"
            ], JsonResponse::HTTP_CONFLICT);
        $this->entityManagerInterface->persist($product);
        $this->entityManagerInterface->flush();
        return $this->json($product);
    }

    #[Route('/api/product/{id}', name: "app_api_update_product", methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: "Unauthorized")]
    public function updateProduct(Product $product = null, Request $request, SerializerInterface $serializer)
    {
        if ($product === null) return $this->json([
            "code" => JsonResponse::HTTP_NOT_FOUND,
            "message" => "product not found"
        ], JsonResponse::HTTP_NOT_FOUND);

        if (empty($request->getContent())) return $this->json([
            "code" => JsonResponse::HTTP_NOT_FOUND,
            "message" => "this request has no content"
        ], JsonResponse::HTTP_NOT_FOUND);

        try {
            $updatedProduct = $serializer->deserialize(
                $request->getContent(),
                Product::class,
                'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $product]
            );
        } catch (\Throwable $th) {
            return $this->json([
                "code" => JsonResponse::HTTP_NOT_ACCEPTABLE,
                "message" => $th->getMessage()
            ], JsonResponse::HTTP_NOT_ACCEPTABLE);
        }
        $this->entityManagerInterface->persist($updatedProduct);
        $this->entityManagerInterface->flush();
        return $this->json($product);
    }

    #[Route('/api/product/{id}', name: "app_api_delete_product", methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: "Unauthorized")]
    public function deleteProduct(Product $product = null): JsonResponse
    {
        if ($product === null)
            return $this->json([
                "code" => JsonResponse::HTTP_NOT_FOUND,
                "message" => "product not found"
            ], JsonResponse::HTTP_NOT_FOUND);
        $this->entityManagerInterface->remove($product);
        $this->entityManagerInterface->flush();
        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }
    //<
}
