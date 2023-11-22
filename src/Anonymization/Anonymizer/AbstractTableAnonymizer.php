<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer;

/**
 * Use this class to write an anonymizer that works at the table level
 * instead of at a single column level.
 *
 * This will be used for validation purpose.
 */
abstract class AbstractTableAnonymizer extends AbstractAnonymizer {}
