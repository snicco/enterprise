<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition;

use Snicco\Component\StrArr\Arr;
use Snicco\Component\StrArr\Str;

use WP_User;

use function array_map;
use function explode;
use function implode;
use function ltrim;
use function rawurldecode;
use function strtr;

final class Context
{
    /**
     * @var array<string,string>
     */
    private array $_server = [];

    private ?string $path = null;

    /**
     * @var array<string,string|string[]>
     */
    private array $_get = [];

    /**
     * @var array<string,string|string[]>
     */
    private array $_post = [];

    /**
     * @var array<string,string|string[]>
     */
    private array    $_cookie = [];

    private ?WP_User $user;

    /**
     * @param array<string,string>          $_server
     * @param array<string,string|string[]> $_get
     * @param array<string,string|string[]> $_post
     * @param array<string,string|string[]> $_cookie
     */
    public function __construct(array $_server, array $_get, array $_post, array $_cookie, WP_User $user = null)
    {
        $this->_server = $_server;
        $this->_get = $_get;
        $this->_post = $_post;
        $this->_cookie = $_cookie;
        $this->user = $user;
    }

    public function requestMethod(): string
    {
        return (string) Arr::get($this->_server, 'REQUEST_METHOD', '');
    }

    public function path(): string
    {
        if (! isset($this->path)) {
            $path = (string) Arr::get($this->_server, 'REQUEST_URI', '');
            $this->path = ('' === $path)
                ? ''
                : '/' . ltrim(Str::beforeFirst($this->decodePath($path), '?'), '/');
        }

        return $this->path;
    }

    /**
     * @return string|string[]|null
     */
    public function queryVar(string $name)
    {
        return $this->_get[$name] ?? null;
    }

    /**
     * @return array<string,string|string[]>
     */
    public function post(): array
    {
        return $this->_post;
    }

    /**
     * @return array<string,string|string[]>
     */
    public function cookies(): array
    {
        return $this->_cookie;
    }

    public function user(): WP_User
    {
        if (null === $this->user) {
            $this->user = new WP_User(0);
        }

        return $this->user;
    }

    public function scriptName(): string
    {
        return $this->_server['SCRIPT_NAME'] ?? '';
    }

    private function decodePath(string $path): string
    {
        $split = explode('/', $path);

        return implode(
            '/',
            array_map(fn (string $segment): string => rawurldecode(strtr($segment, [
                '%2F' => '%252F',
            ])), $split)
        );
    }
}
