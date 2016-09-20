<?php

namespace RemoteImageUploader\Interfaces;

interface OAuth
{
    /**
     * Direct user to site for authorization
     * and process get access token if user have authorized.
     *
     * @param string $callbackUrl
     *
     * @return void
     */
    public function authorize($callbackUrl = '');


    /**
     * Determine if user have authorized.
     *
     * @return boolean
     */
    public function isAuthorized();

    /**
     * Refresh token and save new information.
     *
     * @return void
     *
     * @throws Exception if failure.
     */
    public function refreshToken();

    /**
     * Sets token.
     *
     * @param array $token
     */
    public function setToken(array $token);

    /**
     * Returns token information.
     *
     * @param null|string Get token value by given key.
     *
     * @return array
     */
    public function getToken($key = null);

    /**
     * Determine if token is expired.
     *
     * @return boolean
     */
    public function isExpired();
}
