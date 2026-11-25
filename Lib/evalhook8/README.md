# evalhook8

Port of the original evalhook extension for PHP 8.0–8.4. It records eval()
source strings and exposes them through eval_log(), which HookHead.php uses
to populate EVAL_CODE.

Build:
  phpize
  ./configure
  make
  make install
  echo extension=evalhook.so >> "$(php --ini | awk '/Loaded Configuration/{print $NF}')"
