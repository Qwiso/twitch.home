<!DOCTYPE html>
<html>
    <head>
        <title>{{url('')}}</title>

        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css" rel="stylesheet" type="text/css">
        <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet" type="text/css">
        <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.caroufredsel/6.2.1/jquery.carouFredSel.packed.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.touchswipe/1.6.15/jquery.touchSwipe.min.js"></script>
        {{--<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.2/angular.min.js"></script>--}}
        <style>
            html, body {
                height: 100%;
            }

            body {
                margin: 0;
                padding: 0;
                width: 100%;
                background-color: #263b4f;
            }
            .channel {
                text-align: center;
                margin: 0 5px;
                display: inline-block;
                width: 200px;
                height: 200px;
            }
            .caroufredsel_wrapper {
                margin: 0 auto !important;
            }

            .back, .next {
                width: 50px;
                height: 100%;
                background-color: red;
            }
            .back { float: left; }
            .next { float: right; }
        </style>
    </head>
    <body>
        <div class="container-fluid">
            <div class="content">
                {{--<span class="text-center">{!! $top->render() !!}</span>--}}
                @foreach($top as $id => $gameid)
                <div style="border: 4px solid slategrey; margin-bottom: 10px; color: white;">
                    <h4>{{$gameid[0]->game->name}}</h4>
                    <div class="wrapper_{{$id}}">
                        <div class="back back_{{$id}}"></div>
                        @foreach($gameid[0]->channelSnaps as $chan)
                            <div class="channel">
                                {{--<img class="img-sm" src="{{$chan->channel->image}}">--}}
                                {{--{{$chan->channel->name}}<br>--}}
                                <i class="fa fa-fw fa-user"></i> {{$chan->viewers}}<br>
                                <i class="fa fa-fw fa-heart"></i> {{$chan->followers}}<br>
                                <i class="fa fa-fw fa-hourglass-2"></i> {{$chan->start_time}}
                            </div>
                        @endforeach
                        <div class="next next_{{$id}}"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </body>
    <script>
        $(function(){

            var gids = {!! $top !!};

            $.each(gids, function(a){
                $('.wrapper_'+a).carouFredSel({
                    infinite: true,
                    circular: false,
                    previous: {
                        button: '.back_'+a,
                        items: 5
                    },
                    next: {
                        button: '.next_'+a,
                        items: 5
                    },
                    swipe: {
                        onMouse: true,
                        onTouch: true,
                        options: {
                            items: 5
                        }
                    },
                    auto: {
                        play: false
                    },
                    items: {
                        visible: 5,
                        width: 200
                    },
                    scroll: {
                        items: 5
                    },
                    debug: true
                });
            });
        });
    </script>
</html>