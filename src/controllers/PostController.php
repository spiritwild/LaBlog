<?php

namespace Lablog\Lablog\Controllers;

use Lablog\Lablog\Post\PostGatewayInterface;
use Lablog\Lablog\Post\PostConfigGatewayInterface;
use Lablog\Lablog\Post\Post;
use Lablog\Lablog\Processor\ProcessorInterface;
use Stringy\StringyStatic as Stringy;

class PostController extends \BaseController
{
    public function __construct(
        PostGatewayInterface $post,
        PostConfigGatewayInterface $postConfig,
        ProcessorInterface $processor)
    {
        $this->post = $post;
        $this->postConfig = $postConfig;
        $this->processor = $processor;
    }

    /**
     * Show all of the posts.
     * @return \View
     */
    public function showPosts()
    {
        echo 'All posts.';
    }

    /**
     * Show a single post.
     * @param string $postName The name of the post to retrieve.
     * @return \View
     */
    public function showPost($category, $postName)
    {
        $ds = DIRECTORY_SEPARATOR;
        $postPath = str_replace('/', $ds, $category.'/'.$postName);

        $fullPostPath = app_path().$ds.'lablog'.$ds.$postPath.'.post';

        if ($this->post->exists($fullPostPath)) {
            $postContent = $this->post->get($fullPostPath);

            $configWrap = \Config::get('lablog::post.configWrap') ?: '{POSTCONFIG}';

            $postContent = $this->postConfig->strip($postContent, $configWrap);

            $config = $this->postConfig->decode($postContent['config']);

            if (isset($config->title)) {
                $name = $config->title;
            } else {
                $name = $postName;
            }

            if (isset($config->content)) {
                $content = $config->content;
            } else {
                $content = $postContent['content'];
            }

            if (isset($config->modified)) {
                $modified = $config->modified;
            } else {
                $modified = $this->post->modified($fullPostPath);
            }

            $post = new Post;
            $post->name = $name;
            $post->modified = $modified;
            $post->content = $this->processor->process($content);
            $post->path = $fullPostPath;

            $template = \Config::get('lablog::theme');
            $extra = \Config::get('lablog::extra.post');

            return \View::make('lablog::themes.'.$template.'.post', array(
                'post' => $post,
                'config' => $config,
                'extra' => $extra
            ));

        } else {
            $extra = \Config::get('lablog::extra.post');
            $message = isset($extra['notFound']) ? $extra['notFound'] : 'Post not found.';
            return \App::abort(404, $message);
        }
    }
}