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

`tests/phpunit.xml` + `tests/php8/` run five PHPUnit tests:

- parser 8.4 syntax is accepted;
- the parser factory returns independent parsers;
- invalid syntax raises `PhpParser\Error`;
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
OK (5 tests, 8 assertions)
Lines:   50.00% (4/8)
```

The 50% is the PHP 8 branch of `ParserCompat.php`; the PHP 5/7 branches
cannot execute on a PHP 8 runtime. This is a **port baseline**, not full FUGIO
coverage, and there is no coverage gate yet.

## Python tests

```bash
python3 -m unittest discover -s tests/python -v
```

4 tests: `--php_ver 8` accepted, `--php_ver 9` rejected, defaults preserved,
and the PHP 8 `uopz` branch is present.
