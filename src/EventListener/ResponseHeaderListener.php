<?php

declare(strict_types=1);

/*
 * This file is part of the project by AGBOKOUDJO Franck.
 *
 * (c) AGBOKOUDJO Franck <franckagbokoudjo301@gmail.com>
 * Phone: +229 0167 25 18 86
 * LinkedIn: https://www.linkedin.com/in/internationales-web-services-120520193/
 * Github: https://github.com/Agbokoudjo/norldfinance.com
 * Company: INTERNATIONALES WEB SERVICES
 *
 * For more information, please feel free to contact the author.
 */

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @author AGBOKOUDJO Franck <franckagbokoudjo301@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
#[AsEventListener(event: KernelEvents::RESPONSE, priority: -100)]
class ResponseHeaderListener
{
    public function __invoke(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Turbo-Charged-By', 'INTERNATIONALES WEB APPS & SERVICES');
          $response->headers->set('X-Created-By', 'INTERNATIONALES WEB APPS & SERVICES');
        $response->headers->set('Server', 'INTERNATIONALES WEB APPS & SERVICES SERVER');
        $response->headers->set('X-Powered-By', '+229 0167251886');
    }
}
