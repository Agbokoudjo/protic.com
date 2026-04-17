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

use App\CommandHandler\TeamMemberSyncHandler;
use App\CommandHandler\UpdatePasswordUserHandler;
use App\CommandHandler\UpdateUserProfileHandler;
use App\Entity\BaseUserInterface;
use App\Entity\SonataUser;
use App\Queue\AsyncMethodDispatcherInterface;
use App\Security\Provider\UserProvider;
use Doctrine\ORM\EntityManagerInterface;
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
        private EntityManagerInterface $em,
        private AsyncMethodDispatcherInterface $enqueueMethod,
        private UserProvider $userProvider
        ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            'sonata.admin.event.persistence.post_update' => ['postUpdate',500],
            'sonata.admin.event.persistence.pre_update' => ['preUpdate', 500],
            'sonata.admin.event.persistence.post_persist'=>['postPersit',500]
          
        ];
    }

    final public function postPersit(PersistenceEvent $sonataEvent): void
    {
        $entity = $sonataEvent->getObject();
        if (!$entity instanceof SonataUser) return;

        $this->dispatchUpdateCommand($entity);

        if (!$entity->isMember()) {
            return;
        }

        $this->enqueueMethod->dispatch(TeamMemberSyncHandler::class, 'createOrUpdateTeamMember', [$entity->getId()]);
    }

    final public function preUpdate(PersistenceEvent $event):void {

        $adminUser=$event->getObject() ;
        if (!($adminUser instanceof SonataUser)) return;

        $plainPassword=$adminUser->getPlainPassword() ;

        if($plainPassword){
            $this->enqueueMethod->dispatch(
                UpdatePasswordUserHandler::class, 
                    'handle', [
                    $adminUser->getId(),
                    $plainPassword 
                    ]
                    );
        }
    }

    final public function postUpdate(PersistenceEvent $sonataEvent): void
    {
        $entity=$sonataEvent->getObject();
        if (!$entity instanceof SonataUser) return;

        $this->dispatchUpdateCommand($entity);
        $this->userProvider->invalidateUserCache($entity->getId());


        // Récupère le changeset pour détecter ce qui a changé
        $changeSet = $this->em->getUnitOfWork()->getEntityChangeSet($entity);

        $teamFields = ['isMember', 'teamBio', 'teamPosition', 'teamInitial', 'teamAltText', 'avatarName'];
        $hasTeamChange = array_intersect_key($changeSet, array_flip($teamFields)) !== [];

        if (!$hasTeamChange) {
            return; // Rien de lié à l'équipe n'a changé → on ne touche à rien
        }

        if (!$entity->isMember()) {
            // isMember vient de passer à false → masquer le membre sans supprimer
            $this->enqueueMethod->dispatch(TeamMemberSyncHandler::class, 'deactivateTeamMember', [$entity->getId()]);
            return;
        }

        $this->enqueueMethod->dispatch(TeamMemberSyncHandler::class, 'createOrUpdateTeamMember', [$entity->getId()]);
    }
    
    private function dispatchUpdateCommand(BaseUserInterface $entity): void
    {
        $this->enqueueMethod->dispatch(UpdateUserProfileHandler::class, 'handle', [$entity->getId()]);
    }
}
