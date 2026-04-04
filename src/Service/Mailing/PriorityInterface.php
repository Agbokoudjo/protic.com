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

namespace App\Service\Mailing;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package App\Application\Service\Mailer
 */
interface PriorityInterface
{
    /**
     * Priorité maximale (critique) - traité en premier.
     * 
     * Exemples : Réinitialisation de mot de passe, codes de vérification 2FA
     */
    public const PRIORITY_HIGHEST = 1;

    /**
     * Priorité haute - traité rapidement.
     * 
     * Exemples : Confirmation d'inscription, changement de mot de passe
     */
    public const PRIORITY_HIGH = 2;

    /**
     * Priorité normale (par défaut) - traité dans l'ordre standard.
     * 
     * Exemples : Notifications de compte, emails informatifs
     */
    public const PRIORITY_NORMAL = 3;

    /**
     * Priorité basse - traité quand le système est moins chargé.
     * 
     * Exemples : Newsletters, résumés hebdomadaires
     */
    public const PRIORITY_LOW = 4;

    /**
     * Priorité minimale - traité en dernier.
     * 
     * Exemples : Statistiques mensuelles, rapports automatiques
     */
    public const PRIORITY_LOWEST = 5;

}
