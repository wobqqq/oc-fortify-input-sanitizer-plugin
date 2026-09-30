<?php

declare(strict_types=1);

namespace Wobqqq\FortifyInputSanitizer\Validator\Rules;

use Lang;
use Wobqqq\FortifyInputSanitizer\Services\PatternMatcher;

final class InputSanitizerRegexRule
{
    /**
     * A pattern that does not compile would fail every request it is tried on.
     *
     * @param array<mixed, mixed> $params
     */
    public function validate(string $attribute, mixed $value, array $params): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return is_string($value) && PatternMatcher::compiles(trim($value));
    }

    public function message(): string
    {
        /** @var string $message */
        $message = Lang::get('validation.regex');

        return $message;
    }
}
