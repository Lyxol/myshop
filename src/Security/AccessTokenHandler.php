<?php
namespace App\Security;

use App\Repository\TokenRepository;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class AccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private TokenRepository $repository,
        private EntityManagerInterface $entityManager
    ){}

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        $token = $this->repository->findOneByToken($accessToken);
        if($token === null)
            throw new BadCredentialsException('Invalid Credentials');
        if($token->getExpireAt() < new DateTime()){
            $this->entityManager->remove($token);
            throw new BadCredentialsException('Token Expired');
        }
            
        $account = $token->getAccount();
        return new UserBadge($account->getEmail());
    }
}