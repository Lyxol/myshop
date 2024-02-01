<?php

namespace App\Controller\Api;

use App\Entity\Account;
use App\Repository\AccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class UserController extends AbstractController
{

    public function __construct(
        private AccountRepository $accountRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $userPasswordHasherInterface
    ) {
    }

    #[IsGranted("ROLE_ADMIN", message: "Unauthorized")]
    #[Route('/api/user', name: 'app_api_show_account', methods: ['GET'])]
    public function showAccounts(): JsonResponse
    {
        $userList = $this->accountRepository->findAll();
        return $this->json($userList);
    }

    #[IsGranted("ROLE_ADMIN", message: "Unauthorized")]
    #[Route('/api/user/{id}', name: 'app_api_show_one_account', methods: ['GET'])]
    public function showOneAccount(Account $account = null): JsonResponse
    {
        if ($account === null)
            return $this->json(
                [
                    "code" => JsonResponse::HTTP_NOT_FOUND,
                    "message" => "account not found"
                ],
                JsonResponse::HTTP_NOT_FOUND
            );
        return $this->json($account);
    }

    #[IsGranted("ROLE_ADMIN", message: "Unauthorized")]
    #[Route('/api/user/{id}', name: 'app_api_update_account', methods: ['PUT'])]
    public function updateAccount(Account $account = null, Request $request, SerializerInterface $serializer)
    {
        if ($account === null) return $this->json([
            "code" => JsonResponse::HTTP_NOT_FOUND,
            "message" => "account not found"
        ], JsonResponse::HTTP_NOT_FOUND);

        if (empty($request->getContent())) return $this->json([
            "code" => JsonResponse::HTTP_NOT_FOUND,
            "message" => "this request has no content"
        ], JsonResponse::HTTP_NOT_FOUND);

        try {
            $updatedAccount = $serializer->deserialize(
                $request->getContent(),
                Account::class,
                'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $account]
            );
            $updatedAccount->setPassword(
                $this->userPasswordHasherInterface->hashPassword($updatedAccount,$updatedAccount->getPassword())
            );
        } catch (\Throwable $th) {
            return $this->json([
                "code" => JsonResponse::HTTP_NOT_ACCEPTABLE,
                "message" => $th->getMessage()
            ], JsonResponse::HTTP_NOT_ACCEPTABLE);
        }

        $this->entityManager->persist($updatedAccount);
        $this->entityManager->flush();
        return $this->json($account);
    }

    #[IsGranted("ROLE_ADMIN", message: "Unauthorized")]
    #[Route('/api/user', name: 'app_api_create_account', methods: ['POST'])]
    public function createAccount(Request $request, SerializerInterface $serializer)
    {
        try {
            $account = $serializer->deserialize($request->getContent(), Account::class, 'json');
        } catch (\Throwable $th) {
            return $this->json([
                "code" => JsonResponse::HTTP_NOT_ACCEPTABLE,
                "message" => $th->getMessage()
            ], JsonResponse::HTTP_NOT_ACCEPTABLE);
        }
        $account->setPassword(
            $this->userPasswordHasherInterface->hashPassword($account, $account->getPassword())
        );
        if ($this->accountRepository->findOneByEmail($account->getEmail()) !== null)
            return $this->json([
                "code" => JsonResponse::HTTP_CONFLICT,
                "message" => "already exist"
            ], JsonResponse::HTTP_CONFLICT);
        $this->entityManager->persist($account);
        $this->entityManager->flush();
        return $this->json($account);
    }

    #[IsGranted("ROLE_ADMIN", message: "Unauthorized")]
    #[Route('/api/user/{id}', name: 'app_api_delete_account', methods: ['DELETE'])]
    public function deleteAccount(Account $account)
    {
        if ($account->getId() === $this->getUser()->getId()) return $this->json([
            "code" => JsonResponse::HTTP_UNAUTHORIZED,
            "message" => "cannot delete yourself"
        ], JsonResponse::HTTP_UNAUTHORIZED);

        if ($account === null) return $this->json([
            "code" => JsonResponse::HTTP_NOT_FOUND,
            "message" => "account not found"
        ], JsonResponse::HTTP_NOT_FOUND);

        $this->entityManager->remove($account);
        $this->entityManager->flush();
        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
