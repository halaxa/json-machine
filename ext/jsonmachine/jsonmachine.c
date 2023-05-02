/* jsonmachine extension for PHP */

#ifdef HAVE_CONFIG_H
# include "config.h"
#endif

#include "php.h"
#include "ext/standard/info.h"
#include "php_jsonmachine.h"
#include "jsonmachine_arginfo.h"

#include <stdio.h>
#include <string.h>

/* For compatibility with older PHP versions */
#ifndef ZEND_PARSE_PARAMETERS_NONE
#define ZEND_PARSE_PARAMETERS_NONE() \
	ZEND_PARSE_PARAMETERS_START(0, 0) \
	ZEND_PARSE_PARAMETERS_END()
#endif

unsigned char uc(char ch)
{
    return (unsigned char) ch;
}

bool zBool(zval *trueFalse)
{
    return Z_TYPE_P(trueFalse) == IS_TRUE;
}

PHP_FUNCTION(jsonmachine_next_token)
{
    char *chunk;
    size_t chunk_len;
    zval *zTokenBuffer;
    zval *zEscaping;
    zval *zInString;
    zval *zLastIndex;

    ZEND_PARSE_PARAMETERS_START(5, 5)
        Z_PARAM_STRING(chunk, chunk_len)
        Z_PARAM_ZVAL(zTokenBuffer)
        Z_PARAM_ZVAL(zEscaping)
        Z_PARAM_ZVAL(zInString)
        Z_PARAM_ZVAL(zLastIndex)
    ZEND_PARSE_PARAMETERS_END();

/// pure c POC

    char json[] = "[{\"one\": 1}, {\"two\": false}, {\"thr\\\"ee\": \"string\"}]";
    printf("%s\n", json);

    char * tokenBuffer = Z_STRVAL_P(zTokenBuffer);
    zend_bool escaping = zBool(zEscaping);
    zend_bool inString = zBool(zInString);

    bool insignificantBytes[256];
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


    bool tokenBoundaries[256];
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


    bool colonCommaBracket[256];
    for (int j = 0; j < 256; j++) {
        colonCommaBracket[j] = false;
    }

    colonCommaBracket[uc('{')] = true;
    colonCommaBracket[uc('}')] = true;
    colonCommaBracket[uc('[')] = true;
    colonCommaBracket[uc(']')] = true;
    colonCommaBracket[uc(':')] = true;
    colonCommaBracket[uc(',')] = true;

    unsigned char byte;
    int i;
    for (i = Z_LVAL_P(zLastIndex); i < strlen(json); i++) {
        byte = json[i];

        if (escaping) {
            escaping = false;
            tokenBuffer[strlen(tokenBuffer)] = byte;
            continue;
        }

        if (insignificantBytes[byte]) {
            tokenBuffer[strlen(tokenBuffer)] = byte;
            continue;
        }

        if (inString) {
            if (byte == '"') {
                inString = false;
            } else if (byte == '\\') {
                escaping = true;
            }
            tokenBuffer[strlen(tokenBuffer)] = byte;
            continue;
        }

        if (tokenBoundaries[byte]) {
            if (strlen(tokenBuffer)) {
                printf("%s\n", tokenBuffer);
                ZVAL_BOOL(Z_REFVAL_P(zEscaping), false);
                ZVAL_BOOL(Z_REFVAL_P(zInString), false);
                ZVAL_STRING(Z_REFVAL_P(zTokenBuffer), "");
                ZVAL_LONG(Z_REFVAL_P(zLastIndex), i);
                RETURN_STR((zend_string *) &tokenBuffer);
//                memset(tokenBuffer,0,strlen(tokenBuffer));
            }
            if (colonCommaBracket[byte]) {
                printf("%c\n", byte);
                ZVAL_BOOL(Z_REFVAL_P(zEscaping), false);
                ZVAL_BOOL(Z_REFVAL_P(zInString), false);
                ZVAL_STRING(Z_REFVAL_P(zTokenBuffer), "");
                ZVAL_LONG(Z_REFVAL_P(zLastIndex), i+1);
                RETURN_STR((zend_string *) &byte);
            }
        } else { // else branch matches `"` but also `\` outside of a string literal which is an error anyway but strictly speaking not correctly parsed token
            inString = true;
            tokenBuffer[strlen(tokenBuffer)] = byte;
        }
    }

    ZVAL_STRING(Z_REFVAL_P(zTokenBuffer), tokenBuffer);
    ZVAL_BOOL(Z_REFVAL_P(zEscaping), escaping);
    ZVAL_BOOL(Z_REFVAL_P(zInString), inString);
    ZVAL_LONG(Z_REFVAL_P(zLastIndex), i+1);
}


/* {{{ void test1() */
PHP_FUNCTION(test1)
{
	ZEND_PARSE_PARAMETERS_NONE();

	php_printf("The extension %s is loaded and working!\r\n", "jsonmachine");
}
/* }}} */

/* {{{ string test2( [ string $var ] ) */
PHP_FUNCTION(test2)
{
	char *var = "World";
	size_t var_len = sizeof("World") - 1;
	zend_string *retval;

	ZEND_PARSE_PARAMETERS_START(0, 1)
		Z_PARAM_OPTIONAL
		Z_PARAM_STRING(var, var_len)
	ZEND_PARSE_PARAMETERS_END();

	retval = strpprintf(0, "Hello %s", var);

	RETURN_STR(retval);
}
/* }}}*/

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
	NULL,							/* PHP_MINIT - Module initialization */
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
