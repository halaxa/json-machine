
#ifdef HAVE_CONFIG_H
#include "../ext_config.h"
#endif

#include <php.h>
#include "../php_ext.h"
#include "../ext.h"

#include <Zend/zend_operators.h>
#include <Zend/zend_exceptions.h>
#include <Zend/zend_interfaces.h>

#include "kernel/main.h"
#include "kernel/object.h"
#include "kernel/memory.h"
#include "kernel/fcall.h"
#include "kernel/operators.h"
#include "kernel/string.h"
#include "kernel/concat.h"
#include "kernel/array.h"


ZEPHIR_INIT_CLASS(JsonMachine_ExtTokens)
{
	ZEPHIR_REGISTER_CLASS(JsonMachine, ExtTokens, jsonmachine, exttokens, jsonmachine_exttokens_method_entry, 0);

	/** @var Iterator */
	zend_declare_property_null(jsonmachine_exttokens_ce, SL("jsonChunks"), ZEND_ACC_PRIVATE);
	/** @var array */
	zend_declare_property_null(jsonmachine_exttokens_ce, SL("tokenBoundaries"), ZEND_ACC_PRIVATE);
	/** @var array  */
	zend_declare_property_null(jsonmachine_exttokens_ce, SL("jsonInsignificantBytes"), ZEND_ACC_PRIVATE);
	/** @var string */
	zend_declare_property_string(jsonmachine_exttokens_ce, SL("carryToken"), "", ZEND_ACC_PRIVATE);
	/** @var string */
	zend_declare_property_string(jsonmachine_exttokens_ce, SL("current"), "", ZEND_ACC_PRIVATE);
	/** @var int */
	zend_declare_property_long(jsonmachine_exttokens_ce, SL("key"), -1, ZEND_ACC_PRIVATE);
	/** @var string */
	zend_declare_property_null(jsonmachine_exttokens_ce, SL("chunk"), ZEND_ACC_PRIVATE);
	/** @var int */
	zend_declare_property_null(jsonmachine_exttokens_ce, SL("chunkLength"), ZEND_ACC_PRIVATE);
	/** @var int */
	zend_declare_property_null(jsonmachine_exttokens_ce, SL("chunkIndex"), ZEND_ACC_PRIVATE);
	/** @var bool */
	zend_declare_property_bool(jsonmachine_exttokens_ce, SL("inString"), 0, ZEND_ACC_PRIVATE);
	/** @var string */
	zend_declare_property_string(jsonmachine_exttokens_ce, SL("tokenBuffer"), "", ZEND_ACC_PRIVATE);
	/** @var bool */
	zend_declare_property_bool(jsonmachine_exttokens_ce, SL("escaping"), 0, ZEND_ACC_PRIVATE);
	jsonmachine_exttokens_ce->create_object = zephir_init_properties_JsonMachine_ExtTokens;

	zend_class_implements(jsonmachine_exttokens_ce, 1, zend_ce_iterator);
	return SUCCESS;
}

/**
 * @param Iterator<string> $jsonChunks
 */
PHP_METHOD(JsonMachine_ExtTokens, __construct)
{
	zephir_method_globals *ZEPHIR_METHOD_GLOBALS_PTR = NULL;
	zend_long ZEPHIR_LAST_CALL_STATUS;
	zval *jsonChunks, jsonChunks_sub, _0, _1;
	zval *this_ptr = getThis();

	ZVAL_UNDEF(&jsonChunks_sub);
	ZVAL_UNDEF(&_0);
	ZVAL_UNDEF(&_1);
#if PHP_VERSION_ID >= 80000
	bool is_null_true = 1;
	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_OBJECT_OF_CLASS(jsonChunks, zend_ce_iterator)
	ZEND_PARSE_PARAMETERS_END();
#endif


	ZEPHIR_MM_GROW();
	zephir_fetch_params(1, 1, 0, &jsonChunks);


	zephir_update_property_zval(this_ptr, ZEND_STRL("jsonChunks"), jsonChunks);
	ZEPHIR_CALL_METHOD(&_0, this_ptr, "mapofboundarybytes", NULL, 1);
	zephir_check_call_status();
	zephir_update_property_zval(this_ptr, ZEND_STRL("tokenBoundaries"), &_0);
	ZEPHIR_CALL_METHOD(&_1, this_ptr, "jsoninsignificantbytes", NULL, 2);
	zephir_check_call_status();
	zephir_update_property_zval(this_ptr, ZEND_STRL("jsonInsignificantBytes"), &_1);
	ZEPHIR_MM_RESTORE();
}

