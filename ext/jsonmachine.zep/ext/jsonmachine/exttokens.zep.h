
extern zend_class_entry *jsonmachine_exttokens_ce;

ZEPHIR_INIT_CLASS(JsonMachine_ExtTokens);

PHP_METHOD(JsonMachine_ExtTokens, __construct);
PHP_METHOD(JsonMachine_ExtTokens, rewind);
PHP_METHOD(JsonMachine_ExtTokens, next);
PHP_METHOD(JsonMachine_ExtTokens, valid);
PHP_METHOD(JsonMachine_ExtTokens, current);
PHP_METHOD(JsonMachine_ExtTokens, key);
PHP_METHOD(JsonMachine_ExtTokens, mapOfBoundaryBytes);
PHP_METHOD(JsonMachine_ExtTokens, jsonInsignificantBytes);
PHP_METHOD(JsonMachine_ExtTokens, initCurrentChunk);
PHP_METHOD(JsonMachine_ExtTokens, getPosition);
PHP_METHOD(JsonMachine_ExtTokens, getLine);
PHP_METHOD(JsonMachine_ExtTokens, getColumn);
PHP_METHOD(JsonMachine_ExtTokens, jsonChunksRewind);
PHP_METHOD(JsonMachine_ExtTokens, jsonChunksNext);
zend_object *zephir_init_properties_JsonMachine_ExtTokens(zend_class_entry *class_type);

ZEND_BEGIN_ARG_INFO_EX(arginfo_jsonmachine_exttokens___construct, 0, 0, 1)
	ZEND_ARG_OBJ_INFO(0, jsonChunks, Iterator, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_jsonmachine_exttokens_rewind, 0, 0, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_jsonmachine_exttokens_next, 0, 0, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_jsonmachine_exttokens_valid, 0, 0, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_jsonmachine_exttokens_current, 0, 0, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_jsonmachine_exttokens_key, 0, 0, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_jsonmachine_exttokens_mapofboundarybytes, 0, 0, IS_ARRAY, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_jsonmachine_exttokens_jsoninsignificantbytes, 0, 0, IS_ARRAY, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_jsonmachine_exttokens_initcurrentchunk, 0, 0, _IS_BOOL, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_jsonmachine_exttokens_getposition, 0, 0, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_jsonmachine_exttokens_getline, 0, 0, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_jsonmachine_exttokens_getcolumn, 0, 0, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_jsonmachine_exttokens_jsonchunksrewind, 0, 0, _IS_BOOL, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_jsonmachine_exttokens_jsonchunksnext, 0, 0, _IS_BOOL, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_jsonmachine_exttokens_zephir_init_properties_jsonmachine_exttokens, 0, 0, 0)
ZEND_END_ARG_INFO()

ZEPHIR_INIT_FUNCS(jsonmachine_exttokens_method_entry) {
	PHP_ME(JsonMachine_ExtTokens, __construct, arginfo_jsonmachine_exttokens___construct, ZEND_ACC_PUBLIC|ZEND_ACC_CTOR)
#if PHP_VERSION_ID >= 80000
	PHP_ME(JsonMachine_ExtTokens, rewind, arginfo_jsonmachine_exttokens_rewind, ZEND_ACC_PUBLIC)
#else
	PHP_ME(JsonMachine_ExtTokens, rewind, NULL, ZEND_ACC_PUBLIC)
#endif
#if PHP_VERSION_ID >= 80000
	PHP_ME(JsonMachine_ExtTokens, next, arginfo_jsonmachine_exttokens_next, ZEND_ACC_PUBLIC)
#else
	PHP_ME(JsonMachine_ExtTokens, next, NULL, ZEND_ACC_PUBLIC)
#endif
#if PHP_VERSION_ID >= 80000
	PHP_ME(JsonMachine_ExtTokens, valid, arginfo_jsonmachine_exttokens_valid, ZEND_ACC_PUBLIC)
#else
	PHP_ME(JsonMachine_ExtTokens, valid, NULL, ZEND_ACC_PUBLIC)
#endif
#if PHP_VERSION_ID >= 80000
	PHP_ME(JsonMachine_ExtTokens, current, arginfo_jsonmachine_exttokens_current, ZEND_ACC_PUBLIC)
#else
	PHP_ME(JsonMachine_ExtTokens, current, NULL, ZEND_ACC_PUBLIC)
#endif
#if PHP_VERSION_ID >= 80000
	PHP_ME(JsonMachine_ExtTokens, key, arginfo_jsonmachine_exttokens_key, ZEND_ACC_PUBLIC)
#else
	PHP_ME(JsonMachine_ExtTokens, key, NULL, ZEND_ACC_PUBLIC)
#endif
	PHP_ME(JsonMachine_ExtTokens, mapOfBoundaryBytes, arginfo_jsonmachine_exttokens_mapofboundarybytes, ZEND_ACC_PRIVATE)
	PHP_ME(JsonMachine_ExtTokens, jsonInsignificantBytes, arginfo_jsonmachine_exttokens_jsoninsignificantbytes, ZEND_ACC_PRIVATE)
	PHP_ME(JsonMachine_ExtTokens, initCurrentChunk, arginfo_jsonmachine_exttokens_initcurrentchunk, ZEND_ACC_PRIVATE)
	PHP_ME(JsonMachine_ExtTokens, getPosition, arginfo_jsonmachine_exttokens_getposition, ZEND_ACC_PUBLIC)
	PHP_ME(JsonMachine_ExtTokens, getLine, arginfo_jsonmachine_exttokens_getline, ZEND_ACC_PUBLIC)
	PHP_ME(JsonMachine_ExtTokens, getColumn, arginfo_jsonmachine_exttokens_getcolumn, ZEND_ACC_PUBLIC)
	PHP_ME(JsonMachine_ExtTokens, jsonChunksRewind, arginfo_jsonmachine_exttokens_jsonchunksrewind, ZEND_ACC_PRIVATE)
	PHP_ME(JsonMachine_ExtTokens, jsonChunksNext, arginfo_jsonmachine_exttokens_jsonchunksnext, ZEND_ACC_PRIVATE)
	PHP_FE_END
};
