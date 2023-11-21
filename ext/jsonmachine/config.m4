dnl config.m4 for extension jsonmachine

dnl Comments in this file start with the string 'dnl'.
dnl Remove where necessary.

dnl If your extension references something external, use 'with':

dnl PHP_ARG_WITH([jsonmachine],
dnl   [for jsonmachine support],
dnl   [AS_HELP_STRING([--with-jsonmachine],
dnl     [Include jsonmachine support])])

dnl Otherwise use 'enable':

PHP_ARG_ENABLE([jsonmachine],
  [whether to enable jsonmachine support],
  [AS_HELP_STRING([--enable-jsonmachine],
    [Enable jsonmachine support])],
  [no])

if test "$PHP_JSONMACHINE" != "no"; then
  dnl Write more examples of tests here...

  dnl Remove this code block if the library does not support pkg-config.
  dnl PKG_CHECK_MODULES([LIBFOO], [foo])
  dnl PHP_EVAL_INCLINE($LIBFOO_CFLAGS)
  dnl PHP_EVAL_LIBLINE($LIBFOO_LIBS, JSONMACHINE_SHARED_LIBADD)

  dnl If you need to check for a particular library version using PKG_CHECK_MODULES,
  dnl you can use comparison operators. For example:
  dnl PKG_CHECK_MODULES([LIBFOO], [foo >= 1.2.3])
  dnl PKG_CHECK_MODULES([LIBFOO], [foo < 3.4])
  dnl PKG_CHECK_MODULES([LIBFOO], [foo = 1.2.3])

  dnl Remove this code block if the library supports pkg-config.
  dnl --with-jsonmachine -> check with-path
  dnl SEARCH_PATH="/usr/local /usr"     # you might want to change this
  dnl SEARCH_FOR="/include/jsonmachine.h"  # you most likely want to change this
  dnl if test -r $PHP_JSONMACHINE/$SEARCH_FOR; then # path given as parameter
  dnl   JSONMACHINE_DIR=$PHP_JSONMACHINE
  dnl else # search default path list
  dnl   AC_MSG_CHECKING([for jsonmachine files in default path])
  dnl   for i in $SEARCH_PATH ; do
  dnl     if test -r $i/$SEARCH_FOR; then
  dnl       JSONMACHINE_DIR=$i
  dnl       AC_MSG_RESULT(found in $i)
  dnl     fi
  dnl   done
  dnl fi
  dnl
  dnl if test -z "$JSONMACHINE_DIR"; then
  dnl   AC_MSG_RESULT([not found])
  dnl   AC_MSG_ERROR([Please reinstall the jsonmachine distribution])
  dnl fi

  dnl Remove this code block if the library supports pkg-config.
  dnl --with-jsonmachine -> add include path
  dnl PHP_ADD_INCLUDE($JSONMACHINE_DIR/include)

  dnl Remove this code block if the library supports pkg-config.
  dnl --with-jsonmachine -> check for lib and symbol presence
  dnl LIBNAME=JSONMACHINE # you may want to change this
  dnl LIBSYMBOL=JSONMACHINE # you most likely want to change this

  dnl If you need to check for a particular library function (e.g. a conditional
  dnl or version-dependent feature) and you are using pkg-config:
  dnl PHP_CHECK_LIBRARY($LIBNAME, $LIBSYMBOL,
  dnl [
  dnl   AC_DEFINE(HAVE_JSONMACHINE_FEATURE, 1, [ ])
  dnl ],[
  dnl   AC_MSG_ERROR([FEATURE not supported by your jsonmachine library.])
  dnl ], [
  dnl   $LIBFOO_LIBS
  dnl ])

  dnl If you need to check for a particular library function (e.g. a conditional
  dnl or version-dependent feature) and you are not using pkg-config:
  dnl PHP_CHECK_LIBRARY($LIBNAME, $LIBSYMBOL,
  dnl [
  dnl   PHP_ADD_LIBRARY_WITH_PATH($LIBNAME, $JSONMACHINE_DIR/$PHP_LIBDIR, JSONMACHINE_SHARED_LIBADD)
  dnl   AC_DEFINE(HAVE_JSONMACHINE_FEATURE, 1, [ ])
  dnl ],[
  dnl   AC_MSG_ERROR([FEATURE not supported by your jsonmachine library.])
  dnl ],[
  dnl   -L$JSONMACHINE_DIR/$PHP_LIBDIR -lm
  dnl ])
  dnl
  dnl PHP_SUBST(JSONMACHINE_SHARED_LIBADD)

  dnl In case of no dependencies
  AC_DEFINE(HAVE_JSONMACHINE, 1, [ Have jsonmachine support ])

  PHP_NEW_EXTENSION(jsonmachine, jsonmachine.c, $ext_shared)
fi
