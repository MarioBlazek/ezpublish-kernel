<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace eZ\Publish\API\Repository\Events\Content;

use eZ\Publish\SPI\Repository\Event\BeforeEvent;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\Relation;
use eZ\Publish\API\Repository\Values\Content\VersionInfo;

interface BeforeAddRelationEvent extends BeforeEvent
{
    public function getSourceVersion(): VersionInfo;

    public function getDestinationContent(): ContentInfo;

    public function getRelation(): Relation;

    public function setRelation(?Relation $relation): void;

    public function hasRelation(): bool;
}
