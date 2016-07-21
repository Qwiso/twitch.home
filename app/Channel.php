<?php

namespace App;

use Eloquent;

class Channel extends Eloquent {

    protected $guarded = [''];

    public function tags(){ return $this->morphToMany('App\Tag', 'taggable'); }
    public function snaps(){ return $this->hasMany('App\ChannelSnap'); }
    public function recentSnap(){ return $this->snaps()->orderBy('timestamp', 'desc')->first(); }
    public function sessions(){ return $this->hasMany('App\ChannelSession'); }

}