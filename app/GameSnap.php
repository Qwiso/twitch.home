<?php

namespace App;

use Eloquent;

class GameSnap extends Eloquent {

    protected $primaryKey = 'game_id';
    protected $guarded = [''];
    public $timestamps = false;
    public function game(){ return $this->belongsTo('App\Game'); }
    public function channels(){ return $this->hasManyThrough('App\Channel', 'App\ChannelSnap', 'game_id', 'id'); }
    public function channelSnaps(){ return $this->hasMany('App\ChannelSnap', 'game_id', 'game_id'); }

}