PHP_METHOD(JsonMachine_ExtTokens, rewind)
{
	zephir_method_globals *ZEPHIR_METHOD_GLOBALS_PTR = NULL;
	zend_long ZEPHIR_LAST_CALL_STATUS;
	zval *this_ptr = getThis();



	ZEPHIR_MM_GROW();

	ZEPHIR_CALL_METHOD(NULL, this_ptr, "jsonchunksrewind", NULL, 3);
	zephir_check_call_status();
	ZEPHIR_CALL_METHOD(NULL, this_ptr, "next", NULL, 0);
	zephir_check_call_status();
	ZEPHIR_MM_RESTORE();
}

PHP_METHOD(JsonMachine_ExtTokens, next)
{
	zval byte;
	zephir_method_globals *ZEPHIR_METHOD_GLOBALS_PTR = NULL;
	zend_long ZEPHIR_LAST_CALL_STATUS;
	zval __$true, __$false, _0, _1, _2, _28, _29, _3$$3, _6$$3, _7$$3, _8$$3, _9$$3, _12$$3, _13$$3, _16$$3, _19$$3, _4$$4, _5$$4, _10$$5, _11$$5, _14$$6, _15$$6, _17$$7, _18$$7, _20$$10, _23$$10, _24$$10, _25$$10, _21$$11, _22$$11, _26$$14, _27$$14, _30$$17, _31$$17;
	zval *this_ptr = getThis();

	ZVAL_BOOL(&__$true, 1);
	ZVAL_BOOL(&__$false, 0);
	ZVAL_UNDEF(&_0);
	ZVAL_UNDEF(&_1);
	ZVAL_UNDEF(&_2);
	ZVAL_UNDEF(&_28);
	ZVAL_UNDEF(&_29);
	ZVAL_UNDEF(&_3$$3);
	ZVAL_UNDEF(&_6$$3);
	ZVAL_UNDEF(&_7$$3);
	ZVAL_UNDEF(&_8$$3);
	ZVAL_UNDEF(&_9$$3);
	ZVAL_UNDEF(&_12$$3);
	ZVAL_UNDEF(&_13$$3);
	ZVAL_UNDEF(&_16$$3);
	ZVAL_UNDEF(&_19$$3);
	ZVAL_UNDEF(&_4$$4);
	ZVAL_UNDEF(&_5$$4);
	ZVAL_UNDEF(&_10$$5);
	ZVAL_UNDEF(&_11$$5);
	ZVAL_UNDEF(&_14$$6);
	ZVAL_UNDEF(&_15$$6);
	ZVAL_UNDEF(&_17$$7);
	ZVAL_UNDEF(&_18$$7);
	ZVAL_UNDEF(&_20$$10);
	ZVAL_UNDEF(&_23$$10);
	ZVAL_UNDEF(&_24$$10);
	ZVAL_UNDEF(&_25$$10);
	ZVAL_UNDEF(&_21$$11);
	ZVAL_UNDEF(&_22$$11);
	ZVAL_UNDEF(&_26$$14);
	ZVAL_UNDEF(&_27$$14);
	ZVAL_UNDEF(&_30$$17);
	ZVAL_UNDEF(&_31$$17);
	ZVAL_UNDEF(&byte);


	ZEPHIR_MM_GROW();

	ZEPHIR_INIT_VAR(&_0);
	ZEPHIR_INIT_NVAR(&_0);
	ZVAL_STRING(&_0, "");
	zephir_update_property_zval(this_ptr, ZEND_STRL("current"), &_0);
	while (1) {
		zephir_read_property(&_1, this_ptr, ZEND_STRL("chunkIndex"), PH_NOISY_CC | PH_READONLY);
		zephir_read_property(&_2, this_ptr, ZEND_STRL("chunkLength"), PH_NOISY_CC | PH_READONLY);
		if (!(ZEPHIR_LT(&_1, &_2))) {
			break;
		}
		zephir_read_property(&_3$$3, this_ptr, ZEND_STRL("carryToken"), PH_NOISY_CC | PH_READONLY);
		if (!ZEPHIR_IS_STRING(&_3$$3, "")) {
			zephir_read_property(&_4$$4, this_ptr, ZEND_STRL("carryToken"), PH_NOISY_CC | PH_READONLY);
			zephir_update_property_zval(this_ptr, ZEND_STRL("current"), &_4$$4);
			ZEPHIR_INIT_NVAR(&_5$$4);
			ZEPHIR_INIT_NVAR(&_5$$4);
			ZVAL_STRING(&_5$$4, "");
			zephir_update_property_zval(this_ptr, ZEND_STRL("carryToken"), &_5$$4);
			RETURN_ON_FAILURE(zephir_property_incr(this_ptr, SL("key")));
			RETURN_MM_NULL();
		}
		zephir_read_property(&_6$$3, this_ptr, ZEND_STRL("chunk"), PH_NOISY_CC | PH_READONLY);
		zephir_read_property(&_7$$3, this_ptr, ZEND_STRL("chunkIndex"), PH_NOISY_CC | PH_READONLY);
		ZVAL_LONG(&_8$$3, 1);
		ZEPHIR_INIT_NVAR(&byte);
		zephir_substr(&byte, &_6$$3, zephir_get_intval(&_7$$3), 1 , 0);
		zephir_read_property(&_9$$3, this_ptr, ZEND_STRL("escaping"), PH_NOISY_CC | PH_READONLY);
		if (UNEXPECTED(zephir_is_true(&_9$$3))) {
			if (0) {
				zephir_update_property_zval(this_ptr, ZEND_STRL("escaping"), &__$true);
			} else {
				zephir_update_property_zval(this_ptr, ZEND_STRL("escaping"), &__$false);
			}
			zephir_read_property(&_10$$5, this_ptr, ZEND_STRL("tokenBuffer"), PH_NOISY_CC | PH_READONLY);
			ZEPHIR_INIT_NVAR(&_11$$5);
			ZEPHIR_CONCAT_VV(&_11$$5, &_10$$5, &byte);
			zephir_update_property_zval(this_ptr, ZEND_STRL("tokenBuffer"), &_11$$5);
			RETURN_ON_FAILURE(zephir_property_incr(this_ptr, SL("chunkIndex")));
			continue;
		}
		zephir_read_property(&_12$$3, this_ptr, ZEND_STRL("jsonInsignificantBytes"), PH_NOISY_CC | PH_READONLY);
		zephir_array_fetch(&_13$$3, &_12$$3, &byte, PH_NOISY | PH_READONLY, "jsonmachine/exttokens.zep", 69);
		if (zephir_is_true(&_13$$3)) {
			zephir_read_property(&_14$$6, this_ptr, ZEND_STRL("tokenBuffer"), PH_NOISY_CC | PH_READONLY);
			ZEPHIR_INIT_NVAR(&_15$$6);
			ZEPHIR_CONCAT_VV(&_15$$6, &_14$$6, &byte);
			zephir_update_property_zval(this_ptr, ZEND_STRL("tokenBuffer"), &_15$$6);
			RETURN_ON_FAILURE(zephir_property_incr(this_ptr, SL("chunkIndex")));
			continue;
		}
		zephir_read_property(&_16$$3, this_ptr, ZEND_STRL("inString"), PH_NOISY_CC | PH_READONLY);
		if (zephir_is_true(&_16$$3)) {
			if (ZEPHIR_IS_STRING(&byte, "\"")) {
				if (0) {
					zephir_update_property_zval(this_ptr, ZEND_STRL("inString"), &__$true);
				} else {
					zephir_update_property_zval(this_ptr, ZEND_STRL("inString"), &__$false);
				}
			} else if (ZEPHIR_IS_STRING(&byte, "\\")) {
				if (1) {
					zephir_update_property_zval(this_ptr, ZEND_STRL("escaping"), &__$true);
				} else {
					zephir_update_property_zval(this_ptr, ZEND_STRL("escaping"), &__$false);
				}
			}
			zephir_read_property(&_17$$7, this_ptr, ZEND_STRL("tokenBuffer"), PH_NOISY_CC | PH_READONLY);
			ZEPHIR_INIT_NVAR(&_18$$7);
			ZEPHIR_CONCAT_VV(&_18$$7, &_17$$7, &byte);
			zephir_update_property_zval(this_ptr, ZEND_STRL("tokenBuffer"), &_18$$7);
			RETURN_ON_FAILURE(zephir_property_incr(this_ptr, SL("chunkIndex")));
			continue;
		}
		zephir_read_property(&_19$$3, this_ptr, ZEND_STRL("tokenBoundaries"), PH_NOISY_CC | PH_READONLY);
		if (zephir_array_isset(&_19$$3, &byte)) {
			zephir_read_property(&_20$$10, this_ptr, ZEND_STRL("tokenBuffer"), PH_NOISY_CC | PH_READONLY);
			if (!ZEPHIR_IS_STRING(&_20$$10, "")) {
				zephir_read_property(&_21$$11, this_ptr, ZEND_STRL("tokenBuffer"), PH_NOISY_CC | PH_READONLY);
				zephir_update_property_zval(this_ptr, ZEND_STRL("current"), &_21$$11);
				ZEPHIR_INIT_NVAR(&_22$$11);
				ZEPHIR_INIT_NVAR(&_22$$11);
				ZVAL_STRING(&_22$$11, "");
				zephir_update_property_zval(this_ptr, ZEND_STRL("tokenBuffer"), &_22$$11);
			}
			zephir_read_property(&_23$$10, this_ptr, ZEND_STRL("tokenBoundaries"), PH_NOISY_CC | PH_READONLY);
			zephir_array_fetch(&_24$$10, &_23$$10, &byte, PH_NOISY | PH_READONLY, "jsonmachine/exttokens.zep", 90);
			if (zephir_is_true(&_24$$10)) {
				zephir_update_property_zval(this_ptr, ZEND_STRL("carryToken"), &byte);
			}
			zephir_read_property(&_25$$10, this_ptr, ZEND_STRL("current"), PH_NOISY_CC | PH_READONLY);
			if (!ZEPHIR_IS_STRING(&_25$$10, "")) {
				RETURN_ON_FAILURE(zephir_property_incr(this_ptr, SL("key")));
				RETURN_ON_FAILURE(zephir_property_incr(this_ptr, SL("chunkIndex")));
				RETURN_MM_NULL();
			}
		} else {
			if (ZEPHIR_IS_STRING(&byte, "\"")) {
				if (1) {
					zephir_update_property_zval(this_ptr, ZEND_STRL("inString"), &__$true);
				} else {
					zephir_update_property_zval(this_ptr, ZEND_STRL("inString"), &__$false);
				}
			}
			zephir_read_property(&_26$$14, this_ptr, ZEND_STRL("tokenBuffer"), PH_NOISY_CC | PH_READONLY);
			ZEPHIR_INIT_NVAR(&_27$$14);
			ZEPHIR_CONCAT_VV(&_27$$14, &_26$$14, &byte);
			zephir_update_property_zval(this_ptr, ZEND_STRL("tokenBuffer"), &_27$$14);
		}
		RETURN_ON_FAILURE(zephir_property_incr(this_ptr, SL("chunkIndex")));
	}
	ZEPHIR_CALL_METHOD(&_28, this_ptr, "jsonchunksnext", NULL, 4);
	zephir_check_call_status();
	zephir_read_property(&_29, this_ptr, ZEND_STRL("carryToken"), PH_NOISY_CC | PH_READONLY);
	if (zephir_is_true(&_28)) {
		ZEPHIR_CALL_METHOD(NULL, this_ptr, "next", NULL, 5);
		zephir_check_call_status();
	} else if (zephir_is_true(&_29)) {
		zephir_read_property(&_30$$17, this_ptr, ZEND_STRL("carryToken"), PH_NOISY_CC | PH_READONLY);
		zephir_update_property_zval(this_ptr, ZEND_STRL("current"), &_30$$17);
		ZEPHIR_INIT_VAR(&_31$$17);
		ZEPHIR_INIT_NVAR(&_31$$17);
		ZVAL_STRING(&_31$$17, "");
		zephir_update_property_zval(this_ptr, ZEND_STRL("carryToken"), &_31$$17);
		RETURN_ON_FAILURE(zephir_property_incr(this_ptr, SL("key")));
	}
	ZEPHIR_MM_RESTORE();
}

