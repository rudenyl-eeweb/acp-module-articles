<?php
namespace Modules\Articles\Controllers;

use Modules\Articles\Entities\Article;
use Illuminate\Routing\Controller as BaseController;

class ArticlesController extends BaseController
{
    public function index()
    {
        $articles = Article::all();

        return response()->json($articles);
    }
}
