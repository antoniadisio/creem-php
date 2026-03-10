<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use Creem\Tests\TestCase;

test('response fixtures are complete for the coverage manifest', function (): void {
    /** @var TestCase $this */
    $this->assertSame($this->coverageManifest()->fixtureNames(), $this->responseFixtureCatalog()->names());

    foreach ($this->responseFixtureCatalog()->names() as $fixture) {
        $this->fixture($fixture);
    }
});