PHP_METHOD(JsonMachine_ExtTokens, valid)
{
	zval _0;
	zval *this_ptr = getThis();

	ZVAL_UNDEF(&_0);



	zephir_read_property(&_0, this_ptr, ZEND_STRL("current"), PH_NOISY_CC | PH_READONLY);
	RETURN_BOOL(!ZEPHIR_IS_STRING_IDENTICAL(&_0, ""));
}

PHP_METHOD(JsonMachine_ExtTokens, current)
{
	zval *this_ptr = getThis();



	RETURN_MEMBER(getThis(), "current");
}

PHP_METHOD(JsonMachine_ExtTokens, key)
{
	zval *this_ptr = getThis();



	RETURN_MEMBER(getThis(), "key");
}

PHP_METHOD(JsonMachine_ExtTokens, mapOfBoundaryBytes)
{
	zval boundary, utf8bom, _0, _1, _2, _3, _4, _5, _6, _7, _8, _9, _10, _11, _12, _13, _14, _15, _16, _17, _18, _19, _20, _21;
	zephir_method_globals *ZEPHIR_METHOD_GLOBALS_PTR = NULL;
	zval *this_ptr = getThis();

	ZVAL_UNDEF(&boundary);
	ZVAL_UNDEF(&utf8bom);
	ZVAL_UNDEF(&_0);
	ZVAL_UNDEF(&_1);
	ZVAL_UNDEF(&_2);
	ZVAL_UNDEF(&_3);
	ZVAL_UNDEF(&_4);
	ZVAL_UNDEF(&_5);
	ZVAL_UNDEF(&_6);
	ZVAL_UNDEF(&_7);
	ZVAL_UNDEF(&_8);
	ZVAL_UNDEF(&_9);
	ZVAL_UNDEF(&_10);
	ZVAL_UNDEF(&_11);
	ZVAL_UNDEF(&_12);
	ZVAL_UNDEF(&_13);
	ZVAL_UNDEF(&_14);
	ZVAL_UNDEF(&_15);
	ZVAL_UNDEF(&_16);
	ZVAL_UNDEF(&_17);
	ZVAL_UNDEF(&_18);
	ZVAL_UNDEF(&_19);
	ZVAL_UNDEF(&_20);
	ZVAL_UNDEF(&_21);


	ZEPHIR_MM_GROW();

	ZEPHIR_INIT_VAR(&utf8bom);
	ZVAL_STRING(&utf8bom, "﻿");
	ZEPHIR_INIT_VAR(&boundary);
	array_init(&boundary);
	ZVAL_LONG(&_0, 0);
	ZVAL_LONG(&_1, 1);
	ZEPHIR_INIT_VAR(&_2);
	zephir_substr(&_2, &utf8bom, 0 , 1 , 0);
	ZEPHIR_INIT_VAR(&_3);
	ZVAL_LONG(&_3, 0);
	zephir_array_update_zval(&boundary, &_2, &_3, PH_COPY | PH_SEPARATE);
	ZVAL_LONG(&_4, 1);
	ZVAL_LONG(&_5, 1);
	ZEPHIR_INIT_VAR(&_6);
	zephir_substr(&_6, &utf8bom, 1 , 1 , 0);
	ZEPHIR_INIT_VAR(&_7);
	ZVAL_LONG(&_7, 0);
	zephir_array_update_zval(&boundary, &_6, &_7, PH_COPY | PH_SEPARATE);
	ZVAL_LONG(&_8, 2);
	ZVAL_LONG(&_9, 1);
	ZEPHIR_INIT_VAR(&_10);
	zephir_substr(&_10, &utf8bom, 2 , 1 , 0);
	ZEPHIR_INIT_VAR(&_11);
	ZVAL_LONG(&_11, 0);
	zephir_array_update_zval(&boundary, &_10, &_11, PH_COPY | PH_SEPARATE);
	ZEPHIR_INIT_VAR(&_12);
	ZVAL_LONG(&_12, 0);
	zephir_array_update_string(&boundary, SL(" "), &_12, PH_COPY | PH_SEPARATE);
	ZEPHIR_INIT_VAR(&_13);
	ZVAL_LONG(&_13, 0);
	zephir_array_update_string(&boundary, SL("\n"), &_13, PH_COPY | PH_SEPARATE);
	ZEPHIR_INIT_VAR(&_14);
	ZVAL_LONG(&_14, 0);
	zephir_array_update_string(&boundary, SL("\r"), &_14, PH_COPY | PH_SEPARATE);
	ZEPHIR_INIT_VAR(&_15);
	ZVAL_LONG(&_15, 0);
	zephir_array_update_string(&boundary, SL("\t"), &_15, PH_COPY | PH_SEPARATE);
	ZEPHIR_INIT_VAR(&_16);
	ZVAL_LONG(&_16, 1);
	zephir_array_update_string(&boundary, SL("{"), &_16, PH_COPY | PH_SEPARATE);
	ZEPHIR_INIT_VAR(&_17);
	ZVAL_LONG(&_17, 1);
	zephir_array_update_string(&boundary, SL("}"), &_17, PH_COPY | PH_SEPARATE);
	ZEPHIR_INIT_VAR(&_18);
	ZVAL_LONG(&_18, 1);
	zephir_array_update_string(&boundary, SL("["), &_18, PH_COPY | PH_SEPARATE);
	ZEPHIR_INIT_VAR(&_19);
	ZVAL_LONG(&_19, 1);
	zephir_array_update_string(&boundary, SL("]"), &_19, PH_COPY | PH_SEPARATE);
	ZEPHIR_INIT_VAR(&_20);
	ZVAL_LONG(&_20, 1);
	zephir_array_update_string(&boundary, SL(":"), &_20, PH_COPY | PH_SEPARATE);
	ZEPHIR_INIT_VAR(&_21);
	ZVAL_LONG(&_21, 1);
	zephir_array_update_string(&boundary, SL(","), &_21, PH_COPY | PH_SEPARATE);
	RETURN_CCTOR(&boundary);
}

