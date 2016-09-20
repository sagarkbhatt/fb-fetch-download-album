<?php

/**
 * You must have at least API.
 * Register here: {@link https://www.flickr.com/services/apps/create/noncommercial/}.
 */
namespace RemoteImageUploader\Adapters;

use RemoteImageUploader\Factory;
use RemoteImageUploader\Helper;
use RemoteImageUploader\Interfaces\OAuth;
use Exception;

class Flickr extends Factory implements OAuth
{
    const REQUEST_TOKEN_ENPOINT = 'https://www.flickr.com/services/oauth/request_token';
    const AUTH_ENDPOINT = 'https://www.flickr.com/services/oauth/authorize';
    const ACCESS_TOKEN_ENDPOINT = 'https://www.flickr.com/services/oauth/access_token';
    const API_ENDPOINT = 'https://www.flickr.com/services/rest';
    const UPLOAD_ENDPOINT = 'https://www.flickr.com/services/upload/';

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        return parent::getOptions() + array(
            'api_key'    => null,
            'api_secret' => null,

            // if you have oauth_token and secret, you can set
            // to the options to pass
            'oauth_token'        => null,
            'oauth_token_secret' => null,
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getOptionHash()
    {
        return md5($this['oauth_token'].$this['oauth_token_secret'].$this['api_key'].$this['api_secret']);
    }

    /**
     * {@inheritdoc}
     */
    public function authorize($callbackUrl = '')
    {
        if ($this->isAuthorized()) {
            return;
        }

        if (isset($_GET['oauth_token']) && isset($_GET['oauth_verifier'])) {
            $oauthToken = $this->getData('temp_token');

            $this->requestToken($_GET['oauth_token'], $_GET['oauth_verifier'], $oauthToken['oauth_token_secret']);
        } else {
            Helper::redirectTo($this->getAuthorizationUrl($callbackUrl));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthorized()
    {
        return $this->getAccessToken() && $this->getSecretToken();
    }

    private function requestToken($oauthToken, $oauthVerifier, $oauthTokenSecret)
    {
        $params = $this->getParameters(array(
            'oauth_token'    => $oauthToken,
            'oauth_verifier' => $oauthVerifier,
        ));
        list($url, $params) = $this->prepareRequestData(self::ACCESS_TOKEN_ENDPOINT, 'GET', $params, $oauthTokenSecret);

        $request = $this->createRequest($url)->send();

        parse_str($request, $result);

        if (isset($result['oauth_problem'])) {
            throw new Exception(sprintf('Request token failed %s', $result['oauth_problem']));
        }

        $this->setToken(array(
            'oauth_token'        => $result['oauth_token'],
            'oauth_token_secret' => $result['oauth_token_secret']
        ));
    }

    private function getAccessToken()
    {
        return $this['oauth_token']
            ? $this['oauth_token']
            : $this->getToken('oauth_token');
    }

    private function getSecretToken()
    {
        return $this['oauth_token_secret']
            ? $this['oauth_token_secret']
            : $this->getToken('oauth_token_secret');
    }

    /**
     * {@inheritdoc}
     */
    public function refreshToken()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setToken(array $token)
    {
        $this->setData('token', $token, 86400 * 365 * 10);
    }

    /**
     * {@inheritdoc}
     */
    public function getToken($key = null)
    {
        $token = $this->getData('token', array());

        if ($key !== null) {
            return isset($token[$key]) ? $token[$key] : null;
        }

        return $token;
    }

    /**
     * {@inheritdoc}
     */
    public function isExpired()
    {
        return !$this->getToken();
    }

    /**
     * {@inheritdoc}
     */
    protected function doUpload($file)
    {
        $params = $this->getParameters(array(
            'description'    => '',
            'tags'           => '',
            'is_public'      => 1,
            'is_friend'      => '',
            'is_family'      => '',
            'content_type'   => 1, // 1: photo, 2:screenshot
            'safety_level'   => '',
            'hidden'         => '',
            'oauth_token'    => $this->getAccessToken(),
        ));

        list($url, $params) = $this->prepareRequestData(self::UPLOAD_ENDPOINT, 'POST', $params);

        $request = $this->createRequest(self::UPLOAD_ENDPOINT, 'POST')
            ->withFormParam($params)
            ->withFormFile('photo', $file)
            ->send();

        if (!$photoId = Helper::match('#<photoid>(\w+)</photoid>#', $request)) {
            parse_str($request, $result);

            if (isset($result['oauth_problem'])) {
                throw new Exception(sprintf('Upload failed: "%s"', $result['oauth_problem']));
            } else {
                $error = Helper::match('#code="(.+?)"#', $request);
                $msg = Helper::match('#msg="(.+?)"#', $request);
                throw new Exception(sprintf('Upload failed: "%s" (%d)', $msg, $error));
            }
        }
        $result = $this->callApi('flickr.photos.getInfo', array(
            'photo_id' => $photoId,
        ));

        return $this->getPhotoUrl($result['photo']);
    }

    /**
     * {@inheritdoc}
     *
     * Flickr does not support transload, so we need to download the url
     * to a temp file then upload it.
     */
    protected function doTransload($url)
    {
        if (!$tempFile = Helper::download($url)) {
            throw new Exception('Url is not accessible');
        }

        try {
            $result = $this->doUpload($tempFile);
            file_exists($tempFile) && unlink($tempFile);
        } catch (Exception $e) {
            file_exists($tempFile) && unlink($tempFile);

            throw new Exception(sprintf('Transload failed. %s', $e->getMessage()));
        }

        return $result;
    }

    /**
     * Get photo url.
     * {@link https://www.flickr.com/services/api/misc.urls.html}.
     *
     * @param array $info
     *
     * @return string
     */
    protected function getPhotoUrl(array $photo)
    {
        return strtr(
            'http://farm{farm-id}.staticflickr.com/{server-id}/{id}_{o-secret}_o.{o-format}',
            array(
                '{farm-id}'   => $photo['farm'],
                '{server-id}' => $photo['server'],
                '{id}'        => $photo['id'],
                '{o-secret}'  => $photo['originalsecret'],
                '{o-format}'  => $photo['originalformat'],
            )
        );
    }

    /**
     * Call Flickr OAuth API.
     *
     * @param string $method
     * @param array  $params
     *
     * @return array
     *
     * @throws Exception
     */
    protected function callApi($method, array $params = array())
    {
        $params += $this->getParameters($params + array(
            'method'         => $method,
            'oauth_token'    => $this->getAccessToken(),
            'format'         => 'json',
            'nojsoncallback' => '1',
        ));
        list($url, $params) = $this->prepareRequestData(self::API_ENDPOINT, 'GET', $params);

        $request = $this->createRequest($url)->send();

        if (false === $result = json_decode($request, true)) {
            parse_str($request, $result);
            if (isset($result['oauth_problem'])) {
                throw new Exception(sprintf('API error: "%s"', $result['oauth_problem']));
            }
        }
        if ($result['stat'] == 'fail') {
            throw new Exception(sprintf('API error: "%s" "%s" (%s)',
                                        $method, $result['message'], $result['code']));
        }

        return $result;
    }

    /**
     * Prepare oauth request data and return url, parameters.
     *
     * @param string $endpoint
     * @param string $method
     * @param array  $params
     *
     * @return array
     */
    private function prepareRequestData($endpoint, $method = 'GET', $params = array(), $secretKey2 = null)
    {
        $baseString = $this->getBaseString($endpoint, $method, $params);
        $params = $this->pushSignature($params, $baseString, $secretKey2);
        if ($method == 'GET') {
            $url = $endpoint.'?'.http_build_query($params);
        } else {
            $url = $endpoint;
        }

        return array($url, $params);
    }

    private function getAuthorizationUrl($callbackUrl)
    {
        $params = $this->getParameters(array(
            'oauth_callback' => $callbackUrl,
        ));
        list($url, $params) = $this->prepareRequestData(self::REQUEST_TOKEN_ENPOINT, 'GET', $params, '');

        $request = $this->createRequest($url)->send();

        parse_str($request, $result);

        if (isset($result['oauth_problem'])) {
            throw new Exception(sprintf('Request token error: "%s"', $result['oauth_problem']));
        }
        // oauth_callback_confirmed
        // oauth_token
        // oauth_token_secret
        $this->setData('temp_token', $result, 900);

        list($url, ) = $this->prepareRequestData(self::AUTH_ENDPOINT, 'GET', array(
            'oauth_token' => $result['oauth_token'],
            'perms'       => 'write',
        ));

        return $url;
    }

    /**
     * Get OAuth base string.
     *
     * @param array $parameters
     *
     * @return string
     */
    private function getBaseString($url, $method, array $params)
    {
        return $method.'&'.urlencode($url).'&'.urlencode(http_build_query($params));
    }

    /**
     * Push OAuth signature.
     *
     * @param array  $params
     * @param string $baseString
     * @param string $secretKey2
     *
     * @return void
     */
    private function pushSignature(&$params, $baseString, $secretKey2 = null)
    {
        $secretKey2 = isset($secretKey2) ? $secretKey2 : $this->getSecretToken();

        $secret = $this['api_secret'].'&'.$secretKey2;
        $params['oauth_signature'] = base64_encode(hash_hmac('sha1', $baseString, $secret, true));

        return $params;
    }

    /**
     * Get OAuth parameters.
     *
     * @param array $params
     *
     * @return array
     */
    private function getParameters(array $params)
    {
        $params = $params + array(
            'oauth_nonce'            => uniqid(),
            'oauth_timestamp'        => time(),
            'oauth_consumer_key'     => $this['api_key'],
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_version'          => '1.0',
        );
        ksort($params);

        return $params;
    }
}
