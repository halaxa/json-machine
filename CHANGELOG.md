# master
## New features
- [Custom decoder](README.md#custom-decoder)
- `ext-json` is not required anymore, because custom decoder might not need it.
Built-in decoders however depend on it so it must be present if you use them. 
## BC breaks
- Function `httpClientChunks()` is deprecated so that compatibility with Symfony HttpClient
is not on the maintainer of JSON Machine. The code is simple and everyone can make their own
function and maintain it.
- Invalid json object keys will now throw and won't be ignored anymore.
## Fixed bugs
- Decoding of json object keys checks for errors and does not silently ignore them.
