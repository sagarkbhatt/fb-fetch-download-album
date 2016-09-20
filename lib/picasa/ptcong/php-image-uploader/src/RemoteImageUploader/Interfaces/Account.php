<?php

namespace RemoteImageUploader\Interfaces;

interface Account
{
    /**
     * Login to service with specific account
     * then save received information for sending next requests.
     *
     * @return void
     *
     * @throws Exception if failure.
     */
    public function login();

    /**
     * Returns login informations that we have received by calling
     * {@link login} method
     *
     * @return mixed
     */
    public function getLoginData();

    /**
     * Determine if we have logged in.
     *
     * @return boolean
     */
    public function isLoggedIn();
}
