<!doctype html>
<html class="no-js" lang="">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title>@yield('page_title')</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="apple-touch-icon" href="icon.png">
        <!-- Place favicon.ico in the root directory -->

        <link rel="stylesheet" href="{{ $app['url']->to('/') }}/css/icomoon.css">
        <link rel="stylesheet" href="{{ $app['url']->to('/') }}/css/normalize.css">
        <link rel="stylesheet" href="{{ $app['url']->to('/') }}/css/main.css">

        <script
              src="https://code.jquery.com/jquery-2.2.4.min.js"
              integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44="
              crossorigin="anonymous">
        </script>
    </head>
    <body>
        <!--[if lte IE 9]>
            <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="https://browsehappy.com/">upgrade your browser</a> to improve your experience and security.</p>
        <![endif]-->

        <!-- Add your site or application content here -->
        <div id="app">
            
            @component('snippets.header')
                {{ $app['url']->to('/') }}
            @endcomponent
            <!-- Se muestra el contenido -->
            @yield('content')
            <!-- Se muestra el footer -->
            @component('snippets.footer')
                <br />
            @endcomponent
        </div>
        <script src="{{ $app['url']->to('/') }}/js/app-sleefs.js"></script>
    </body>
</html>