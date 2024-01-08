<?php

namespace App\Controller\Api;

use App\Entity\Token;
use App\Repository\AccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TokenController extends AbstractController
{
    public function __construct(
        private AccountRepository $accountRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/api/login', name: 'app_api_login')]
    public function login(Request $request): Response
    {
        $body = json_decode($request->getContent());
        if ($body === null || !isset($body->email) || !isset($body->password))
            return $this->json(["error" => "Incorrect Request Body"],400);
        $email = $body->email;
        $password = $body->password;
        $account = $this->accountRepository->findByEmailAndPassword($email, $password);
        if($account === null)
            return $this->json(["error" => "No Account Were Found"],401);
        $token = new Token();
        $token->setAccount($account);
        //TODO:Add token creation logic
        return $this->json(["received" => true]);
    }
}
