<?php
if (PHP_VERSION_ID >= 80000) {
	define("PHP7", true);
	define("PHP8", true);
}
elseif (PHP_VERSION >= "7.2") {
	define("PHP7", true);
	define("PHP8", false);
}
else {
	define("PHP7", false);
	define("PHP8", false);
}

if (PHP8) {
	require __DIR__ . '/../Lib/PHP-Parser8/vendor/autoload.php';
}
elseif (PHP7) {
	require __DIR__ . '/../Lib/PHP-Parser7/vendor/autoload.php';
}
else {
	require __DIR__ . '/../Lib/PHP-Parser/vendor/autoload.php';
}

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Error;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;

class MyNodeVisitor extends NodeVisitorAbstract
{
  public $builtin_count = 0;
  public $builtin_func = array();

  private function toString($arg) {
		if ($arg == null) {
			return $arg;
		}
		else if (is_string($arg)) {
			return $arg;
		}
		else if ($arg instanceof Node\Scalar\EncapsedStringPart) {
			return $arg->value;
		}
		else if ($arg instanceof Node\Name) {
			return $arg->toString();
		}
		else if ($arg instanceof Node\Identifier) {
			return $arg->toString();
    }
  }

	public function enterNode(Node $node) {
    if ($node instanceof Node\Expr\FuncCall){
      $func_name = $this->toString($node->name);
      if (in_array($func_name, get_defined_functions()['internal'])){
        $this->builtin_count += 1;
        if (array_key_exists($func_name, $this->builtin_func)){
          $this->builtin_func[$func_name] += 1;
        }
        else{
          $this->builtin_func[$func_name] = 1;
        }
      }
    }
  }

  public function afterTraverse(array $nodes) {
    foreach($this->builtin_func as $func=>$cnt)
      echo "$func: $cnt \n";
    echo "Total: " . $this->builtin_count . "\n";
  }
}

if (PHP8) {
	$parser = (new ParserFactory())->createForHostVersion();
}
elseif (PHP7) {
	$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
}
else {
	$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP5);
}

$traverser = new NodeTraverser;
$traverser->addVisitor(new MyNodeVisitor);

try {
  $code = file_get_contents($argv[1]);
	$ast = $parser->parse($code);
	$ast = $traverser->traverse($ast);
} catch (Error $error) {
	echo "Parse error: {$error->getMessage()}\n";
	return;
}
?>