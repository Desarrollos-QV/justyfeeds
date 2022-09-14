<?php 
 
namespace App\Http\Controllers\api;
 
use App\Http\Requests;
use App\Http\Controllers\Controller; 

// Event Translate
use App\Events\TranslateJson;

use App\Providers\PostsHelperServiceProvider;
use App\Providers\ListsHelperServiceProvider;
use App\Providers\NotificationServiceProvider;
use App\Http\Requests\UpdateReactionRequest;

use Illuminate\Http\Request;
use Auth;
use DB;
use Validator;
use Redirect;
use stdClass;

use App\User;
use App\Model\Wallet;
use App\Model\Post;
use App\Model\PostComment;
use App\Model\Reaction;
use App\Model\UserList;
use App\Model\UserListMember;

 
class ApiController extends Controller {

	/**
	 * 
	 * Funciones iniciales
	 * 
	*/

	public function welcome()
	{
		$data = file_get_contents("https://justyfan.kiibo.mx/public/translate/data.json");
		$json = json_decode($data, true);
		
		$translate = new TranslateJson;

		return response()->json([
			'status' => $translate->Translate("en", "es", $json)
		]);
	} 

	public function homepage()
	{
		$req = new Post;
		try { 
			return response()->json([
				'data' => $req->getAppData()
			]);
		} catch (\Exception $th) {
			return response()->json(['data' => 'error','error' => $th->getMessage()]);
		}
	}

	/**
	 * 
	 * Obtenemos todos los intereses
	 * 
	 */

	public function setInterest(Request $Request)
	{
		try { 

			$ints = $Request->get('interest');

			for ($i=0; $i < count($ints); $i++) { 
				$add = DB::table('interests')->where('id',$ints[$i])->value('interest');
				$add +=1;
				
				DB::table('interests')->where('id',$ints[$i])->update(['interest' => $add]);
			}

			return response()->json([
				'data' => true
			]);
		} catch (\Exception $th) {
			return response()->json(['data' => 'error','error' => $th->getMessage()]);
		}
	}
	
	public function getInterest()
	{
		try { 
			return response()->json([
				'data' => DB::table('interests')->where('status',0)->get()
			]);
		} catch (\Exception $th) {
			return response()->json(['data' => 'error','error' => $th->getMessage()]);
		}
	}

	/**
	 * 
	 * Funciones para usuarios
	 * 
	*/
	
	public function userinfo($id)
	{
		try { 
			return response()->json([
				'data' => User::find($id),
				'wallet' => DB::table('wallets')->where('user_id',$id)->first()
			]);
		} catch (\Exception $th) {
			return response()->json(['data' => 'error','error' => $th->getMessage()]);
		}
	}

	public function getProfile($user)
	{
		try { 
			$userDat = new User; 
			return response()->json($userDat->getProfile($user));
		} catch (\Exception $th) {
			return response()->json(['data' => 'error','error' => $th->getMessage()]);
		}
	}

	public function login(Request $Request)
	{
		try {
			$userDat = new User; 
			return response()->json($userDat->login($Request->all()));
		} catch (\Exception $th) {
			return response()->json(['data' => 'error','error' => $th->getMessage()]);
		}
	}

	public function following(Request $request)
	{
		try {
			$userID = $request->get('user_id');
			$userOrigin = $request->get('userOrigin');
 
			// Obtenemos la lista
			$userLists = UserList::where('user_id', $userOrigin)->where('name','Following')->first();
			$listID    = $userLists->id;

			if (!$this->isAuthorized($listID,$userOrigin)) {
				return response()->json(['success' => false, 'errors' => [__('Not authorized')], 'message'=> __('Not authorized')], 403);
			}

			return ListsHelperServiceProvider::addListMember($listID, $userID, false);
		} catch (\Exception $th) {
			return response()->json(['data' => 'error','error' => $th->getMessage()]);
		}
	}

	public function isAuthorized($listID,$userOrigin)
    {
        // Checking if is authorized
        $userLists = UserList::where('user_id', $userOrigin)->get()->pluck('id')->toArray();
        $isOwnedList = in_array($listID, $userLists);
        if (! $isOwnedList) {
            return false;
        }

        return true;
    }

	/**
	 * 
	 * Funciones de Posts
	 * 
	 */

