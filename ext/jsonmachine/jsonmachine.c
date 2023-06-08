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

#define Z_EXTTOKENS_OBJ_P(zv) ((exttokens_object *)Z_OBJ_P((zv)))

typedef struct _exttokens_object {
    zend_object std;
    zval jsonChunks;

    ssize_t key;
    zval current;

    bool rewindCalled;
    zval chunk;
    zval tokenBuffer;
    bool inString;
    bool escaping;
    size_t lastIndex;
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
    this->inString = false;
    this->escaping = false;
    this->lastIndex = 0;
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
    zval token;
    ZVAL_EMPTY_STRING(&token);

    exttokens_object *this = Z_EXTTOKENS_OBJ_P(getThis());
    ZVAL_EMPTY_STRING(&this->current);

    this->key++;

    if (this->lastIndex && this->lastIndex == Z_STRLEN(this->chunk)) {
        return;
    }

    do {
        do {
            char * chunk = Z_STRVAL(this->chunk);

            size_t i;
            for (i = this->lastIndex; i < Z_STRLEN(this->chunk); i++) {
                unsigned char byte;
                byte = (unsigned char) chunk[i];
                if (this->escaping) {
                    this->escaping = false;
                    append_char_to_zval_string(&this->tokenBuffer, byte);
                    continue;
                }
                if (insignificantBytes[byte]) {
                    append_char_to_zval_string(&this->tokenBuffer, byte);
                    continue;
                }
                if (this->inString) {
                    if (byte == '"') {
                        this->inString = false;
                    } else if (byte == '\\') {
                        this->escaping = true;
                    }
                    append_char_to_zval_string(&this->tokenBuffer, byte);

                    continue;
                }

                if (tokenBoundaries[byte]) {
                    if (Z_STRLEN(this->tokenBuffer)) {
                        this->lastIndex = i;
                        ZVAL_COPY(&token, &this->tokenBuffer);
                        ZVAL_EMPTY_STRING(&this->tokenBuffer);
                        goto after;
                    }
                    if (colonCommaBracket[byte]) {
                        this->lastIndex = i+1;
                        ZVAL_STR(&token, zend_string_init((char *)&byte, 1, 0));
                        goto after;
                    }
                } else { // else branch matches `"` but also `\` outside of a string literal which is an error anyway but strictly speaking not correctly parsed token
                    this->inString = true;
                    append_char_to_zval_string(&this->tokenBuffer, byte);
                }
            }

            this->lastIndex = i;
        } while (0);

        after:
        if (this->lastIndex == Z_STRLEN(this->chunk)) {
            zval valid;
            if (this->rewindCalled) {
                zend_call_method_with_0_params(Z_OBJ(this->jsonChunks), Z_OBJCE(this->jsonChunks), NULL, "next", NULL);
            } else {
                zend_call_method_with_0_params(Z_OBJ(this->jsonChunks), Z_OBJCE(this->jsonChunks), NULL, "rewind", NULL);
                this->rewindCalled = true;
            }
            zend_call_method_with_0_params(Z_OBJ(this->jsonChunks), Z_OBJCE(this->jsonChunks), NULL, "valid", &valid);
            if ( ! zBool(&valid)) {
                if (Z_STRLEN_P(&token)) {
                    ZVAL_COPY(&this->current, &token);
                } else {
                    ZVAL_COPY(&this->current, &this->tokenBuffer);
                }
                return;
            }
            zend_call_method_with_0_params(Z_OBJ(this->jsonChunks), Z_OBJCE(this->jsonChunks), NULL, "current", &this->chunk);
            // todo test me:
            if (Z_TYPE(this->chunk) != IS_STRING) {
                zend_error(E_ERROR, "Iterator providing token chunks must produce strings.");
            }
            this->lastIndex = 0;
        }
    } while (Z_STRLEN(token) == 0);

    ZVAL_COPY(&this->current, &token);
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
	NULL,       					/* zend_function_entry */
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
