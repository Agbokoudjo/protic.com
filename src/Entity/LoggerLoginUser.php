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

use App\Domain\BaseLoggerLoginUser;
use App\Repository\LoggerLoginUserRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;

/**
 * Entité Doctrine représentant un enregistrement de connexion utilisateur.
 *
 * Cette entité est intentionnellement en LECTURE SEULE après persistance :
 * les logs de connexion constituent un journal d'audit immuable.
 * Aucune méthode de mutation n'est exposée (sauf via fromDomain en création).
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
#[ORM\Entity(repositoryClass: LoggerLoginUserRepository::class)]
#[ORM\Table(name: 'logger_login_user')]
#[ORM\HasLifecycleCallbacks]
#[Gedmo\SoftDeleteable]
class LoggerLoginUser extends BaseLoggerLoginUser
{
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 200)]
    protected string $username;

    #[ORM\Column(type: 'string', length: 255)]
    protected string $email;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    protected ?string $lastLoginIp = null;

    #[ORM\Column(type: 'datetime_immutable')]
    protected \DateTimeImmutable $createdAt;

   
    public function __construct() {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return sprintf('%s <%s>', $this->username, $this->email);
    }
}
