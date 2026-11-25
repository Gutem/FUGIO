<?php
use PHPUnit\Framework\TestCase;

final class FuzzerInternalTest extends TestCase
{
    protected function setUp(): void
    {
        foreach ([
            'MAKE_AUX_PROP' => '50',
            'MAKE_AUX_SUB_PROP' => '50',
            'MUTATE_PROP' => '100',
            'MAX_STR_LENGTH' => '150',
            'ARR_OBJ_PROP' => '70',
            'FIRST_INDEX_OBJ_PROP' => '95',
            'SEED_BASIC_VALUE' => '1',
            'MEMORY_LIMIT' => '512M',
        ] as $name => $value) {
            if (!defined($name)) {
                define($name, $value);
            }
        }
        require_once __DIR__ . '/../../Fuzzer/PayloadCreator.php';
        require_once __DIR__ . '/../../Fuzzer/Executor.php';
        require_once __DIR__ . '/../../Fuzzer/DebugUtils.php';
        require_once __DIR__ . '/../../Fuzzer/SeedNode.php';
        require_once __DIR__ . '/../../Fuzzer/SeedTree.php';
        require_once __DIR__ . '/../../Fuzzer/FuzzSlave.php';
        require_once __DIR__ . '/../../Fuzzer/FuzzManager.php';
    }

    public function testFuzzSlaveCandidateHelpers(): void
    {
        $slave = (new ReflectionClass('FuzzSlave'))->newInstanceWithoutConstructor();
        $slave->cand_props_hash_table = [];

        $cand = new stdClass();
        $cand->class = 'Demo';
        $cand->method = 'm';
        $cand->prop_list = [];
        $cand->file = 'demo.php';
        $cand->visibility = 'public';

        self::assertSame([$cand], $slave->GetClassCandidates(['x' => [$cand]], []));
        self::assertArrayHasKey('Demo', $slave->MakePropCandidatesHT(['p' => [$cand]]));
        self::assertSame(md5(md5('h1') . 'h2'), $slave->GetBranchHitHash(
            ['branchHit' => [['hash' => 'h1'], ['hash' => 'h2']]]
        ));
    }

    public function testExecutorHelpers(): void
    {
        $executor = (new ReflectionClass('Executor'))->newInstanceWithoutConstructor();
        $executor->class_naming = [];

        self::assertSame('h', $executor->FindMethodHash(
            [['type' => 'Method', 'class' => 'C', 'method' => 'm', 'hash' => 'h']],
            'C',
            'm'
        ));
        self::assertNull($executor->FindMethodHash([], 'C', 'm'));

        $suffix = (new ReflectionMethod('Executor', 'GetSuffixName'));
        $suffix->setAccessible(true);
        self::assertSame(0, $suffix->invoke($executor, 'A\\B'));
        self::assertSame(1, $suffix->invoke($executor, 'A\\B'));

        $key = (new ReflectionMethod('Executor', 'GetPropertyKey'));
        $key->setAccessible(true);
        self::assertTrue($key->invoke($executor, ['name' => '$x'])['rand']);
        self::assertFalse($key->invoke($executor, ['name' => 'x'])['rand']);

        $value = (new ReflectionMethod('Executor', 'GetPropertyValue'));
        $value->setAccessible(true);
        self::assertSame('new A', $value->invoke($executor, ['type' => 'Object', 'value' => 'A'])['value']);
        self::assertSame('true', $value->invoke($executor, ['type' => 'Bool', 'value' => true])['value']);

        $next = (new ReflectionMethod('Executor', 'GetNextWriteClass'));
        $next->setAccessible(true);
        self::assertSame('A', $next->invoke($executor, null, 'A', [], []));
        self::assertSame(null, $next->invoke($executor, null, null, [], []));
    }

