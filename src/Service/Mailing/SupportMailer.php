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

use App\Service\Mailing\MailerFactoryInterface;
use App\Service\Mailing\SupportMailerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class SupportMailer implements SupportMailerInterface
{
    private const CONFIG_TYPE = 'support';

    public function __construct(
        private readonly MailerFactoryInterface $mailerFactory,
        private readonly ?LoggerInterface $logger = null) {}

    public function sendManager(
        string|array $recipientEmail,
        string $subject,
        string $htmlTemplate,
        ?array $context = null,
        ?string $senderEmail=null,
        ?string $replyToEmail = null,
        ?array $attachments = []
    ): void {

        $fromConfig = $this->mailerFactory->fromConfig(self::CONFIG_TYPE);

        if (!isset($fromConfig['address'], $fromConfig['name'])) {
            throw new RuntimeException(
                'Configuration système invalide : clés "address" et "name" requises'
            );
        }

        try {
            $supportEmail = $this->mailerFactory->createTemplateEmail(
                $senderEmail ??  $fromConfig['address'],
                $fromConfig['name'],
                $recipientEmail,
                $subject,
                $htmlTemplate,
                $context ?? []
            );

            if(!empty($attachments)){
                foreach ($attachments as $path => $name) {
                    // Si le fichier finit par une extension image, on l'embed (pour Twig)
                    // Sinon, on l'attache classiquement (PDF, etc.)
                    if (preg_match('/\.(jpg|jpeg|png|gif|svg)$/i', $path)) {
                        $supportEmail->embedFromPath($path, $name);
                    } else {
                        // Le deuxième paramètre est le nom que verra l'utilisateur
                        $supportEmail->attachFromPath($path, is_string($name) ? $name : null);
                    }
                }
            }

            $supportEmail->replyTo($replyToEmail ?? $senderEmail);

            $supportEmail->getHeaders()->addTextHeader('X-Transport', self::CONFIG_TYPE);
            
            $this->mailerFactory->sendAsync($supportEmail);

        } catch (RuntimeException $e) {
            $this->logger?->error('Échec de la récupération de la configuration support', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            $this->logger?->error('Échec de l\'envoi de l\'email support', [
                'recipient' =>  $recipientEmail,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException(
                sprintf('Impossible d\'envoyer l\'email support : %s', $e->getMessage()),
                0,
                $e
            );
        }
    }
}