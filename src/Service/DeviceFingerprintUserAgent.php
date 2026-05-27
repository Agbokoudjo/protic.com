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

namespace App\Service;

use Symfony\Component\HttpFoundation\Request ;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
trait DeviceFingerprintUserAgent
{
   /**
     * Construit une empreinte appareil légère à partir des en-têtes HTTP.
     *
     * Ce hash permet de détecter un changement d'appareil sans stocker
     * de données personnelles supplémentaires (juste un SHA-256).
     */
    protected function buildDeviceFingerprint(Request $request): ?string
    {
        $ua       = $request->headers->get('User-Agent', '');
        $lang     = $request->headers->get('Accept-Language', '');
        $platform = $request->headers->get('Sec-CH-UA-Platform', '');

        if (empty($ua)) {
            return null;
        }

        return hash('sha256', implode('|', [$ua, $lang, $platform]));
    }
}