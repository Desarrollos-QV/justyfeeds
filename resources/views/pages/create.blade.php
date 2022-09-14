@extends('layouts.user-no-nav')
@section('page_title', __('New post'))

@section('styles')
    {!!
        Minify::stylesheet([
            '/css/posts/post.css',
            '/libs/dropzone/dist/dropzone.css',
         ])->withFullUrl()
    !!}
@stop

@section('scripts')
    {!!
        Minify::javascript([
            '/js/Post.js',
            '/js/posts/create-helper.js',
            (Route::currentRouteName() =='posts.create' ? '/js/posts/create.js' : '/js/posts/edit.js'),
            '/libs/dropzone/dist/dropzone.js',
            '/libs/jquery-circle-progress/dist/circle-progress.min.js',
            '/js/FileUpload.js',
            'https://cdn.jsdelivr.net/npm/video-metadata-thumbnails/lib/video-metadata-thumbnails.iife.js',
         ])->withFullUrl()
    !!}
@stop

@section('content')

    <div class="row">
        <div class="col-12">
            @include('elements.post-price-setup',['postPrice'=>(isset($post) ? $post->price : 0)])
            @include('elements.post-save-confirmation')
            
            <div class="d-flex justify-content-between pt-4 pb-3 px-3 border-bottom">
                <h5 class="text-truncate text-bold  {{(Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? '' : 'text-dark-r') : (Cookie::get('app_theme') == 'dark' ? '' : 'text-dark-r'))}}">{{Route::currentRouteName() == 'posts.create' ? __('New post') : __('Edit post')}}</h5>
            </div>

            @if(!GenericHelper::isUserVerified() && getSetting('site.enforce_user_identity_checks'))
                <div class="alert alert-warning text-white font-weight-bold mt-2 mb-0" role="alert">
                    {{__("Before being able to publish an item, you need to complete your")}} <a class="text-white" href="{{route('my.settings',['type'=>'verify'])}}">{{__("profile verification")}}</a>.
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif
        </div>
 
        <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
            <div class=""> 
                <div id="tpl" class="jsx-2404389384 upload-card before-upload-stage">
                    <div class="jsx-2404389384 dropzone-previews dropzone"></div>
                </div>

                <div class="jsx-2404389384 upload-text before-upload-stage file-upload-button dropzone">
                    <img src="{{asset('/img/upload.png')}}" class="jsx-2404389384 cloud-icon">
                    <div class="jsx-2404389384 text-main">
                        <span class="css-1q42136">Selecciona el video para cargar</span>
                    </div>
                    <div class="jsx-2404389384 text-sub">
                        <span class="css-1wbo2p7">O arrastra y suelta un archivo</span>
                    </div>
                    <div class="jsx-2404389384 text-video-info">
                        <div class="jsx-2404389384">
                            <span class="css-tad11f">MP4, AVI, JPG, PNG, </span>
                        </div>
                        <div class="jsx-2404389384">
                            <span class="css-tad11f">Resolución de al menos 720x1280</span>
                        </div>
                        <div class="jsx-2404389384">
                            <span class="css-tad11f">Hasta 3 minutos</span>
                        </div>
                        <div class="jsx-2404389384">
                            <span class="css-tad11f">Menos de 1&nbsp;GB</span>
                        </div>
                    </div>
                    <div class="jsx-2404389384 file-select-button">
                        <button class="css-ku5jwq file-upload-button">
                            <div class="css-4c683v">
                                <div class="css-1z070dx">Selecciona un archivo</div>
                            </div>
                        </button>
                    </div>
                </div> 
            </div> 
        </div>
        <div class="col-lg-8 col-md-8 col-sm-12 col-xs-12" style="margin-top:20px;">
            <div class="pl-3 pr-3 pt-2"> 
                <div class="w-100">
                    <h5>{{__('Cover page')}}</h5>
                    <div class="preview_blob">
                        <img src="" class="preview_pic_blob">
                    </div>
                </div>
            </div>

            <div class="pl-3 pr-3 pt-2"> 
                <div class="w-100"> 
                    <h5>{{__('Content_create')}}</h5>
                    <textarea id="dropzone-uploader" name="input-text" class="form-control border dropzone w-100" rows="3" 
                                spellcheck="false" 
                                placeholder="{{__('Write a new post, drag and drop files to upload files.')}}" 
                                value="{{isset($post) ? $post->text : ''}}"></textarea>
                    
                    <span class="invalid-feedback" role="alert">
                        <strong>{{__('Your post must contain more than 10 characters.')}}</strong>
                    </span> 
                    <div class="d-flex justify-content-between w-100 mb-3 mt-3">
                        <div class="d-flex">
                            <div class="mt-1">
                                <span class="h-pill h-pill-primary post-price-button" onclick="PostCreate.showSetPricePostDialog()">
                                    @include('elements.icon',['icon'=>'logo-usd','variant'=>'medium','centered'=>true])
                                    <span class="d-none d-md-block">{{__("Set post price")}}</span>
                                    <span class="d-block d-md-none">{{__("Price")}}</span>
                                    <span class="post-price-label ml-1">{{(isset($post) && $post) > 0 ? "(".config('app.site.currency_symbol')."$post->price".(config('app.site.currency_symbol') ? '' : config('app.site.currency_code')).")" : ''}}</span>
                                </span>
                            </div>
                            <div class="mt-1 ml-2">
                                
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-center">
                            @if(Route::currentRouteName() == 'posts.create')
                                <div class="d-none d-md-block">
                                    <a href="#" class="draft-clear-button mr-1 mr-md-3">{{__('Clear draft')}}</a>
                                </div>
                            @endif
                            @if(!GenericHelper::isUserVerified() && getSetting('site.enforce_user_identity_checks'))
                                <button class="btn btn-outline-primary disabled mb-0" disabled>{{__('Publish')}}</button>
                            @else
                                <button class="btn btn-outline-primary post-create-button mb-0" disabled>{{__('Publish')}}</button>
                            @endif
                        </div>
                    </div>
                </div> 
            </div>
        </div> 
    </div> 
@stop
