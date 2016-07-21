<?php

namespace App;

use Eloquent;

class ChannelBlock extends Eloquent {

    protected $dates = ['start_time','end_time'];
    public function channel(){ return $this->belongsToMany('App\Channel'); }

}