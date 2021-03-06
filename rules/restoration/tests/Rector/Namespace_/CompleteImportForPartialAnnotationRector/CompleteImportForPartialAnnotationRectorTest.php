<?php

declare(strict_types=1);

namespace Rector\Restoration\Tests\Rector\Namespace_\CompleteImportForPartialAnnotationRector;

use Iterator;
use Rector\Core\Testing\PHPUnit\AbstractRectorTestCase;
use Rector\Restoration\Rector\Namespace_\CompleteImportForPartialAnnotationRector;
use Rector\Restoration\ValueObject\UseWithAlias;
use Symplify\SmartFileSystem\SmartFileInfo;

final class CompleteImportForPartialAnnotationRectorTest extends AbstractRectorTestCase
{
    /**
     * @dataProvider provideData()
     */
    public function test(SmartFileInfo $fileInfo): void
    {
        $this->doTestFileInfo($fileInfo);
    }

    public function provideData(): Iterator
    {
        return $this->yieldFilesFromDirectory(__DIR__ . '/Fixture');
    }

    /**
     * @return mixed[]
     */
    protected function getRectorsWithConfiguration(): array
    {
        return [
            CompleteImportForPartialAnnotationRector::class => [
                CompleteImportForPartialAnnotationRector::USE_IMPORTS_TO_RESTORE => [
                    new UseWithAlias('Doctrine\ORM\Mapping', 'ORM'),
                ],
            ],
        ];
    }
}
