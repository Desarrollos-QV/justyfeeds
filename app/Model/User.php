<?php

namespace App;

use App\Model\Subscription;
use App\Model\UserList;
use App\Model\Post;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Hash;

class User extends \TCG\Voyager\Models\User implements MustVerifyEmail
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'role_id', 'password', 'username', 'bio', 'birthdate', 'location', 
        'website',
        'tiktok_url',
        'instagram_url',
        'onlyfans_url', 'avatar', 'cover', 'postcode', 'settings',
        'billing_address', 'first_name', 'last_name', 'profile_access_price',
        'profile_access_price_6_months',
        'profile_access_price_12_months',
        'public_profile', 'city', 'country', 'state', 'email_verified_at', 'paid_profile',
        'auth_provider','auth_provider_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'public_profile' => 'boolean',
        'settings' => 'array',
    ];

    /*
     * Virtual attributes
     */

    public function getAvatarAttribute($value)
    {
        return self::getStorageAvatarPath($value);
    }

    public function getCoverAttribute($value)
    {
        if($value){
            if(getSetting('storage.driver') == 's3'){
                return 'https://'.getSetting('storage.aws_bucket_name').'.s3.'.getSetting('storage.aws_region').'.amazonaws.com/'.$value;
            }
            elseif(getSetting('storage.driver') == 'wasabi'){
                return Storage::url($value);
            }
            else{
                return Storage::disk('public')->url($value);
            }
        }else{
            return asset(config('voyager.user.default_cover', '/img/default-cover.png'));
        }
    }

    public function getFansCountAttribute(){
        $activeSubscriptionsCount = Subscription::query()
            ->where('recipient_user_id', Auth::user()->id)
            ->whereDate('expires_at', '>=', new \DateTime('now', new \DateTimeZone('UTC')))
            ->count('id');

        return $activeSubscriptionsCount;
    }

    public function getFollowingCountAttribute(){
        $userId = Auth::user()->id;
        $userFollowingMembers = UserList::query()
            ->where(['user_id' => $userId, 'type' => 'followers'])
            ->withCount('members')->first();

        return $userFollowingMembers != null && $userFollowingMembers->members_count > 0 ? $userFollowingMembers->members_count : 0;
    }


    /**
     * Static function that handles remote storage drivers
     *
     * @param $value
     * @return string
     */
    public static function getStorageAvatarPath($value){
        if($value){
            if(getSetting('storage.driver') == 's3'){
                return 'https://'.getSetting('storage.aws_bucket_name').'.s3.'.getSetting('storage.aws_region').'.amazonaws.com/'.$value;
            }
            elseif(getSetting('storage.driver') == 'wasabi'){
                return Storage::url($value);
            }
            else{
                return Storage::disk('public')->url($value);
            }
        }else{
            return asset(config('voyager.user.default_avatar', '/img/default-avatar.png'));
        }
    }

    /*
     * Relationships
     */
    public function user()
    {
        return $this->hasOne('App\User', 'id');
    }

    public function posts()
    {
        return $this->hasMany('App\Model\Post');
    }

    public function postComments()
    {
        return $this->hasMany('App\Model\PostComment');
    }

    public function reactions()
    {
        return $this->hasMany('App\Model\Reaction');
    }

    public function reactions_posts()
    {
        return $this->hasMany('App\Model\Reaction','post_id');
    }

    public function subscriptions()
    {
        return $this->hasMany('App\Model\Subscription');
    }

    public function activeSubscriptions()
    {
        return $this->hasMany('App\Model\Subscription', 'sender_user_id')->where('status', 'completed');
    }

    public function activeCanceledSubscriptions()
    {
        return $this->hasMany('App\Model\Subscription', 'sender_user_id')->where('status', 'canceled')->where('expire_at', '<', Carbon::now());
    }

    public function subscribers()
    {
        return $this->hasMany('App\Model\Subscription', 'recipient_user_id');
    }

    public function transactions()
    {
        return $this->hasMany('App\Model\Transaction');
    }

    public function withdrawals()
    {
        return $this->hasMany('App\Model\Withdrawal');
    }

    public function attachments()
    {
        return $this->hasMany('App\Model\Attachment');
    }

    public function lists()
    {
        return $this->hasMany('App\Model\UserList');
    }

    public function bookmarks()
    {
        return $this->hasMany('App\Model\UserBookmark');
    }

    public function wallet()
    {
        return $this->hasOne('App\Model\Wallet');
    }

    public function verification()
    {
        return $this->hasOne('App\Model\UserVerify');
    }

    public function offer()
    {
        return $this->hasOne('App\Model\CreatorOffer');
    }

    public function getProfile($id)
    {
        $userID = (isset($_GET['userID'])) ? $_GET['userID'] : 0;

        $dat = User::where('id',$id)->withCount('posts','reactions','attachments','subscribers')->with('user','posts','reactions','attachments')
        ->orderBy('id','DESC')
        ->get();

        $data = [];
        $reactions = 0;
        $posts = [];
        $subscriber = false;
        foreach ($dat as $value) { 
            foreach ($value->posts as $key) {
                $reacts = Post::where('id',$key->id)->withCount('reactions')->first();
                
                $reactions += $reacts->reactions_count;
            }
            
            foreach ($value->attachments as $key) {
                
                if ($key->attachmentType == 'video') {

                    $post = Post::where('id',$key->post_id)->with('attachments')->first();
                    $p    = collect($post)->except('attachments');


                    $posts[] = [
                        'id' => $p['id'],
                        'text' => $p['text'],
                        'price' => $p['price'],
                        'type'  => $post->attachments[0]->type,
                        'path'  => $post->attachments[0]->path,
                        'thumbnail' => $post->attachments[0]->thumbnail,
                        'blob_pic' => $post->attachments[0]->blob_pic,
                        'status' => $p['status'],
                    ];
                }
            }
 
            if ($value->user->paid_profile) { // el perfil es de paga
                // Obtenemos la subs de estas cuentas
                $let_subs = Subscription::where('sender_user_id',$userID)->where('recipient_user_id',$value->user->id)->where('status','completed')->first();
                (isset($let_subs) && isset($let_subs->id)) ? $subscriber = true : $subscriber = false;
            }else { 
                $lists = UserList::with(['members'])
                ->where('user_id', $userID)
                ->first();

                if ($lists) {
                    foreach ($lists['members'] as $val_list) { 
                        if ($val_list->user_id == $value->user->id) { // lo sigue
                            $subscriber = true;
                            $list_id    = $val_list->list_id;
                            break;
                        }
                    }
                }
            } 

            $data = [
                'data'     => 'ok',
                'reactions' => $reactions,
                'posts_count' => count($posts),
                'subscriber' => $subscriber,
                'subscribers' => $value->subscribers_count,
                'user' => collect($value->user)->except('settings'),
                'posts' => $posts
            ];
        }
        

        return $data;
    }

    public function login($data)
    {
        $flag = false;
        $chk = User::where('email',$data['email'])->first();

        if (isset($chk->id)) {
            $chk_p = Hash::check($data['password'],$chk->password);
            if ($chk_p) {
                $flag = true;
            }
        }

        if ($flag) {
            return [
                'status' => 'done',
                'user' => $chk,
            ];
        }else {
            return [
                'status' => 'fail',
                'msg' => 'bad_creds'
            ];
        }

    }
}
