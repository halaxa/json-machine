/* jsonmachine extension for PHP */

#ifdef HAVE_CONFIG_H
# include "config.h"
#endif

#include "php.h"
#include "ext/standard/info.h"
#include "ext/standard/php_var.h"
#include "php_jsonmachine.h"
#include "jsonmachine_arginfo.h"
#include "zend_interfaces.h"

#include <stdio.h>
#include <string.h>

/* For compatibility with older PHP versions */
#ifndef ZEND_PARSE_PARAMETERS_NONE
#define ZEND_PARSE_PARAMETERS_NONE() \
	ZEND_PARSE_PARAMETERS_START(0, 0) \
	ZEND_PARSE_PARAMETERS_END()
#endif

static zend_always_inline void append_char_to_zval_string(zval *str, char c)
{
    size_t len = Z_STRLEN_P(str);
    zend_string *new_str = zend_string_realloc(Z_STR_P(str), len + 1, 0);
    ZSTR_VAL(new_str)[len] = c;
    ZSTR_VAL(new_str)[len + 1] = '\0';
    ZVAL_STR(str, new_str);
}

unsigned char uc(char ch)
{
    return (unsigned char) ch;
}

bool zBool(zval *trueFalse)
{
    return Z_TYPE_P(trueFalse) == IS_TRUE;
}

static bool colonCommaBracket[256];
static bool tokenBoundaries[256];
static bool insignificantBytes[256];

void jsonmachine_next_token(
    zval * zChunk,
    size_t chunk_len,
    zval * zTokenBuffer,
    zval * zEscaping,
    zval * zInString,
    zval * zLastIndex,
    zval * return_value
)
{
    char * chunk = Z_STRVAL_P(zChunk);
    bool escaping = zBool(zEscaping);
    bool inString = zBool(zInString);
    long int lastIndex = Z_LVAL_P(zLastIndex);
    size_t i;

    for (i = lastIndex; i < chunk_len; i++) {
        unsigned char byte;
        byte = (unsigned char) chunk[i];
        if (escaping) {
            escaping = false;
            append_char_to_zval_string(zTokenBuffer, byte);
            continue;
        }
        if (insignificantBytes[byte]) {
            append_char_to_zval_string(zTokenBuffer, byte);
            continue;
        }
        if (inString) {
            if (byte == '"') {
                inString = false;
            } else if (byte == '\\') {
                escaping = true;
            }
            append_char_to_zval_string(zTokenBuffer, byte);

            continue;
        }

        if (tokenBoundaries[byte]) {
            if (Z_STRLEN_P(zTokenBuffer)) {
                ZVAL_BOOL(zEscaping, false);
                ZVAL_BOOL(zInString, false);
                ZVAL_LONG(zLastIndex, i);
                ZVAL_COPY_VALUE(return_value, zTokenBuffer);
                ZVAL_EMPTY_STRING(zTokenBuffer);
                return;
            }
            if (colonCommaBracket[byte]) {
                ZVAL_BOOL(zEscaping, false);
                ZVAL_BOOL(zInString, false);
                ZVAL_LONG(zLastIndex, i+1);
                RETURN_STR(zend_string_init((char *)&byte, 1, 0));
            }
        } else { // else branch matches `"` but also `\` outside of a string literal which is an error anyway but strictly speaking not correctly parsed token
            inString = true;
            append_char_to_zval_string(zTokenBuffer, byte);
        }
    }

    ZVAL_BOOL(zEscaping, escaping);
    ZVAL_BOOL(zInString, inString);
    ZVAL_LONG(zLastIndex, i);
}

PHP_FUNCTION(jsonmachine_next_token)
{
    zval *zChunk;
    zval *zTokenBuffer;
    zval *zEscaping;
    zval *zInString;
    zval *zLastIndex;

    ZEND_PARSE_PARAMETERS_START(5, 5)
        Z_PARAM_ZVAL(zChunk)
        Z_PARAM_ZVAL(zTokenBuffer)
        Z_PARAM_ZVAL(zEscaping)
        Z_PARAM_ZVAL(zInString)
        Z_PARAM_ZVAL(zLastIndex)
    ZEND_PARSE_PARAMETERS_END();

    ZVAL_DEREF(zTokenBuffer);
    ZVAL_DEREF(zEscaping);
    ZVAL_DEREF(zInString);
    ZVAL_DEREF(zLastIndex);

    jsonmachine_next_token(zChunk, Z_STRLEN_P(zChunk), zTokenBuffer, zEscaping, zInString, zLastIndex, return_value);
}

