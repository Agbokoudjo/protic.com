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

namespace App\Entity;

use App\Entity\BaseUserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
abstract class AbstractUser implements BaseUserInterface ,\Stringable,
    LegacyPasswordAuthenticatedUserInterface,
    PasswordAuthenticatedUserInterface,
    EquatableInterface
{
    /**
     * @var int|string|null
     */
    protected int|string|null $id=null;

    protected  ?string $username = null;

    protected ?string $usernameCanonical = null;

    protected ?string $email = null;

    protected ?string $emailCanonical = null;

    protected bool $enabled = false;

    protected ?string $salt = null;

    protected ?string $password = null;

    protected ?string $plainPassword = null;

    protected ?\DateTimeInterface $lastLogin = null;

    protected ?string $confirmationToken = null;

    protected ?string $slug = null;
    
    protected ?\DateTimeInterface $passwordRequestedAt = null;

    /**
     * @var string[]
     */
    protected array $roles = [];

    protected ?\DateTimeInterface $createdAt = null;

    protected ?\DateTimeInterface $updatedAt = null;

    protected ?\DateTimeInterface  $tokenRequestedAt = null;

    // Le compte est-il vérifié par e-mail ?
    protected bool $emailVerified ;

    protected ?\DateTimeImmutable $emailVerifiedAt = null;

    public function __toString(): string
    {
        return $this->getUsername();
    }

    /**
     * @return mixed[]
     */
    public function __serialize(): array
    {
        return [
            $this->password,
            $this->salt,
            $this->usernameCanonical,
            $this->username,
            $this->enabled,
            $this->id,
            $this->email,
            $this->emailCanonical,
        ];
    }

    /**
     * @param mixed[] $data
     */
    public function __unserialize(array $data): void
    {
        [
            $this->password,
            $this->salt,
            $this->usernameCanonical,
            $this->username,
            $this->enabled,
            $this->id,
            $this->email,
            $this->emailCanonical
        ] = $data;
    }

    public function getUserIdentifier(): string
    {
        $username = $this->getEmail();
        if (null === $username || '' === $username) {
            return '-';
        }

        return $username;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function addRole(string $role): void
    {
        $role = strtoupper($role);

        if ($role === static::ROLE_DEFAULT) {
            return;
        }

        if (!\in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }
        // we need to make sure to have at least one role
        $this->roles[] = static::ROLE_DEFAULT;
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUsernameCanonical(): ?string
    {
        return $this->usernameCanonical;
    }

    public function getSalt(): ?string
    {
        return $this->salt;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getEmailCanonical(): ?string
    {
        return $this->emailCanonical;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function getConfirmationToken(): ?string
    {
        return $this->confirmationToken;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        return array_values(array_unique($roles));
    }

    public function hasRole(string $role): bool
    {
        return \in_array(strtoupper($role), $this->getRoles(), true);
    }

    public function isAccountNonExpired(): bool
    {
        return true;
    }

    public function isAccountNonLocked(): bool
    {
        return true;
    }

    public function isCredentialsNonExpired(): bool
    {
        return true;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function removeRole(string $role): void
    {
        if (false !== $key = array_search(strtoupper($role), $this->roles, true)) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
        }
    }

    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    public function setUsernameCanonical(?string $usernameCanonical): void
    {
        $this->usernameCanonical = $usernameCanonical;
    }

    public function setSalt(?string $salt): void
    {
        $this->salt = $salt;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function setEmailCanonical(?string $emailCanonical): void
    {
        $this->emailCanonical = $emailCanonical;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function setPlainPassword(?string $password): void
    {
        $this->plainPassword = $password;

        // Do not remove this, it will trigger preUpdate doctrine event
        // when you only change the password, since plainPassword
        // is not persisted on the entity, doctrine does not watch for it.
        $this->updatedAt = new \DateTime();
    }

    public function setLastLogin(?\DateTimeInterface $time = null): void
    {
        $this->lastLogin = $time;
    }

    public function setConfirmationToken(?string $confirmationToken): void
    {
        $this->confirmationToken = $confirmationToken;
    }

    public function setPasswordRequestedAt(?\DateTimeInterface $date = null): void
    {
        $this->passwordRequestedAt = $date;
    }

    public function getPasswordRequestedAt(): ?\DateTimeInterface
    {
        return $this->passwordRequestedAt;
    }

    public function isPasswordRequestNonExpired(int $ttl): bool
    {
        $passwordRequestedAt = $this->getPasswordRequestedAt();

        return null !== $passwordRequestedAt && $passwordRequestedAt->getTimestamp() + $ttl > time();
    }

    public function setRoles(array $roles): void
    {
        $this->roles = [];

        foreach ($roles as $role) {
            $this->addRole($role);
        }
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt = null): void
    {
        $this->createdAt = $createdAt;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt = null): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getRealRoles(): array
    {
        return $this->roles;
    }

    public function setRealRoles(array $roles): void
    {
        $this->setRoles($roles);
    }

    public function prePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable('now',new \DateTimeZone('UTC'));
    }

    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public function getStatus():bool{
        
        return $this->isEnabled();
    }

    /**
     * Get the value of tokenRequestedAt
     */
    public function getTokenRequestedAt(): ?\DateTimeInterface
    {
        return $this->tokenRequestedAt;
    }

    /**
     * Set the value of tokenRequestedAt
     */
    public function setTokenRequestedAt(?\DateTimeInterface  $tokenRequestedAt): void
    {
        $this->tokenRequestedAt = $tokenRequestedAt;
    }

    /**
     * Get the value of isEmailVerified
     */
    public function isEmailVerified(): bool
    {
        return $this->emailVerified ?? false;
    }

    
    public function setIsEmailVerified(bool $isEmailVerified): void
    {
        $this->emailVerified = $isEmailVerified;
    }

    public function getEmailVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function setEmailVerifiedAt(?\DateTimeImmutable $emailVerifiedAt): void
    {
        $this->emailVerifiedAt = $emailVerifiedAt;
    }

    public function hasPassword(): bool
    {
        // Utilise une vérification stricte pour s'assurer que le mot de passe n'est pas NULL et n'est pas vide.
        return $this->password !== null && $this->password !== '';
    }

    /**
     * Get the value of slug
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): void
    {
        $this->slug = $slug;
    }

    public function isEqualTo(UserInterface $user): bool
    {
        if (!$user instanceof self) {
            return false;
        }

        if ($this->password !== $user->getPassword()) {
            return false;
        }

        if ($this->getUserIdentifier() !== $user->getUserIdentifier()) {
            return false;
        }

        return true;
    }
}




