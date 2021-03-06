<?php

declare(strict_types=1);

namespace Rector\DowngradePhp74\Rector\Coalesce;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp\Coalesce as AssignCoalesce;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\RectorDefinition\CodeSample;
use Rector\Core\RectorDefinition\RectorDefinition;

/**
 * @see https://wiki.php.net/rfc/null_coalesce_equal_operator
 * @see \Rector\DowngradePhp74\Tests\Rector\Coalesce\DowngradeNullCoalescingOperatorRector\DowngradeNullCoalescingOperatorRectorTest
 */
final class DowngradeNullCoalescingOperatorRector extends AbstractRector
{
    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('Remove null coalescing operator ??=', [
            new CodeSample(
                <<<'PHP'
$array = [];
$array['user_id'] ??= 'value';
PHP
                ,
                <<<'PHP'
$array = [];
$array['user_id'] = $array['user_id'] ?? 'value';
PHP
            ),
        ]);
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [AssignCoalesce::class];
    }

    /**
     * @param AssignCoalesce $node
     */
    public function refactor(Node $node): ?Node
    {
        return new Assign($node->var, new Coalesce($node->var, $node->expr));
    }
}