#define Z_EXTTOKENS_OBJ_P(zv) ((exttokens_object *)Z_OBJ_P((zv)))

typedef struct _exttokens_object {
    zend_object std;
    zval jsonChunks;

    ssize_t key;
    zval current;

    bool rewindCalled;
    zval chunk;
    zval tokenBuffer;
    zval inString;
    zval escaping;
    zval lastIndex;
} exttokens_object;

PHP_METHOD(ExtTokens, __construct)
{
    zval *jsonChunks;

    ZEND_PARSE_PARAMETERS_START(1, 1)
        Z_PARAM_ZVAL(jsonChunks)
    ZEND_PARSE_PARAMETERS_END();

    exttokens_object *this = Z_EXTTOKENS_OBJ_P(getThis());

    ZVAL_COPY(&this->jsonChunks, jsonChunks);
    ZVAL_EMPTY_STRING(&this->tokenBuffer);
    ZVAL_EMPTY_STRING(&this->chunk);
    ZVAL_EMPTY_STRING(&this->current);
    ZVAL_BOOL(&this->inString, false);
    ZVAL_BOOL(&this->escaping, false);
    ZVAL_LONG(&this->lastIndex, 0);
    this->key = -1;
    this->rewindCalled = false;
}

PHP_METHOD(ExtTokens, current)
{
    exttokens_object *this = Z_EXTTOKENS_OBJ_P(getThis());
    RETURN_ZVAL(&this->current, 0, 0);
}

PHP_METHOD(ExtTokens, next)
{
    exttokens_object *this = Z_EXTTOKENS_OBJ_P(getThis());

    this->key++;
    ZVAL_EMPTY_STRING(&this->current);
    if (Z_LVAL(this->lastIndex) && Z_LVAL(this->lastIndex) == Z_STRLEN(this->chunk)) {
        return;
    }
    ZVAL_EMPTY_STRING(return_value);

    do {
        jsonmachine_next_token(
            &this->chunk,
            Z_STRLEN(this->chunk),
            &this->tokenBuffer,
            &this->escaping,
            &this->inString,
            &this->lastIndex,
            return_value
        );

        if (Z_LVAL(this->lastIndex) == Z_STRLEN(this->chunk)) {
            zval valid;
            if (this->rewindCalled) {
                zend_call_method_with_0_params(Z_OBJ(this->jsonChunks), Z_OBJCE(this->jsonChunks), NULL, "next", NULL);
            } else {
                zend_call_method_with_0_params(Z_OBJ(this->jsonChunks), Z_OBJCE(this->jsonChunks), NULL, "rewind", NULL);
                this->rewindCalled = true;
            }
            zend_call_method_with_0_params(Z_OBJ(this->jsonChunks), Z_OBJCE(this->jsonChunks), NULL, "valid", &valid);
            if ( ! zBool(&valid)) {
                if (Z_STRLEN_P(return_value)) {
                    ZVAL_COPY(&this->current, return_value);
                } else {
                    ZVAL_COPY(&this->current, &this->tokenBuffer);
                }
                return;
            }
            zend_call_method_with_0_params(Z_OBJ(this->jsonChunks), Z_OBJCE(this->jsonChunks), NULL, "current", &this->chunk);
            // todo test me:
            if (Z_TYPE(this->chunk) != IS_STRING) {
                zend_error(E_ERROR, "Iterator providing token chunks must return string.");
            }
            ZVAL_LONG(&this->lastIndex, 0);
        }

    } while (Z_STRLEN_P(return_value) == 0);

    ZVAL_COPY(&this->current, return_value);
}

PHP_METHOD(ExtTokens, key)
{
    exttokens_object *this = Z_EXTTOKENS_OBJ_P(getThis());
    RETURN_LONG(this->key);
}

PHP_METHOD(ExtTokens, valid)
{
    exttokens_object *this = Z_EXTTOKENS_OBJ_P(getThis());
    RETURN_BOOL(Z_STRLEN(this->current));
}

PHP_METHOD(ExtTokens, rewind)
{
    exttokens_object *this = Z_EXTTOKENS_OBJ_P(getThis());
    zend_call_method_with_0_params(&this->std, this->std.ce, NULL, "next", NULL);
}

