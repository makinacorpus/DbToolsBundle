<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Mock;

use RuntimeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\MetadataInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilder;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TestingExecutionContext implements ExecutionContextInterface
{
    private ConstraintViolationList $violationList;

    public function __construct()
    {
        $this->violationList = new ConstraintViolationList();
    }

    /**
     * {@inheritdoc}
     */
    public function addViolation(string $message, array $params = []): void
    {
        $this->violationList->add(new ConstraintViolation($message, null, $params, null, null, null));
    }

    /**
     * {@inheritdoc}
     */
    public function buildViolation(string $message, array $parameters = []): ConstraintViolationBuilderInterface
    {
        return new ConstraintViolationBuilder(
            $this->violationList,
            null,
            'Invalid.',
            [],
            null,
            null,
            null,
            new class () implements TranslatorInterface {
                public function trans(string $id, array $parameters = [], string $domain = null, string $locale = null): string
                {
                    return $id;
                }
                public function getLocale(): string
                {
                    return 'C';
                }
            },
            null
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getValidator(): ValidatorInterface
    {
        throw new RuntimeException("Testing execution context does not allow this.");
    }

    /**
     * {@inheritdoc}
     */
    public function getObject(): ?object
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function setNode(mixed $value, ?object $object, MetadataInterface $metadata = null, string $propertyPath): void {}

    /**
     * {@inheritdoc}
     */
    public function setGroup(?string $group): void {}

    /**
     * {@inheritdoc}
     */
    public function setConstraint(Constraint $constraint): void {}

    /**
     * {@inheritdoc}
     */
    public function markGroupAsValidated(string $cacheKey, string $groupHash): void {}

    /**
     * {@inheritdoc}
     */
    public function isGroupValidated(string $cacheKey, string $groupHash): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function markConstraintAsValidated(string $cacheKey, string $constraintHash): void {}

    /**
     * {@inheritdoc}
     */
    public function isConstraintValidated(string $cacheKey, string $constraintHash): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function markObjectAsInitialized(string $cacheKey): void {}

    /**
     * {@inheritdoc}
     */
    public function isObjectInitialized(string $cacheKey): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violationList;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoot(): mixed
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue(): mixed
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata(): ?MetadataInterface
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroup(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassName(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyName(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyPath(string $subPath = ''): string
    {
        return '';
    }
}
