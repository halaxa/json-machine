<?php

namespace JsonMachine;

use JsonMachine\Exception\InvalidArgumentException;
use JsonMachine\JsonDecoder\ExtJsonDecoder;
use JsonMachine\JsonDecoder\ItemDecoder;

/**
 * Entry-point facade for JSON Machine.
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
     * @param iterable     $bytesIterator
     * @param array|string $jsonPointer
     * @param ItemDecoder  $jsonDecoder
     * @param bool         $debugEnabled
     *
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
            $this->jsonDecoder ?: new ExtJsonDecoder()
        );
    }

    /**
     * @param string $string
     *
     * @return self
     *
     * @throws InvalidArgumentException
     */
    public static function fromString($string, array $options = [])
    {
        $opts = self::normalizeOptions($options);

        return new self(new StringChunks($string), $opts['pointer'], $opts['decoder'], $opts['debug']);
    }

    /**
     * @param string $file
     *
     * @return self
     *
     * @throws Exception\InvalidArgumentException
     */
    public static function fromFile($file, array $options = [])
    {
        $opts = self::normalizeOptions($options);

        return new self(new FileChunks($file), $opts['pointer'], $opts['decoder'], $opts['debug']);
    }

    /**
     * @param resource $stream
     *
     * @return self
     *
     * @throws Exception\InvalidArgumentException
     */
    public static function fromStream($stream, array $options = [])
    {
        $opts = self::normalizeOptions($options);

        return new self(new StreamChunks($stream), $opts['pointer'], $opts['decoder'], $opts['debug']);
    }

    /**
     * @param iterable $iterable
     *
     * @return self
     *
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

    public function getJsonPointers(): array
    {
        return $this->parser->getJsonPointers();
    }

    public function getCurrentJsonPointer(): string
    {
        return $this->parser->getCurrentJsonPointer();
    }

    public function getMatchedJsonPointer(): string
    {
        return $this->parser->getMatchedJsonPointer();
    }

    /**
     * @return array{pointer: string, decoder: ItemDecoder, debug: bool}
     *
     * @throws InvalidArgumentException
     */
    private static function normalizeOptions(array $options): array
    {
        $pointerKey = isset($options['pointers']) ? 'pointers' : 'pointer';
        $pointerType = ($pointerKey === 'pointers') ? 'array' : 'string';

        self::optionMustBeType($options, $pointerKey, $pointerType);
        self::optionMustBeType($options, 'decoder', ItemDecoder::class);
        self::optionMustBeType($options, 'debug', 'boolean');

        return [
            'pointer' => $options[$pointerKey] ?? '',
            'decoder' => $options['decoder'] ?? new ExtJsonDecoder(),
            'debug' => $options['debug'] ?? false,
        ];
    }

    private static function optionMustBeType(array $options, string $name, string $type)
    {
        if ( ! isset($options[$name])) {
            return;
        }

        $value = $options[$name];

        if (class_exists($type) || interface_exists($type)) {
            if ( ! $value instanceof $type) {
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
