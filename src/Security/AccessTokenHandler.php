<?php
// src/Security/AccessTokenHandler.php
namespace App\Security;

use App\Repository\TokenRepository;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class AccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private TokenRepository $repository
    ){}

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        $token = $this->repository->findOneByToken($accessToken);
        if($token === null)
            throw new BadCredentialsException('Invalid Credentials');
        $account = $token->getAccount();
        return new UserBadge($account->getEmail());
    }
}