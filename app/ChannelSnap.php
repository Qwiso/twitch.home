<?php

namespace App;

use Eloquent;

class ChannelSnap extends Eloquent {

    protected $primaryKey = 'channel_id';
    protected $dates = ['timestamp'];
    protected $guarded = [''];
    public $timestamps = false;

    public function game(){ return $this->belongsTo('App\Game'); }
    public function channel(){ return $this->belongsTo('App\Channel'); }

}