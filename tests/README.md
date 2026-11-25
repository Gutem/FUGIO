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

Current measured result on `php:8.4-cli` + PCOV 1.0.12:

```text
OK (5 tests, 8 assertions)
Lines: 0.00% (0/8)
```

The 0% is an instrumentation artifact: `ParserCompat.php` is loaded during
PHPUnit test discovery, before the coverage driver starts, so its executed
lines are not attributed. The tests themselves pass; the harness is in place,
but **there is no reliable coverage number yet** and no coverage gate.

## Python tests

```bash
python3 -m unittest discover -s tests/python -v
```

4 tests: `--php_ver 8` accepted, `--php_ver 9` rejected, defaults preserved,
and the PHP 8 `uopz` branch is present.
