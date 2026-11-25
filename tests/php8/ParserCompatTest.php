<?php
use PHPUnit\Framework\TestCase;

final class ParserCompatTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../Fuzzer/inc/ParserCompat.php';
    }

    public function testParsesPhp84Syntax(): void
    {
        $code = <<<'PHP'
<?php
namespace Demo;
#[\Attribute]
final class Gadget {
    public readonly string $name;
    public function __construct(private int $id = 1) {}
    public function __destruct(): void { system($this->name); }
}
PHP;
        $ast = fugio_create_parser()->parse($code);
        self::assertIsArray($ast);
        self::assertNotEmpty($ast);
    }

    public function testReturnsAReusableParser(): void
    {
        $first = fugio_create_parser();
        $second = fugio_create_parser();
        self::assertNotSame($first, $second);
        self::assertNotEmpty($first->parse('<?php echo 1;'));
        self::assertNotEmpty($second->parse('<?php echo 2;'));
    }

    public function testRejectsInvalidCode(): void
    {
        $this->expectException(\PhpParser\Error::class);
        fugio_create_parser()->parse('<?php class {');
    }
}
