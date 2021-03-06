<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace eZ\Publish\Core\Event\Content;

use eZ\Publish\API\Repository\Events\Content\DeleteTranslationEvent as DeleteTranslationEventInterface;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use Symfony\Contracts\EventDispatcher\Event;

final class DeleteTranslationEvent extends Event implements DeleteTranslationEventInterface
{
    /** @var \eZ\Publish\API\Repository\Values\Content\ContentInfo */
    private $contentInfo;

    private $languageCode;

    public function __construct(
        ContentInfo $contentInfo,
        $languageCode
    ) {
        $this->contentInfo = $contentInfo;
        $this->languageCode = $languageCode;
    }

    public function getContentInfo(): ContentInfo
    {
        return $this->contentInfo;
    }

    public function getLanguageCode()
    {
        return $this->languageCode;
    }
}
