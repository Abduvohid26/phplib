<?php

namespace SecureProxy;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiBase = $_ENV['API_BASE'];

class SecureProxyClient
{
    private string $apiBase = "https://videoyukla.uz/check/token";
    private string $proxyToken;
    private ?string $proxyUrl = null;

    public function __construct(string $proxyToken)
    {
        $this->proxyToken = $proxyToken;
    }

    private function getProxyUrl(): ?string
    {
        $client = new Client(['timeout' => 30.0]);

        try {
            $response = $client->request('GET', $this->apiBase, [
                'query' => ['proxy_token' => $this->proxyToken],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['proxy_url'])) {
                return $data['proxy_url'];
            } else {
                throw new \Exception("Invalid or expired proxy token");
            }
        } catch (RequestException $e) {
            echo "Request error occurred: " . $e->getMessage();
            return null;
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage();
            return null;
        }
    }

    public function request(string $url): array
    {
        if ($this->proxyUrl === null) {
            $this->proxyUrl = $this->getProxyUrl();
        }

        if (!$this->proxyUrl) {
            return ["error" => "Proxy URL not available"];
        }

        $client = new Client([
            'proxy' => $this->proxyUrl,
            'timeout' => 60.0,
        ]);

        try {
            $response = $client->post($url);
            return [
                'content' => $response->getBody()->getContents(),
                'status_code' => $response->getStatusCode(),
            ];
        } catch (RequestException $e) {
            return [
                'error' => "Request failed: " . $e->getMessage(),
                'status_code' => $e->getCode(),
            ];
        }
    }
}
