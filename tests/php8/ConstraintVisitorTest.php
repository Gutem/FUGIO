<?php
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ConstraintVisitorTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../Fuzzer/inc/ParserCompat.php';
        require_once __DIR__ . '/../../Fuzzer/inc/ASTLoader.php';
        require_once __DIR__ . '/../../Fuzzer/inc/ConstraintVisitor.php';
    }

    public static function typeHints(): array
    {
        return [
            'array' => ['is_array($this->p);', 'Array'],
            'bool' => ['is_bool($this->p);', 'Boolean'],
            'float' => ['is_float($this->p);', 'Float'],
            'double' => ['is_double($this->p);', 'Float'],
            'real' => ['is_real($this->p);', 'Float'],
            'int' => ['is_int($this->p);', 'Int'],
            'integer' => ['is_integer($this->p);', 'Int'],
            'long' => ['is_long($this->p);', 'Int'],
            'string' => ['is_string($this->p);', 'String'],
        ];
    }

    #[DataProvider('typeHints')]
    public function testTypeHint(string $expr, string $expectedType): void
    {
        $visitor = new ConstraintNodeVisitor();
        ConstraintLoad('<?php ' . $expr, $visitor);
        self::assertSame('ONLY_TYPE', $visitor->constraint_info['result']);
        self::assertSame('p', $visitor->constraint_info['prop_name']);
        self::assertSame($expectedType, $visitor->constraint_info['type']);
    }

    public static function values(): array
    {
        return [
            'string' => ['$this->p === "abc";', 'String', 'abc'],
            'int' => ['$this->p === 42;', 'Int', 42],
            'float' => ['$this->p === 1.5;', 'Float', 1.5],
            'bool' => ['$this->p === true;', 'Boolean', 'true'],
            'bool-false' => ['$this->p === false;', 'Boolean', 'false'],
            'reversed' => ['"abc" === $this->p;', 'String', 'abc'],
        ];
    }

    #[DataProvider('values')]
    public function testPropertyValue(string $expr, string $expectedType, mixed $expectedValue): void
    {
        $visitor = new ConstraintNodeVisitor();
        ConstraintLoad('<?php ' . $expr, $visitor);
        self::assertSame('TYPE_VALUE', $visitor->constraint_info['result']);
        self::assertSame('p', $visitor->constraint_info['prop_name']);
        self::assertSame($expectedType, $visitor->constraint_info['type']);
        self::assertSame($expectedValue, $visitor->constraint_info['value']);
    }
}
