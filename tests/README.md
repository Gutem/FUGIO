# PHP 8 smoke tests

Executed in `php:8.4-cli` (container):

| Check | Result |
|:--|:--|
| `php -l` on modified PHP files | no syntax errors |
| `tests/php8_smoke.php` with php-parser 5.9.0 | `PHP8 smoke: ok (8.4.28)` |
| `Lib/evalhook8` build (`phpize`/`make`) | `Build complete` |
| `eval_log()` after `eval()` | returned the eval source string |
| rendered `HookHead.php` with placeholders replaced | `No syntax errors detected` |

The hook template is not lintable before placeholder substitution; the test
above renders it first.

## PHPUnit harness (best effort)

`tests/phpunit.xml` + `tests/php8/` run twenty-four PHPUnit tests:

- parser 8.4 syntax is accepted;
- the parser factory returns independent parsers;
- invalid syntax raises `PhpParser\Error`;
- `ASTLoad` works with and without a visitor;
- `ConstraintLoad` captures `is_*` type hints and literal values;
- `InstrumentationByAST` returns a tree;
- `PayloadCreator` var export, unquote, LCS and LMS;
- `SeedTree`/`SeedNode` hashes, add, count and remove;
- the rendered `HookHead.php` passes `php -l`;
- `__set_state()` is static.

Run with coverage (requires `pcov`):

```bash
cd tests
composer install
../tests/coverage.sh
```

Current measured result on `php:8.4-cli` + Xdebug 3.5.3:

```text
OK (24 tests, 68 assertions)
Lines:   14.97% (248/1657)
```

`ConstraintVisitor.php` is at 82.20%; `PayloadCreator.php` is 5.46%,
`SeedNode` 17.82% and `SeedTree` 21.57%. The PHP 5/7 branches cannot execute on
a PHP 8 runtime. The low overall number is because `PayloadCreator` and the
remaining fuzzer code are large; this is a **port baseline**, not full FUGIO
coverage, and there is no coverage gate yet.

## Python tests

```bash
python3 -m unittest discover -s tests/python -v
```

4 tests: `--php_ver 8` accepted, `--php_ver 9` rejected, defaults preserved,
and the PHP 8 `uopz` branch is present.
