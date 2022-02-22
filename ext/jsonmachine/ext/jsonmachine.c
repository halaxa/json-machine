
/* This file was generated automatically by Zephir do not modify it! */

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include <php.h>

#include "php_ext.h"
#include "jsonmachine.h"

#include <ext/standard/info.h>

#include <Zend/zend_operators.h>
#include <Zend/zend_exceptions.h>
#include <Zend/zend_interfaces.h>

#include "kernel/globals.h"
#include "kernel/main.h"
#include "kernel/fcall.h"
#include "kernel/memory.h"



zend_class_entry *jsonmachine_exttokens_ce;

ZEND_DECLARE_MODULE_GLOBALS(jsonmachine)

PHP_INI_BEGIN()
	
PHP_INI_END()

static PHP_MINIT_FUNCTION(jsonmachine)
{
	REGISTER_INI_ENTRIES();
	zephir_module_init();
	ZEPHIR_INIT(JsonMachine_ExtTokens);
	
	return SUCCESS;
}

#ifndef ZEPHIR_RELEASE
static PHP_MSHUTDOWN_FUNCTION(jsonmachine)
{
	
	zephir_deinitialize_memory();
	UNREGISTER_INI_ENTRIES();
	return SUCCESS;
}
#endif

/**
 * Initialize globals on each request or each thread started
 */
static void php_zephir_init_globals(zend_jsonmachine_globals *jsonmachine_globals)
{
	jsonmachine_globals->initialized = 0;

	/* Cache Enabled */
	jsonmachine_globals->cache_enabled = 1;

	/* Recursive Lock */
	jsonmachine_globals->recursive_lock = 0;

	/* Static cache */
	memset(jsonmachine_globals->scache, '\0', sizeof(zephir_fcall_cache_entry*) * ZEPHIR_MAX_CACHE_SLOTS);

	
	
}

/**
 * Initialize globals only on each thread started
 */
static void php_zephir_init_module_globals(zend_jsonmachine_globals *jsonmachine_globals)
{
	
}

static PHP_RINIT_FUNCTION(jsonmachine)
{
	zend_jsonmachine_globals *jsonmachine_globals_ptr;
	jsonmachine_globals_ptr = ZEPHIR_VGLOBAL;

	php_zephir_init_globals(jsonmachine_globals_ptr);
	zephir_initialize_memory(jsonmachine_globals_ptr);

	
	return SUCCESS;
}

static PHP_RSHUTDOWN_FUNCTION(jsonmachine)
{
	
	zephir_deinitialize_memory();
	return SUCCESS;
}



static PHP_MINFO_FUNCTION(jsonmachine)
{
	php_info_print_box_start(0);
	php_printf("%s", PHP_JSONMACHINE_DESCRIPTION);
	php_info_print_box_end();

	php_info_print_table_start();
	php_info_print_table_header(2, PHP_JSONMACHINE_NAME, "enabled");
	php_info_print_table_row(2, "Author", PHP_JSONMACHINE_AUTHOR);
	php_info_print_table_row(2, "Version", PHP_JSONMACHINE_VERSION);
	php_info_print_table_row(2, "Build Date", __DATE__ " " __TIME__ );
	php_info_print_table_row(2, "Powered by Zephir", "Version " PHP_JSONMACHINE_ZEPVERSION);
	php_info_print_table_end();
	
	DISPLAY_INI_ENTRIES();
}

static PHP_GINIT_FUNCTION(jsonmachine)
{
#if defined(COMPILE_DL_JSONMACHINE) && defined(ZTS)
	ZEND_TSRMLS_CACHE_UPDATE();
#endif

	php_zephir_init_globals(jsonmachine_globals);
	php_zephir_init_module_globals(jsonmachine_globals);
}

static PHP_GSHUTDOWN_FUNCTION(jsonmachine)
{
	
}


zend_function_entry php_jsonmachine_functions[] = {
	ZEND_FE_END

};

static const zend_module_dep php_jsonmachine_deps[] = {
	
	ZEND_MOD_END
};

zend_module_entry jsonmachine_module_entry = {
	STANDARD_MODULE_HEADER_EX,
	NULL,
	php_jsonmachine_deps,
	PHP_JSONMACHINE_EXTNAME,
	php_jsonmachine_functions,
	PHP_MINIT(jsonmachine),
#ifndef ZEPHIR_RELEASE
	PHP_MSHUTDOWN(jsonmachine),
#else
	NULL,
#endif
	PHP_RINIT(jsonmachine),
	PHP_RSHUTDOWN(jsonmachine),
	PHP_MINFO(jsonmachine),
	PHP_JSONMACHINE_VERSION,
	ZEND_MODULE_GLOBALS(jsonmachine),
	PHP_GINIT(jsonmachine),
	PHP_GSHUTDOWN(jsonmachine),
#ifdef ZEPHIR_POST_REQUEST
	PHP_PRSHUTDOWN(jsonmachine),
#else
	NULL,
#endif
	STANDARD_MODULE_PROPERTIES_EX
};

/* implement standard "stub" routine to introduce ourselves to Zend */
#ifdef COMPILE_DL_JSONMACHINE
# ifdef ZTS
ZEND_TSRMLS_CACHE_DEFINE()
# endif
ZEND_GET_MODULE(jsonmachine)
#endif
