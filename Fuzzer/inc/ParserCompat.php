<?php
use PhpParser\ParserFactory;

/**
 * PHP 8 uses php-parser 5 (createForHostVersion); PHP 7 uses php-parser 4.
 * Kept in one place so the instrumentation and the slave agree on the AST.
 */
function fugio_create_parser()
{
    if (PHP_VERSION_ID >= 80000) {
        $factory = new ParserFactory();
        if (method_exists($factory, 'createForHostVersion')) {
            return $factory->createForHostVersion();
        }
        // php-parser 5.0/5.1 fallback
        return $factory->createForNewestSupportedVersion();
    }
    if (PHP_VERSION_ID >= 70200) {
        return (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
    }
    return (new ParserFactory())->create(ParserFactory::PREFER_PHP5);
}
