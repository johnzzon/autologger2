<?php


namespace App\Service;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class Fibery
{

    /**
     * @var \Symfony\Contracts\HttpClient\HttpClientInterface
     */
    protected $client;

    /**
     * @var \Symfony\Component\Cache\Adapter\FilesystemAdapter
     */
    private $cache;

    /**
     * Fibery constructor.
     */
    public function __construct()
    {
        $workspace = getenv('FIBERY_WORKSPACE');
        $this->client = new Client(['base_uri' => "https://$workspace.fibery.io"]);
        $this->cache = new FilesystemAdapter();
    }

    /**
     * @param string $id
     * @param string $time_spent
     * @param string $message
     */
    public function logTime(string $id, string $date, string $time_spent, string $message): void
    {
        $moment_cache = $this->cache->getItem('fibery.moment.'.$id);
        if (!$moment_cache->isHit()) {
            $moment = $this->getMomentFromId($id);
            $moment_cache->set($moment);
        }
        $moment = $moment_cache->get();
        $this->createTimelog($moment['uuid'], $moment['project'], $date, $time_spent, $message);
    }

    /**
     * @param string $id
     *   The public ID of the moment.
     */
    public function getMomentFromId(string $id): ?array
    {
        $body = <<<JSON

[
  {
    "command": "fibery.entity/query",
    "args": {
      "query": {
        "q/from": "Projekthantering/Moment",
        "q/select": [
          "fibery/id",
          "fibery/public-id",
          "Projekthantering/name",
          {
            "Projekthantering/Projekt": [
              "fibery/id"
            ]
          }
        ],
        "q/where": [
          "=",
          [
            "fibery/public-id"
          ],
          "\$id"
        ],
        "q/limit": 20
      },
      "params": {
        "\$id": "$id"
      }
    }
  }
]
JSON;
        $options = [
            'headers' => [
                'Authorization' => 'Token '.getenv('FIBERY_TOKEN'),
                'Content-Type' => 'application/json',
            ],
            'body' => $body,
        ];
        $response = $this->client->post('/api/commands', $options);
        $response_body = $response->getBody()->getContents();

        $response_data = json_decode($response_body, true);

        if ($response_data[0]['success'] !== true) {
            return null;
        }

        return [
            'uuid' => $response_data[0]['result'][0]['fibery/id'] ?? null,
            'project' => $response_data[0]['result'][0]['Projekthantering/Projekt']['fibery/id'] ?? null,
        ];
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function createTimelog(string $moment_uuid, string $project_uuid, string $date, string $time_spent, string $message) {
        $fibery_user = getenv('FIBERY_USER');

        $body = <<<JSON
[
  {
    "command": "fibery.entity/create",
    "args": {
      "type": "Projekthantering/Tidlogg",
      "entity": {
        "Projekthantering/Tidsåtgång": "$time_spent",
        "Projekthantering/Dag": "$date",
        "Projekthantering/name": "Autolog: $message",
        "Projekthantering/user": {
          "fibery/id": "$fibery_user"
        },
        "Projekthantering/Moment": {
          "fibery/id": "$moment_uuid"
        },
        "Projekthantering/Projekt": {
          "fibery/id": "$project_uuid"
        }
      }
    }
  }
]
JSON;

        $options = [
            'headers' => [
                'Authorization' => 'Token '.getenv('FIBERY_TOKEN'),
                'Content-Type' => 'application/json',
            ],
            'body' => $body,
        ];
        $response = $this->client->post('/api/commands', $options);
        $response_body = $response->getBody()->getContents();

        $response_data = json_decode($response_body, true);

        if ($response_data[0]['success'] !== true) {
            throw new \Exception("Shit didn't work yo");
        }

    }

}