<?php

namespace Modules\Articles\Controllers;

use Tymon\JWTAuth\JWTAuth;
use Modules\Articles\Repositories\ArticleRepository;
use Illuminate\Routing\Controller as BaseController;

/**
 * Articles resource representation.
 *
 * @Resource("Articles", uri="/articles")
 */
class ArticlesController extends BaseController
{
    /**
     * @var \Tymon\JWTAuth\JWTAuth
     */
    protected $auth;

    /**
     * @var \Modules\Articles\Repositories\ArticleRepository
     */
    protected $repository;

    public function __construct(JWTAuth $auth, ArticleRepository $repository)
    {
        $this->repository = $repository;
        $this->auth = $auth;
    }

    /**
     * Show all articles
     *
     * @Get("/")
     * @Versions({"v1"})
     */
    public function index()
    {
        return $this->repository->getAll();
    }

    /**
     * Get article info
     *
     * @Get("/{id}")
     * @Versions({"v1"})
     */
    public function show($id)
    {
        return $this->repository->one($id);
    }

    /**
     * Add an article entry
     *
     * @Post("/")
     * @Versions({"v1"})
     * @Transactions({
     *      @Request("title=Foo Bar&slug=foo-bar&summary=Foo Bar description", contentType="application/x-www-form-urlencoded")
     *      @Response(200, body={"id": 1, "created": true})
     *      @Response(422, body={"error": {"title": "Title already exists!"}})
     * })
     */
    public function store()
    {
        $article = $this->repository->create();

        return [
            'id' => $article->id,
            'title' => $article->title,
            'created' => true
        ];
    }

    /**
     * Update article
     *
     * @Put("/{id}")
     * @Versions({"v1"})
     * @Transactions({
     *      @Request("title=...&slug=...&summary=...", contentType="application/x-www-form-urlencoded")
     *      @Response(200, body={"id": 1, "updated": true})
     *      @Response(422, body={"error": "Error updating article."})
     * })
     */
    public function update($id)
    {
        $context = 'updated';
        $article = $this->repository->update($id, $context);

        return [
            'id' => $article->id,
            $context => true
        ];
    }

    /**
     * Delete an article
     *
     * @Delete("/{id}")
     * @Versions({"v1"})
     * @Transactions({
     *      @Response(200, body={"id": 1, "updated": true})
     *      @Response(422, body={"error": "Error deleting article."})
     * })
     */
    public function destroy($id)
    {
        $context = $this->repository->delete($id);

        return [
            $context => true
        ];
    }
}
