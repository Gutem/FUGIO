<?php
use PhpParser\NodeVisitorAbstract;
use PHPUnit\Framework\TestCase;

final class ASTLoaderTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../Fuzzer/inc/ParserCompat.php';
        require_once __DIR__ . '/../../Fuzzer/inc/ASTLoader.php';
        require_once __DIR__ . '/../../Fuzzer/inc/ConstraintVisitor.php';
    }

    private function fixture(string $code): string
    {
        $path = tempnam(sys_get_temp_dir(), 'fugio-ast-') . '.php';
        file_put_contents($path, $code);
        $this->files[] = $path;
        return $path;
    }

    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $path) {
            @unlink($path);
        }
    }

    public function testAstLoadWithoutVisitor(): void
    {
        $ast = ASTLoad($this->fixture('<?php $a = 1;'));
        self::assertIsArray($ast);
        self::assertNotEmpty($ast);
    }

    public function testAstLoadWithVisitor(): void
    {
        $visitor = new class extends NodeVisitorAbstract {
            public int $seen = 0;
            public function enterNode(\PhpParser\Node $node) {
                $this->seen++;
                return null;
            }
        };
        $ast = ASTLoad($this->fixture('<?php function f() { return 1; }'), $visitor);
        self::assertIsArray($ast);
        self::assertGreaterThan(0, $visitor->seen);
    }

    public function testConstraintLoadCapturesIsString(): void
    {
        $visitor = new ConstraintNodeVisitor();
        $ast = ConstraintLoad('<?php is_string($this->prop_1);', $visitor);
        self::assertIsArray($ast);
        self::assertSame('ONLY_TYPE', $visitor->constraint_info['result']);
        self::assertSame('prop_1', $visitor->constraint_info['prop_name']);
        self::assertSame('String', $visitor->constraint_info['type']);
    }

    public function testInstrumentationByAstReturnsTree(): void
    {
        $visitor = new class extends NodeVisitorAbstract {};
        $ast = ASTLoad($this->fixture('<?php echo 1;'));
        $result = InstrumentationByAST($ast, $visitor);
        self::assertIsArray($result);
    }
}
