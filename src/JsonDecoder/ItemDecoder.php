<?php


namespace JsonMachine\JsonDecoder;

interface ItemDecoder
{
    /**
     * Decodes object keys in the document so Parser can keep track of where it is. These are not the keys that
     * are yielded to the user, only those necessary to find correct json pointer path while traversing the document.
     * It MUST return ValidStringResult on success, because object keys in json pointer (which is to be matched) are
     * always strings.
     *
     * @return InvalidResult|ValidStringResult
     */
    public function decodeInternalKey($jsonScalarKey);

    /**
     * Decodes keys which are directly yielded to the user.
     *
     * @return InvalidResult|ValidResult
     */
    public function decodeKey($jsonScalarKey);

    /**
     * Decodes composite or scalar values which are directly yielded to the user.
     *
     * @return InvalidResult|ValidResult
     */
    public function decodeValue($jsonValue);
}
