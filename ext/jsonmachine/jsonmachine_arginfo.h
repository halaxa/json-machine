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
