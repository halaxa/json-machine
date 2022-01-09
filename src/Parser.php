<?php declare(strict_types=1);


namespace JsonMachine;


use IteratorAggregate;
use JsonMachine\JsonDecoder\ItemDecoder;
use Traversable;

class Parser implements IteratorAggregate, PositionAware
{
    /** @var FollowUpParser */
    private $followUpParser;

    /**
     * @param Traversable $lexer
     * @param string $jsonPointer Follows json pointer RFC https://tools.ietf.org/html/rfc6901
     * @param ItemDecoder $jsonDecoder
     */
    public function __construct(Traversable $lexer, $jsonPointer = '', ItemDecoder $jsonDecoder = null)
    {
        $this->followUpParser = new FollowUpParser($lexer, $jsonPointer, $jsonDecoder);
    }

    public function getIterator()
    {
        yield from $this->followUpParser;

        $this->followUpParser->end();
    }

    public function getPosition()
    {
        return $this->followUpParser->getPosition();
    }

    public function getJsonPointerPath()
    {
        return $this->followUpParser->getJsonPointerPath();
    }
}
