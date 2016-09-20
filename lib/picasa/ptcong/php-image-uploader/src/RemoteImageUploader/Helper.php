<?php

namespace RemoteImageUploader;

use EasyRequest;

class Helper
{
    /**
     * Helper to redirect to a url.
     *
     * @param string $url
     *
     * @return void
     */
    public static function redirectTo($url)
    {
        if (headers_sent()) {
            echo '<meta http-equiv="refresh" content="0; url='.$url.'">'
                .'<script type="text/javascript">window.location.href = "'.$url.'";</script>';
        } else {
            header('Location: '.$url);
        }
        exit;
    }

    public static function match($pattern, $text, $group = 1, $default = null)
    {
        if (preg_match($pattern, $text, $match)) {
            return $match[$group];
        }

        return $default;
    }

    /**
     * Downloads a url to a temporary file
     * then return its file path.
     *
     * @param string $url
     *
     * @return false|string False if download failure.
     */
    public static function download($url)
    {
        // some urls require request headers same as browsers
        // and `fopen` can't access them
        // so we should use this method to download
        $request = EasyRequest::create($url, 'GET')->send();
        if ($request->getResponseStatus() == 200) {
            $tmpfile = tempnam('php://temp', '');
            $fp = fopen($tmpfile, 'w');
            fwrite($fp, (string) $request);
            fclose($fp);

            return $tmpfile;
        }

        return false;


        // $url = strtr(trim(rawurldecode($url)), array(' ' => '%20'));
        // if ($data = fopen($url, 'rb')) {
        //     $tmpfile = tempnam("php://temp", 'tmp');
        //     $tmp = fopen($tmpfile, 'w');
        //     while ($buff = fread($data, 1024 * 8)) {
        //         fwrite($tmp, $buff);
        //     }
        //     fclose($data);
        //     fclose($tmp);
        //     return $tmpfile;
        // }
        // return false;
    }
}