    public function testFuzzManagerLoadsChainInfo(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'fugio-chain-') . '.json';
        file_put_contents($path, json_encode(['chain' => []]));
        $manager = (new ReflectionClass('FuzzManager'))->newInstanceWithoutConstructor();
        $loaded = $manager->LoadChainInfo($path);
        @unlink($path);
        self::assertSame([], $loaded->chain);
    }


    public function testFuzzSlaveRevisionAndCallback(): void
    {
        $slave = (new ReflectionClass('FuzzSlave'))->newInstanceWithoutConstructor();
        $seed = new stdClass();
        $seed->fuzz_result = ['result' => ['case' => ['case' => 'ERROR', 'msg' => ['type' => 'NON_OBJECT_METHOD']]]];
        self::assertSame(
            ['result' => true, 'type' => 'NON_OBJECT_METHOD'],
            $slave->IsNeedRevise($seed)
        );
        $msg = new stdClass();
        $msg->body = 'payload';
        $slave->GetConstraintCallBack($msg);
        self::assertSame('payload', $slave->constraints_raw);
    }

    public function testExecutorPropertyValueVariants(): void
    {
        $executor = (new ReflectionClass('Executor'))->newInstanceWithoutConstructor();
        $value = new ReflectionMethod('Executor', 'GetPropertyValue');
        $value->setAccessible(true);
        self::assertSame('false', $value->invoke($executor, ['type' => 'Bool', 'value' => false])['value']);
        self::assertStringContainsString('0 => 1', $value->invoke($executor, ['type' => 'Array', 'value' => [1, 2]])['value']);
        self::assertNull($value->invoke($executor, ['type' => 'Null', 'value' => null])['value']);
        self::assertSame(7, $value->invoke($executor, ['type' => 'Int', 'value' => 7])['value']);
    }

    public function testFuzzSlaveForeachCandidates(): void
    {
        $slave = (new ReflectionClass('FuzzSlave'))->newInstanceWithoutConstructor();
        $slave->cand_props_hash_table = [];
        $cand = new stdClass();
        $cand->class = 'A';
        $cand->method = 'm';
        $cand->prop_list = [];
        $result = $slave->GetForeachCandidates(['p' => [$cand]]);
        self::assertArrayHasKey('p', $result);
    }

    public function testDebugUtilsTemplateInfo(): void
    {
        $seed = new SeedNode();
        $seed->seed_idx = 1;
        $seed->depth = 0;
        $seed->goal_depth = 0;
        $seed->input_tree = ['$this' => [
            'name' => '$this', 'type' => 'Object', 'value' => 'A',
            'file' => 'a.php', 'visibility' => null, 'deps' => [],
        ]];
        $chain = [(object) ['class' => 'A', 'real_class' => 'A', 'method' => 'm', 'sink' => null]];
        ob_start();
        (new DebugUtils())->PrintTemplateInfo($seed, $chain);
        $out = ob_get_clean();
        self::assertStringContainsString('SEED#(1)', $out);
    }

    public function testSeedNodeMergedTreeList(): void
    {
        $seed = new SeedNode();
        $seed->input_tree = [
            '$this' => [
                'name' => '$this',
                'type' => 'Object',
                'value' => 'Root',
                'info' => new stdClass(),
                'deps' => [],
            ],
        ];
        $list = $seed->GetMergedTreeList(null);
        self::assertIsArray($list);
    }

    public function testFuzzManagerInstrumentationExistingFile(): void
    {
        $dir = sys_get_temp_dir() . '/fugio-fm-' . uniqid();
        mkdir($dir);
        file_put_contents($dir . '/inst_PUT.php', '<?php');
        file_put_contents($dir . '/chain.json', '{}');
        $manager = (new ReflectionClass('FuzzManager'))->newInstanceWithoutConstructor();
        $manager->file_chain = $dir . '/chain.json';
        self::assertSame($dir . '/inst_PUT.php', $manager->Instrumentation());
        @unlink($dir . '/inst_PUT.php');
        @unlink($dir . '/chain.json');
        @rmdir($dir);
    }

    public function testDebugUtilsSelectedLogDoesNotThrow(): void
    {
        $debug = new DebugUtils();
        ob_start();
        $debug->PrintSelectedLog([
            1 => ['count' => 1, 'depth' => 0, 'goal_depth' => 1, 'sink_reached' => false, 'removed' => false],
        ]);
        $out = ob_get_clean();
        self::assertStringContainsString('Selected Seed Log', $out);
    }
}
