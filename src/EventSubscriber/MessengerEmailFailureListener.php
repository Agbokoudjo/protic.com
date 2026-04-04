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

namespace App\EventSubscriber;

use App\QueueHandler\AsyncMethodDispatcher;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Messenger\Transport\Receiver\ListableReceiverInterface;

final class MessengerEmailFailureListener implements EventSubscriberInterface{

    public function __construct(private readonly AsyncMethodDispatcher $asyncMethodDipatcher) {}

    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageFailedEvent::class => 'onMessageFailed',
        ];
    }

    public function onMessageFailed(WorkerMessageFailedEvent $event): void
    {
        $envelope= $event->getEnvelope();
        $message = $envelope->getMessage();

        if (!($message instanceof SendEmailMessage)) { return; }

        $originalEmail = $message->getMessage();
        $error = $event->getThrowable();

        if ($error instanceof TransportExceptionInterface && $originalEmail instanceof Email) {
            $errorMessage = $error->getMessage();
            $to = implode(', ', array_map(fn($addr) => $addr->getAddress(), $originalEmail->getTo()));

            $this->asyncMethodDipatcher
                ->dispatch(
                LoggerInterface::class,
                'error',
                [
                    sprintf(
                        'ÉCHEC DE L\'ENVOI ASYNCHRONE par Messenger à [%s]. Cause: %s',
                        $to,
                        $errorMessage
                    )
                ]
            );

            // --- Logique pour déterminer si l'échec est permanent (Hard Bounce) ---

            // Les serveurs SMTP signalent souvent les adresses non valides avec des codes d'erreur 5xx 
            // ou des messages spécifiques (ex: User unknown, Mailbox not found).

            // 1. Définir les indicateurs d'échec permanent (c'est souvent dépendant du transport utilisé !)
            $isPermanentFailure = false;

            // Exemple de vérification des codes 5xx, ou des messages spécifiques
            // (La méthode exacte dépend de la façon dont le TransportExceptionInterface expose le code SMTP)
            if (str_contains($errorMessage, 'User unknown') || str_contains($errorMessage, 'Mailbox not found') || preg_match('/^5\d{2}/', $errorMessage)) {
                $isPermanentFailure = true;
            }

            $receiver=$event->getReceiverName();
            if (!$receiver instanceof ListableReceiverInterface) {
                throw new \RuntimeException(\sprintf('The "%s" receiver does not support removing specific messages.', $receiver));
            }

            if ($isPermanentFailure) {
                // 2. Marquer la tâche comme ÉCHOUÉE DÉFINITIVEMENT
                // Cela empêche Messenger de relancer la tâche (Ignorer la Retry Strategy).
                $receiver->reject($envelope);

                $this->asyncMethodDipatcher
                    ->dispatch(
                        LoggerInterface::class,
                        'warning',
                        [
                        sprintf(
                            'ÉCHEC PERMANENT détecté pour l\'e-mail [%s]. La tâche NE SERA PAS relancée.',
                            $to
                        )
                        ]
                    );
            }
        }
    }
}