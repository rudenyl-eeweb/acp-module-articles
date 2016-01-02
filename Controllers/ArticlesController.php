<?php
namespace Modules\Articles\Controllers;

use Modules\Articles\Entities\Article;
use Dingo\Api\Http\Request;
use Illuminate\Routing\Controller as BaseController;

/**
 * Articles resource representation.
 *
 * @Resource("Articles", uri="/articles")
 */
class ArticlesController extends BaseController
{
    /**
     * Show all articles
     *
     * @Get("/")
     * @Versions({"v1"})
     */
    public function index()
    {
        $articles = Article::all();

        return response()->json($articles);
    }

    /**
     * Add an article entry
     *
     * @Post("/")
     * @Versions({"v1"})
     * @Transactions({
     *      @Request("title=Foo Bar&slug=foo-bar&summary=Foo Bar description", contentType="application/x-www-form-urlencoded")
     *      @Response(200, body={"id": <id>, "created": true})
     *      @Response(422, body={"error": {"title": "Title already exists!"}})
     * })
     */
    public function store(Request $request)
    {
        $request->only('title', 'slug', 'summary');

        $article = Article::create($request->all());

        return response()->json([
            'id' => $article->id,
            'title' => $article->title,
            'created' => true
        ]);
    }     

    /**
     * Update article
     *
     * @Put("/{id}")
     * @Versions({"v1"})
     * @Transactions({
     *      @Request("title=...&slug=...&summary=...", contentType="application/x-www-form-urlencoded")
     *      @Response(200, body={"id": <id>, "updated": true})
     *      @Response(422, body={"error": "Error updating article."})
     * })
     */
    public function update(Request $request, $id)
    {
        $request->only('title', 'slug', 'summary');

        $article = Article::findorFail($id);
        $article->update($request->all());

        return response()->json([
            'id' => $article->id,
            'updated' => true
        ]);
    }     

    /**
     * Delete an article
     *
     * @Delete("/{id}")
     * @Versions({"v1"})
     * @Transactions({
     *      @Response(200, body={"id": <id>, "updated": true})
     *      @Response(422, body={"error": "Error deleting article."})
     * })
     */
    public function destroy($id)
    {
        $article = Article::findorFail($id);
        $article->delete();

        return response()->json([
            'deleted' => true
        ]);
    }     
}