	public function getComments($id,$user_id,$username)
	{
		try {
			
			$postID = $id;
			$reaction = false;
            // Checking authorization & post existence
            $post = Post::with(['user'])->where('id', $postID)->first();
            if (! $post) {
                return response()->json(['success' => false, 'errors' => [__('Not found')], 'message'=> __('Post not found')], 404);
            }

            if (PostsHelperServiceProvider::hasActiveSub($user_id, $post->user_id) || $user_id == $post->user_id || (!$post->user->paid_profile)) {

				$comments_data = PostComment::withCount('reactions')->with(['author', 'reactions'])->orderBy('created_at', "DESC")
				->where('post_id', $postID)->paginate(10);
				
				$comments = [];
				foreach ($comments_data as $key => $value) {
					// Obtenemos si ya ha reaccionado al post
                    $reaction_chk = Reaction::where('user_id',$user_id)->where('post_comment_id',$value->id)->first();
                    (isset($reaction_chk) && isset($reaction_chk->id)) ? $reaction = true : $reaction = false;

					$comments[] = [
						'id' => $value->id,
						'message' => $value->message,
						'created_at' => $value->created_at->diffForHumans(),
						'reaction'   => $reaction,
						'reactions' => $value->reactions_count, 
						'author' => [
							'id' => $value->author->id,
							'name' => $value->author->name,
							'username' => $value->author->username,
							'email' => $value->author->email,
							'avatar' => $value->author->avatar,
						],
						
					];
				}

                return response()->json([
                    'success' => true,
                    'data' => $comments
                ]);
            } else {
                return response()->json(['success' => false, 'errors' => [__('Not authorized')], 'message' => __('Not authorized')], 403);
            } 
	
		} catch (\Exception $th) {
			return response()->json(['data' => 'error','error' => $th->getMessage()]);
		}
	}

	public function addNewComment(Request $request)
	{
		try {
			$comment = $request->get('message');
            $postID  = $request->get('post_id');
			$userID  = $request->get('user_id'); 

            // Checking authorization & post existence
            $post = Post::where('id', $postID)->first();
            if (!$post) {
                return response()->json(['success' => false, 'errors' => [__('Not found')], 'message' => __('Post not found')], 404);
            }

            if (PostsHelperServiceProvider::hasActiveSub($userID, $post->user_id) || $userID == $post->user_id || (!$post->user->paid_profile)) {
                $comment = PostComment::create([
                    'message' => $comment,
                    'post_id' => $postID,
                    'user_id' => $userID,
                ]);

                $post = Post::query()->where('id', $postID)->first();
                if ($comment != null && $post != null && $comment->user_id != $post->user_id) {
                    NotificationServiceProvider::createNewPostCommentNotification($comment);
                }

                return response()->json([
                    'success' => true
                ]);
            }
            else{
                return response()->json(['success' => false, 'errors' => [__('Not authorized')], 'message' => __('Not authorized')], 403);
            } 

		} catch (\Exception $th) {
			return response()->json(['data' => 'error','error' => $th->getMessage()]);
		}
	}

	public function addNewReaction(Request $request)
	{
		$type 	= $request->get('type');
        $action = $request->get('action');
        $id 	= $request->get('id');
		$userID = $request->get('userID');

        $data = [
            'reaction_type' => 'like',
            'user_id' => $userID,
        ];

        try {
            // Checking authorization & post existence
            $postComment = PostComment::where('id', $id)->first();
            $post = null;
            if ($postComment != null) {
                $post = $postComment->post;
            } else if ($type === 'post' && $id != null) {
                $post = Post::where('id', $id)->first();
            }

            if (!$post) {
                return response()->json(['success' => false, 'errors' => [__('Not found')], 'message' => __('Post not found')], 404);
            }

            if (PostsHelperServiceProvider::hasActiveSub($userID, $post->user_id) || $userID == $post->user_id || (!$post->user->paid_profile)) {
                if ($type == 'post') {
                    $data['post_id'] = $id;
                } elseif ($type == 'comment') {
                    $data['post_comment_id'] = $id;
                }
                $message = '';
                if ($action == 'add') {
                    $message = __('Reaction added.');
                    $reaction = Reaction::create($data);

                    if ($reaction != null) {
                        NotificationServiceProvider::createNewReactionNotification($reaction);
                    }
                } elseif ($action == 'remove') {
                    $message = __('Reaction removed.');
                    Reaction::where($data)->first()->delete();
                }

                return response()->json(['success' => true, 'message' => $message]);
            }

        } catch (\Exception $exception) {
            return response()->json(['success' => false, 'errors' => [__('An internal error has occurred.')], 'message' => $exception->getMessage()]);
        }
	}
}
