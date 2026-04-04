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

namespace App\Queue;

/**
 * Interface AsyncMethodDispatcherInterface
 *
 * Définit le contrat pour l’envoi asynchrone d’un appel de méthode d’un service.
 * Cette interface permet de déléguer l’exécution d’une méthode à une file de messages (queue)
 * via un bus de messages, afin que le traitement soit effectué ultérieurement ou avec un délai défini.
 *
 * Exemple d’utilisation :
 *  - Exécuter une méthode métier après un certain délai.
 *  - Déléguer une tâche lourde à un worker asynchrone pour ne pas bloquer la requête HTTP.
 *
 * @method void dispatch(string $service, string $method, array $params = [], ?\DateTimeInterface $date = null)
 *   @param string $service Nom complet (FQCN) du service à invoquer.
 *   @param string $method Nom de la méthode du service à exécuter.
 *   @param array $params Paramètres à transmettre à la méthode appelée.
 *   @param \DateTimeInterface|null $date Date d’exécution différée (null = exécution immédiate).
 *  @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 *  @package App\Application\Queue
 */
interface AsyncMethodDispatcherInterface
{
    public function dispatch(string $service, string $method, array $params = [], ?\DateTimeInterface $date = null): void;
}
