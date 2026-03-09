<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use Creem\Tests\TestCase;

use function array_map;
use function array_unique;
use function array_values;
use function basename;
use function file_get_contents;
use function glob;
use function sort;
use function sprintf;

test('response fixtures are complete for the coverage manifest', function (): void {
    /** @var TestCase $this */
    $expectedFixtures = [];

    foreach ($this->coverageManifest() as $coverage) {
        $expectedFixtures = [...$expectedFixtures, ...$coverage['fixtures']];
    }

    $expectedFixtures = array_values(array_unique($expectedFixtures));
    $actualFixtures = array_map(
        basename(...),
        glob($this->fixturesDirectory().'/*.json') ?: [],
    );

    sort($expectedFixtures);
    sort($actualFixtures);

    $this->assertSame($expectedFixtures, $actualFixtures);

    foreach ($expectedFixtures as $fixture) {
        $contents = file_get_contents($this->fixturesDirectory().'/'.$fixture);

        $this->assertNotFalse($contents, sprintf('Fixture %s could not be read.', $fixture));

        $payload = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($payload, sprintf('Fixture %s must decode to an array payload.', $fixture));
    }
});
