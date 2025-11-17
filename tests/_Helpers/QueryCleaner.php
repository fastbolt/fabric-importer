<?php

/**
 * Copyright © Fastbolt Schraubengroßhandels GmbH.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fastbolt\FabricImporter\Tests\_Helpers;

trait QueryCleaner
{
    /**
     * Removes multi-spaces and linebreaks
     */
    private function cleanString(string $s): string
    {
        return trim(preg_replace('/\s+/', ' ', $s));
    }
}
