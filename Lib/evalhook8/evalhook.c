/*
  evalhook for PHP 8.x (FUGIO fork)

  Keeps the original idea: record every string passed to eval(), so FUGIO can
  analyse eval'd code. Ported from the PHP 5/7 extension.
*/

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "ext/standard/info.h"
#include "zend_compile.h"
#include "php_evalhook.h"

PHP_FUNCTION(eval_log);
ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_eval_log, 0, 0, IS_ARRAY, 0)
ZEND_END_ARG_INFO()
static zend_function_entry eval_log_functions[] = {
    PHP_FE(eval_log, arginfo_eval_log)
    {NULL, NULL, NULL}
};

zend_module_entry evalhook_module_entry = {
    STANDARD_MODULE_HEADER,
    "evalhook",
    eval_log_functions,
    PHP_MINIT(evalhook),
    PHP_MSHUTDOWN(evalhook),
    PHP_RINIT(evalhook),
    PHP_RSHUTDOWN(evalhook),
    PHP_MINFO(evalhook),
    "0.2",
    STANDARD_MODULE_PROPERTIES
};

#ifdef COMPILE_DL_EVALHOOK
ZEND_GET_MODULE(evalhook)
#endif

ZEND_BEGIN_MODULE_GLOBALS(evalhook)
    zval new_array;
ZEND_END_MODULE_GLOBALS(evalhook)

#ifdef ZTS
#define COUNTER_G(v) TSRMG(evalhook_globals_id, zend_evalhook_globals *, v)
#else
#define COUNTER_G(v) (evalhook_globals.v)
#endif

ZEND_DECLARE_MODULE_GLOBALS(evalhook)

#if PHP_VERSION_ID >= 80200
typedef zend_op_array *(*evalhook_compile_string_t)(
    zend_string *source_string, const char *filename,
    zend_compile_position position);
static evalhook_compile_string_t orig_compile_string;
static zend_op_array *evalhook_compile_string(
    zend_string *source_string, const char *filename,
    zend_compile_position position)
{
    add_next_index_stringl(&COUNTER_G(new_array), ZSTR_VAL(source_string),
                           ZSTR_LEN(source_string));
    return orig_compile_string(source_string, filename, position);
}
#else
typedef zend_op_array *(*evalhook_compile_string_t)(
    zend_string *source_string, const char *filename);
static evalhook_compile_string_t orig_compile_string;
static zend_op_array *evalhook_compile_string(
    zend_string *source_string, const char *filename)
{
    add_next_index_stringl(&COUNTER_G(new_array), ZSTR_VAL(source_string),
                           ZSTR_LEN(source_string));
    return orig_compile_string(source_string, filename);
}
#endif

static zend_bool evalhook_hooked = 0;

PHP_MINIT_FUNCTION(evalhook)
{
    if (evalhook_hooked == 0) {
        evalhook_hooked = 1;
        orig_compile_string = zend_compile_string;
        zend_compile_string = evalhook_compile_string;
    }
    return SUCCESS;
}

PHP_RINIT_FUNCTION(evalhook)
{
    array_init(&COUNTER_G(new_array));
    return SUCCESS;
}

PHP_MSHUTDOWN_FUNCTION(evalhook)
{
    if (evalhook_hooked == 1) {
        evalhook_hooked = 0;
        zend_compile_string = orig_compile_string;
    }
    return SUCCESS;
}

PHP_RSHUTDOWN_FUNCTION(evalhook)
{
    return SUCCESS;
}

PHP_MINFO_FUNCTION(evalhook)
{
    php_info_print_table_start();
    php_info_print_table_header(2, "evalhook support", "enabled");
    php_info_print_table_end();
}

PHP_FUNCTION(eval_log)
{
    HashTable *arr_hash;
    zval *data;

    array_init(return_value);

    arr_hash = Z_ARRVAL(COUNTER_G(new_array));
    ZEND_HASH_FOREACH_VAL(arr_hash, data) {
        add_next_index_stringl(return_value, Z_STRVAL_P(data), Z_STRLEN_P(data));
    }
    ZEND_HASH_FOREACH_END();
}
