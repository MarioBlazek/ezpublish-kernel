<?php

/**
 * Contains Invalid Argument Type Exception implementation.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 *
 * @version //autogentag//
 */
namespace eZ\Publish\Core\Base\Exceptions;

use eZ\Publish\API\Repository\Exceptions\InvalidArgumentException as APIInvalidArgumentException;
use Exception;

/**
 * Invalid Argument Type Exception implementation.
 *
 * @use: throw new InvalidArgumentException( 'nodes', 'array' );
 */
class InvalidArgumentException extends APIInvalidArgumentException implements TranslatableExceptionInterface
{
    use TranslatableException;

    /**
     * Generates: "Argument '{$argumentName}' is invalid: {$whatIsWrong}".
     *
     * @param string $argumentName
     * @param string $whatIsWrong
     * @param \Exception|null $previous
     */
    public function __construct($argumentName, $whatIsWrong, Exception $previous = null)
    {
        $this->setMessageTemplate("Argument '%argumentName%' is invalid: %whatIsWrong%");
        $this->setParameters(['%argumentName%' => $argumentName, '%whatIsWrong%' => $whatIsWrong]);
        parent::__construct($this->getBaseTranslation(), 0, $previous);
    }
}
