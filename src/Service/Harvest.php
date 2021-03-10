<?php


namespace App\Service;


use BestIt\Harvest\Client;
use BestIt\Harvest\Models\Projects\Projects;

class Harvest
{

    protected $client;

    public function __construct()
    {
        $this->client = new Client(
            getenv('HARVEST_SERVER_URL'),
            getenv('HARVEST_USERNAME'),
            getenv('HARVEST_PASSWORD')
        );
    }

    public function getProjects(): Projects
    {
        return $this->client->projects()->all();
    }

}