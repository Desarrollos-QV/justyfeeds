<nav class="user-navbar-gl sticky-top">
    <div class='nav__left'>
      <form action="{{ route('search.get')}}" class="search-box-wrapper w-100" method="GET">
        <div class="input-group input-group-seamless-append">
            <input type="text" class="form-control shadow-none" aria-label="Text input with dropdown button" placeholder="Search" name="query" value="{{isset($searchTerm) && $searchTerm ? $searchTerm : ''}}">
            <div class="input-group-append">
                <span class="input-group-text">
                    <span class="h-pill h-pill-primary rounded file-upload-button" onclick="submitSearch();">
                        @include('elements.icon',['icon'=>'search'])
                    </span>
                </span>
            </div>
        </div>
        <input type="hidden" name="filter" value="{{isset($activeFilter) && $activeFilter !== false ? $activeFilter : 'top'}}" />
      </form>
    </div>

    <div class='nav__mid'>
        <a href='{{Auth::check() ? route('feed') : route('home')}}' class='icon'>
          <i class='material-icons'>
            @include('elements.icon',['icon'=>'home-outline','variant'=>'large'])
          </i>
        </a>
        <a href='{{route('my.notifications')}}' class='icon'>
          <i class='material-icons'>
            @include('elements.icon',['icon'=>'notifications-outline','variant'=>'large'])
          </i>
        </a>
        <a href='{{route('my.bookmarks')}}' class='icon'>
          <i class='material-icons'>
            @include('elements.icon',['icon'=>'bookmark-outline','variant'=>'large'])
          </i>
        </a>
        <a href='{{route('my.lists.all')}}' class='icon'>
          <i class='material-icons'>
            @include('elements.icon',['icon'=>'list-outline','variant'=>'large'])
          </i>
        </a>
    </div>

    <div class="nav__right">
        <a href='{{route('profile',['username'=>Auth::user()->username])}}' class="avatar">
            <img class='avatar__img' src='{{Auth::user()->avatar}}' />
            <span><strong>{{Auth::user()->username}}</strong></span>
        </a>
        <div class="buttons">
            <a href="{{route('posts.create')}}">
                @include('elements.icon',['icon'=>'add-circle-outline','variant'=>'large'])
            </a>
            <a href="{{route('my.messenger.get')}}">
                @include('elements.icon',['icon'=>'chatbubble-outline','variant'=>'large'])
            </a>
            <a href="{{route('my.notifications',['type' => 'likes'])}}"> 
                @include('elements.icon',['icon'=>'heart-outline','variant'=>'large'])
            </a> 
        </div>
    </div>
</nav>