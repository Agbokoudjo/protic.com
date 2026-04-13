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

use App\CommandHandler\UpdateUserProfileHandler;
use App\Entity\BaseUserInterface;
use App\Queue\AsyncMethodDispatcherInterface;
use App\Security\Provider\UserProvider;
use Sonata\AdminBundle\Event\PersistenceEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event Subscriber pour les événements Doctrine des utilisateurs
 * 
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
final readonly class UserEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AsyncMethodDispatcherInterface $enqueueMethod,
        private UserProvider $userProvider
        ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            'sonata.admin.event.persistence.post_update' => ['postUpdate',500],
            'sonata.admin.event.persistence.post_persist'=>['postPersit',500]
          
        ];
    }

    final public function postPersit(PersistenceEvent $sonataEvent): void
    {
        $entity = $sonataEvent->getObject();
        if (!$entity instanceof BaseUserInterface) return;

        $this->dispatchUpdateCommand($entity);
    }


    final public function postUpdate(PersistenceEvent $sonataEvent): void
    {
        $entity=$sonataEvent->getObject();
        if (!$entity instanceof BaseUserInterface) return;

        $this->dispatchUpdateCommand($entity);
        $this->userProvider->invalidateUserCache($entity->getId());
    }
    
    private function dispatchUpdateCommand(BaseUserInterface $entity): void
    {
        $this->enqueueMethod->dispatch(UpdateUserProfileHandler::class, 'handle', [$entity->getId()]);
    }
}
