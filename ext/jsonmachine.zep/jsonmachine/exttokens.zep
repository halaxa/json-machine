namespace JsonMachine;

use Iterator;

class ExtTokens implements \Iterator
{
    /** @var Iterator */
    private jsonChunks;

    /** @var array */
    private tokenBoundaries = [];
    /** @var array  */
    private jsonInsignificantBytes = [];
    /** @var string */
    private carryToken = "";
    /** @var string */
    private current = "";
    /** @var int */
    private key = -1;
    /** @var string */
    private chunk;
    /** @var int */
    private chunkLength;
    /** @var int */
    private chunkIndex;

    /** @var bool */
    private inString = false;
    /** @var string */
    private tokenBuffer = "";
    /** @var bool */
    private escaping = false;

    /**
     * @param Iterator<string> $jsonChunks
     */
    public function __construct(<Iterator> jsonChunks)
    {
        let this->jsonChunks = jsonChunks;
        let this->tokenBoundaries = this->mapOfBoundaryBytes();
        let this->jsonInsignificantBytes = this->jsonInsignificantBytes();
    }

    public function rewind()
    {
        this->jsonChunksRewind();
        this->next();
    }

    public function next()
    {
        string byte;
        let this->current = "";

        while (this->chunkIndex < this->chunkLength) {
            if (this->carryToken != "") {
                let this->current = this->carryToken;
                let this->carryToken = "";
                let this->key++;
                return;
            }
            let byte = substr(this->chunk, this->chunkIndex, 1);
            if unlikely this->escaping {
                let this->escaping = false;
                let this->tokenBuffer = this->tokenBuffer . byte;
                let this->chunkIndex++;
                continue;
            }
            if (this->jsonInsignificantBytes[byte]) {
                let this->tokenBuffer = this->tokenBuffer . byte;
                let this->chunkIndex++;
                continue;
            }
            if (this->inString) {
                if (byte == "\"") {
                    let this->inString = false;
                } elseif (byte == "\\") {
                    let this->escaping = true;
                }
                let this->tokenBuffer = this->tokenBuffer . byte;
                let this->chunkIndex++;
                continue;
            }
            if (isset this->tokenBoundaries[byte]) {
                // if byte is any token boundary
                if (this->tokenBuffer != "") {
                    let this->current = this->tokenBuffer;
                    let this->tokenBuffer = "";
                }
                if (this->tokenBoundaries[byte]) {
                    // if byte is not whitespace token boundary
                    let this->carryToken = byte;
                }
                if (this->current != "") {
                    let this->key++;
                    let this->chunkIndex++;
                    return;
                }
            } else {
                if (byte == "\"") {
                    let this->inString = true;
                }
                let this->tokenBuffer = this->tokenBuffer . byte;
            }

            let this->chunkIndex++;
        }
        if (this->jsonChunksNext()) {
            this->next();
        } elseif (this->carryToken) {
            let this->current = this->carryToken;
            let this->carryToken = "";
            let this->key++;
        }
    }

    public function valid()
    {
        return this->current !== "";
    }

    public function current()
    {
        return this->current;
    }

    public function key()
    {
        return this->key;
    }

    private function mapOfBoundaryBytes() -> array
    {
        var boundary;
        var utf8bom;

        let utf8bom = "﻿";
        let boundary = [];
        let boundary[substr(utf8bom, 0, 1)] = 0;
        let boundary[substr(utf8bom, 1, 1)] = 0;
        let boundary[substr(utf8bom, 2, 1)] = 0;
        let boundary[" "] = 0;
        let boundary["\n"] = 0;
        let boundary["\r"] = 0;
        let boundary["\t"] = 0;
        let boundary["{"] = 1;
        let boundary["}"] = 1;
        let boundary["["] = 1;
        let boundary["]"] = 1;
        let boundary[":"] = 1;
        let boundary[","] = 1;

        return boundary;
    }

    private function jsonInsignificantBytes() -> array
    {
        var bytes;
        let bytes = [];
        var ord;
for ord in range(0, 255) {
            let bytes[chr(ord)] = !in_array(chr(ord), ["\\", "\"", "\xef", "\xbb", "\xbf", " ", "\n", "\r", "\t", "{", "}", "[", "]", ":", ","]);
        }

        return bytes;
    }

    private function initCurrentChunk() -> bool
    {
        var valid;
        let valid = this->jsonChunks->valid();
        if (valid) {
            let this->chunk = this->jsonChunks->current();
            let this->chunkLength = strlen(this->chunk);
            let this->chunkIndex = 0;
        }
        return valid;
    }

    public function getPosition() -> int
    {
        return 0;
    }

    public function getLine() -> int
    {
        return 1;
    }

    public function getColumn() -> int
    {
        return 0;
    }

    private function jsonChunksRewind() -> bool
    {
        this->jsonChunks->rewind();
        return this->initCurrentChunk();
    }

    private function jsonChunksNext() -> bool
    {
        this->jsonChunks->next();
        return this->initCurrentChunk();
    }
}
