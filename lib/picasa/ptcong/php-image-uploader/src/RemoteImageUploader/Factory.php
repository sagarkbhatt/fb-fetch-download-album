<?php

namespace RemoteImageUploader;

use ArrayAccess;
use InvalidArgumentException;
use EasyRequest;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\ArrayCache;

abstract class Factory implements ArrayAccess
{
    /**
     * Cache instance.
     *
     * @var \Doctrine\Common\Cache
     */
    protected $cacher = null;

    /**
     * Options.
     *
     * @var array
     */
    protected $options = array(
        'username'   => null,
        'password'   => null,
        'api_key'    => null,
        'api_secret' => null,
        'cacher'     => null,
    );

    /**
     * Create new adapter.
     *
     * @param string $adapter
     * @param array  $options
     *
     * @return self
     */
    public static function create($adapter, array $options = array())
    {
        $class = sprintf('\RemoteImageUploader\Adapters\%s', ucfirst($adapter));

        return new $class($options);
    }

    /**
     * Create new uploader instance.
     *
     * @param array $options
     */
    private function __construct(array $options = array())
    {
        $this->setOptions($options);

        if ($cacher = $this['cacher']) {
            $this->setCacher($cacher);
        }
    }

    /**
     * Sets options.
     *
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->getOptions(), $options);
    }

    /**
     * Returns array of options.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Create request instance.
     *
     * @param string $url
     * @param string $method
     *
     * @return EasyRequest
     */
    protected function createRequest($url, $method = 'GET')
    {
        return EasyRequest::create($url, $method);
    }

    /**
     * Set cacher.
     *
     * @param Doctrine\Common\Cache $cache
     */
    public function setCacher(Cache $cacher)
    {
        $this->cacher = $cacher;
    }

    /**
     * Returns cacher.
     *
     * @return \Doctrine\Common\Cache
     */
    public function getCacher()
    {
        if ($this->cacher === null) {
            $this->cacher = new ArrayCache;
        }

        return $this->cacher;
    }

    /**
     * Returns key hashed by options.
     *
     * @param string $key
     *
     * @return string
     */
    protected function getCacheKey($key)
    {
        return md5(get_called_class().$this->getOptionHash().$key);
    }

    /**
     * Hash key supports use multiple account, tokens.
     *
     * @param string $key
     *
     * @return string
     */
    protected function getOptionHash()
    {
        return md5($this['username'].$this['password'].$this['api_key'].$this['api_secret'].$this['refresh_token']);
    }

    /**
     * Puts data into the cache.
     * If a cache entry with the given id already exists, its data will be replaced.
     *
     * @param string $key      The cache id.
     * @param mixed  $value    The cache entry/data.
     * @param int    $lifeTime The lifetime in number of seconds for this cache entry.
     *                         If zero (the default), the entry never expires (although it may be deleted from the cache
     *                         to make place for other entries).
     *
     * @return bool TRUE if the entry was successfully stored in the cache, FALSE otherwise.
     */
    protected function setData($key, $value, $lifeTime)
    {
        $key = $this->getCacheKey($key);

        return $this->getCacher()->save($key, $value, $lifeTime);
    }

    /**
     * Fetches an entry from the cache.
     *
     * @param string $key The id of the cache entry to fetch.
     *
     * @return mixed The cached data or FALSE, if no cache entry exists for the given id.
     */
    protected function getData($key, $default = null)
    {
        $key = $this->getCacheKey($key);
        $result = $this->getCacher()->fetch($key);

        return $result !== false ? $result : $default;
    }

    /**
     * Tests if an entry exists in the cache.
     *
     * @param string $key The cache id of the entry to check for.
     *
     * @return bool TRUE if a cache entry exists for the given cache id, FALSE otherwise.
     */
    protected function hasData($key)
    {
        $key = $this->getCacheKey($key);

        return $this->getCacher()->contains($key);
    }

    /**
     * Deletes a cache entry.
     *
     * @param string $key The cache id.
     *
     * @return bool TRUE if the cache entry was successfully deleted, FALSE otherwise.
     *              Deleting a non-existing entry is considered successful.
     */
    protected function deleteData($key)
    {
        // avoid issue if scream is enabled
        // {@link http://php.net/manual/en/intro.scream.php}
        if (!$this->hasData($key)) {
            return true;
        }
        $key = $this->getCacheKey($key);

        $this->getCacher()->delete($key);
    }

    /**
     * Process upload an image file..
     *
     * @param string $file Image file path
     *
     * @return string Image URL
     *
     * @throws InvalidArgumentException if argument is invalid
     */
    public function upload($file)
    {
        if (!$filepath = realpath($file)) {
            throw new InvalidArgumentException(sprintf('File "%s" is not exists.', $file));
        }
        if (!getimagesize($filepath)) {
            throw new InvalidArgumentException(sprintf('File "%s" is not an image.', $file));
        }

        return $this->doUpload($filepath);
    }

    /**
     * Process transload an image url.
     *
     * @param string $url Image URL.
     *
     * @return string Image URL returned from service.
     *
     * @throws InvalidArgumentException if argument is invalid
     */
    public function transload($url)
    {
        if (!preg_match('#^(http|ftp)#', $url)) {
            throw new InvalidArgumentException(sprintf('URL is invalid "%s".', $url));
        }

        return $this->doTransload($url);
    }

    /**
     * Sets option.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function offsetSet($name, $value)
    {
        $this->options[$name] = $value;
    }

    /**
     * Returns option value of given name.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function offsetGet($name)
    {
        return $this->offsetExists($name) ? $this->options[$name] : null;
    }

    /**
     * Unsets an option.
     *
     * @param string $name
     */
    public function offsetUnset($name)
    {
        unset($this->options[$name]);
    }

    /**
     * Determine whether option exist ?
     *
     * @param string $offset to check
     *
     * @return bool
     */
    public function offsetExists($name)
    {
        return isset($this->options[$name]);
    }

    /**
     * Do upload.
     *
     * @param string $file File path
     *
     * @return string Image URL returned from service.
     *
     * @throws Exception if failure.
     */
    abstract protected function doUpload($file);

    /**
     * Do transload.
     *
     * @param string $url Image URL.
     *
     * @return string Image URL returned from service.
     *
     * @throws Exception if failure.
     */
    abstract protected function doTransload($url);
}
