<?php namespace CryptoPolice\Platform\Components;

use DB, Auth, Flash, Session, Validator;
use Cms\Classes\ComponentBase;
use CryptoPolice\Platform\Classes\Helpers;
use CryptoPolice\Platform\Models\CommunityPost;
use CryptoPolice\Platform\Models\CommunityComment;
use CryptoPolice\Academy\Components\Recaptcha as Recaptcha;


class PostComments extends ComponentBase
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
        $comments = Db::table('cryptopolice_platform_community_comment as comment')
            ->join('users', 'comment.user_id', 'users.id')
            ->leftJoin('system_files', function ($join) {
                $join->on('comment.user_id', '=', 'system_files.attachment_id')
                    ->where('system_files.attachment_type', 'RainLab\User\Models\User');
            })
            ->select('system_files.disk_name as user_image', 'comment.*', 'users.nickname', 'users.email')
            ->where('comment.post_id', $this->param('id'))
            ->orderBy('comment.created_at', 'desc')
            ->get();

        $helper = new Helpers();

        foreach ($comments as $key => $value) {
            if ($value->user_image) {
                $comments[$key]->user_image = $helper->setImagePath($value->user_image);
            }
        }
        $this->page['comments'] = $this->makeArrayTree($comments);
    }


    private function build_sorter()
    {
        return function ($firstCommentReply, $secondSecondReply)
        {
            return strnatcmp($firstCommentReply->created_at, $secondSecondReply->created_at);
        };
    }

    private function makeArrayTree($comments)
    {

        $childs = [];

        foreach ($comments as $comment) {
            $childs[$comment->parent_id][] = $comment;
        }

        foreach ($comments as $comment) {
            if (isset($childs[$comment->id])) {
                usort($childs[$comment->id], $this->build_sorter());
                $comment->childs = $childs[$comment->id];
            }
        }

        count($childs) > 0 ? $tree = $childs[0] : $tree = [];
        return $tree;
    }


    public function onAddComment()
    {

        $helper = new Helpers();

        Recaptcha::verifyCaptcha();

        if (input('_token') == Session::token()) {

            if ($helper->checkLinks(input('description'))) {
                Flash::error('Links are not allowed');
            } else {

                $user = Auth::getUser();

                $comment = new CommunityComment;
                $comment->user_id = $user->id;
                $comment->post_id = $this->param('id');
                $comment->description = input('description');
                if (!empty(input('parent_id'))) {
                    $comment->parent_id = input('parent_id');
                }
                $comment->save();

                $this->increasePostsCommentsCount($this->param('id'));

                Flash::success('Your comment has been successfully added');
                return redirect()->back();
            }
        }
    }

    public function increasePostsCommentsCount($id)
    {
        return CommunityPost::find($id)->increment('comment_count');
    }

    public function decreasePostsCommentsCount($id)
    {
        return CommunityPost::find($id)->decrement('comment_count');
    }

    public function onDeleteComment()
    {

        $user = Auth::getUser();

        if ($user && !empty(post('id'))) {
            DB::table('cryptopolice_platform_community_comment')->where('user_id', $user->id)->where('id', post('id'))->delete();
            Flash::warning('Your comment has been successfully deleted');
            $this->decreasePostsCommentsCount($this->param('id'));
            return redirect()->back();
        }
    }
}