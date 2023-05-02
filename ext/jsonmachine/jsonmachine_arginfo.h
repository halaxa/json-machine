/* This is a generated file, edit the .stub.php file instead.
 * Stub hash: 3932bcd6beac35482cca138424967d73d6a8cd69 */

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_test1, 0, 0, IS_VOID, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_test2, 0, 0, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, str, IS_STRING, 0, "\"\"")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_jsonmachine_next_token, 0, 5, IS_VOID, 0)
	ZEND_ARG_TYPE_INFO(0, chunk, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(1, tokenBuffer, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(1, escaping, _IS_BOOL, 0)
	ZEND_ARG_TYPE_INFO(1, inString, _IS_BOOL, 0)
	ZEND_ARG_TYPE_INFO(1, lastIndex, IS_LONG, 0)
ZEND_END_ARG_INFO()


ZEND_FUNCTION(test1);
ZEND_FUNCTION(test2);
ZEND_FUNCTION(jsonmachine_next_token);


static const zend_function_entry ext_functions[] = {
	ZEND_FE(test1, arginfo_test1)
	ZEND_FE(test2, arginfo_test2)
	ZEND_FE(jsonmachine_next_token, arginfo_jsonmachine_next_token)
	ZEND_FE_END
};
