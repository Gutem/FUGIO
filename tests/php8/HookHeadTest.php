<?php
use PHPUnit\Framework\TestCase;

final class HookHeadTest extends TestCase
{
    public function testRenderedHookHeaderIsValidPhp8(): void
    {
        $template = file_get_contents(__DIR__ . '/../../Utils/HookFiles/HookHead.php');
        $replacements = [
            '{{__POINT_PHAR_HASH__}}' => 'abc',
            '{{__POINT_RABBITMQ_IP__}}' => '127.0.0.1',
            '{{__POINT_RABBITMQ_PORT__}}' => '5672',
            '{{__POINT_RABBITMQ_ID__}}' => 'fugio',
            '{{__POINT_RABBITMQ_PW__}}' => 'fugio_password',
        ];
        $rendered = strtr($template, $replacements);
        $path = tempnam(sys_get_temp_dir(), 'fugio-hook-') . '.php';
        file_put_contents($path, $rendered);
        exec(PHP_BINARY . ' -l ' . escapeshellarg($path), $output, $rc);
        @unlink($path);
        self::assertSame(0, $rc, implode("\n", $output));
    }

    public function testDummySetStateIsStatic(): void
    {
        $template = file_get_contents(__DIR__ . '/../../Utils/HookFiles/HookHead.php');
        self::assertStringContainsString('public static function __set_state($array)', $template);
    }
}
