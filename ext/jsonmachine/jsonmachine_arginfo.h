/* This is a generated file, edit the .stub.php file instead.
 * Stub hash: b169221255fda27e055e1a5d9e61f85449e0b0aa */

ZEND_BEGIN_ARG_INFO_EX(arginfo_jsonmachine_next_token, 0, 0, 5)
    ZEND_ARG_TYPE_INFO(0, chunk, IS_STRING, 0)
    ZEND_ARG_TYPE_INFO(1, tokenBuffer, IS_STRING, 0)
    ZEND_ARG_TYPE_INFO(1, escaping, _IS_BOOL, 0)
    ZEND_ARG_TYPE_INFO(1, inString, _IS_BOOL, 0)
    ZEND_ARG_TYPE_INFO(1, lastIndex, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_FUNCTION(jsonmachine_next_token);

static const zend_function_entry ext_functions[] = {
	ZEND_FE(jsonmachine_next_token, arginfo_jsonmachine_next_token)
	ZEND_FE_END
};

PHP_METHOD(ExtTokens, __construct);
PHP_METHOD(ExtTokens, current);
PHP_METHOD(ExtTokens, next);
PHP_METHOD(ExtTokens, key);
PHP_METHOD(ExtTokens, valid);
PHP_METHOD(ExtTokens, rewind);

ZEND_BEGIN_ARG_INFO(arginfo_exttokens_construct, 0)
    ZEND_ARG_OBJ_INFO(0, iterator, Iterator, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO(arginfo_exttokens_void, 0)
ZEND_END_ARG_INFO()

static const zend_function_entry exttokens_methods[] = {
    PHP_ME(ExtTokens, __construct, arginfo_exttokens_construct, ZEND_ACC_PUBLIC | ZEND_ACC_CTOR)
    PHP_ME(ExtTokens, current, arginfo_exttokens_void, ZEND_ACC_PUBLIC)
    PHP_ME(ExtTokens, next, arginfo_exttokens_void, ZEND_ACC_PUBLIC)
    PHP_ME(ExtTokens, key, arginfo_exttokens_void, ZEND_ACC_PUBLIC)
    PHP_ME(ExtTokens, valid, arginfo_exttokens_void, ZEND_ACC_PUBLIC)
    PHP_ME(ExtTokens, rewind, arginfo_exttokens_void, ZEND_ACC_PUBLIC)
    PHP_FE_END
};

typedef struct _exttokens_object {
    zend_object std;
    zval iterator;
} exttokens_object;

zend_class_entry *exttokens_ce;
zend_object_handlers exttokens_object_handlers;
