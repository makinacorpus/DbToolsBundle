<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Mock;

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

    #[\Override]
    public function addViolation(string $message, array $params = []): void
    {
        $this->violationList->add(new ConstraintViolation($message, null, $params, null, null, null));
    }

    #[\Override]
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
            new class () implements TranslatorInterface
            {
                #[\Override]
                public function trans(string $id, array $parameters = [], string $domain = null, string $locale = null): string
                {
                    return $id;
                }

                #[\Override]
                public function getLocale(): string
                {
                    return 'C';
                }
            },
            null
        );
    }

    #[\Override]
    public function getValidator(): ValidatorInterface
    {
        throw new \RuntimeException("Testing execution context does not allow this.");
    }

    #[\Override]
    public function getObject(): ?object
    {
        return null;
    }

    #[\Override]
    public function setNode(mixed $value, ?object $object, MetadataInterface $metadata = null, string $propertyPath): void {}

    #[\Override]
    public function setGroup(?string $group): void {}

    #[\Override]
    public function setConstraint(Constraint $constraint): void {}

    #[\Override]
    public function markGroupAsValidated(string $cacheKey, string $groupHash): void {}

    #[\Override]
    public function isGroupValidated(string $cacheKey, string $groupHash): bool
    {
        return false;
    }

    #[\Override]
    public function markConstraintAsValidated(string $cacheKey, string $constraintHash): void {}

    #[\Override]
    public function isConstraintValidated(string $cacheKey, string $constraintHash): bool
    {
        return false;
    }

    #[\Override]
    public function markObjectAsInitialized(string $cacheKey): void {}

    #[\Override]
    public function isObjectInitialized(string $cacheKey): bool
    {
        return true;
    }

    #[\Override]
    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violationList;
    }

    #[\Override]
    public function getRoot(): mixed
    {
        return null;
    }

    #[\Override]
    public function getValue(): mixed
    {
        return null;
    }

    #[\Override]
    public function getMetadata(): ?MetadataInterface
    {
        return null;
    }

    #[\Override]
    public function getGroup(): ?string
    {
        return null;
    }

    #[\Override]
    public function getClassName(): ?string
    {
        return null;
    }

    #[\Override]
    public function getPropertyName(): ?string
    {
        return null;
    }

    #[\Override]
    public function getPropertyPath(string $subPath = ''): string
    {
        return '';
    }
}
