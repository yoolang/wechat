<?php

declare(strict_types=1);

namespace EasyWeChat\OpenWork;

use EasyWeChat\Kernel\Contracts\AccessToken as AccessTokenInterface;
use EasyWeChat\Kernel\Exceptions\HttpException;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ProviderAccessToken implements AccessTokenInterface
{
    protected HttpClientInterface $httpClient;
    protected CacheInterface $cache;

    public function __construct(
        protected string $corpId,
        protected string $providerSecret,
        protected ?string $key = null,
        ?CacheInterface $cache = null,
        ?HttpClientInterface $httpClient = null,
    ) {
        $this->httpClient = $httpClient ?? new HttpClient();
        $this->cache = $cache ?? new Psr16Cache(new FilesystemAdapter(namespace: 'easywechat', defaultLifetime: 1500));
    }

    public function getKey(): string
    {
        return $this->key ?? $this->key = \sprintf('open_work.access_token.%s', $this->corpId);
    }

    public function setKey(string $key): static
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @throws \EasyWeChat\Kernel\Exceptions\HttpException
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getToken(): string
    {
        $key = $this->getKey();

        if ($token = $this->cache->get($key)) {
            return $token;
        }

        $response = $this->httpClient->request(
            'GET',
            'cgi-bin/service/get_provider_token',
            [
                'json' => [
                    'corpid' => $this->corpId,
                    'provider_secret' => $this->providerSecret,
                ],
            ]
        )->toArray();

        if (empty($response['provider_access_token'])) {
            throw new HttpException('Failed to get provider_access_token.');
        }

        $this->cache->set($key, $response['provider_access_token'], \abs($response['expires_in'] - 100));

        return $response['provider_access_token'];
    }
}