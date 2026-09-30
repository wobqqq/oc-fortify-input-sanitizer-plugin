<?php

declare(strict_types=1);

use Wobqqq\FortifyInputSanitizer\Services\PatternMatcher;

arch('every file declares strict types')
    ->expect('Wobqqq\FortifyInputSanitizer')
    ->toUseStrictTypes();

arch('no debugging calls are left behind')
    ->expect(['dd', 'dump', 'var_dump', 'print_r', 'ray', 'die', 'exit'])
    ->not->toBeUsed();

arch('data transfer objects are immutable')
    ->expect('Wobqqq\FortifyInputSanitizer\Dto')
    ->toBeFinal()
    ->toBeReadonly();

arch('patterns are only run through the matcher that cannot fail a request')
    ->expect('preg_match')
    ->toOnlyBeUsedIn(PatternMatcher::class);
