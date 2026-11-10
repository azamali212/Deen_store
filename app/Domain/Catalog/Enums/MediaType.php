<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Enums;

/**
 * What a product_media row holds, and the technical facts that follow from it.
 *
 * NOTE WHAT IS DELIBERATELY NOT IN HERE: the maximum upload size. A size limit
 * is POLICY — the owner raises it on a Tuesday because sellers complained, and
 * that must be a config line, never a deploy (BLUEPRINT section 9). The mime
 * types below are not policy; they are what the word "image" MEANS to this
 * platform, and changing that list changes what the image pipeline (O-6) has
 * to be able to resize. That is the line between the two files.
 */
enum MediaType: string
{
    case IMAGE = 'image';
    case VIDEO = 'video';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<int, string>
     *
     * Used as `mimetypes:` in the upload request. Mime type, not extension:
     * an extension is just the end of a filename and anyone can rename
     * payload.php to payload.jpg, while `mimetypes:` makes Laravel read the
     * file's real bytes. Checking the extension alone is how an upload form
     * becomes a way to put executable code on the server.
     */
    public function allowedMimeTypes(): array
    {
        return match ($this) {
            self::IMAGE => ['image/jpeg', 'image/png', 'image/webp'],
            self::VIDEO => ['video/mp4', 'video/webm'],
        };
    }

    /**
     * @return array<int, string>
     *
     * For the stored filename and for the seller-facing error message only.
     * Never trusted as the check — see allowedMimeTypes().
     */
    public function allowedExtensions(): array
    {
        return match ($this) {
            self::IMAGE => ['jpg', 'jpeg', 'png', 'webp'],
            self::VIDEO => ['mp4', 'webm'],
        };
    }

    /**
     * True when the pipeline must produce a still frame before this row can be
     * shown anywhere.
     *
     * A video has nothing to put in a grid, a search result or an email. If
     * the poster frame is treated as optional the gallery silently falls back
     * to a grey box on the hottest read path in the system, so this is a
     * requirement of the row, not a nice-to-have.
     */
    public function requiresPosterFrame(): bool
    {
        return $this === self::VIDEO;
    }

    public function label(): string
    {
        return match ($this) {
            self::IMAGE => 'Photo',
            self::VIDEO => 'Video',
        };
    }
}
