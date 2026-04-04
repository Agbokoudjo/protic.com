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

interface BaseUserInterface 
{
    public const ROLE_DEFAULT = 'ROLE_USER';

    /**
     * @return int|string|null
     */
    public function getId();

    public function setUsername(string $username): void;

    public function getUsername(): ?string;
    
    public function getUsernameCanonical(): ?string;

    public function setUsernameCanonical(?string $usernameCanonical): void;

    public function setSalt(?string $salt): void;

    public function getEmail(): ?string;

    public function setEmail(?string $email): void;

    public function getEmailCanonical(): ?string;

    public function setEmailCanonical(?string $emailCanonical): void;

    public function getPlainPassword(): ?string;

    public function setPlainPassword(?string $password): void;

    public function setPassword(?string $password): void;

    /**
     * Checks if the user has a hashed password defined in the database.
     * This is crucial for determining local login capability vs. external authentication.
     *
     * @return bool Returns true if the 'password' property is not null and not an empty string.
     */
    public function hasPassword():bool ;

    public function setEnabled(bool $enabled): void;

    public function getConfirmationToken(): ?string;

    public function setConfirmationToken(?string $confirmationToken): void;

    public function getPasswordRequestedAt(): ?\DateTimeInterface;

    public function setPasswordRequestedAt(?\DateTimeInterface $date = null): void;

    public function isPasswordRequestNonExpired(int $ttl): bool;

    public function setLastLogin(?\DateTimeInterface $time = null): void;

    public function hasRole(string $role): bool;

    /**
     * @param string[] $roles
     */
    public function setRoles(array $roles): void;

    public function addRole(string $role): void;

    public function removeRole(string $role): void;

    public function isAccountNonExpired(): bool;

    public function isAccountNonLocked(): bool;

    public function isCredentialsNonExpired(): bool;

    public function isEnabled(): bool;

    public function getStatus(): bool;

    public function setCreatedAt(?\DateTimeInterface $createdAt = null): void;

    public function getCreatedAt(): ?\DateTimeInterface;

    public function setUpdatedAt(?\DateTimeInterface $updatedAt = null): void;

    public function getUpdatedAt(): ?\DateTimeInterface;

    /**
     * @return string[]
     */
    public function getRealRoles(): array;

    /**
     * @param string[] $roles
     */
    public function setRealRoles(array $roles): void;
    
    public function getUserIdentifier(): string;
    
    public function eraseCredentials(): void;

    public function prePersist(): void;

    public function preUpdate(): void;

    public function getRolePrincipal(): string;

    public function getLastLogin(): ?\DateTimeInterface;

    public function getTokenRequestedAt(): ?\DateTimeInterface;

    public function setTokenRequestedAt(?\DateTimeInterface $tokenRequestedAt): void ;

    public function setIsEmailVerified(bool $isEmailVerified): void ;

    public function getEmailVerifiedAt(): ?\DateTimeImmutable ;

    public function setEmailVerifiedAt(?\DateTimeImmutable $emailVerifiedAt):void ;

    public function isEmailVerified(): bool ;

    public function getSlug(): ?string;

    public function setSlug(?string $slug): void;
}
