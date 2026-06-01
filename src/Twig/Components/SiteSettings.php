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

namespace App\Twig\Components;

use App\Entity\GlobalSetting;
use App\Service\GlobalSettingProvider;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class SiteSettings
{
    public function __construct(
        private readonly GlobalSettingProvider $globalSettingProvider){}

    /**
     * Récupère les paramètres (depuis le cache ou la DB)
     */
    public function getSettings(): ?GlobalSetting
    {
        return $this->globalSettingProvider->getSettings() ;
    }
}
