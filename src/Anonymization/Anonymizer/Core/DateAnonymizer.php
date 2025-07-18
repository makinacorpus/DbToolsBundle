<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractSingleColumnAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;
use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Vendor;
use MakinaCorpus\QueryBuilder\Query\Update;

#[AsAnonymizer(
    name: 'date',
    pack: 'core',
    description: <<<TXT
    Anonymize a column by changing the date it contains.
    You can either choose a 'min' and a 'max' date, case in which a random date will
    be selected between these bounds, or alternatively set a 'delta' which must be
    a valid date interval string (e.g. "1 week", "1 day 10 hours", ...).
    You should set the 'format' (default: 'datetime') value as this anonymizator
    can work with 'datetime' or 'date' formats.
    TXT
)]
class DateAnonymizer extends AbstractSingleColumnAnonymizer
{
    #[\Override]
    protected function validateOptions(): void
    {
        $format = $this->options->get('format', 'datetime');
        if (!\in_array($format, ['date', 'datetime'])) {
            throw new \InvalidArgumentException(\sprintf("'format' value is invalid, expected 'date' or 'datetime', got '%s'.", $format));
        }

        $min = $this->options->getDate('min');
        $max = $this->options->getDate('max');
        if (($min && !$max) || ($max && !$min)) {
            throw new \InvalidArgumentException("You must specify both 'min' and 'max' boundaries.");
        }

        // @phpstan-ignore-next-line False positive detected.
        if ($min && $max) {
            if ($max <= $min) {
                throw new \InvalidArgumentException("'min' value must be less than 'max' value.");
            }
            if ($this->options->has('delta')) {
                throw new \InvalidArgumentException("'delta' option cannot be specified if 'min' and 'max' are in use.");
            }
        } else {
            $this->options->getInterval('delta', null, true);
        }
    }

    #[\Override]
    public function createAnonymizeExpression(Update $update): Expression
    {
        $format = $this->options->get('format', 'datetime');

        $min = $this->options->getDate('min');
        $max = $this->options->getDate('max');

        if ($min && $max) {
            return $this->anonymizeWithDateRange($update, $format, $min, $max);
        }

        if ($delta = $this->options->getInterval('delta')) {
            return $this->anonmizeWithDelta($update, $format, $delta);
        }

        throw new \InvalidArgumentException("Providing either the 'delta' option, or both 'min' and 'max' options is required.");
    }

    private function anonymizeWithDateRange(Update $update, string $format, \DateTimeImmutable $min, \DateTimeImmutable $max): Expression
    {
        $diff = $max->diff($min, true);

        if ('date' === $format) {
            // Compute a diff in number of days.
            $unit = 'day';
            $delta = $diff->d + $diff->m * 30 + $diff->y * 360;
        } elseif (68 < $diff->y) {
            // We hit UNIX timestamp maximum integer limit, and may cause
            // int overflow or other kind of crashes server side. In order to
            // do this, we lower the granularity to hours.
            $unit = 'hour';
            $delta = $diff->h + $diff->d * 24 + $diff->m * 720 + $diff->y * 8640;
        } else {
            $unit = 'second';
            $delta = $diff->s + $diff->i * 60 + $diff->h * 3600 + $diff->d * 86400 + $diff->m * 2592000 + $diff->y * 31104000;
        }

        // Cut in half to compute middle date.
        $delta /= 2;
        $middleDate = $min->add(\DateInterval::createFromDateString(\sprintf("%d %s", $delta, $unit)));

        return $this->anonymizeWithDeltaAndReferenceDate($update, $format, $middleDate, $delta, $unit);
    }

    private function anonmizeWithDelta(Update $update, string $format, \DateInterval $delta): Expression
    {
        // @todo I wish for a better alternative...
        // query-builder can deal with \DateInterval by- itself, but we are
        // randomizing values here, so we need to be able to apply a single
        // figure random delta, in order to be able to use SQL random at the
        // right place, otherwise the algorithm would be very complex..
        // In order to achieve this, we arbitrarily converted a month to 30
        // days, we are working on an interval value hence we cannot guess
        // which will be the exact impacted month duration in days. This will
        // create a deviation where the interval may be more or less a few
        // days than the user expected, it's an acceptable deviation.
        if ('date' !== $format && $delta->s) {
            // Please, never use seconds...
            $delta = $delta->s + $delta->i * 60 + $delta->h * 3600 + $delta->d * 86400 + $delta->m * 2592000 + $delta->y * 31104000;
            $unit = 'second';
        } elseif ('date' !== $format && $delta->i) {
            $delta = $delta->i + $delta->h * 60 + $delta->d * 1440 + $delta->m * 43200 + $delta->y * 518400;
            $unit = 'minute';
        } elseif ('date' !== $format && $delta->h) {
            $delta = $delta->h + $delta->d * 24 + $delta->m * 720 + $delta->y * 8640;
            $unit = 'hour';
        } elseif ($delta->d) {
            $delta = $delta->d + $delta->m * 30 + $delta->y * 360;
            $unit = 'day';
        } elseif ($delta->m) {
            $delta = $delta->m + $delta->y * 12;
            $unit = 'month';
        } elseif ($delta->y) {
            $delta = $delta->y;
            $unit = 'year';
        } else {
            throw new \InvalidArgumentException("'delta' option interval is empty.");
        }

        $expr = $update->expression();
        $columnExpr = $expr->column($this->columnName, $this->tableName);

        return $this->anonymizeWithDeltaAndReferenceDate($update, $format, $columnExpr, $delta, $unit);
    }

    private function anonymizeWithDeltaAndReferenceDate(Update $update, string $format, mixed $referenceDate, int $delta, string $unit): Expression
    {
        $expr = $update->expression();

        $randomDeltaExpr = $this->getRandomIntExpression($delta, 0 - $delta);

        if ($this->databaseSession->vendorIs(Vendor::SQLITE)) {
            return $this->getSetIfNotNullExpression(
                $expr->dateAdd(
                    $referenceDate,
                    $expr->intervalUnit(
                        // This additional cast is necessary for SQLite only because it
                        // will mix up int addition and string concatenation, causing
                        // the interval string to be malformed. For all other vendors,
                        // it's a no-op.
                        $expr->cast($randomDeltaExpr, 'varchar'),
                        $unit
                    )
                )
            );
        } else {
            return $this->getSetIfNotNullExpression(
                $expr->cast(
                    $expr->dateAdd(
                        $referenceDate,
                        $expr->intervalUnit($randomDeltaExpr, $unit),
                    ),
                    'date' === $format ? 'date' : 'timestamp',
                )
            );
        }
    }
}
