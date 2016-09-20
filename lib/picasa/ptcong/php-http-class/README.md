# Simple PHP Http Client

A simple Http library that inspired by PSR-7 and working with curl/socket for sending request.

## Requirements
PHP needs to be a minimum version of PHP 5.0 with socket or curl enabled.

## Installation

To install this library, install composer and issue the following command:

```
composer require "ptcong/php-http-class": "^3.0"
composer update
```

If your hosting is running with PHP 5.2, you should refer to this library:
https://bitbucket.org/xrstf/composer-php52

## Usage
First, you neeed to include composer autoloader.
```php
require dirname(__FILE__).'/vendor/autoload.php';
```

* [Create a client](#create-a-client)
* [Options and Helper methods](#options-and-helper-methods)
* [Some simple options (timeout, protocol version, user-agent, etc...)](#some-simple-options)
* [Send with headers](#with-headers)
* [Send with cookies](#with-cookies)
* [Send with query string](#with-query-string)
* [Send with form params](#with-form-params)
* [Send with multipart data](#with-multipart-data)
* [Upload a file](#upload-a-file)
* [Post RAW data](#post-raw-data)
* [Post JSON data](#post-json-data)
* [Use HTTP/Sock proxy](#with-http-sock5-proxy)
* [Use Auth basic](#with-auth-basic)
* [Sending and get response](#send-request-and-get-responses)
    - [Get send result](#send-request-and-get-responses)
    - [Get response status code](#get-response-status-code)
    - [Get response text](#get-response-body-text)
    - [Get resonse cookies as string](#get-response-cookies-as-string)
    - [Get resonse cookies as array](#get-response-cookies-as-array)
    - [Get header lines as array of string](#get-response-header-lines-by-specified-name)
    - [Get response header lines as comma-separated string](#get-response-header-lines-as-comma-separated-string)
    - [Get response headers as lines](#get-response-headers-as-lines)
    - [Get followed redirect urls, count, requests, cookies collection](#get-followed-redirect-urls-count-requests-cookies-collection)
    - [Get debug info](#get-debug-info)


#### Create a client

```php
$client = EasyRequest::create('GET', 'http://google.com');
// or
$client = EasyRequest::create('http://google.com', 'GET');
$client = EasyRequest::create('http://google.com'); // default is GET
```

Create a client with default options

```php
$method = 'POST'; // may GET/POST/PUT or any HTTP method
$target = 'http://domain.com';
$request = EasyRequest::create($method, $target, array(
    'handler'          => null,  // null|string - "socket" or "curl". null to use default.
    'method'           => 'GET',  // string
    'url'              => null,  // string
    'nobody'           => false, // boolean
    'follow_redirects' => 0,     // integer|true - True to follows all of redirections.
    'protocol_version' => '1.1', // string
    'timeout'          => 10,    // integer Timeout in seconds
    'user_agent'       => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:38.0) Gecko/20100101 Firefox/38.0',
    'auth'             => null,    // null|string An Auth Basic "user:password"
    'proxy'            => null,    // null|string A proxy with format "ip:port"
    'proxy_userpwd'    => null,    // null|string User password with format "user:password"
    'proxy_type'       => 'http',  // string Must be one of "http" or "sock5"
    'headers'          => array(), // array
    'cookies'          => array(), // array
    'json'             => false,   // false|string String json
    'body'             => '',      // string|resource
    'query'            => array(), // array
    'form_params'      => array(), // array
    'multipart'        => array(), // array
))->send();

var_dump($request->getResponse()); // null if have errors occured while sending.
```

#### Options and Helper methods

This library provides two handlers for sending request are Socket and Curl. By default, the library will try to detect your PHP settings and request options what you set to give a handler. But if you perfer to use Socket or Curl, you can specify that by `handler` option.
Socket is built in PHP, so you can use this library for sending request without curl extension.

```php
$client = EasyRequest::create('POST', 'http://domain.com', array(
    'handler' => 'socket' // or 'curl'
));
```
#### Shortcut methods to creating and sending request quickly
You can use all of HTTP methods as shortcut
```php
$client = EasyRequest::post('http://domain.com', $options);
$client = EasyRequest::get('http://domain.com', $options);
$client = EasyRequest::put('http://domain.com', $options);
$client = EasyRequest::delete('http://domain.com', $options);
...
```
#### Some simple options
```php
$client
    ->withTimeout(10) // timeout of sending request
    ->withNobody(true) // specify that you only want to get headers
    ->withProtocolVersion('1.1') // HTTP protocol version
    ->withFollowRedirects(true) // true or an integer
    ->withUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:38.0) Gecko/20100101 Firefox/38.0');
```
#### With headers
```php
$client = EasyRequest::create('POST', 'http://domain.com', array(
    'headers' => array(
        'Referer' => 'http://google.com',
        'Header1' => 'value',
        'Header2' => array(
            'value2', 'value3'
        )
    )
))
// to append value2 to Header1
->withHeader('Header1', 'value2')

// to replace value3 to Header1
->withHeader('Header1', 'value3', flase)

// to append value4, value5 to Header1
->withHeader('Header1', array('value4', 'value5'))

// to append value 7 to Header2, value6 to Header1
->withHeader(array(
  'Header1' => 'value6',
  'Header2' => 'value7'
), null, true)

// to remove a header by case-insensitive
->withoutHeader('header1');
```
#### With cookies
Use helper method to set dynamic values.
```php
$client = EasyRequest::create('POST', 'http://domain.com', array(
    'cookies' => array(
        array(
            'Name' => 'cookie0',
            'Value' => 'value0',
            'Path' => '/',
            'Max-Age'  => 300,
            'Expires'  => time() + 86400,
            'Secure'   => false,
            'Discard'  => false,
            'HttpOnly' => false
        )
    )
))
// with default args
->withCookie('cookie1', 'value1', $path = '/', $secure = false, $httpOnly = false)
->withCookie('cookie2', 'value2')
->withCookie('cookie6=value6; expires=Fri, 26-Jun-2015 03:24:07 GMT')

// Sets multiple cookies by string
->withStringCookies('cookie3=value3; cookie4=value4;cookie5=value5')

// to remove a cookie by name
->withoutCookie('cookie1');
```
#### With query string
Query option similar to Form param option.
```php
$client = EasyRequest::create('POST', 'http://domain.com', array(
    'query' => array(
        'query1' => 'value1',
        'query2' => 'value2',
        'query3' => 'value3',
    )
))
->withQuery('query1', 'value2')
->withQuery('query2', 'value3', false) // to override query2
->withQuery('query3=value3&query4=value4')
->withQuery(array(
    'query5' => 'value5',
    'query6' => 'value6'
))
// to remove a query by name
->withoutQuery('query1');
```
#### With form params
```php
$client = EasyRequest::create('POST', 'http://domain.com', array(
    'form_params' => array(
        'field1' => 'value1',
        'field2' => array('value2', 'value3')
        'field3' => array(
            'nested1' => 1,
            'nested2' => 2
        ),
        'field4' => array(1, 2)
    )
))
->withFormParam('field1', 'value2') // field1 will be field1[]=value1&field1[]=value2
->withFormParam('field1', 'value3', false) // field1 will be field1=value3
->withFormParam('field5=value5&field6=value6&field7=value7')
->withFormParam(array(
    'field8' => 'value8',
    'field9' => 'value9'
));

// to remove a form field by name
->withoutFormParam('field')

// to see your data
var_dump((string) $client->prepareRequest()->getBody());
```
#### With multipart data
Multipart field require `name` and `contents` keys. `filename` and `headers` are optional.
```php
$client = EasyRequest::create('POST', 'http://domain.com', array(
    'multipart' => array(
        array(
            'name' => 'field1',
            'contents' => 'value1'
        ),
        array(
            'name' => 'field2',
            'contents' => 'this is a text file',
            'filename' => 'file.txt',
            'headers' => array(
                'Custom-Header' => 'abc'
            )
        ),
        // may use to upload a file
        array(
            'name' => 'field2',
            'contents' => fopen('/path/to/file'),
            // optional keys
            'filename' => 'file.jpg',
            'headers' => array(
                'Content-Type' => 'image/jpg'
            )
        )
    )
))
->withMultipart('field2', 'value2')
->withMultipart('field3', 'value3', 'fieldname3')
->withMultipart('field4', 'value4', 'fieldname4', array('Custom-Header' => 'value'))
->withMultipart('file1', fopen('/path/to/file'), 'filename1') // to upload a file

// to remove a part
->withoutMultipart('field2');
```
#### Upload a file.
```php
$client
    ->withFormFile('file1', '/path/to/file1', $optionalFileName = null, $optionalHeaders = array())
    ->withFormFile('file2', '/path/to/file2');

// to remove this file
$client->withoutMultipart('file1');
```
#### Post RAW data
```php
$client->withBody('raw data');
```
#### Post JSON data
Used to easily upload JSON encoded data as the body of a request. A `Content-Type: application/json` header will be added if no Content-Type header is already present on the message.
```php
$client->withJson(array(1,2,3));
// or
$client->withJson(json_encode(array(1,2,3)));
```
#### With HTTP/ Sock5 Proxy
You may use a HTTP or Sock Proxy. But Sock Proxy require curl extension.
```php
$client = EasyRequest::create('POST', 'http://domain.com', array(
    'proxy'         => '192.168.1.105:8888',
    'proxy_userpwd' => 'user:pass',
    'proxy_type'    => 'http' // "http" or "sock5"
))
$client->withProxy('192.168.1.105:8888'); // proxy without user pass, default is HTTP proxy
$client->withProxy('192.168.1.105:8888', 'user:pass'); // use HTTP proxy with user, pass
$client->withProxy('192.168.1.105:8888', null, 'sock5'); // use sock5 proxy

$client->withSock5Proxy('192.168.1.105:8888', 'user:pass'); // use sock5 proxy
$client->withHttpProxy('192.168.1.105:8888', 'user:pass'); // use http proxy
```
#### With Auth Basic
```php
$client = EasyRequest::create('POST', 'http://domain.com', array(
    'auth' => 'user:pass',
))
$client->withAuth('user:pass');
```
#### Send request and get responses
```php
$client->send();
var_dump($client->getResponse() !== null);
boolean true
```
##### Get response status code
```php
var_dump($client->getResponseStatus());
int 200
```
##### Get response reason
```php
var_dump($client->getResponseReason());
string 'OK' (length=2)
```
##### Get response body text
```php
var_dump($client->getResponseBody());
string 'Hello' (length=5)

var_dump((string) $client);
string 'Hello' (length=5)
```

##### Get response cookies as string
```php
var_dump($client->getResponseCookies());
string 'c1=v1; c2=v2;' (length=13)
```

##### Get response cookies as array
```php
var_dump($client->getResponseArrayCookies());
array (size=2)
  0 =>
    array (size=9)
      'Name' => string 'c1' (length=2)
      'Value' => string 'v1' (length=2)
      'Domain' => null
      'Path' => string '/' (length=1)
      'Max-Age' => null
      'Expires' => string 'Sun, 28-Jun-2015 11:13:07 GMT' (length=29)
      'Secure' => boolean false
      'Discard' => boolean false
      'HttpOnly' => boolean false
  1 =>
    array (size=9)
      'Name' => string 'c2' (length=2)
      'Value' => string 'v2' (length=2)
      'Domain' => string 'abc.com' (length=7)
      'Path' => string '/Path/' (length=6)
      'Max-Age' => null
      'Expires' => string 'Sun, 28-Jun-2015 11:13:07 GMT' (length=29)
      'Secure' => boolean true
      'Discard' => boolean false
      'HttpOnly' => boolean true
```
##### Get response header lines by specified name
```php
var_dump($client->getResponseHeader('set-cookie')); // Case-insensitive
array (size=2)
  0 => string 'c1=v1; expires=Sun, 28-Jun-2015 11:13:48 GMT' (length=44)
  1 => string 'c2=v2; expires=Sun, 28-Jun-2015 11:13:48 GMT; path=/Path/; domain=abc.com; secure; httponly' (length=91)
```
##### Get response header lines as comma-separated string
```php
var_dump($client->getResponseHeaderLine('server'));
string 'Apache' (length=6)
```
##### Get response headers as lines
```php
var_dump($client->getResponseHeaders());
array (size=9)
  0 => string 'Date: Sun, 28 Jun 2015 11:20:14 GMT' (length=35)
  1 => string 'Server: Apache' (length=14)
  2 => string 'X-Powered-By: PHP/5.2.17' (length=24)
  3 => string 'Set-Cookie: c1=v1; expires=Sun, 28-Jun-2015 11:21:54 GMT' (length=56)
  4 => string 'Set-Cookie: c2=v2; expires=Sun, 28-Jun-2015 11:21:54 GMT; path=/Path/; domain=abc.com; secure; httponly' (length=103)
  5 => string 'Location: c.php' (length=15)
  6 => string 'Content-Length: 0' (length=17)
  7 => string 'Connection: close' (length=17)
  8 => string 'Content-Type: text/html' (length=23)
```
##### Get followed redirect urls, count, requests, cookies collection
```php
var_dump($client->getRedirectedCount());
var_dump($client->getRedirectedUrls());
// all cookies, may has some different sites
var_dump($client->getRedirectedCookies());
// get all request details
var_dump($client->getRedirectedRequests());
```
##### Get debug info
```php
var_dump($client->getDebugInfo());
array (size=4)
  'time_start' => float 1435488809.58
  'time_process' => float 0.00152993202209
  'handler' => string 'socket' (length=6)
  'errors' =>
    array (size=0)
      empty
```