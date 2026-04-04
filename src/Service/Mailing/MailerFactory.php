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
use App\Service\Mailing\PriorityInterface;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
//use Symfony\Component\Mime\Crypto\DkimSigner;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;

/**
 * Implémentation du factory pour la création et l'envoi d'emails.
 * 
 * Supporte l'envoi asynchrone via queue, la signature DKIM,
 * et la gestion de multiples configurations d'expéditeurs.
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package App\Infrastructure\Service\Mailing
 */
final class MailerFactory implements MailerFactoryInterface,PriorityInterface
{
    /**
     * @param string|null $dkimKey Chemin vers la clé privée DKIM
     * @param array<string, array{address: string, name: string}> $fromAddresses Configuration des expéditeurs
     */
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly array $fromAddresses = []
    ) {}

    /**
     * {@inheritdoc}
     */
    public function sendAsync(Email $email): void 
    {
        $this->sendNow($email);
    }

    /**
     * {@inheritdoc}
     */
    public function sendNow(Email $email): void
    {
        try {
            // Envoyer via le transport sélectionné
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            throw new RuntimeException(
                sprintf('Échec de l\'envoi de l\'email: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createTemplateEmail(
        string $senderAddress,
        string $senderName,
        string|array $recipientAddress,
        string $subject,
        string $templatePath,
        ?array $context
    ): Email {
        $this->validateEmailAddress($senderAddress, 'senderAddress');
        $this->validateEmailAddress($recipientAddress, 'recipientAddress');

        $this->validateSubject($subject);

        $templateEmail = (new TemplatedEmail())
            ->from(new Address($senderAddress, $senderName))
            ->to(...(is_array($recipientAddress) ? $recipientAddress : [$recipientAddress]))
            ->subject($subject)
            ->htmlTemplate($templatePath);

        if ($context !== null && !empty($context)) {
            $templateEmail->context($context);
        }

        return $templateEmail;
    }

    /**
     * @return Email
     */
    public function addTextHeader(Email $email ,string $type, string $name, $body): Email
    {
        $email->getHeaders()->addTextHeader($type, $name, $body);

        return $email;
    }

    /**
     * Applique la signature DKIM si configurée.
     *
     * @param Email $email L'email à signer
     * @return Email|Message L'email signé ou l'email original
     */
    /*private function applyDkimSignature(Email $email): Email|Message
    {
        if ($this->dkimKey === null || empty($this->domainName)) {
            return $email;
        }

        try {
            $keyPath = str_starts_with($this->dkimKey, 'file://')
                ? $this->dkimKey
                : "file://{$this->dkimKey}";

            $dkimSigner = new DkimSigner($keyPath, $this->domainName, 'default');
            $message = new Message($email->getPreparedHeaders(), $email->getBody());

            return $dkimSigner->sign($message);
        } catch (\Exception $e) {
            // Log l'erreur mais continue sans DKIM plutôt que de bloquer l'envoi
            error_log(sprintf('Échec signature DKIM: %s', $e->getMessage()));
            return $email;
        }
    }*/

    /**
     * @param string|array $email L'adresse ou liste d'adresses à valider
     */
    private function validateEmailAddress(string|array $email, string $paramName): void
    {
        // Si c'est un tableau, on valide chaque élément récursivement
        if (is_array($email)) {
            foreach ($email as $address) {
                $this->validateEmailAddress($address, $paramName);
            }
            return;
        }

        // Validation standard pour une string
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(
                sprintf('Le paramètre "%s" doit être une adresse email valide. Reçu: "%s"', $paramName, $email)
            );
        }
    }

    /**
     * Récupère la configuration d'un expéditeur par type.
     *
     * @param string $type Le type d'expéditeur (ex: 'system', 'noreply', 'support')
     * 
     * @return array{address: string, name: string} Configuration de l'expéditeur
     * 
     * @throws RuntimeException Si le type n'existe pas
     */
    public function fromConfig(string $type = 'system'): array
    {
        if (!isset($this->fromAddresses[$type])) {
            throw new RuntimeException(
                sprintf(
                    'Type d\'expéditeur "%s" inconnu. Types disponibles: %s',
                    $type,
                    implode(', ', array_keys($this->fromAddresses))
                )
            );
        }

        return $this->fromAddresses[$type];
    }

    /**
     * Valide que le sujet n'est pas vide.
     *
     * @throws InvalidArgumentException Si le sujet est vide
     */
    private function validateSubject(string $subject): void
    {
        if (trim($subject) === '' && empty($subject)) {
            throw new InvalidArgumentException('Le sujet de l\'email ne peut pas être vide');
        }
    }

    public function validatePriority(int $priority): void
    {
        if ($priority < self::PRIORITY_HIGHEST || $priority > self::PRIORITY_LOWEST) {
            throw new InvalidArgumentException(
                sprintf(
                    'La priorité doit être entre %d et %d. Reçu: %d',
                    self::PRIORITY_HIGHEST,
                    self::PRIORITY_LOWEST,
                    $priority
                )
            );
        }
    }
}
