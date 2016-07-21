<?php

namespace App\Crawlers;

use App\Channel;
use DB;
use GuzzleHttp\Client;
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Pool;

class TwitchFollowsCrawler {

    protected $db;

    public function __construct()
    {
        // prepare our database handler some for rude abuse
        $this->db = DB::connection('mysql');
        $this->start = time();
    }

    function bang() {

        // right. this might take a minute to run ...
        set_time_limit(0);

        // pre-cached chunks. ~760k total, 5,000 per chunk, ~150 chunks
//        $channel_chunks = json_decode(file_get_contents("/home/vagrant/Code/ggvods/storage/channels.json"));
//        $channel_chunks = json_decode(file_get_contents("/var/www/html/ggvods/storage/channels.json"));
        $channel_chunks = Channel::get(['id','name'])->chunk(5000);

        // strap in the guzzle client
        $client = new Client;

        // a little bling
        echo "RUNNING: [";

        // ... and let's begin!
        foreach ($channel_chunks as $channel_chunk) {

            // this will hold the request pool
            $requests = [];

            // the resulting blob of data
            $blob = [];

            // this will hold the urls we generate once we know
            // how deep their follow pagination can go
            // we will run it after the first pool finishes
            $deep_request_urls = [];

            foreach ($channel_chunk as $channel) {

                // basic guzzle request. nothing flashy here at all
                $req = $client->createRequest("GET", "https://api.twitch.tv/kraken/users/$channel->name/follows/channels?limit=100");

                // create a callback on the complete event for each of these requests
                // we have to use the & reference on the holder vars we made earlier,
                // otherwise they don't populate properly. ty for that tip, lagbox
                $req->getEmitter()->on('complete', function (CompleteEvent $event) use ($channel, &$blob, &$deep_request_urls) {

                    // $d is the json data returned from the api
                    $d = json_decode($event->getResponse()->getBody()->getContents());

                    // UNIQUE INTERACTION:
                    // sometimes $d->follows is empty and this will
                    // still attempt to use the non-existent data. this
                    // obviously results in a php error. however, because
                    // we use this in a guzzle callback, if will cause the
                    // request to fail. so, a request which has no usable
                    // data will be be discarded without any further code
                    // required on my part. thank you guzzle
                    foreach($d->follows as $follow) {

                        // push the data we need to the holder array
                        array_push($blob, [
                            'channel_id' => $channel->id,
                            'follows' => $follow->channel->name
                        ]);
                    }

                    // here we check how deep their pagination can go
                    // and build the urls for each of those calls
                    $total = $d->_total;
                    if($total > 100)
                    {
                        // since it's a deep link, the first call
                        // will be offset to get the next 100
                        $offset = 100;

                        // this will hold each url needed to get all the follower data
                        // from a single channel
                        $urls = [];
                        while($offset <= $d->_total) {

                            // pushing this url into the $urls for the channel
                            array_push($urls, "https://api.twitch.tv/kraken/users/$channel->name/follows/channels?limit=100&offset=" . $offset);

                            // each time through, we increment the offset
                            $offset += 100;
                        }

                        // in this specific case, we also need to know the channel_id
                        // for the deeper calls since they won't be passed as a var
                        // so, i just group the url's by channel_id for use later
                        $deep_request_urls[$channel->id] = $urls;
                    }

                });

                // okay, we've built the request for this name
                // add it to the list
                array_push($requests, $req);
            }


            // run the first pool of requests
            $pool = new Pool($client, $requests);

            // this call is synchronous
            $pool->wait();

            // because of the callbacks, all of our holders are
            // populated to the first level however we go
            // deeper, so we make some new requests to run
            $deep_requests = [];
            $deep_blob = [];

            foreach($deep_request_urls as $cid => $urls)
            {
                // ... when we can
                if(!empty($urls))
                {
                    foreach($urls as $url)
                    {
                        // this request build is exactly the same as before
                        // but we omit the logic to generate the deeper urls again
                        $req = $client->createRequest("GET", $url);
                        $req->getEmitter()->on('complete', function (CompleteEvent $event) use($cid, &$deep_blob) {

                            $d = json_decode($event->getResponse()->getBody()->getContents());

                            foreach($d->follows as $follow)
                            {
                                array_push($deep_blob, [
                                    'channel_id' => $cid,
                                    'follows' => $follow->channel->name
                                ]);
                            }
                        });

                        // push the request into the pool
                        array_push($deep_requests, $req);
                    }
                }
            }

            // aaaand go!
            $pool2 = new Pool($client, $deep_requests, ['pool_size'=>5000]);
            $pool2->wait();

            // cool. we made it
            // here we just chunk up the results
            $blob_chunks = array_chunk($blob, 1000);
            $more_blob_chunks = array_chunk($deep_blob, 1000);

            // and insert each to the database
            foreach($blob_chunks as $blob_chunk)
            {
                $this->db->table('channel_relationships')->insert($blob_chunk);
            }

            foreach($more_blob_chunks as $blob_chunk)
            {
                $this->db->table('channel_relationships')->insert($blob_chunk);
            }

            // take a breath and go for another
            echo "|";
        }

        // .. we .... WE'RE DONE?!
        echo "]" . PHP_EOL;
        echo "------ DONE?! that only took " . date('i:s', time() - $this->start);
    }

}