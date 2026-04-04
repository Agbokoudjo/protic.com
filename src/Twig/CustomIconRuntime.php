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

namespace App\Twig;

use Twig\Extension\RuntimeExtensionInterface;

final class CustomIconRuntime implements RuntimeExtensionInterface
{
    public function parseIcon(string $icon): string
    {
        if ('' === $icon || str_starts_with($icon, '<')) {
            return $icon;
        }

        // On autorise FontAwesome, Bootstrap Icons, et AdminLTE nav-icon
        if (
            str_starts_with($icon, 'fa ')
            || str_starts_with($icon, 'fas ')
            || str_starts_with($icon, 'far ')
            || str_starts_with($icon, 'fab ')
            || str_starts_with($icon, 'fal ')
            || str_starts_with($icon, 'fad ')
            || str_starts_with($icon, 'bi ')
            || str_starts_with($icon, 'nav-icon ')
        ) {
            return \sprintf('<i class="%s" aria-hidden="true"></i>', $icon);
        }
        return \sprintf('<i class="%s"></i>', $icon);
    }
}
