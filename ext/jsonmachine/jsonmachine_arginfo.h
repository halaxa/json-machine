/* This is a generated file, edit the .stub.php file instead.
 * Stub hash: XXXXXXXXXXXXXXXXXXXXXX */

ZEND_BEGIN_ARG_INFO_EX(arginfo_class_ExtTokens___construct, 0, 0, 1)
	ZEND_ARG_OBJ_INFO(0, iterator, Iterator, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_ExtTokens_current, 0, 0, IS_MIXED, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_ExtTokens_next, 0, 0, IS_VOID, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_ExtTokens_key arginfo_class_ExtTokens_current

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_ExtTokens_valid, 0, 0, _IS_BOOL, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_ExtTokens_rewind arginfo_class_ExtTokens_next

ZEND_METHOD(ExtTokens, __construct);
ZEND_METHOD(ExtTokens, current);
ZEND_METHOD(ExtTokens, next);
ZEND_METHOD(ExtTokens, key);
ZEND_METHOD(ExtTokens, valid);
ZEND_METHOD(ExtTokens, rewind);

static const zend_function_entry class_ExtTokens_methods[] = {
	ZEND_ME(ExtTokens, __construct, arginfo_class_ExtTokens___construct, ZEND_ACC_PUBLIC)
	ZEND_ME(ExtTokens, current, arginfo_class_ExtTokens_current, ZEND_ACC_PUBLIC)
	ZEND_ME(ExtTokens, next, arginfo_class_ExtTokens_next, ZEND_ACC_PUBLIC)
	ZEND_ME(ExtTokens, key, arginfo_class_ExtTokens_key, ZEND_ACC_PUBLIC)
	ZEND_ME(ExtTokens, valid, arginfo_class_ExtTokens_valid, ZEND_ACC_PUBLIC)
	ZEND_ME(ExtTokens, rewind, arginfo_class_ExtTokens_rewind, ZEND_ACC_PUBLIC)
	ZEND_FE_END
};
