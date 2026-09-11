<?php

declare(strict_types=1);

namespace RalfHortt\WpCliShared\Support;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

final class SymfonyPrompter
{
    private static ?QuestionHelper $helper = null;

    private static ?InputInterface $input = null;

    private static ?OutputInterface $output = null;

    public static function isAvailable(): bool
    {
        return class_exists(QuestionHelper::class)
            && class_exists(ChoiceQuestion::class)
            && class_exists(ConfirmationQuestion::class)
            && class_exists(Question::class);
    }

    public static function askText(string $label, string $default = '', bool $required = true): string
    {
        if (! self::isAvailable()) {
            return FallbackPrompter::askText($label, $default, $required);
        }

        $question = new Question($label . ': ', $default !== '' ? $default : null);
        $question->setMaxAttempts(null);

        if ($required) {
            $question->setValidator(static function (?string $value) use ($default): string {
                $value = trim((string) $value);

                if ($value === '' && $default !== '') {
                    return $default;
                }

                if ($value === '') {
                    throw new \RuntimeException('Value is required.');
                }

                return $value;
            });
        }

        $answer = (string) self::helper()->ask(self::input(), self::output(), $question);

        return trim($answer);
    }

    public static function askConfirm(string $label, bool $default = false): bool
    {
        if (! self::isAvailable()) {
            return FallbackPrompter::askConfirm($label, $default);
        }

        $question = new ConfirmationQuestion(
            $label . ' ' . ($default ? '[Y/n]' : '[y/N]') . ': ',
            $default
        );

        return (bool) self::helper()->ask(self::input(), self::output(), $question);
    }

    /**
     * @param  array<int, string>  $choices
     */
    public static function askChoice(
        string $label,
        array $choices,
        ?string $default = null,
        bool $allowFreeText = false,
    ): string {
        if ($choices === []) {
            return self::askText($label, $default ?? '', $default === null);
        }

        if (! self::isAvailable()) {
            return FallbackPrompter::askChoice($label, $choices, $default);
        }

        if ($allowFreeText) {
            $question = new Question($label . ': ', $default);
            $question->setAutocompleterValues($choices);

            return trim((string) self::helper()->ask(self::input(), self::output(), $question));
        }

        $defaultIndex = null;

        if ($default !== null && $default !== '') {
            $index = array_search($default, $choices, true);

            if ($index !== false) {
                $defaultIndex = $index;
            }
        }

        $question = new ChoiceQuestion($label . ': ', $choices, $defaultIndex);
        $question->setAutocompleterValues($choices);
        $question->setErrorMessage('Value "%s" is invalid.');

        return (string) self::helper()->ask(self::input(), self::output(), $question);
    }

    /**
     * @param  array<int, string>  $choices
     * @return array<int, string>
     */
    public static function askMultiChoice(string $label, array $choices): array
    {
        if ($choices === []) {
            return [];
        }

        if (! self::isAvailable()) {
            return FallbackPrompter::askMultiChoice($label, $choices);
        }

        $question = new Question($label . ' (comma-separated, leave empty for none): ', '');
        $question->setAutocompleterValues($choices);

        $answer = self::helper()->ask(self::input(), self::output(), $question);

        if (! is_string($answer)) {
            return [];
        }

        return self::parseMultiChoiceInput($answer, $choices);
    }

    /**
     * @param  array<int, string>  $choices
     * @return array<int, string>
     */
    public static function parseMultiChoiceInput(string $answer, array $choices): array
    {
        $answer = trim($answer);

        if ($answer === '') {
            return [];
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $answer)), static fn (string $part): bool => $part !== ''));
        $numericParts = array_values(array_filter($parts, 'ctype_digit'));
        $useOneBasedIndexes = $numericParts !== []
            && count($numericParts) === count($parts)
            && ! in_array('0', $numericParts, true)
            && max(array_map('intval', $numericParts)) <= count($choices);

        $selected = [];

        foreach ($parts as $part) {
            if (ctype_digit($part)) {
                $index = (int) $part;

                if ($useOneBasedIndexes) {
                    $index -= 1;
                }

                if (isset($choices[$index])) {
                    $choice = $choices[$index];
                } else {
                    throw new \RuntimeException(sprintf('Value "%s" is invalid.', $part));
                }
            } elseif (in_array($part, $choices, true)) {
                $choice = $part;
            } else {
                throw new \RuntimeException(sprintf('Value "%s" is invalid.', $part));
            }

            if (! in_array($choice, $selected, true)) {
                $selected[] = $choice;
            }
        }

        return $selected;
    }

    private static function helper(): QuestionHelper
    {
        return self::$helper ??= new QuestionHelper();
    }

    private static function input(): InputInterface
    {
        return self::$input ??= new ArgvInput();
    }

    private static function output(): OutputInterface
    {
        return self::$output ??= new ConsoleOutput();
    }
}
