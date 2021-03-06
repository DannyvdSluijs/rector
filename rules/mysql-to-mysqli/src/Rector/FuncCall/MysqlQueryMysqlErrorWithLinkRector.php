<?php

declare(strict_types=1);

namespace Rector\MysqlToMysqli\Rector\FuncCall;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Type\ResourceType;
use PHPStan\Type\UnionType;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\RectorDefinition\CodeSample;
use Rector\Core\RectorDefinition\RectorDefinition;

/**
 * @see https://www.php.net/manual/en/mysqli.error.php
 * @see https://www.php.net/manual/en/mysqli.query.php
 *
 * @see \Rector\MysqlToMysqli\Tests\Rector\FuncCall\MysqlQueryMysqlErrorWithLinkRector\MysqlQueryMysqlErrorWithLinkRectorTest
 */
final class MysqlQueryMysqlErrorWithLinkRector extends AbstractRector
{
    /**
     * @var array<string, string>
     */
    private const FUNCTION_RENAME_MAP = [
        'mysql_affected_rows' => 'mysqli_affected_rows',
        'mysql_client_encoding' => 'mysqli_character_set_name',
        'mysql_close' => 'mysqli_close',
        'mysql_errno' => 'mysqli_errno',
        'mysql_error' => 'mysqli_error',
        'mysql_escape_string' => 'mysqli_real_escape_string',
        'mysql_get_host_info' => 'mysqli_get_host_info',
        'mysql_get_proto_info' => 'mysqli_get_proto_info',
        'mysql_get_server_info' => 'mysqli_get_server_info',
        'mysql_info' => 'mysqli_info',
        'mysql_insert_id' => 'mysqli_insert_id',
        'mysql_ping' => 'mysqli_ping',
        'mysql_query' => 'mysqli_query',
        'mysql_real_escape_string' => 'mysqli_real_escape_string',
        'mysql_select_db' => 'mysqli_select_db',
        'mysql_set_charset' => 'mysqli_set_charset',
        'mysql_stat' => 'mysqli_stat',
        'mysql_thread_id' => 'mysqli_thread_id',
    ];

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('Add mysql_query and mysql_error with connection', [
            new CodeSample(
                <<<'PHP'
class SomeClass
{
    public function run()
    {
        $conn = mysqli_connect('host', 'user', 'pass');

        mysql_error();
        $sql = 'SELECT';

        return mysql_query($sql);
    }
}
PHP
,
                <<<'PHP'
class SomeClass
{
    public function run()
    {
        $conn = mysqli_connect('host', 'user', 'pass');

        mysqli_error($conn);
        $sql = 'SELECT';

        return mysqli_query($conn, $sql);
    }
}
PHP
            ),
        ]);
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [FuncCall::class];
    }

    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node): ?Node
    {
        foreach (self::FUNCTION_RENAME_MAP as $oldFunction => $newFunction) {
            if (! $this->isName($node, $oldFunction)) {
                continue;
            }

            if (
                count($node->args) === 0
                || ! $this->isProbablyMysql($node->args[0]->value)
            ) {
                $connectionVariable = $this->findConnectionVariable($node);

                if ($connectionVariable === null) {
                    return null;
                }

                $node->args = array_merge([new Arg($connectionVariable)], $node->args);
            }

            $node->name = new Name($newFunction);

            return $node;
        }

        return null;
    }

    private function isProbablyMysql(Node $node): bool
    {
        if ($this->isObjectType($node, 'mysqli')) {
            return true;
        }

        $st = $this->getStaticType($node);
        $resourceType = new ResourceType();

        if ($st->equals($resourceType)) {
            return true;
        }

        if ($st instanceof UnionType) {
            foreach ($st->getTypes() as $type) {
                if ($type->equals($resourceType)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function findConnectionVariable(FuncCall $funcCall): ?Expr
    {
        $connectionAssign = $this->betterNodeFinder->findFirstPrevious($funcCall, function (Node $node): ?bool {
            if (! $node instanceof Assign) {
                return null;
            }

            return $this->isObjectType($node->expr, 'mysqli');
        });

        return $connectionAssign !== null ? $connectionAssign->var : null;
    }
}
