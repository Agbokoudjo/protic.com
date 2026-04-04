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

use App\Service\Mailing\EmailSenderInterface;
use App\Service\Mailing\PriorityInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Service d'envoi d'emails système automatisés.
 * 
 * Utilise la configuration 'system' pour envoyer des emails
 * de notification système (confirmation, réinitialisation, etc.).
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package App\Service\Mailing
 */
#[AutoconfigureTag(name:"app.system.mailer")]
final class SystemMailer implements EmailSenderInterface
{
    private const CONFIG_TYPE = 'system';

    public function __construct(
        private readonly MailerFactoryInterface $mailerFactory,
        private readonly ?LoggerInterface $logger = null
    ) {}

    /**
     * {@inheritdoc}
     */
    public function send(
        string|array $recipientAddress,
        string $subject,
        string $templatePath,
        array $context,
        int $priority = PriorityInterface::PRIORITY_HIGH
    ): void {

        $this->mailerFactory->validatePriority($priority);
        try {
            $fromConfig = $this->mailerFactory->fromConfig(self::CONFIG_TYPE);

            if (!isset($fromConfig['address'], $fromConfig['name'])) {
                throw new RuntimeException(
                    'Configuration système invalide : clés "address" et "name" requises'
                );
            }

            $systemEmail = $this->mailerFactory
                ->createTemplateEmail(
                $fromConfig['address'],
                $fromConfig['name'],
                $recipientAddress,
                $subject,
                $templatePath,
                $context
            )
            ->priority($priority);

            $systemEmail->getHeaders()->addTextHeader('X-Transport', self::CONFIG_TYPE);
            
            $this->mailerFactory->sendAsync($systemEmail);

            $this->logger?->info('Email système envoyé avec succès', [
                'recipient' => $recipientAddress,
                'subject' => $subject,
                'template' => $templatePath,
            ]);
        } catch (RuntimeException $e) {
            $this->logger?->error('Échec de la récupération de la configuration système', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            $this->logger?->error('Échec de l\'envoi de l\'email système', [
                'recipient' => $recipientAddress,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException(
                sprintf('Impossible d\'envoyer l\'email système : %s', $e->getMessage()),
                0,
                $e
            );
        }
    }
}
