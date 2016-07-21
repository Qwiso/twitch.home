<?php

namespace App\Console\Commands;

use App\Crawlers\TwitchAPICrawler;
use Illuminate\Console\Command;

class TwitchAPICrawl extends Command {

    protected $signature = 'twitch:crawl-api';
    protected $description = 'the big bang';

    protected $twitch;

    public function __construct(TwitchAPICrawler $twitch)
    {
        parent::__construct();
        $this->twitch = $twitch;
    }

    public function handle()
    {
        $this->twitch->bang();
    }

}