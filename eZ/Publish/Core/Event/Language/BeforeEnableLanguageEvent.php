<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace eZ\Publish\Core\Event\Language;

use eZ\Publish\API\Repository\Events\Language\BeforeEnableLanguageEvent as BeforeEnableLanguageEventInterface;
use eZ\Publish\API\Repository\Values\Content\Language;
use Symfony\Contracts\EventDispatcher\Event;
use UnexpectedValueException;

final class BeforeEnableLanguageEvent extends Event implements BeforeEnableLanguageEventInterface
{
    /** @var \eZ\Publish\API\Repository\Values\Content\Language */
    private $language;

    /** @var \eZ\Publish\API\Repository\Values\Content\Language|null */
    private $enabledLanguage;

    public function __construct(Language $language)
    {
        $this->language = $language;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function getEnabledLanguage(): Language
    {
        if (!$this->hasEnabledLanguage()) {
            throw new UnexpectedValueException(sprintf('Return value is not set or not a type of %s. Check hasEnabledLanguage() or set it by setEnabledLanguage() before you call getter.', Language::class));
        }

        return $this->enabledLanguage;
    }

    public function setEnabledLanguage(?Language $enabledLanguage): void
    {
        $this->enabledLanguage = $enabledLanguage;
    }

    public function hasEnabledLanguage(): bool
    {
        return $this->enabledLanguage instanceof Language;
    }
}
