<?php

declare(strict_types=1);

namespace App\Tests\Dto\ListingReport;

use App\Dto\ListingReport\CreateListingReportRequest;
use App\Enum\ListingReportReason;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CreateListingReportRequestTest extends TestCase
{
    public function testRejectsCommentShorterThanThirtyCharacters(): void
    {
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $dto = new CreateListingReportRequest(
            ListingReportReason::Spam,
            'короткий текст',
        );

        $violations = $validator->validate($dto);

        self::assertGreaterThan(0, $violations->count());
    }

    public function testAcceptsCommentWithThirtyCharacters(): void
    {
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $comment = str_repeat('а', CreateListingReportRequest::COMMENT_MIN_LENGTH);
        $dto = new CreateListingReportRequest(
            ListingReportReason::Spam,
            $comment,
        );

        $violations = $validator->validate($dto);

        self::assertCount(0, $violations);
    }
}
