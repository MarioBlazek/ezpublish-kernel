<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace eZ\Publish\Core\Event\Location;

use eZ\Publish\API\Repository\Events\Location\UnhideLocationEvent as UnhideLocationEventInterface;
use eZ\Publish\API\Repository\Values\Content\Location;
use Symfony\Contracts\EventDispatcher\Event;

final class UnhideLocationEvent extends Event implements UnhideLocationEventInterface
{
    /** @var \eZ\Publish\API\Repository\Values\Content\Location */
    private $revealedLocation;

    /** @var \eZ\Publish\API\Repository\Values\Content\Location */
    private $location;

    public function __construct(
        Location $revealedLocation,
        Location $location
    ) {
        $this->revealedLocation = $revealedLocation;
        $this->location = $location;
    }

    public function getRevealedLocation(): Location
    {
        return $this->revealedLocation;
    }

    public function getLocation(): Location
    {
        return $this->location;
    }
}
