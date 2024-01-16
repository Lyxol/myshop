<?php

namespace App\Controller\Api;

use App\Entity\Account;
use App\Entity\Token;
use App\Repository\AccountRepository;
use App\Repository\TokenRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\ByteString;

class TokenController extends AbstractController
{
    public function __construct(
        private AccountRepository $accountRepository,
        private TokenRepository $tokenRepository,
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
        $token =  $account->getToken();
        do {
            $tkn_string = ByteString::fromRandom(128)->toString();
        } while ($this->tokenRepository->findOneByToken($tkn_string) !== null);
        $token->setToken($tkn_string);
        $token->setExpireAt((new DateTime())->modify('+1 hour'));
        $this->entityManager->flush();

        return $this->json(["token" => $token->getToken()]);
    }

    #[Route('/api/register', name: 'app_api_register')]
    public function register(Request $request): Response
    {
        $body = json_decode($request->getContent());
        if ($body === null || !isset($body->email) || !isset($body->password))
            return $this->json(["error" => "Incorrect Request Body"],400);

        $email = $body->email;
        $password = $body->password;
        $account = $this->accountRepository->findByEmailAndPassword($email, $password);
        if($account !== null)
            return $this->json(["error" => "This account already exist"],401);

        $account = new Account();
        $account->setEmail($email);
        $account->setPassword($password);
        $this->entityManager->persist($account);
        $this->entityManager->flush();

        $token = new Token();
        do {
            $tkn_string = ByteString::fromRandom(128)->toString();
        } while ($this->tokenRepository->findOneByToken($tkn_string) !== null);
        $token->setToken($tkn_string);
        $token->setAccount($account);
        $token->setExpireAt((new DateTime())->modify('+1 hour'));
        $this->entityManager->persist($token);
        $this->entityManager->flush();
        
        return $this->json(["token" => $token->getToken()]);
    }
}
