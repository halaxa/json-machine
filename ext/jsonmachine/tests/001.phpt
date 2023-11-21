--TEST--
Check if jsonmachine is loaded
--SKIPIF--
<?php
if (!extension_loaded('jsonmachine')) {
    echo 'skip';
}
?>
--FILE--
<?php
echo 'The extension "jsonmachine" is available';
?>
--EXPECT--
The extension "jsonmachine" is available
