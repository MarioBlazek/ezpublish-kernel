<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace eZ\Publish\Core\Event\Language;

use eZ\Publish\API\Repository\Events\Language\DeleteLanguageEvent as DeleteLanguageEventInterface;
use eZ\Publish\API\Repository\Values\Content\Language;
use Symfony\Contracts\EventDispatcher\Event;

final class DeleteLanguageEvent extends Event implements DeleteLanguageEventInterface
{
    /** @var \eZ\Publish\API\Repository\Values\Content\Language */
    private $language;

    public function __construct(Language $language)
    {
        $this->language = $language;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }
}
