<?php
use PHPUnit\Framework\TestCase;

final class PayloadCreatorTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../Fuzzer/PayloadCreator.php';
    }

    public function testVarExportScalarsAndArrays(): void
    {
        $p = new PayloadCreator();
        self::assertSame("'x'", $p->VarExport('x'));
        self::assertSame('42', $p->VarExport(42));
        self::assertSame("[0=>'a',1=>2]", $p->VarExport(['a', 2]));
    }

    public function testVarUnQuoteOnlyStripsMatchingQuotes(): void
    {
        $p = new PayloadCreator();
        self::assertSame('abc', $p->VarUnQuote("'abc'"));
        self::assertSame('abc', $p->VarUnQuote('abc'));
        self::assertSame("'abc", $p->VarUnQuote("'abc"));
    }

    public function testPropertyLcsExactAndPartial(): void
    {
        $p = new PayloadCreator();
        self::assertSame(1, $p->PropertyLCS('abc', 'abc')['similarity']);
        $partial = $p->PropertyLCS('abcd', 'abxd');
        self::assertSame('abd', $partial['value']);
        self::assertEqualsWithDelta(0.75, $partial['similarity'], 0.001);
        self::assertSame(0, $p->PropertyLCS('', 'abc')['similarity']);
    }

    public function testPropertyLms(): void
    {
        $p = new PayloadCreator();
        self::assertSame('', $p->PropertyLMS('', 'x'));
        self::assertSame('abc', $p->PropertyLMS('xabc', 'abc'));
        self::assertSame('bc', $p->PropertyLMS('abc', 'xbc'));
    }
}
