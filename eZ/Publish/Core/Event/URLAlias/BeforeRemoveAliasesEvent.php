<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace eZ\Publish\Core\Event\URLAlias;

use eZ\Publish\API\Repository\Events\URLAlias\BeforeRemoveAliasesEvent as BeforeRemoveAliasesEventInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class BeforeRemoveAliasesEvent extends Event implements BeforeRemoveAliasesEventInterface
{
    /** @var array */
    private $aliasList;

    public function __construct(array $aliasList)
    {
        $this->aliasList = $aliasList;
    }

    public function getAliasList(): array
    {
        return $this->aliasList;
    }
}
