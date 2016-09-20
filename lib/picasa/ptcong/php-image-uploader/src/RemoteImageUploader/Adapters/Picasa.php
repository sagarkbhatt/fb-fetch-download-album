<?php
/**
 * You must have at least one API.
 * Register here {@link https://console.developers.google.com/apis/credentials}
 * More: {@link https://developers.google.com/identity/protocols/OAuth2}
 */

namespace RemoteImageUploader\Adapters;

use RemoteImageUploader\Interfaces\OAuth;
use RemoteImageUploader\Factory;
use RemoteImageUploader\Helper;
use Exception;

class Picasa extends Factory implements OAuth
{
    const OAUTH_AUTH_ENDPOINT = 'https://accounts.google.com/o/oauth2/auth';
    const OAUTH_TOKEN_ENDPOINT = 'https://www.googleapis.com/oauth2/v3/token';
    const OAUTH_SCOPE_PICASA = 'https://picasaweb.google.com/data/';

    const USER_FEED_ENDPOINT = 'https://picasaweb.google.com/data/feed/api/user/default?alt=json';
    const ALBUM_FEED_ENPOINT = 'https://picasaweb.google.com/data/feed/api/user/default/albumid/%s?alt=json';

    const KEY_EXPIRES_AT = 'EXPIRES_AT';
    const ALBUM_CACHE_TIME = 31536000; // 1 year

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        return parent::getOptions() + array(
            'api_key'    => null, // client id
            'api_secret' => null, // client secret

            // if `album_id` is `null`, this script will automatic
            // create a new album for storage every 2000 photos
            // (due Google Picasa's limitation)
            'album_id'               => null,
            'auto_album_title'       => 'Auto Album %s',
            'auto_album_access'      => 'public',
            'auto_album_description' => 'Created by Remote Image Uploader',

            // if you have `refresh_token` you can set it here
            // to pass authorize action.
            'refresh_token' => null,
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
            'response_type'          => 'code',
            'client_id'              => $this['api_key'],
            'redirect_uri'           => $callbackUrl,
            'scope'                  => self::OAUTH_SCOPE_PICASA,
            'state'                  => 'request_token',
            'approval_prompt'        => 'force',
            'access_type'            => 'offline',
            'include_granted_scopes' => 'true',
        );

        $url = self::OAUTH_AUTH_ENDPOINT.'?'.http_build_query($params);

