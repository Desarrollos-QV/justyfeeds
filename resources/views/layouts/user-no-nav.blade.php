<!doctype html>
<html class="h-100" dir="{{Cookie::get('app_rtl') == 'rtl' ? 'rtl' : 'ltr'}}" lang="{{session('locale')}}">
<head>
    @include('template.head',['additionalCss' => [
                '/libs/animate.css/animate.css',
                '/libs/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.css',
                '/css/side-menu.css',
             ]])
</head>
<body class="d-flex flex-column">

    @include('template.user-navbar')

<div class="flex-fill">
    @include('template.user-side-menu')

    <div class="container-xl overflow-x-hidden-m">
        <div class="row main-wrapper">
            <!-- col-2 col-md-3 pt-4 p-0 -->
            <div class="side-bar-profile">
                @include('template.side-menu')
            </div>

            <!-- col-12 col-md-9 min-vh-100 border-left px-0 overflow-x-hidden-m content-wrapper {{(in_array(Route::currentRouteName(),['feed','profile','my.messenger.get','search.get','my.notifications','my.bookmarks','my.lists.all','my.lists.show','my.settings']) ? '' : 'border-right' )}} -->
            <div class="min-vh-100 px-0 overflow-x-hidden-m @if(in_array(Route::currentRouteName(),['feed'])) content-bar-feed @else content-bar-wrap @endif">
                @yield('content')
            </div>

            @if(in_array(Route::currentRouteName(),['feed']))
            <!-- col-12 col-sm-12 col-md-5 col-lg-4 first border-left order-0 pt-4 pb-5 min-vh-100 suggestions-wrapper d-none d-md-block -->
            <div class="suggestions-wrapper side-bar-suggestions">
                <div class="feed-widgets">
                    
                    @include('elements.feed.suggestions-box',['profiles'=>$suggestions])
                    
                    @if(getSetting('ad-spaces.sidebar_ad_spot'))
                        <div class="d-flex justify-content-center align-items-center mt-4">
                            {!! getSetting('ad-spaces.sidebar_ad_spot') !!}
                        </div>
                    @endif
                </div> 
            </div>
            @endif

            @if(in_array(Route::currentRouteName(),['profile']))
            <!-- col-12 col-md-4 d-none d-md-block pt-3 -->
            <div class="suggestions-wrapper side-bar-suggestions">
                <div class="feed-widgets">
                    @include('elements.profile.widgets')
                </div>
            </div>
            @endif
        </div>

        <div class="d-block d-md-none fixed-bottom">
            @include('elements.mobile-navbar')
        </div>
    </div>

</div>
@include('template.footer-compact',['compact'=>true])

@include('template.jsVars')
@include('template.jsAssets',['additionalJs' => [
               '/libs/jquery-backstretch/jquery.backstretch.min.js',
               '/libs/wow.js/dist/wow.min.js',
               '/libs/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.concat.min.js',
               '/js/SideMenu.js'
]])

</body>
</html>
