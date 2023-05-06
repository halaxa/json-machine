/* jsonmachine extension for PHP */

#ifndef PHP_JSONMACHINE_H
# define PHP_JSONMACHINE_H

extern zend_module_entry jsonmachine_module_entry;
# define phpext_jsonmachine_ptr &jsonmachine_module_entry

# define PHP_JSONMACHINE_VERSION "0.1.0"

# if defined(ZTS) && defined(COMPILE_DL_JSONMACHINE)
ZEND_TSRMLS_CACHE_EXTERN()
# endif

#endif	/* PHP_JSONMACHINE_H */
