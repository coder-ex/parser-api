<?php

namespace App\Services\Ozon;

use Illuminate\Support\Facades\Http;

class OzonToken
{
    private ?int $attempts;
    private ?int $sleep;
    private ?array $header;
    private ?string $body;

    private function __construct(?string $project = null, ?string $secret = null, ?int $attempts = null)
    {
        $this->attempts = $attempts;
        $this->sleep = 10;

        $this->header = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $this->body = json_encode(
            [
                "client_id" => $project,
                "client_secret" => $secret,
                "grant_type" => "client_credentials"
            ]
        );
    }

    /**
     * запрос на токен Bearer
     *
     * @param string $urlAPI
     * @param string $project
     * @param string $secret
     * @return void
     */
    public static function getToken(string $urlAPI, string $project, string $secret, ?int $sleep = 3)
    {
        $class = new static($project, $secret, $sleep);

        $url = $urlAPI . '/api/client/token';

        $count = 0;
        do {
            $options = ['connect_timeout' => 30, 'timeout' => 120];
            $response = Http::withOptions($options)->withHeaders($class->header)->withBody($class->body, 'application/json')->post($url);

            if ($response->status() >= 400) {
                if ($response->serverError()) { // error 500
                    if ($count > $class->attempts) {
                        $response->throw();
                    }

                    sleep($class->sleep);   // на всякий случай задержка 10, 15, 20 сек
                    $count++;
                    $class->sleep += 5;

                    echo "N. ", $count, " | вышли на повторный запрос getToken\n";

                    continue;
                } else {
                    $response->throw();
                }
            }

            $json = $response->json();

            if (array_key_exists('access_token', $json)) {
                return $json['access_token'];
            }

            break;
        } while (true);
    }
}
