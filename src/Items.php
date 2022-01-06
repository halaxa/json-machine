<?php

namespace JsonMachine;

use JsonMachine\Exception\InvalidArgumentException;
use JsonMachine\JsonDecoder\ItemDecoder;
use JsonMachine\JsonDecoder\ExtJsonDecoder;

/**
 * Entry-point facade for JSON Machine
 */
final class Items implements \IteratorAggregate, PositionAware
{
    /**
     * @var iterable
     */
    private $bytesIterator;

    /**
     * @var string
     */
    private $jsonPointer;

    /**
     * @var ItemDecoder|null
     */
    private $jsonDecoder;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var bool
     */
    private $debugEnabled;

    /**
     * @param iterable $bytesIterator
     * @param string $jsonPointer
     * @param ItemDecoder $jsonDecoder
     * @param bool $debugEnabled
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($bytesIterator, $jsonPointer = '', ItemDecoder $jsonDecoder = null, $debugEnabled = false)
    {
        $this->bytesIterator = $bytesIterator;
        $this->jsonPointer = $jsonPointer;
        $this->jsonDecoder = $jsonDecoder;
        $this->debugEnabled = $debugEnabled;

        if ($debugEnabled) {
            $lexerClass = DebugLexer::class;
        } else {
            $lexerClass = Lexer::class;
        }

        $this->parser = new Parser(
            new $lexerClass(
                $this->bytesIterator
            ),
            $this->jsonPointer,
            $this->jsonDecoder ?: new ExtJsonDecoder(false)
        );
    }

    /**
     * @param string $string
     * @param array $options
     * @return self
     * @throws InvalidArgumentException
     */
    public static function fromString($string, array $options = [])
    {
        $opts = self::normalizeOptions($options);

        return new self(new StringChunks($string), $opts['pointer'], $opts['decoder'], $opts['debug']);
    }

    /**
     * @param string $file
     * @param array $options
     * @return self
     * @throws Exception\InvalidArgumentException
     */
    public static function fromFile($file, array $options = [])
    {
        $opts = self::normalizeOptions($options);

        return new self(new FileChunks($file), $opts['pointer'], $opts['decoder'], $opts['debug']);
    }

    /**
     * @param resource $stream
     * @param array $options
     * @return self
     * @throws Exception\InvalidArgumentException
     */
    public static function fromStream($stream, array $options = [])
    {
        $opts = self::normalizeOptions($options);

        return new self(new StreamChunks($stream), $opts['pointer'], $opts['decoder'], $opts['debug']);
    }

    /**
     * @param iterable $iterable
     * @param array $options
     * @return self
     * @throws Exception\InvalidArgumentException
     */
    public static function fromIterable($iterable, array $options = [])
    {
        $opts = self::normalizeOptions($options);

        return new self($iterable, $opts['pointer'], $opts['decoder'], $opts['debug']);
    }

    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return $this->parser->getIterator();
    }

    public function getPosition()
    {
        return $this->parser->getPosition();
    }

    /**
     * @param array $options
     * @return array{pointer: string, decoder: ItemDecoder, debug: bool}
     * @throws InvalidArgumentException
     */
    private static function normalizeOptions(array $options)
    {
        $mergedOptions = array_merge([
            'pointer' => '',
            'decoder' => null,
            'debug' => false,
        ], $options);

        self::optionMustBeType('pointer', $mergedOptions['pointer'], 'string');
        self::optionMustBeType('decoder', $mergedOptions['decoder'], ItemDecoder::class);
        self::optionMustBeType('debug', $mergedOptions['debug'], 'boolean');

        return $mergedOptions;
    }

    private static function optionMustBeType($name, $value, $type)
    {
        if ($value === null) {
            return;
        }

        if (class_exists($type) || interface_exists($type)) {
            if (! $value instanceof $type) {
                throw new InvalidArgumentException(
                    sprintf(
                        "Option '$name' must be an instance of $type, %s given.",
                        is_object($value) ? gettype($value) : get_class($value)
                    )
                );
            }
        } elseif (gettype($value) !== $type) {
            throw new InvalidArgumentException(
                sprintf(
                    "Option '$name' must be $type, %s given.",
                    gettype($value)
                )
            );
        }
    }

    /**
     * @return bool
     */
    public function isDebugEnabled()
    {
        return $this->debugEnabled;
    }
}