PHP_METHOD(JsonMachine_ExtTokens, jsonInsignificantBytes)
{
	zval _5$$3;
	zend_bool _0;
	zval bytes, ord, _3$$3, _6$$3, _7$$3;
	zephir_method_globals *ZEPHIR_METHOD_GLOBALS_PTR = NULL;
	zend_long ZEPHIR_LAST_CALL_STATUS, _1, _2;
	zephir_fcall_cache_entry *_4 = NULL;
	zval *this_ptr = getThis();

	ZVAL_UNDEF(&bytes);
	ZVAL_UNDEF(&ord);
	ZVAL_UNDEF(&_3$$3);
	ZVAL_UNDEF(&_6$$3);
	ZVAL_UNDEF(&_7$$3);
	ZVAL_UNDEF(&_5$$3);


	ZEPHIR_MM_GROW();

	ZEPHIR_INIT_VAR(&bytes);
	array_init(&bytes);
	_2 = 255;
	_1 = 0;
	_0 = 0;
	if (_1 <= _2) {
		while (1) {
			if (_0) {
				_1++;
				if (!(_1 <= _2)) {
					break;
				}
			} else {
				_0 = 1;
			}
			ZEPHIR_INIT_NVAR(&ord);
			ZVAL_LONG(&ord, _1);
			ZEPHIR_CALL_FUNCTION(&_3$$3, "chr", &_4, 6, &ord);
			zephir_check_call_status();
			ZEPHIR_INIT_NVAR(&_5$$3);
			zephir_create_array(&_5$$3, 15, 0);
			ZEPHIR_INIT_NVAR(&_6$$3);
			ZVAL_STRING(&_6$$3, "\\");
			zephir_array_fast_append(&_5$$3, &_6$$3);
			ZEPHIR_INIT_NVAR(&_6$$3);
			ZVAL_STRING(&_6$$3, "\"");
			zephir_array_fast_append(&_5$$3, &_6$$3);
			ZEPHIR_INIT_NVAR(&_6$$3);
			ZVAL_STRING(&_6$$3, "\xef");
			zephir_array_fast_append(&_5$$3, &_6$$3);
			ZEPHIR_INIT_NVAR(&_6$$3);
			ZVAL_STRING(&_6$$3, "\xbb");
			zephir_array_fast_append(&_5$$3, &_6$$3);
			ZEPHIR_INIT_NVAR(&_6$$3);
			ZVAL_STRING(&_6$$3, "\xbf");
			zephir_array_fast_append(&_5$$3, &_6$$3);
			ZEPHIR_INIT_NVAR(&_6$$3);
			ZVAL_STRING(&_6$$3, " ");
			zephir_array_fast_append(&_5$$3, &_6$$3);
			ZEPHIR_INIT_NVAR(&_6$$3);
			ZVAL_STRING(&_6$$3, "\n");
			zephir_array_fast_append(&_5$$3, &_6$$3);
			ZEPHIR_INIT_NVAR(&_6$$3);
			ZVAL_STRING(&_6$$3, "\r");
			zephir_array_fast_append(&_5$$3, &_6$$3);
			ZEPHIR_INIT_NVAR(&_6$$3);
			ZVAL_STRING(&_6$$3, "\t");
			zephir_array_fast_append(&_5$$3, &_6$$3);
			ZEPHIR_INIT_NVAR(&_6$$3);
			ZVAL_STRING(&_6$$3, "{");
			zephir_array_fast_append(&_5$$3, &_6$$3);
			ZEPHIR_INIT_NVAR(&_6$$3);
			ZVAL_STRING(&_6$$3, "}");
			zephir_array_fast_append(&_5$$3, &_6$$3);
			ZEPHIR_INIT_NVAR(&_6$$3);
			ZVAL_STRING(&_6$$3, "[");
			zephir_array_fast_append(&_5$$3, &_6$$3);
			ZEPHIR_INIT_NVAR(&_6$$3);
			ZVAL_STRING(&_6$$3, "]");
			zephir_array_fast_append(&_5$$3, &_6$$3);
			ZEPHIR_INIT_NVAR(&_6$$3);
			ZVAL_STRING(&_6$$3, ":");
			zephir_array_fast_append(&_5$$3, &_6$$3);
			ZEPHIR_INIT_NVAR(&_6$$3);
			ZVAL_STRING(&_6$$3, ",");
			zephir_array_fast_append(&_5$$3, &_6$$3);
			ZEPHIR_CALL_FUNCTION(&_7$$3, "chr", &_4, 6, &ord);
			zephir_check_call_status();
			ZEPHIR_INIT_NVAR(&_6$$3);
			ZVAL_BOOL(&_6$$3, !(zephir_fast_in_array(&_3$$3, &_5$$3)));
			zephir_array_update_zval(&bytes, &_7$$3, &_6$$3, PH_COPY | PH_SEPARATE);
		}
	}
	RETURN_CCTOR(&bytes);
}

