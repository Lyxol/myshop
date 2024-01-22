<?php

namespace App\Controller\Api;

use App\Entity\Account;
use App\Repository\AccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

class UserController extends AbstractController
{

    public function __construct(
        private AccountRepository $accountRepository,
        private EntityManagerInterface $entityManager
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
    public function showOneAccount(int $id): JsonResponse
    {
        $user = $this->accountRepository->findOneById($id);
        if ($user === null)
            return $this->json(["error" => "account not found"]);
        return $this->json($user);
    }

    //TODO: à tester
    #[IsGranted("ROLE_ADMIN", message: "Unauthorized")]
    #[Route('/api/user', name: 'app_api_create_account', methods: ['POST'])]
    public function createAccount(Request $request, SerializerInterface $serializer)
    {
        $account = $serializer->deserialize($request->getContent(), Account::class, 'json');
        if ($this->accountRepository->findOneByEmail($account->getEmail()) !== null)
            return $this->json(["error" => "already exist"], 409);
        return $this->json($account);
    }

    #[IsGranted("ROLE_ADMIN", message: "Unauthorized")]
    #[Route('/api/user/{id}', name: 'app_api_delete_account', methods: ['DELETE'])]
    public function deleteAccount(int $id)
    {
        $account = $this->accountRepository->findOneById($id);
        if ($account === null) return $this->json(["error" => "account not found"], 404);
        $this->entityManager->remove($account);
        $this->entityManager->flush();
        return $this->json($account);
    }
}
