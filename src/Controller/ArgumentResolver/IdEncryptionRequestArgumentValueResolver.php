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

namespace App\Controller\ArgumentResolver;

use App\Security\Encryption\IdEncryptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Service pour chiffrer et déchiffrer les IDs des entités
 * 
 * Utilise Base64 URL-SAFE pour éviter les conflits avec les routes
 * 
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
final class  IdEncryptionRequestArgumentValueResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly IdEncryptionInterface $encryptionService
    ) {}

     public function resolve(Request $request, ArgumentMetadata $argument): iterable{

        if (!$request->attributes->has('id')) {
            return [];
        }

        $encodedId = $request->attributes->get('id');

        if (is_numeric($encodedId) || empty($encodedId)) {
            return [];
        }

        try {
            // Décrypter l'ID
            $decodedId = $this->encryptionService->decryptId((string) $encodedId);
            // on Vérifie que le résultat est un entier valide ou une chaine valid
            if (!is_numeric($decodedId) || (int)$decodedId <= 0) {
                throw new \Exception(\sprintf("ID: %s décodé invalide.", $decodedId));
            }
            
            $request->attributes->set('id', (string)$decodedId);

            if($argument->getType() === Request::class){
                return [$request];
            }

            if ($argument->getName() !== 'id') {
                return [];
            }

            return [(int) $decodedId];
        } catch (\Exception $e) {
             // Pour les URLs avec un ID malformé, on lance un 404 pour empêcher toute fuite d'information.
              $request->attributes->set('id', null); 
             throw new NotFoundHttpException(\sprintf("Ressource introuvable : Identifiant invalide."));
        }
     }
}
