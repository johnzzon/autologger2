<?php


namespace App\Service;

use GuzzleHttp\Client;
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

        $timelog = $this->findTimelog($moment['uuid'], $date, $message);

        if ($timelog !== null) {
            $this->updateTimelog($timelog, $time_spent);
        } else {
            $this->createTimelog($moment['uuid'], $date, $time_spent, $message);
        }
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
        "q/from": "Kunder_och_projekt/Moment",
        "q/select": [
          "fibery/id",
          "fibery/public-id",
          "Kunder_och_projekt/name"
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
        ];
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function createTimelog(
        string $moment_uuid,
        string $date,
        string $time_spent,
        string $message
    ) {
        $fibery_user = getenv('FIBERY_USER');

        $body = <<<JSON
[
  {
    "command": "fibery.entity/create",
    "args": {
      "type": "Kunder_och_projekt/Tidlogg",
      "entity": {
        "Kunder_och_projekt/Tidsåtgång": "$time_spent",
        "Kunder_och_projekt/Dag": "$date",
        "Kunder_och_projekt/name": "Autolog: $message",
        "Kunder_och_projekt/user": {
          "fibery/id": "$fibery_user"
        },
        "Kunder_och_projekt/Moment": {
          "fibery/id": "$moment_uuid"
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

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function findTimelog(string $moment_uuid, string $date, string $message)
    {
        $fibery_user = getenv('FIBERY_USER');

        $body = <<<JSON
[
  {
    "command": "fibery.entity/query",
    "args": {
      "query": {
        "q/from": "Kunder_och_projekt/Tidlogg",
        "q/select": [
          "fibery/id",
          "Kunder_och_projekt/Beräknade timmar",
          "Kunder_och_projekt/Dag",
          "Kunder_och_projekt/name"
        ],
        "q/where": [
          "q/and",
          ["=", [ "Kunder_och_projekt/user", "fibery/id" ], "\$user"],
          ["=", [ "Kunder_och_projekt/Moment", "fibery/id"], "\$moment"],
          ["=", [ "Kunder_och_projekt/Dag"], "\$day"],
          ["=", [ "Kunder_och_projekt/name"], "\$message"]
        ],
        "q/limit": 20
      },
      "params": {
        "\$user": "$fibery_user",
        "\$moment": "$moment_uuid",
        "\$day": "$date",
        "\$message": "Autolog: $message"
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

        return $response_data[0]['result'][0]['fibery/id'] ?? null;
    }

    /**
     * @param $timelog_uuid
     * @param string $time_spent
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateTimelog($timelog_uuid, string $time_spent): void
    {
        $body = <<<JSON
[
  {
    "command": "fibery.entity/update",
    "args": {
      "type": "Kunder_och_projekt/Tidlogg",
      "entity": {
        "fibery/id": "$timelog_uuid",
        "Kunder_och_projekt/Tidsåtgång": "$time_spent"
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