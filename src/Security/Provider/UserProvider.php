<?php

declare(strict_types=1);

/*
 * This file is part of the project by AGBOKOUDJO Franck.
 *
 * (c) AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * Phone: +229 01 67 25 18 86
 * LinkedIn: https://www.linkedin.com/in/internationales-web-apps-services-120520193/
 * Github: https://github.com/Agbokoudjo/
 * Company: INTERNATIONALES WEB APPS & SERVICES
 *
 * For more information, please feel free to contact the author.
 */

namespace App\Security\Provider;

use App\Entity\BaseUserInterface;
use App\Persistance\UserManagerInterface;
use App\Queue\AsyncMethodDispatcherInterface;
use App\Serializer\SerializerFacade;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface as SecurityUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
class UserProvider implements UserProviderInterface
{
    public function __construct(
        private UserManagerInterface $userManager,
        #[Target('session.cache_pool')]
        private  TagAwareCacheInterface $userCacheProvider,
        private SerializerFacade $serializer,
        private AsyncMethodDispatcherInterface $asyncDispatcher
    ) {}

    /**
     * @param string $username
     */
    public function loadUserByUsername($username): SecurityUserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    /**
     * Appelée UNIQUEMENT lors du LOGIN
     * Charge l'utilisateur depuis la DB et le met en cache
     */
    public function loadUserByIdentifier(string $identifier): SecurityUserInterface
    {
        $this->asyncDispatcher->dispatch(
            LoggerInterface::class,
            'debug',
            [
                'Loading user by identifier',
                ['identifier' => $identifier]
            ]
        );

        $cacheKey = 'user_login_' . md5($identifier);

        $userData = $this->userCacheProvider->get($cacheKey, function (ItemInterface $item) use ($identifier) {
            $user = $this->findUser($identifier);

            if (!$user instanceof BaseUserInterface) {
                throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
            }

            $item->expiresAfter(3600);
            $item->tag(['users', 'user_id_' . $user->getId()]);

            return $this->serializer->normalize($user, 'json', [
                'groups' => ['user:cache', 'user:security'],
                AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => fn() => null,
            ]);
        });

        // Désérialiser en objet User
        $userDenormalize = $this->serializer->denormalize($userData, $this->userManager->getClass(), ['groups' => ['user:cache', 'user:security']]);

        if (null === $userDenormalize || !$userDenormalize->isEnabled()) {
            throw new UserNotFoundException(\sprintf('Username "%s" does not exist.', $identifier));
        }

        if (!$userDenormalize instanceof UserInterface) {
            throw new UnsupportedUserException(\sprintf('Expected an instance of %s, but got "%s".', UserInterface::class, $userDenormalize::class));
        }

        return $userDenormalize;
    }

    public function refreshUser(UserInterface $user): SecurityUserInterface
    {
        if (!$user instanceof UserInterface) {
            throw new UnsupportedUserException(\sprintf('Expected an instance of %s, but got "%s".', UserInterface::class, $user::class));
        }

        if (!$this->supportsClass($user::class)) {
            throw new UnsupportedUserException(\sprintf('Expected an instance of %s, but got "%s".', $this->userManager->getClass(), $user::class));
        }

        if (!$user instanceof BaseUserInterface) {
            throw new UnsupportedUserException(\sprintf('User must implement %s', BaseUserInterface::class));
        }

        try {
            $cacheKey = \sprintf('user_%s_%s', (string) $user->getId(), md5($user->getUserIdentifier()));
            $userData = $this->userCacheProvider->get($cacheKey, function (ItemInterface $item) use ($user) {
                $freshUser = $this->findUser($user->getUserIdentifier());

                if (!$freshUser instanceof BaseUserInterface) {
                    throw new UserNotFoundException(\sprintf('User with ID "%s" could not be reloaded.', $user->getId() ?? ''));
                }

                $item->expiresAfter(3600);
                $item->tag(['ADMIN','users', 'user_id_' . $user->getId()]);

                return $this->serializer->normalize($freshUser, null, [
                    'groups' => ['user:cache', 'user:security'],
                    AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => fn() => null,
                ]);
            });

            return $this->serializer->denormalize($userData, $this->userManager->getClass(), ['groups' => ['user:cache', 'user:security']]);
        } catch (\Throwable $th) {
            $this->asyncDispatcher->dispatch(
                LoggerInterface::class,
                'error',
                [
                    'Cache error',
                    ['error' => $th->getMessage()]
                ]
            );

            $freshUser = $this->findUser($user->getUserIdentifier());
            if (!($freshUser instanceof UserInterface)) {
                throw new UserNotFoundException(\sprintf('User with ID "%s" could not be reloaded.', $user->getId() ?? ''));
            }

            return $freshUser;
        }
    }

    public function invalidateUserCache(int|string $userId): void
    {
        try {
            $freshUser = $this->userManager->find($userId);

            if (!($freshUser instanceof BaseUserInterface)) {
                return ;
            }

            $cacheKeyUser = 'user_login_' . $freshUser->getUserIdentifier();
            $this->userCacheProvider->invalidateTags(['user_id_' . $userId, 'ADMIN', 'users']);
            $this->userCacheProvider->delete($cacheKeyUser) ;
        } catch (\Throwable $th) {
            
        }
    }

    /**
     * @param string $class
     */
    public function supportsClass($class): bool
    {
        $userClass = $this->userManager->getClass();

        return $userClass === $class || is_subclass_of($class, $userClass);
    }

    private function findUser(string $username): ?BaseUserInterface
    {
        return $this->userManager->findUserByUsernameOrEmail($username);
    }

}
