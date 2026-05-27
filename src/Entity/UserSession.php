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

use App\Repository\UserSessionRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Entité de gestion des sessions utilisateur uniques.
 *
 * Garantit qu'un utilisateur ne peut être connecté que sur un seul
 * navigateur / appareil à la fois. La logique de révocation repose sur
 * le champ `active` : une session révoquée est conservée pour l'audit
 * (SoftDeleteable) mais ignorée par SessionControlListener.
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 */
#[ORM\Entity(repositoryClass: UserSessionRepository::class)]
#[ORM\Table(name: 'user_sessions')]
#[ORM\Index(columns: ['user_identifier'], name: 'idx_user_session_identifier')]
#[ORM\Index(columns: ['session_id'],      name: 'idx_user_session_id')]
#[ORM\Index(columns: ['last_activity_at'], name: 'idx_user_session_last_activity')]
#[ORM\Index(columns: ['active'],           name: 'idx_user_session_active')]
#[Gedmo\SoftDeleteable]
class UserSession extends AbstractUserSession
{
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['property_serializer'])]
    protected int|string|null $id = null;

    /**
     * Identifiant métier de l'utilisateur (email, username, UUID…).
     * Stocké en string pour rester indépendant du type d'entité User.
     */
    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['property_serializer'])]
    protected string|int|null $userIdentifier = null;

    /**
     * ID de session PHP (PHPSESSID).
     * Unique en base : un ID ne peut appartenir qu'à une seule ligne.
     */
    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Groups(['property_serializer'])]
    protected ?string $sessionId = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['property_serializer'])]
    protected ?string $ipAddress = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Groups(['property_serializer'])]
    protected ?string $userAgent = null;

    /** Hash SHA-256 de (User-Agent + Accept-Language) pour détecter le changement d'appareil. */
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    protected ?string $deviceFingerprint = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['property_serializer'])]
    protected ?\DateTimeInterface $createdAt = null;

    /**
     * Horodatage de dernière activité.
     * Mis à jour à chaque requête (via updateSessionActivity dans le repository,
     * avec limitation par cache pour éviter un flush à chaque requête).
     */
    #[ORM\Column(type: 'datetime')]
    #[Groups(['property_serializer'])]
    protected ?\DateTimeInterface $lastActivityAt = null;

    public  function setId(int|string $_id):void{
        $this->id=$_id ;
    }

    /**
     * true  → session courante valide.
     * false → révoquée (connexion sur un autre appareil ou nettoyage inactivité).
     */
    #[ORM\Column(type: 'boolean')]
    protected bool $active = true;

    public function __construct()
    {
        $this->createdAt  = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->updateActivity() ;
        $this->active = true;
    }

    /**
     * Met à jour lastActivityAt à maintenant (UTC).
     * Appelé par updateSessionActivity() dans le repository,
     * limité à une fois par heure via le verrou cache.
     */
    public function updateActivity(): void
    {
        $this->setLastActivityAt(new \DateTime('now', new \DateTimeZone('UTC')));
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }
}
