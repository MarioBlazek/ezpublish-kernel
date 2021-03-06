<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace eZ\Publish\Core\Event\Content;

use eZ\Publish\API\Repository\Events\Content\CreateContentEvent as CreateContentEventInterface;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\ContentCreateStruct;
use Symfony\Contracts\EventDispatcher\Event;

final class CreateContentEvent extends Event implements CreateContentEventInterface
{
    /** @var \eZ\Publish\API\Repository\Values\Content\ContentCreateStruct */
    private $contentCreateStruct;

    /** @var array */
    private $locationCreateStructs;

    /** @var \eZ\Publish\API\Repository\Values\Content\Content */
    private $content;

    public function __construct(
        Content $content,
        ContentCreateStruct $contentCreateStruct,
        array $locationCreateStructs
    ) {
        $this->content = $content;
        $this->contentCreateStruct = $contentCreateStruct;
        $this->locationCreateStructs = $locationCreateStructs;
    }

    public function getContentCreateStruct(): ContentCreateStruct
    {
        return $this->contentCreateStruct;
    }

    public function getLocationCreateStructs(): array
    {
        return $this->locationCreateStructs;
    }

    public function getContent(): Content
    {
        return $this->content;
    }
}
