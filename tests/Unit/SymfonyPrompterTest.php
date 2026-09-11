<?php

declare(strict_types=1);

use RalfHortt\WpCliShared\Support\SymfonyPrompter;

it('allows optional multichoice empty input', function (): void {
    $result = SymfonyPrompter::parseMultiChoiceInput('', [
        'Medien (attachment)',
        'Seiten (page)',
        'Beitraege (post)',
    ]);

    expect($result)->toBe([]);
});

it('parses comma separated numeric indexes', function (): void {
    $result = SymfonyPrompter::parseMultiChoiceInput('1,3', [
        'Medien (attachment)',
        'Seiten (page)',
        'Beitraege (post)',
    ]);

    expect($result)->toBe([
        'Medien (attachment)',
        'Beitraege (post)',
    ]);
});

it('parses comma separated labels', function (): void {
    $result = SymfonyPrompter::parseMultiChoiceInput('Seiten (page), Beitraege (post)', [
        'Medien (attachment)',
        'Seiten (page)',
        'Beitraege (post)',
    ]);

    expect($result)->toBe([
        'Seiten (page)',
        'Beitraege (post)',
    ]);
});

it('throws on invalid multichoice value', function (): void {
    SymfonyPrompter::parseMultiChoiceInput('999', [
        'Medien (attachment)',
        'Seiten (page)',
        'Beitraege (post)',
    ]);
})->throws(RuntimeException::class, 'Value "999" is invalid.');
