<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace eZ\Publish\Core\Event\Trash;

use eZ\Publish\API\Repository\Events\Trash\BeforeRecoverEvent as BeforeRecoverEventInterface;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\TrashItem;
use Symfony\Contracts\EventDispatcher\Event;
use UnexpectedValueException;

final class BeforeRecoverEvent extends Event implements BeforeRecoverEventInterface
{
    /** @var \eZ\Publish\API\Repository\Values\Content\TrashItem */
    private $trashItem;

    /** @var \eZ\Publish\API\Repository\Values\Content\Location */
    private $newParentLocation;

    /** @var \eZ\Publish\API\Repository\Values\Content\Location|null */
    private $location;

    public function __construct(TrashItem $trashItem, ?Location $newParentLocation = null)
    {
        $this->trashItem = $trashItem;
        $this->newParentLocation = $newParentLocation;
    }

    public function getTrashItem(): TrashItem
    {
        return $this->trashItem;
    }

    public function getNewParentLocation(): ?Location
    {
        return $this->newParentLocation;
    }

    public function getLocation(): Location
    {
        if (!$this->hasLocation()) {
            throw new UnexpectedValueException(sprintf('Return value is not set or not a type of %s. Check hasLocation() or set it by setLocation() before you call getter.', Location::class));
        }

        return $this->location;
    }

    public function setLocation(?Location $location): void
    {
        $this->location = $location;
    }

    public function hasLocation(): bool
    {
        return $this->location instanceof Location;
    }
}
