<?php

namespace App\Console\Commands;

use App\Crawlers\TwitchFollowsCrawler;
use Illuminate\Console\Command;

class TwitchFollowsCrawl extends Command {

    protected $signature = 'twitch:crawl-follows';
    protected $description = 'buckle up kiddies';

    protected $twitch;

    public function __construct(TwitchFollowsCrawler $twitch)
    {
        parent::__construct();
        $this->twitch = $twitch;
    }

    public function handle()
    {
        $this->twitch->bang();
    }

}