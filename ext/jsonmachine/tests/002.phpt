--TEST--
test1() Basic test
--SKIPIF--
<?php
if (!extension_loaded('jsonmachine')) {
    echo 'skip';
}
?>
--FILE--
<?php
$ret = test1();

var_dump($ret);
?>
--EXPECT--
The extension jsonmachine is loaded and working!
NULL