        if (isset($_GET['code'])) {
            $this->requestToken($_GET['code'], $callbackUrl);
        } else {
            Helper::redirectTo($url);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthorized()
    {
        return !!$this->getRefreshToken();
    }

    private function requestToken($authCode, $callbackUrl = '')
    {
        $token = $this->sendTokenRequest(array(
            'code'          => $authCode,
            'client_id'     => $this['api_key'],
            'client_secret' => $this['api_secret'],
            'redirect_uri'  => $callbackUrl,
            'grant_type'    => 'authorization_code',
        ));

        $this->setToken($token);
    }

    /**
     * {@inheritdoc}
     */
    public function refreshToken()
    {
        $token = $this->sendTokenRequest(array(
            'refresh_token' => $this->getRefreshToken(),
            'client_id'     => $this['api_key'],
            'client_secret' => $this['api_secret'],
            'grant_type'    => 'refresh_token',
        ));

        $this->setToken($token);
    }

    private function sendTokenRequest(array $params)
    {
        $request = $this->createRequest(self::OAUTH_TOKEN_ENDPOINT, 'POST')
            ->withFormParam($params)
            ->send();

        $result = json_decode($request, true);

        if (!empty($result['error'])) {
            throw new Exception(sprintf('%s: %s', $result['error'], $result['error_description']));
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setToken(array $token)
    {
        $token = array_merge($this->getData('token', array()), $token);

        $token[self::KEY_EXPIRES_AT] = time() + $token['expires_in'];

        $this->setData('token', $token, $token['expires_in']);

        if (empty($this['refresh_token']) && !empty($token['refresh_token'])) {
            // `refresh_token` don't have expires so we can use it for long time.
            $this->setData('refresh_token', $token['refresh_token'], 86400 * 365 * 10);
        }
    }

    private function getRefreshToken()
    {
        return $this['refresh_token'] ? $this['refresh_token'] : $this->getData('refresh_token');
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

        return !$expiresAt || $expiresAt < time();
    }

    /**
     * {@inheritdoc}
     */
    protected function doUpload($file)
    {
        $this->checkAndRefreshToken();

        $albumId = $this->getWorkingAlbumId();

        $request = $this->createRequest($this->getAlbumUrl($albumId), 'POST')
            ->withHeader($this->getGeneralHeaders())
            ->withHeader('Content-Type', 'image/jpeg')
            ->withHeader('Slug', basename($file))
            ->withBody(fopen($file, 'r'))
            ->send();

        if ($request->getResponseStatus() != 201) {
            if (preg_match('#No album found|Photo limit reached#i', $request)) {
                $this->deleteData($albumId);
                $this->deleteData('album_id');
                static $retry = false;
                if (! $retry) {
                    $retry = true;

                    return $this->doUpload($file);
                }
            }
            throw new Exception(sprintf('Upload failed. %s', (string) $request));
        }

        $result = json_decode($request, true);

        $url = preg_replace('#/[^/]+$#', '/s0$0', $result['entry']['content']['src']);

        $this->updateAlbumCounter($albumId, array('uploaded'  => 1, 'remaining' => -1));

        return $url;
    }

    /**
     * {@inheritdoc}
     *
     * Picasa does not support transload, so we need to download the url
     * to a temp file then upload it.
     */
    protected function doTransload($url)
    {
        if (!$tempFile = Helper::download($url)) {
            throw new Exception('Url is not accessible');
        }

        try {
            $result = $this->doUpload($tempFile);
        } catch (Exception $e) {
            // ??
        }

        file_exists($tempFile) && unlink($tempFile);

        if (empty($result)) {
            throw new Exception('Transload failed');
        }

        return $result;
    }

    /**
     * Gets album counter as uploaded photos and remaining.
     *
     * @param string $albumId
     *
     * @return array
     */
    private function getAlbumCounter($albumId)
    {
        if (!$counter = $this->getData($albumId)) {
            $request = $this->createRequest($this->getAlbumUrl($albumId))
                ->withHeader($this->getGeneralHeaders())
                ->send();

            if ($request->getResponseStatus() != 200) {
                throw new Exception((string) $request);
            }

            $result = json_decode($request, true);

            $counter = array(
                'uploaded'  => $result['feed']['gphoto$numphotos']['$t'],
                'remaining' => $result['feed']['gphoto$numphotosremaining']['$t']
            );

            $this->setData($albumId, $counter, self::ALBUM_CACHE_TIME);
        }

        return $counter;
    }

    /**
     * Update counter after uploaded one.
     *
     * @param string $albumId
     * @param array  $update
     *
     * @return void
     */
    private function updateAlbumCounter($albumId, array $update)
    {
        $counter = $this->getAlbumCounter($albumId);

        foreach ($update as $key => $value) {
            $counter[$key] = intval($counter[$key]) + intval($value);
        }

        $this->setData($albumId, $counter, self::ALBUM_CACHE_TIME);
    }

    /**
     * Gets current album id. If have no album id,
     * it will create a new one.
     *
     * @return string
     */
    private function getWorkingAlbumId()
    {
        $albumId = $this->getData('album_id', $this['album_id']);

        if (empty($albumId)
            || ($counter = $this->getAlbumCounter($albumId)) && $counter['remaining'] <= 0
        ) {
            if ($this['album_id']) {
                throw new Exception('"%s" album is full, please create a new one '
                                    .'or empty "album_id" option to let us '
                                    .'automatic handle this problem.', $albumId);
            } else {
                // remove unnecessary cache
                $this->deleteData($albumId);

                $albumId = $this->createNewAlbum(
                    sprintf($this['auto_album_title'], date('Y-m-d H:i')),
                    $this['auto_album_access'],
                    $this['auto_album_description']
                );

                if ($albumId) {
                    $this->setData('album_id', $albumId, self::ALBUM_CACHE_TIME);
                }
            }
        }

        return $albumId;
    }

    /**
     * Create new picas album.
     *
     * @param string $title
     * @param string $access
     * @param string $description
     *
     * @return string
     *
     * @throws Exception if failure.
     */

    protected function createNewAlbum($title, $access = 'public', $description = '')
    {
        $this->checkAndRefreshToken();

        $request = $this->createRequest(self::USER_FEED_ENDPOINT, 'POST')
            ->withHeader($this->getGeneralHeaders())
            ->withHeader('Content-Type', 'application/atom+xml')
            ->withBody(
                "<entry xmlns='http://www.w3.org/2005/Atom' "
                    ."xmlns:media='http://search.yahoo.com/mrss/' "
                    ."xmlns:gphoto='http://schemas.google.com/photos/2007'>"
                ."<title type='text'>{$title}</title>"
                ."<summary type='text'>{$description}</summary>"
                ."<gphoto:access>{$access}</gphoto:access>"
                ."<category scheme='http://schemas.google.com/g/2005#kind' "
                    ."term='http://schemas.google.com/photos/2007#album'></category>"
                .'</entry>'
            )
            ->send();

        $result = json_decode($request, true);

        if ($request->getResponseStatus() != 201) {
            throw new Exception(sprintf('Create new album failed. %s', (string) $request));
        }

        return $result['entry']['gphoto$id']['$t'];
    }

    private function getAlbumUrl($albumId)
    {
        return sprintf(self::ALBUM_FEED_ENPOINT, $albumId);
    }

    private function getGeneralHeaders()
    {
        $token = $this->getToken();

        return array(
            'Authorization' => sprintf('%s %s', $token['token_type'], $token['access_token']),
            'GData-Version' => '2',
            'MIME-version'  => '1.0',
        );
    }

    private function checkAndRefreshToken()
    {
        if (empty($this['api_key']) || empty($this['api_secret'])) {
            throw new Exception('Missing api_key, api_secret configuration.');
        }

        $this->isExpired() && $this->refreshToken();
    }
}
