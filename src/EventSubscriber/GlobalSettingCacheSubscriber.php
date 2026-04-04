<?php

namespace App\EventSubscriber;

use App\Entity\GlobalSetting;
use App\Service\GlobalSettingProvider;
use Sonata\AdminBundle\Event\PersistenceEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class GlobalSettingCacheSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly GlobalSettingProvider $globalSettingProviderCache) {}

    public static function getSubscribedEvents(): array
    {
        return [
            'sonata.admin.event.persistence.post_persist' => 'invalidate',
            'sonata.admin.event.persistence.post_update' => 'invalidate',
            'sonata.admin.event.persistence.post_remove' => 'invalidate',
        ];
    }

    private function invalidate(PersistenceEvent $event): void
    {
        $entity = $event->getObject();

        if ($entity instanceof GlobalSetting) {
            $this->globalSettingProviderCache->clearCache();
        }
    }
}