PHP_METHOD(JsonMachine_ExtTokens, initCurrentChunk)
{
	zval valid, _0, _1$$3, _2$$3, _3$$3, _4$$3;
	zephir_method_globals *ZEPHIR_METHOD_GLOBALS_PTR = NULL;
	zend_long ZEPHIR_LAST_CALL_STATUS;
	zval *this_ptr = getThis();

	ZVAL_UNDEF(&valid);
	ZVAL_UNDEF(&_0);
	ZVAL_UNDEF(&_1$$3);
	ZVAL_UNDEF(&_2$$3);
	ZVAL_UNDEF(&_3$$3);
	ZVAL_UNDEF(&_4$$3);


	ZEPHIR_MM_GROW();

	zephir_read_property(&_0, this_ptr, ZEND_STRL("jsonChunks"), PH_NOISY_CC | PH_READONLY);
	ZEPHIR_CALL_METHOD(&valid, &_0, "valid", NULL, 0);
	zephir_check_call_status();
	if (zephir_is_true(&valid)) {
		zephir_read_property(&_1$$3, this_ptr, ZEND_STRL("jsonChunks"), PH_NOISY_CC | PH_READONLY);
		ZEPHIR_CALL_METHOD(&_2$$3, &_1$$3, "current", NULL, 0);
		zephir_check_call_status();
		zephir_update_property_zval(this_ptr, ZEND_STRL("chunk"), &_2$$3);
		zephir_read_property(&_3$$3, this_ptr, ZEND_STRL("chunk"), PH_NOISY_CC | PH_READONLY);
		ZEPHIR_INIT_ZVAL_NREF(_4$$3);
		ZVAL_LONG(&_4$$3, zephir_fast_strlen_ev(&_3$$3));
		zephir_update_property_zval(this_ptr, ZEND_STRL("chunkLength"), &_4$$3);
		ZEPHIR_INIT_ZVAL_NREF(_4$$3);
		ZVAL_LONG(&_4$$3, 0);
		zephir_update_property_zval(this_ptr, ZEND_STRL("chunkIndex"), &_4$$3);
	}
	RETURN_CCTOR(&valid);
}

