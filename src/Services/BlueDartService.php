<?php

namespace App\Services;

use App\Entity\Setting;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class BlueDartService
{
    const OAUTH_URL = 'https://apigateway.bluedart.com/in/transportation/token/v1/login';
    const BLUE_DART_URL = 'https://apigateway.bluedart.com/in/transportation';
    private ?string $token = null;

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly HttpClientInterface $client,
    ){}

    private function getToken(): void
    {
        $em = $this->doctrine->getManager();
        $setting = $em->getRepository(Setting::class)->findOneBy([]);
        if(!$setting->getBlueDartToken() or !$setting->getBlueDartTokenCreatedAt() or $setting->getBlueDartTokenCreatedAt() < date_create('23 hours ago')){
            $setting->setBlueDartToken($this->blueDartToken());
            $setting->setBlueDartTokenCreatedAt(date_create());
            $em->persist($setting);
            $em->flush();
        }
        $this->token = $setting->getBlueDartToken();
    }

    public function blueDartToken(): ?string
    {
        $response = $this->client->request('GET', self::OAUTH_URL, [
            'headers' => [
                'ClientID' => $_ENV['BLUE_DART_CLIENT_ID'],
                'ClientSecret' => $_ENV['BLUE_DART_CLIENT_SECRET'],
            ],
        ]);
        $decodedPayload = $response->toArray();
        return $decodedPayload['JWTToken']??null;
    }

    public function trackShipment($endpoint, $params = null, $method = 'GET'): array
    {
        try {
            $this->getToken();
            $options = [
                'headers' => [
                    'JWTToken' => $this->token,
                ],
                'query' => $params,
            ];
            $client = HttpClient::create();
            $response = $client->request('GET', self::BLUE_DART_URL . $endpoint, $options);
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
        $endpoint = "/tracking/v1/shipment";
        $params = [
            'handler' => 'tnt',
            'loginid' => $_ENV['BLUE_DART_LOGIN_ID'],
            'numbers' => trim($id),
            'format' => 'json',
            'lickey' => $_ENV['BLUE_DART_LIC_KEY'],
            'scan' => '1',
            'action' => 'custawbquery',
            'verno' => '1',
            'awb' => 'awb',
        ];
        return $this->trackShipment($endpoint, $params);
    }
}