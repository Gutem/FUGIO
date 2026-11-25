<?php
use PHPUnit\Framework\TestCase;

final class SeedTreeTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../Fuzzer/SeedNode.php';
        require_once __DIR__ . '/../../Fuzzer/SeedTree.php';
    }

    private function node(string $class): SeedNode
    {
        $n = new SeedNode();
        $n->input_tree = [
            '$this' => [
                'name' => '$this',
                'type' => 'Object',
                'value' => $class,
                'preserve' => 'TYPE_VALUE',
                'deps' => [],
            ],
        ];
        return $n;
    }

    public function testStructureHashIsStable(): void
    {
        $a = $this->node('A');
        $b = $this->node('A');
        self::assertSame($a->GetStructureHash(), $b->GetStructureHash());
        // Object nodes deliberately drop their value from the structure hash.
        self::assertSame($a->GetStructureHash(), $this->node('B')->GetStructureHash());
    }

    public function testAddCountAndRemove(): void
    {
        $root = $this->node('Root');
        $child = $this->node('Child');
        $tree = new SeedTree();
        $tree->SetRoot($root);
        $tree->AddSeed($root, $child, false);
        self::assertSame(2, $tree->GetSeedCount());
        self::assertSame($root, $child->parent);
        self::assertTrue($tree->isDuplicateStructure($this->node('Root')));
        self::assertFalse($tree->IsNewPath($this->node('Other')));
        $tree->RemoveSeed($child);
        self::assertSame(1, $tree->GetSeedCount());
    }
}
