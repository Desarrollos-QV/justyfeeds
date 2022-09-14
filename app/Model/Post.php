<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'text',
        'price',
        'status',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
    ];

    /*
     * Relationships
     */

    public function user()
    {
        return $this->hasOne('App\User', 'id', 'user_id');
    }

    public function comments()
    {
        return $this->hasMany('App\Model\PostComment');
    }

    
    public function reactions()
    {
        return $this->hasMany('App\Model\Reaction');
    }

    public function bookmarks()
    {
        return $this->hasMany('App\Model\UserBookmark');
    }

    public function attachments()
    {
        return $this->hasMany('App\Model\Attachment');
    }

    public function transactions()
    {
        return $this->hasMany('App\Model\Transaction');
    }

    public function postPurchases()
    {
        return $this->hasMany('App\Model\Transaction', 'post_id', 'id')->where('status', 'approved')->where('type', 'post-unlock');
    }

    public function tips()
    {
        return $this->hasMany('App\Model\Transaction')->where('type', 'tip');
    }

    /**
     * 
     * Funciones para obtener posts en el aplicativo
     * 
    */

    public function getAppData()
    {  

        $userID = (isset($_GET['userID'])) ? $_GET['userID'] : 0;
        $relatiions = ['user', 'attachments', 'reactions','comments','user.subscribers'];
        
        if ($userID != 0) { // Existe un usuario logged
            $post = Post::withCount('reactions','comments')->with($relatiions)->where('posts.user_id','!=',$userID)->orderBy('id','DESC')->get();
        }else {
            $post = Post::withCount('reactions','comments')->with($relatiions)->orderBy('id','DESC')->get();
        }
         
        $data = [];
        $subscriber = false;
        $reaction = false;
        $list_id = [];
        foreach ($post as $key => $value) {
            
            // Filtramos resultados a solo videos
            if (isset($value->attachments[0])) { 
                $attach = $value->attachments[0];
                if ($attach->attachmentType == 'video') { 
                    $subscriber = false;
                    $reaction = false;
                    $list_id    = null;

                    // Obtenemos la Subscription
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

                    // Obtenemos si ya ha reaccionado al post
                    $reaction_chk = Reaction::where('user_id',$userID)->where('post_id',$value->id)->first();
                    (isset($reaction_chk) && isset($reaction_chk->id)) ? $reaction = true : $reaction = false;

                    $data[] = [
                        'id' => $value->id,
                        'user_id' => $value->user_id,
                        'text' => $value->text,
                        'price' => $value->price,
                        'status' => $value->status,
                        'reactions_count' => $value->reactions_count,
                        'comments_count' => $value->comments_count, 
                        'subscriber' => $subscriber,
                        'reaction'   => $reaction,
                        'list_id'    => $list_id,
                        'user' => collect($value->user)->except('settings','auth_provider'), 
                        'user_subs' => count($value->user->subscribers),
                        'attachments' => collect($value->attachments[0])->except('thumbnail'),
                    ];
                }
            }
        }

        return $data;
        
    }

}