zend_object *exttokens_create_handler(zend_class_entry *ce)
{
    exttokens_object *objval = emalloc(sizeof(exttokens_object));
    memset(objval, 0, sizeof(exttokens_object));
    zend_object_std_init(&objval->std, ce);
    object_properties_init(&objval->std, ce);
    objval->std.handlers = &exttokens_object_handlers;
    return &objval->std;
}


void init_char_maps()
{
    for (int j = 0; j < 256; j++) {
        insignificantBytes[j] = true;
    }
    insignificantBytes[uc('\\')] = false;
    insignificantBytes[uc('"')] = false;
    insignificantBytes[uc('\xEF')] = false;
    insignificantBytes[uc('\xBB')] = false;
    insignificantBytes[uc('\xBF')] = false;
    insignificantBytes[uc(' ')] = false;
    insignificantBytes[uc('\n')] = false;
    insignificantBytes[uc('\r')] = false;
    insignificantBytes[uc('\t')] = false;
    insignificantBytes[uc('{')] = false;
    insignificantBytes[uc('}')] = false;
    insignificantBytes[uc('[')] = false;
    insignificantBytes[uc(']')] = false;
    insignificantBytes[uc(':')] = false;
    insignificantBytes[uc(',')] = false;

    for (int j = 0; j < 256; j++) {
        tokenBoundaries[j] = false;
    }
    tokenBoundaries[uc('\xEF')] = true;
    tokenBoundaries[uc('\xBB')] = true;
    tokenBoundaries[uc('\xBF')] = true;
    tokenBoundaries[uc(' ')] = true;
    tokenBoundaries[uc('\n')] = true;
    tokenBoundaries[uc('\r')] = true;
    tokenBoundaries[uc('\t')] = true;
    tokenBoundaries[uc('{')] = true;
    tokenBoundaries[uc('}')] = true;
    tokenBoundaries[uc('[')] = true;
    tokenBoundaries[uc(']')] = true;
    tokenBoundaries[uc(':')] = true;
    tokenBoundaries[uc(',')] = true;

    for (int j = 0; j < 256; j++) {
        colonCommaBracket[j] = false;
    }
    colonCommaBracket[uc('{')] = true;
    colonCommaBracket[uc('}')] = true;
    colonCommaBracket[uc('[')] = true;
    colonCommaBracket[uc(']')] = true;
    colonCommaBracket[uc(':')] = true;
    colonCommaBracket[uc(',')] = true;
}

PHP_MINIT_FUNCTION(jsonmachine)
{
    init_char_maps();

    zend_class_entry ce;
    INIT_CLASS_ENTRY(ce, "ExtTokens", class_ExtTokens_methods);
    exttokens_ce = zend_register_internal_class(&ce);
    exttokens_ce->create_object = exttokens_create_handler;
    zend_class_implements(exttokens_ce, 1, zend_ce_iterator);

    memcpy(&exttokens_object_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
    exttokens_object_handlers.clone_obj = NULL;

    return SUCCESS;
}

/* {{{ PHP_RINIT_FUNCTION */
PHP_RINIT_FUNCTION(jsonmachine)
{
#if defined(ZTS) && defined(COMPILE_DL_JSONMACHINE)
	ZEND_TSRMLS_CACHE_UPDATE();
#endif

	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MINFO_FUNCTION */
PHP_MINFO_FUNCTION(jsonmachine)
{
	php_info_print_table_start();
	php_info_print_table_header(2, "jsonmachine support", "enabled");
	php_info_print_table_end();
}
/* }}} */

/* {{{ jsonmachine_module_entry */
zend_module_entry jsonmachine_module_entry = {
	STANDARD_MODULE_HEADER,
	"jsonmachine",					/* Extension name */
	ext_functions,					/* zend_function_entry */
	PHP_MINIT(jsonmachine),			/* PHP_MINIT - Module initialization */
	NULL,							/* PHP_MSHUTDOWN - Module shutdown */
	PHP_RINIT(jsonmachine),			/* PHP_RINIT - Request initialization */
	NULL,							/* PHP_RSHUTDOWN - Request shutdown */
	PHP_MINFO(jsonmachine),			/* PHP_MINFO - Module info */
	PHP_JSONMACHINE_VERSION,		/* Version */
	STANDARD_MODULE_PROPERTIES
};
/* }}} */

#ifdef COMPILE_DL_JSONMACHINE
# ifdef ZTS
ZEND_TSRMLS_CACHE_DEFINE()
# endif
ZEND_GET_MODULE(jsonmachine)
#endif
