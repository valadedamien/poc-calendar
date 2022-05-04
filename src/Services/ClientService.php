<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Google_Client;
use Google_Service_Calendar;
use RuntimeException;

class ClientService
{
    private string $credentialsPath;
    private string $tokenPath;
    private Google_Client $client;

    public function __construct(string $credentialsPath, string $tokenPath)
    {
        $this->credentialsPath = $credentialsPath;
        $this->tokenPath = $tokenPath;

        $this->initClient();
    }

    public function getClient(): Google_Client
    {
        $this->setAccessToken();
        $this->refreshToken();

        return $this->client;
    }

    /**
     * Creation du token après demande de consentement.
     */
    public function writeNewToken(string $token): void
    {
        $accessToken = $this->client->fetchAccessTokenWithAuthCode($token);
        $this->writeTokenFile($accessToken);
    }

    /**
     * Met le token dans un fichier.
     */
    public function writeTokenFile(array $accessToken): void
    {
        if (\array_key_exists('error', $accessToken)) {
            throw new Exception(implode(', ', $accessToken));
        }

        if (!file_exists(\dirname($this->tokenPath))) {
            if (!mkdir($concurrentDirectory = \dirname($this->tokenPath), 0700, true) && !is_dir($concurrentDirectory)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }

        file_put_contents($this->tokenPath, json_encode($this->client->getAccessToken(), JSON_THROW_ON_ERROR));
    }

    /**
     * Init du client google.
     */
    private function initClient(): void
    {
        $this->client = new Google_Client();
        $this->client->setApplicationName('Application de test');
        $this->client->setScopes(Google_Service_Calendar::CALENDAR_EVENTS);
        $this->client->setAuthConfig($this->credentialsPath);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');
    }

    /**
     * Si le fichier existe, on set le token.
     */
    private function setAccessToken(): void
    {
        if (file_exists($this->tokenPath)) {
            $accessToken = json_decode(file_get_contents($this->tokenPath), true, 512, JSON_THROW_ON_ERROR);
            $this->client->setAccessToken($accessToken);
        }
    }

    /**
     * Si le token est expiré
     *   Si il y a un refresh token -> on refresh le token + modification du fichier de token
     *   Sinon on demande les accords pour le compte pour avoir un token.
     */
    private function refreshToken(): void
    {
        if ($this->client->isAccessTokenExpired()) {
            if ($this->client->getRefreshToken()) {
                $accessToken = $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                $this->writeTokenFile($accessToken);
            } else {
                header('Status: 301 Moved Permanently', false, 301);
                header('Location: '.$this->client->createAuthUrl());

                exit();
            }
        }
    }
}
