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

namespace App\Persistance;

use App\Domain\BaseUserInterface;
use App\Persistance\RepositoryInterface;
use Sonata\Doctrine\Model\ManagerInterface;

interface UserManagerInterface extends ManagerInterface , RepositoryInterface{
    public function updatePassword(BaseUserInterface $user): void;

    public function findUserByUsername(string $username): ?BaseUserInterface;

    public function findUserByEmail(string $email): ?BaseUserInterface;

    public function findUserByUsernameOrEmail(string $usernameOrEmail): ?BaseUserInterface;

    public function findUserByConfirmationToken(string $token): ?BaseUserInterface;

    public function findUserBySlug(string $slug): ?BaseUserInterface;

    public function find(mixed $id): ?object;
}