PHP_METHOD(JsonMachine_ExtTokens, getPosition)
{
	zval *this_ptr = getThis();



	RETURN_LONG(0);
}

PHP_METHOD(JsonMachine_ExtTokens, getLine)
{
	zval *this_ptr = getThis();



	RETURN_LONG(1);
}

PHP_METHOD(JsonMachine_ExtTokens, getColumn)
{
	zval *this_ptr = getThis();



	RETURN_LONG(0);
}

PHP_METHOD(JsonMachine_ExtTokens, jsonChunksRewind)
{
	zval _0;
	zephir_method_globals *ZEPHIR_METHOD_GLOBALS_PTR = NULL;
	zend_long ZEPHIR_LAST_CALL_STATUS;
	zval *this_ptr = getThis();

	ZVAL_UNDEF(&_0);


	ZEPHIR_MM_GROW();

	zephir_read_property(&_0, this_ptr, ZEND_STRL("jsonChunks"), PH_NOISY_CC | PH_READONLY);
	ZEPHIR_CALL_METHOD(NULL, &_0, "rewind", NULL, 0);
	zephir_check_call_status();
	ZEPHIR_RETURN_CALL_METHOD(this_ptr, "initcurrentchunk", NULL, 7);
	zephir_check_call_status();
	RETURN_MM();
}

