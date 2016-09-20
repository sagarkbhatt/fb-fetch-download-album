<?php
/**
 * You can use this adapter and upload without API,
 * but Imgur limits 25 images for guest upload in a period.
 * So you should use API to avoid this issue by register an API here
 * {@link http://api.imgur.com/oauth2/addclient}.
 */
namespace RemoteImageUploader\Adapters;

use RemoteImageUploader\Factory;
use RemoteImageUploader\Interfaces\OAuth;
use RemoteImageUploader\Helper;
use Exception;

class Imgur extends Factory implements OAuth
{
    const SITE_URL = 'http://imgur.com/';
    const AUTHORIZE_ENDPOINT = 'https://api.imgur.com/oauth2/authorize';
    const TOKEN_ENDPOINT = 'https://api.imgur.com/oauth2/token';
    const UPLOAD_ENPOINT = 'https://api.imgur.com/3/image';

    const START_SESSION_ENDPOINT = 'http://imgur.com/upload/start_session';
    const GUEST_UPLOAD_ENDPOINT = 'http://imgur.com/upload';
    const KEY_EXPIRES_AT = 'EXPIRES_AT';

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        return parent::getOptions() + array(
            'api_key'    => null, // client id
            'api_secret' => null, // client secret

            // if you have `refresh_token` you can set it here
            // to pass authorize action.
            'refresh_token' => null,

            // If you don't want to authorize by yourself, you can set
            // this option to `true`, it will requires `username` and `password`.
            // But sometimes Imgur requires captcha for authorize, in that case,
            // you need to set it to `false` and authorize by yourself.
            'auto_authorize' => false,
            'username'       => null,
            'password'       => null,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function authorize($callbackUrl = '')
    {
        if ($this->isAuthorized()) {
            return;
        }

        $params = array(
            'client_id'     => $this['api_key'],
            'response_type' => 'code',
            'state'         => 'RIU'
        );
        $url = self::AUTHORIZE_ENDPOINT.'?'.http_build_query($params);

        if (isset($_GET['code'])) {
            $this->requestToken($_GET['code']);
        } elseif ($this['auto_authorize']) {
            $this->autoAuthorize($url);
        } else {
            Helper::redirectTo($url);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthorized()
    {
        return (bool) $this->getRefreshToken();
    }

    private function autoAuthorize($url)
    {
        // imgur allows request token at first request.
        // so we should try response_type to token to reduce number of requests.
        $url = str_replace('response_type=code', 'response_type=token', $url);

        $request = $this->createRequest($url)->send();

        $allowValue = Helper::match('#(?:name|id)=[\'"]allow[\'"].*?value="([^"]+)"#', $request);

        if (empty($allowValue)) {
            throw new Exception('Auto authorize: Not found ALLOW_VALUE');
        }
        $target = $request->getOptions('url');
        $cookies = $request->getResponseArrayCookies();

        $request = $this->createRequest($target, 'POST')
            ->withHeader('Referer', $target)
            ->withCookie($cookies)
            ->withFormParam(array(
                'username'                 => $this['username'],
                'password'                 => $this['password'],
                'allow'                    => $allowValue,
                '_jafo[activeExperiments]' => '[{"expID":"exp3025","variation":"control"}]',
                '_jafo[experimentData]'    => '{}',
            ))
            ->send();

        $params = substr(strstr($request->getResponseHeaderLine('location'), '#', false), 1);
        if ($request->getResponseStatus() == 403 || ! $params) {
            throw new Exception('Auto authorize failed');
        }
        parse_str($params, $token);

        $this->setToken($token);
    }


    private function requestToken($authCode)
    {
        $token = $this->sendTokenRequest(
            array(
                'code'          => $authCode,
                'client_id'     => $this['api_key'],
                'client_secret' => $this['api_secret'],
                'grant_type'    => 'authorization_code'
            ),
            'Request token failed'
        );

        $this->setToken($token);
    }

    /**
     * {@inheritdoc}
     */
    public function refreshToken()
    {
        $token = $this->sendTokenRequest(
            array(
                'refresh_token' => $this->getRefreshToken(),
                'client_id'     => $this['api_key'],
                'client_secret' => $this['api_secret'],
                'grant_type'    => 'refresh_token'
            ),
            'Refresh token failed'
        );

        $this->setToken($token);
    }

    private function sendTokenRequest(array $params, $errorMessage)
    {
        $request = $this->createRequest(self::TOKEN_ENDPOINT, 'POST')
            ->withFormParam($params)
            ->send();

        if ($request->getResponseStatus() != 200) {
            throw new Exception($errorMessage);
        }

        return json_decode($request, true);
    }

    /**
     * {@inheritdoc}
     */
    public function setToken(array $token)
    {
        $token = array_merge($this->getData('token', array()), $token);

        // in document, imgur say token will be expired in 3600 seconds,
        // but they given 2 years in this result.
        // And after 1 day, token be invalid ?!?!, so we should use 3600 seconds.
        $expiresIn = min($token['expires_in'], 3600);
        $token[self::KEY_EXPIRES_AT] = time() + $expiresIn;

        $this->setData('token', $token, $expiresIn);

        if (empty($this['refresh_token']) && ! empty($token['refresh_token'])) {
            // `refresh_token` don't have expires so we can use it for long time.
            $this->setData('refresh_token', $token['refresh_token'], 86400 * 365 * 2);
        }
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
        $expiresAt = $this->getToken(self::KEY_EXPIRES_AT);

        return ! $expiresAt || $expiresAt < time();
    }

    /**
     * {@inheritdoc}
     */
    protected function doUpload($file)
    {
        if (empty($this['api_key'])) {
            return $this->doGuestUpload($file);
        }

        $this->checkAndRefreshToken();

        try {
            $request = $this->createRequest(self::UPLOAD_ENPOINT, 'POST')
                ->withHeader('Authorization', sprintf('Bearer %s', $this->getToken('access_token')))
                ->withFormFile('image', $file)
                ->send();

            return $this->getImageUrl($request, 'Upload failed');
        } catch (Exception $e) {
            $this->refreshToken();

            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doTransload($url)
    {
        if (empty($this['api_key'])) {
            return $this->doGuestTransload($url);
        }

        $this->checkAndRefreshToken();

        try {
            $request = $this->createRequest(self::UPLOAD_ENPOINT, 'POST')
                ->withHeader('Authorization', sprintf('Bearer %s', $this->getToken('access_token')))
                ->withFormParam('image', $url)
                ->send();

            return $this->getImageUrl($request, 'Transload failed');
        } catch (Exception $e) {
            $this->refreshToken();

            throw $e;
        }
    }

    private function checkAndRefreshToken()
    {
        if (empty($this['api_key']) || empty($this['api_secret'])) {
            throw new Exception('Missing api_key, api_secret configuration.');
        }

        $this->isExpired() && $this->refreshToken();
    }

    private function getImageUrl($request, $errorMessage)
    {
        $result = json_decode($request, true);

        if (empty($result['success'])) {
            if (isset($result['data']['error'])) {
                $errorMessage = $result['data']['error'];
            }

            throw new Exception($errorMessage);
        }

        return $result['data']['link'];
    }

    private function getRefreshToken()
    {
        return $this['refresh_token'] ? $this['refresh_token'] : $this->getData('refresh_token');
    }

    private function getGuestSession()
    {
        if (! $session = $this->getData('guest_session')) {
            $request = $this->createRequest(self::START_SESSION_ENDPOINT)
                ->withHeader('X-Requested-With', 'XMLHttpRequest')
                ->withHeader('Referer', self::SITE_URL)
                ->send();

            $result = json_decode($request, true);

            if (empty($result['sid'])) {
                throw new Exception('Start session failed');
            }

            $session = $result['sid'];
            $this->setData('guest_session', $session, 900);
        }

        return $session;
    }

    protected function doGuestUpload($file)
    {
        $request = $this->createRequest(self::GUEST_UPLOAD_ENDPOINT, 'POST')
            ->withHeader('Referer', self::SITE_URL)
            ->withFormParam($this->getGuestUploadGeneralParams())
            ->withFormFile('Filedata', $file)
            ->send();

        return $this->handleGuestUploadResult($file, $request, 'Guest upload failed');
    }

    protected function doGuestTransload($url)
    {
        $request = $this->createRequest(self::GUEST_UPLOAD_ENDPOINT, 'POST')
            ->withHeader('Referer', self::SITE_URL)
            ->withFormParam($this->getGuestUploadGeneralParams())
            ->withFormParam('url', $url)
            ->send();

        return $this->handleGuestUploadResult($url, $request, 'Guest transload failed');
    }

    private function getGuestUploadGeneralParams()
    {
        return array(
            'current_upload' => 1,
            'total_uploads'  => 1,
            'terms'          => 1,
            'gallery_type'   => '',
            'location'       => 'outside',
            'gallery_submit' => 0,
            'create_album'   => 0,
            'album_title'    => 'Optional Album Title',
            'sid'            => $this->getGuestSession()
        );
    }

    private function handleGuestUploadResult($file, $request, $errorMessage)
    {
        $result = json_decode($request, true);

        if (isset($result['data']['hash'])) {
            return sprintf('http://i.imgur.com/%s.%s', $result['data']['hash'], $this->getExtension($file));
        }

        if (isset($result['data']['error']['message'])) {
            $this->checkReachedLimit();

            $errorMessage = sprintf('%s %s', $result['data']['error']['message'], $result['data']['error']['type']);
        }

        throw new Exception($errorMessage);
    }

    private function checkReachedLimit()
    {
        $request = $this->createRequest('http://imgur.com/upload/checkcaptcha?total_uploads=1&create_album=0')
            ->send();
        $result = json_decode($request, true);
        if (! empty($result['data']['overLimits'])) {
            throw new Exception(sprintf('Guest upload over limits, please use api. %s', $request));
        }
    }

    private function getExtension($fileName)
    {
        return strtolower(Helper::match('#\.(gif|jpg|jpeg|png)$#i', $fileName, 1, 'jpg'));
    }
}
