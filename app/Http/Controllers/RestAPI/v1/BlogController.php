<?php

namespace App\Http\Controllers\RestAPI\v1;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class BlogController extends Controller
{
    public function blog(Request $request)
    {

        $languageId = $request->query('languageId');

        $query = Blog::query()
            ->leftJoin('comments', 'posts.id', '=', 'comments.post_id')
            ->select('posts.*', DB::raw('COUNT(comments.id) as comment_count'))
            ->where('posts.status', 1)
            ->groupBy('posts.id');

        if ($languageId) {

            $query->where('lang_id', $languageId);
        }

        $blog_posts = $query->get();

        return response()->json([
            'status' => 200,
            'data' => $blog_posts,
        ]);
    }


    public function getBlogBySlug($title_slug)
    {

        $blog_post = Blog::leftJoin('comments', 'posts.id', '=', 'comments.post_id')
            ->select('posts.*', DB::raw('COUNT(comments.id) as comment_count'))
            ->where('posts.title_slug', $title_slug)
            ->where('posts.status', 1)
            ->groupBy('posts.id')
            ->first();

        if ($blog_post) {
            return response()->json([
                'status' => 200,
                'data' => $blog_post,
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'Blog post not found',
            ]);
        }
    }

    public function getBlogByCategory(Request $request)
    {
        $languageId = $request->query('languageId');
        $categoryId = $request->query('categoryId');

        $query = Blog::query()
            ->leftJoin('comments', 'posts.id', '=', 'comments.post_id')
            ->select('posts.*', DB::raw('COUNT(comments.id) as comment_count'))
            ->groupBy('posts.id');

        if ($languageId) {
            $query->where('lang_id', $languageId);
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $blog_posts = $query->get();

        if ($blog_posts->isEmpty()) {
            return response()->json([
                'status' => 404,
                'message' => 'No blog posts found',
            ]);
        }

        $baseUrl = url('blog/');

        $blog_posts->each(function ($post) use ($baseUrl) {
            $post->image_big = $baseUrl . '/' . $post->image_big;
            $post->image_small = $baseUrl . '/' . $post->image_small;
            $post->image_mid = $baseUrl . '/' . $post->image_mid;
            $post->image_slider = $baseUrl . '/' . $post->image_slider;
        });

        return response()->json([
            'status' => 200,
            'data' => $blog_posts,
        ]);
    }

    public function getBlogCategory(Request $request)
    {

        $languageId = $request->query('languageId');

        if (!$languageId) {
            return response()->json([
                'status' => 400,
                'message' => 'languageId is required',
            ]);
        }

        $categories = DB::connection('mysql2')->table('categories')
            ->select('id', 'lang_id', 'name', 'category_order')
            ->where('lang_id', $languageId)
            ->get();

        return response()->json([
            'status' => 200,
            'categories' => $categories,
        ]);
    }
}
