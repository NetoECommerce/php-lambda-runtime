<?php declare(strict_types=1);

namespace Neto\Lambda\Message;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

class UriFactory
{
    /**
     * Construct an Uri object from individual components
     *
     * @param string $scheme Eg: http, https
     * @param string $host Eg: www.example.com
     * @param integer $port Eg: 80, 443
     * @param string $path Eg: /some/path
     * @param string $queryString A URL-encoded query string eg: foo=bar&baz=bar
     * @return UriInterface
     */
    public static function createUriFromParts(
        $scheme = 'https',
        $host = 'localhost',
        $port = null,
        $path = '/',
        $queryString = ''
    ): UriInterface {
        $uri = new Uri();

        return $uri
            ->withScheme($scheme)
            ->withHost($host)
            ->withPort($port)
            ->withPath($path)
            ->withQuery($queryString);
    }
}
