<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Seller\Contracts\DocumentVerifierInterface;

trait FakesDocumentVerification
{
    protected function fakeDocumentVerifier(
        string $personName = 'Azam Ali',
        string $businessName = 'Zimal Fabrics Pvt Ltd',
    ): FakeDocumentVerifier {
        $fake = new FakeDocumentVerifier($personName, $businessName);

        $this->app->instance(DocumentVerifierInterface::class, $fake);

        return $fake;
    }
}
