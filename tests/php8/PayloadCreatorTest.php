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

    public function testMakeGadgetTemplateAndGoalValues(): void
    {
        $p = new PayloadCreator();
        $gadget = (object) [
            'class' => 'A',
            'real_class' => 'A',
            'file' => 'a.php',
            'real_file' => 'a.php',
            'class_alias' => '',
            'var_list' => [],
        ];
        $tree = $p->MakeGadgetTemplate($gadget);
        self::assertSame('A', $tree['$this']['value']);
        self::assertSame('a.php', $tree['$this']['file']);
        self::assertSame('literal', $p->GetGoalValue('literal', 'Literal', 'x'));

        $tmp = tempnam(sys_get_temp_dir(), 'fugio-goal-');
        self::assertStringContainsString(basename($tmp), $p->GetGoalValue('MY_FILE', 'Replace', $tmp));
        @unlink($tmp);
    }

    public function testPropertyLms(): void
    {
        $p = new PayloadCreator();
        self::assertSame('', $p->PropertyLMS('', 'x'));
        self::assertSame('abc', $p->PropertyLMS('xabc', 'abc'));
        self::assertSame('bc', $p->PropertyLMS('abc', 'xbc'));
    }
}
