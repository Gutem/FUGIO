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
