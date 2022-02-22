
/* This file was generated automatically by Zephir do not modify it! */

#ifndef PHP_JSONMACHINE_H
#define PHP_JSONMACHINE_H 1

#ifdef PHP_WIN32
#define ZEPHIR_RELEASE 1
#endif

#include "kernel/globals.h"

#define PHP_JSONMACHINE_NAME        "jsonmachine"
#define PHP_JSONMACHINE_VERSION     "0.0.1"
#define PHP_JSONMACHINE_EXTNAME     "jsonmachine"
#define PHP_JSONMACHINE_AUTHOR      "Phalcon Team"
#define PHP_JSONMACHINE_ZEPVERSION  "0.15.2-5828ae2"
#define PHP_JSONMACHINE_DESCRIPTION ""



ZEND_BEGIN_MODULE_GLOBALS(jsonmachine)

	int initialized;

	/** Function cache */
	HashTable *fcache;

	zephir_fcall_cache_entry *scache[ZEPHIR_MAX_CACHE_SLOTS];

	/* Cache enabled */
	unsigned int cache_enabled;

	/* Max recursion control */
	unsigned int recursive_lock;

	
ZEND_END_MODULE_GLOBALS(jsonmachine)

#ifdef ZTS
#include "TSRM.h"
#endif

ZEND_EXTERN_MODULE_GLOBALS(jsonmachine)

#ifdef ZTS
	#define ZEPHIR_GLOBAL(v) ZEND_MODULE_GLOBALS_ACCESSOR(jsonmachine, v)
#else
	#define ZEPHIR_GLOBAL(v) (jsonmachine_globals.v)
#endif

#ifdef ZTS
	ZEND_TSRMLS_CACHE_EXTERN()
	#define ZEPHIR_VGLOBAL ((zend_jsonmachine_globals *) (*((void ***) tsrm_get_ls_cache()))[TSRM_UNSHUFFLE_RSRC_ID(jsonmachine_globals_id)])
#else
	#define ZEPHIR_VGLOBAL &(jsonmachine_globals)
#endif

#define ZEPHIR_API ZEND_API

#define zephir_globals_def jsonmachine_globals
#define zend_zephir_globals_def zend_jsonmachine_globals

extern zend_module_entry jsonmachine_module_entry;
#define phpext_jsonmachine_ptr &jsonmachine_module_entry

#endif
