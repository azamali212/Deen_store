<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog\Enums;

use App\Domain\Catalog\Enums\MediaType;
use PHPUnit\Framework\TestCase;

final class MediaTypeTest extends TestCase
{
    public function test_values_are_derived_from_the_cases(): void
    {
        self::assertSame(['image', 'video'], MediaType::values());
    }

    public function test_image_accepts_only_the_three_web_safe_formats(): void
    {
        self::assertSame(
            ['image/jpeg', 'image/png', 'image/webp'],
            MediaType::IMAGE->allowedMimeTypes(),
        );
    }

    public function test_video_accepts_only_mp4_and_webm(): void
    {
        self::assertSame(['video/mp4', 'video/webm'], MediaType::VIDEO->allowedMimeTypes());
    }

    /**
     * Guards the upload rule itself: every mime type must be of the family the
     * case claims. An 'image/svg+xml' slipping into the IMAGE list would be an
     * upload path for scriptable content, and a plain list of strings in a
     * migration would never have caught it.
     */
    public function test_each_mime_type_belongs_to_its_own_family(): void
    {
        foreach (MediaType::cases() as $case) {
            foreach ($case->allowedMimeTypes() as $mime) {
                self::assertStringStartsWith($case->value . '/', $mime);
            }
        }
    }

    public function test_only_video_needs_a_poster_frame(): void
    {
        self::assertTrue(MediaType::VIDEO->requiresPosterFrame());
        self::assertFalse(MediaType::IMAGE->requiresPosterFrame());
    }

    public function test_every_case_answers_every_question(): void
    {
        foreach (MediaType::cases() as $case) {
            self::assertNotEmpty($case->allowedMimeTypes());
            self::assertNotEmpty($case->allowedExtensions());
            self::assertIsBool($case->requiresPosterFrame());
            self::assertNotSame('', $case->label());
        }
    }
}
