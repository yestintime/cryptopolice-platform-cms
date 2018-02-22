<?php namespace CryptoPolice\Platform\Components;

use DB;
use Auth;
use Flash;
use Session;
use Request;
use Validator;
use Illuminate\Support\Carbon;
use Cms\Classes\ComponentBase;
use CryptoPolice\Academy\Models\Settings;
use CryptoPolice\Platform\Classes\Helpers;
use October\Rain\Support\Facades\Markdown;
use CryptoPolice\Academy\Components\Recaptcha;
use CryptoPolice\Platform\Models\CommunityPost;

class Posts extends ComponentBase
{

    public function componentDetails()
    {
        return [
            'name' => 'Posts list',
            'description' => 'Community Posts List'
        ];
    }

    public function onRun()
    {
        $this->onGetPosts();
    }

    public function onGetPosts()
    {

        $this->page['limit'] = true;
        $this->page['page_num'] = post('page') ? post('page') + 1 : 1;
        $this->page['search_data'] = post('search');

        // skip 100 records per page, for search 100
        $perPage = !empty(post('search')) ? 100 : 100;

        $skip = post('page') ? post('page') * $perPage : 0;

        $posts = CommunityPost::with('post_image', 'user.avatar')
            ->leftJoin('cryptopolice_platform_community_post_views as views', function ($join) {
                $join->on('cryptopolice_platform_community_posts.id', '=', 'views.post_id');
            })
            ->select(DB::raw('count(views.id) as views_count'), 'cryptopolice_platform_community_posts.*')
            ->where('status', 1)
            ->whereNull('cryptopolice_platform_community_posts.deleted_at')
            ->Where(function ($query) {
                if (!empty(post('search'))) {
                    $query->where('post_title', 'like', '%' . post('search') . '%');
                    $query->orWhere('post_description', 'like', '%' . post('search') . '%');
                }
            })
            ->orderBy('pin', 'desc')
            ->orderBy('created_at', 'desc')
            ->groupBy('cryptopolice_platform_community_posts.id')
            ->skip($skip)->take($perPage)
            ->get();

        if ($posts->isNotEmpty()) {

            $helper = new Helpers();

            foreach ($posts as $key => $value) {

                // set status
                $posts[$key]->status = $this->setStatus($value->created_at, $value->views_count, $value->comment_count);

                $helper = new Helpers();
                // set shares links
                $posts[$key]->twitter   = $helper->setTwitterShare($value->post_description);
                $posts[$key]->reddit    = $helper->setRedditShare($value->post_title);
                $posts[$key]->facebook  = $helper->setFacebookShare();

            }
            $this->page['posts'] = $posts;

        } else {

            // if empty query collection, disable load more form
            $this->page['limit'] = false;
        }
    }

    public function setStatus($createdAt, $views, $comments)
    {

        $hours = Carbon::now()->diffInHours(Carbon::parse($createdAt));
        if ($hours >= Settings::get('hot_post_min_hours') && $hours <= Settings::get('hot_post_max_hours') && $views >= Settings::get('hot_post_views') && $comments >= Settings::get('hot_post_min_comments'))
            return 3;
        if ($hours >= Settings::get('med_post_min_hours') && $hours <= Settings::get('med_post_max_hours') && $views >= Settings::get('med_post_views') && $comments >= Settings::get('med_post_min_comments'))
            return 2;
        if ($hours >= Settings::get('new_post_min_hours') && $hours <= Settings::get('new_post_max_hours') && $views >= Settings::get('new_post_views') && $comments >= Settings::get('new_post_min_comments'))
            return 1;
        return 0;
    }


    public function onAddPost()
    {

        Recaptcha::verifyCaptcha();

        if (input('_token') == Session::token()) {

            $user = Auth::getUser();

            $previousPost = CommunityPost::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->first();

            $minutes = $this->compareDates($previousPost->created_at);
            if ($minutes < 10) {
                Flash::error('You will be able to post after ' . (10 - $minutes) . ' min(s)');
                return false;
            }

            $html = Markdown::parse(strip_tags(input('description')));

            $helper = new Helpers();
            if($helper->checkLinks($html)) {
                Flash::error('Links are not allowed');
            } else {
                $post = new CommunityPost;
                $post->post_title = input('title');
                $post->post_description = $html;
                $post->user_id = $user->id;
                $post->save();

                Flash::success('Post has been successfully added');
                return redirect()->back();
            }
        }
    }

    public function compareDates($date)
    {
        return Carbon::now()->diffInMinutes(Carbon::parse($date));
    }

}