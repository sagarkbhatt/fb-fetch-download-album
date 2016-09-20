## [3.0.4] - 2015-12-15
### Fixed
- Fixed a bug of generating multipart file headers.

## [3.0.3] - 2015-12-15
### Added
- `withHttpProxy()` method to quickly set HTTP proxy.
- `withSock5Proxy()` method to quickly set SOCK5 proxy.
- `getAllResponseCookies()` as alias of `getRedirectedCookies()`

## [3.0.0] - 2015-07-26
### Changed
- Rewrite this library
- Supports HTTP/ Sock5 Proxy (Sock proxy require curl extension)
- With a set of helper methods to easily set dynamic cookies, headers, form params, multipart data, form file, json data, etc..
- More improvement, bug fixes in previous version
