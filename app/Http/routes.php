<?php

Route::group(['middleware' => ['web']], function () {

    Route::get('/', function () {
        return view('welcome');
    });

    Route::get('test', function(){



    });

    Route::group(['middleware'=>'custom_key'], function() {

        Route::group(['prefix'=>'games'], function(){
            Route::get('/', 'GameController@getIndex');
            Route::get('snaps', 'GameSnapController@getIndex');
            Route::get('live', 'GameController@getLive');

            Route::get('search', function(){
                if(request()->has('id')) return \App\Game::find(request()->get('id'));
                if(request()->has('name')) return \App\Game::where('name', 'LIKE', '%'.request()->get('name').'%')->get();
                return response('', 203);
            });

            Route::get('tags/{tags?}', function($tags){
                $tags = str_getcsv($tags);
                return \App\Game::whereHas('tags', function($q) use($tags) {
                    $q->whereIn('text', $tags);
                })->get()->load('tags');
            });

            Route::post('{name?}/tags/{tags?}', function($name = null, $tags = null){
                if(!$name) return response('', 203);
                if($name == '') return response('', 404);
                if(!$game = \App\Game::where('name', $name)->first()) return response('', 404);
                if(!$tags) return response('', 203);
                $tags = str_getcsv($tags);
                foreach($tags as $tag)
                {
                    $new_tag = \App\Tag::firstOrCreate(['text'=>$tag]);
                    $game->tags()->updateOrCreate($new_tag);
                }
                return $game->load('tags');
            });
        });

        Route::group(['prefix'=>'channels'], function(){
            Route::get('/', 'ChannelController@getIndex');
            Route::get('quick/{name}', 'ChannelController@getQuick');
            Route::get('snaps', 'ChannelSnapController@getIndex');
            Route::get('sessions', 'ChannelSessionController@getIndex');

            Route::get('search', function(){
                return \App\Channel::where('name', 'LIKE', '%'.request()->get('name').'%')->get();
            });

            Route::get('tags/{tags?}', function($tags){
                $tags = str_getcsv($tags);
                return \App\Channel::whereHas('tags', function($q) use($tags) {
                    $q->whereIn('text', $tags);
                })->get()->load('tags');
            });

            Route::post('{name?}/tags/{tags?}', function($name = null, $tags = null){
                if(!$name) return response('', 203);
                if($name == '') return response('', 404);
                if(!$channel = \App\Channel::where('name', $name)->first()) return response('', 404);
                if(!$tags) return response('', 203);
                $tags = str_getcsv($tags);
                foreach($tags as $tag)
                {
                    $new_tag = \App\Tag::firstOrCreate(['text'=>$tag]);
                    $channel->tags()->save($new_tag);
                }
                return $channel->load('tags');
            });
        });

        Route::get('log', function(){
            $log = File::get('/var/www/html/ggvods/storage/logs/laravel.log');
            $log = preg_replace('/\n/', '<br>', $log);
            return view('logs', compact('log'));
        });

        Route::get('crawler_log', function(){
            $log = File::get('/var/www/html/ggvods/storage/logs/crawler.log');
            $log = preg_replace('/\n/', '<br>', $log);
            return view('logs', compact('log'));
        });
    });

});