PHP_METHOD(JsonMachine_ExtTokens, jsonChunksNext)
{
	zval _0;
	zephir_method_globals *ZEPHIR_METHOD_GLOBALS_PTR = NULL;
	zend_long ZEPHIR_LAST_CALL_STATUS;
	zval *this_ptr = getThis();

	ZVAL_UNDEF(&_0);


	ZEPHIR_MM_GROW();

	zephir_read_property(&_0, this_ptr, ZEND_STRL("jsonChunks"), PH_NOISY_CC | PH_READONLY);
	ZEPHIR_CALL_METHOD(NULL, &_0, "next", NULL, 0);
	zephir_check_call_status();
	ZEPHIR_RETURN_CALL_METHOD(this_ptr, "initcurrentchunk", NULL, 7);
	zephir_check_call_status();
	RETURN_MM();
}

zend_object *zephir_init_properties_JsonMachine_ExtTokens(zend_class_entry *class_type)
{
		zval _0, _2, _1$$3, _3$$4;
	zephir_method_globals *ZEPHIR_METHOD_GLOBALS_PTR = NULL;
		ZVAL_UNDEF(&_0);
	ZVAL_UNDEF(&_2);
	ZVAL_UNDEF(&_1$$3);
	ZVAL_UNDEF(&_3$$4);
	

		ZEPHIR_MM_GROW();
	
	{
		zval local_this_ptr, *this_ptr = &local_this_ptr;
		ZEPHIR_CREATE_OBJECT(this_ptr, class_type);
		zephir_read_property_ex(&_0, this_ptr, ZEND_STRL("jsonInsignificantBytes"), PH_NOISY_CC | PH_READONLY);
		if (Z_TYPE_P(&_0) == IS_NULL) {
			ZEPHIR_INIT_VAR(&_1$$3);
			array_init(&_1$$3);
			zephir_update_property_zval_ex(this_ptr, ZEND_STRL("jsonInsignificantBytes"), &_1$$3);
		}
		zephir_read_property_ex(&_2, this_ptr, ZEND_STRL("tokenBoundaries"), PH_NOISY_CC | PH_READONLY);
		if (Z_TYPE_P(&_2) == IS_NULL) {
			ZEPHIR_INIT_VAR(&_3$$4);
			array_init(&_3$$4);
			zephir_update_property_zval_ex(this_ptr, ZEND_STRL("tokenBoundaries"), &_3$$4);
		}
		ZEPHIR_MM_RESTORE();
		return Z_OBJ_P(this_ptr);
	}
}

