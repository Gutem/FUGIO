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

`tests/phpunit.xml` + `tests/php8/` run forty-one PHPUnit tests:

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
OK (41 tests, 117 assertions)
Lines:   17.51% (539/3079)
```

`ConstraintVisitor.php` is at 88.98%; `DebugUtils` 46.15%, `SeedNode` 56.44%,
`FuzzSlave` 10.68%, `Executor` 7.10%, `PayloadCreator` 9.75% and `SeedTree`
21.57%. The PHP 5/7 branches cannot execute on
a PHP 8 runtime. The low overall number is because `PayloadCreator` and the
remaining fuzzer code are large; this is a **port baseline**, not full FUGIO
coverage, and there is no coverage gate yet.

## Python tests

```bash
python3 -m unittest discover -s tests/python -v
```

4 tests: `--php_ver 8` accepted, `--php_ver 9` rejected, defaults preserved,
and the PHP 8 `uopz` branch is present.

## Analyzer / Detector (Python)

```bash
python3 -m coverage run --branch --source=Analyzer,Detector \
  -m unittest discover -s tests/python
python3 -m coverage report -m
```

Measured with 19 tests and offline stubs for `pika`, `requests` and
`dateutil`:

| Module | Coverage |
|:--|--:|
| `Analyzer/__init__.py` | 95% |
| `Analyzer/dynamic.py` | 70% |
| `Analyzer/static.py` | 60% |
| `Analyzer/parser.py` | 53% |
| `Detector/DetectorManager.py` | 30% |
| `Analyzer/analyzer.py` | 21% |
| `Analyzer/chain.py` | 5% |
| **Total** | **26%** |

The remaining Analyzer/Detector paths need AST dumps, RabbitMQ callbacks and
`Proxy`/`Executor` integration; the unit tests exercise the pure methods
(visibility/type decoding, parameter ranges, property/method lookup, global
variable encoding, `StaticAnalyzer.parse/update_info`, `DynamicAnalyzer.update_info`,
`ASTParser.parse`, Base64 handling, and chain filtering).

**85% per module is not reached for `analyzer.py` and `chain.py`**; they need
the real AST/RabbitMQ/Proxy flow, not only unit fixtures.
