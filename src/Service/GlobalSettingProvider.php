<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service;

use App\Entity\GlobalSetting;
use App\Repository\GlobalSettingRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class GlobalSettingProvider
{
    public function __construct(
        private readonly GlobalSettingRepository $repository,
        private readonly CacheInterface $cache
    ) {}

    /**
     * Récupère les paramètres (depuis le cache ou la DB)
     */
    public function getSettings(): ?GlobalSetting
    {
        return $this->cache->get('global_site_settings', function (ItemInterface $item) {
            // Expire après 24h par sécurité
            $item->expiresAfter(86400);

            // Ajout d'un tag pour une invalidation plus facile
            //$item->tag(['settings_tag']);

            return $this->repository->findOneBy([], ['id' => 'DESC']);
        });
    }

    /**
     * À appeler après chaque modification dans SonataAdmin
     * pour forcer la mise à jour des données
     */
    public function clearCache(): void
    {
        $this->cache->delete('global_site_settings');
    }
}
