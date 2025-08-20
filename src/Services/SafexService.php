<?php

namespace App\Services;

use App\Entity\Setting;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class SafexService
{
    const OAUTH_URL = 'https://api-auth.safexpress.com/oauth2';
    const SAFEX_URL = 'https://apigateway.safexpress.com/wbtrack/SafexWaybillTracking/webresources/safex_customer';
    private ?string $token = null;

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly HttpClientInterface $client,
    ){}

    private function getToken(): void
    {
        $em = $this->doctrine->getManager();
        $setting = $em->getRepository(Setting::class)->findOneBy([]);
        if(!$setting->getSafexToken() or !$setting->getSafexTokenCreatedAt() or $setting->getSafexTokenCreatedAt() < date_create('23 hours ago')){
            $setting->setSafexToken($this->saFexToken());
            $setting->setSafexTokenCreatedAt(date_create());
            $em->persist($setting);
            $em->flush();
        }
        $this->token = $setting->getSafexToken();
    }

    public function saFexToken(): ?string
    {
        $response = $this->client->request('POST', self::OAUTH_URL."/token", [
            'body' => [
                'grant_type' => 'client_credentials',
                'client_id' => $_ENV['SAFEX_CLIENT_ID'],
                'client_secret' => $_ENV['SAFEX_CLIENT_SECRET'],
            ],
        ]);
        $decodedPayload = $response->toArray();
        return $decodedPayload['access_token']??null;
    }

    public function callSaFex($endpoint, $json = null, $method = 'GET'): array
    {
        try {
            $this->getToken();
            $options = [
                'headers' => [
                    'x-api-key' => $_ENV['SAFEX_X_API_KEY'],
                    'Authorization' => $this->token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $json
            ];
            $client = HttpClient::create();
            $response = $client->request($method, self::SAFEX_URL . $endpoint, $options);
            return $response->toArray();
        }catch (\Exception $exception){
            if($_ENV['APP_ENV'] === 'dev'){
                dd($exception);
            }
            return [];
        }
    }

    public function getTrackingStatus($id): array
    {
        $endpoint = "/tracking";
        $json = [
            "docNo" => trim($id),
            "docType"=> "WB"
        ];
        return $this->callSaFex($endpoint, $json, 'POST');
    }
}