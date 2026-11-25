<?php
// Smoke test for the PHP 8 path. Run after `composer install` in Lib/PHP-Parser8.
require __DIR__ . '/../Lib/PHP-Parser8/vendor/autoload.php';
require __DIR__ . '/../Fuzzer/inc/ParserCompat.php';

$code = <<<'PHP'
<?php
namespace Demo;
#[\Attribute]
class Gadget {
    public readonly string $name;
    public function __construct(private int $id = 1) {}
    public function __destruct(): void { system($this->name); }
}
PHP;

$parser = fugio_create_parser();
$ast = $parser->parse($code);
if (!is_array($ast) || count($ast) === 0) {
    fwrite(STDERR, "PHP8 smoke: empty AST\n");
    exit(1);
}
echo "PHP8 smoke: ok (" . PHP_VERSION . ")\n";
