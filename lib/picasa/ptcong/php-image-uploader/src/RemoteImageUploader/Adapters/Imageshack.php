<?php

/**
 * You must have at least one API.
 * Register here: {@link http://imageshack.us/api_request/}
 *
 * This adapter requires the options: api_key, username, password
 */
namespace RemoteImageUploader\Adapters;

use RemoteImageUploader\Factory;
use RemoteImageUploader\Interfaces\Account;
use Exception;

class Imageshack extends Factory implements Account
{
    const SITE_URL = 'https://imageshack.com/';
    const LOGIN_ENDPOINT = 'https://imageshack.com/rest_api/v2/user/login';
    const UPLOAD_ENPOINT = 'https://imageshack.com/rest_api/v2/images';

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        return parent::getOptions() + array(
            'api_key'  => null,
            'username' => null,
            'password' => null,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function login()
    {
        if (!$this->isLoggedIn()) {
            $request = $this->createRequest(self::LOGIN_ENDPOINT, 'POST')
                ->withHeader('Referer', self::SITE_URL)
                ->withHeader('X-Requested-With', 'XMLHttpRequest')
                ->withFormParam(array(
                    'api_key'     => $this['api_key'],
                    'username'    => $this['username'],
                    'password'    => $this['password'],
                    'set_cookies' => 'true',
                    'remember_me' => 'true',
                ))
                ->send();

            $result = json_decode($request, true);

            $this->handleError($result, 'Login failed');

            // if remember_me is "true" imageshack will set cookies for 1 year
            // but we don't trust them, they have trolled us lots of time
            // by changing API and delete lots of our uploaded images
            // even with premium account. So we should set cache for 1 day.
            $this->setData('login', $result['result'], 86400);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLoginData()
    {
        return $this->getData('login');
    }

    /**
     * {@inheritdoc}
     */
    public function isLoggedIn()
    {
        return $this->hasData('login');
    }

    /**
     * {@inheritdoc}
     */
    protected function doUpload($file)
    {
        $this->checkLoginStatus();

        $request = $this->createRequest(self::UPLOAD_ENPOINT, 'POST')
            ->withFormParam($this->getGeneralParams())
            ->withFormFile('file', $file)
            ->send();

        return $this->getImageUrl($request, 'Upload failed');
    }

    /**
     * {@inheritdoc}
     */
    protected function doTransload($url)
    {
        $this->checkLoginStatus();

        $request = $this->createRequest(self::UPLOAD_ENPOINT, 'POST')
            ->withFormParam($this->getGeneralParams())
            ->withFormParam('url', $url)
            ->send();

        return $this->getImageUrl($request, 'Transload failed');
    }

    private function getImageUrl($request, $errorMessage)
    {
        $result = json_decode($request, true);

        $this->handleError($result, $errorMessage);

        if (isset($result['result']['images'][0]['direct_link'])) {
            return 'http://'.$result['result']['images'][0]['direct_link'];
        }

        throw new Exception($errorMessage);
    }

    private function handleError($result, $errorMessage)
    {
        if (empty($result['success'])) {
            if (isset($result['error']['error_message'])) {
                $errorMessage = $result['error']['error_message'];
            }
            throw new Exception($errorMessage);
        }
    }

    private function checkLoginStatus()
    {
        if (!$this->isLoggedIn()) {
            throw new Exception('You must logged in before sending request');
        }
    }

    private function getGeneralParams()
    {
        $loginData = $this->getLoginData();

        return array(
            'auth_token' => $loginData['auth_token'],
            'api_key'    => $this['api_key'],
        );
    }
}
