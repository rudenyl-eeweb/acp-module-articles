<?php
namespace Modules\Articles\Repositories;

use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Request;
use Modules\Articles\Entities\Article;
use Modules\Articles\Entities\ArticleDomainData;
use Modules\Articles\Repositories\ArticleDomainDataRepository;
use Modules\Articles\Exceptions\FormValidationException;
use API\Core\Contracts\EntityRepositoryInterface;

class ArticleRepository implements EntityRepositoryInterface
{
    /**
     * Get repository model.
     */
    public function getModel()
    {
        $all_domains = (int)Request::get('all_domains', false);
        if ($all_domains) {
            $repository = Article::allDomains()->with('domain')
                ->with('data')
                ->not_from_this_domain();
        }
        else {
            $repository = Article::with('domain')
                ->with('data');
        }

        $with_trashed = (int)Request::get('with_trashed', false);
        $with_trashed and $repository->withTrashed();

        return $repository;
    }

    /**
     * Store article.
     */
    public function create(array $data = null)
    {
        if (is_null($data)) {
            $data = Request::except('id');
        }

        // slug is slug
        $data['slug'] = Str::slug( Arr::get($data, 'slug') ?: $data['title'] );

        // validate
        $this->validate($data);

        // and, create
        $article = Article::create($data);

        // attach data
        (new ArticleDomainDataRepository)->create($article->toArray());

        return $article;
    }

    /**
     * Update item.
     */
    public function update($id, &$context)
    {
        $article = $this->findById($id);
        if (Request::has('restore') && (int)Request::get('restore')) {
            $article->restore();

            $context = 'restored';
        }
        else {
            // validate
            $input = Request::except('access', 'publish_up', 'publish_down', 'published');
            $data = $this->validate($input, $id);

            // slug is slug
            $data['slug'] = Str::slug( Arr::get($data, 'slug') ?: $data['title'] );

            // update
            $article->update($data);

            // update data
            $request_data = Request::only('access', 'publish_up', 'publish_down', 'published');
            $data = ArticleDomainDataRepository::validate($request_data);
            if (is_null($article->data)) {
                $data = array_merge($article->toArray(), $data);
                (new ArticleDomainDataRepository)->create($data);
            }
            else {
                $article->data()->update($data);
                $article->data()->touch();
            }
        }

        return $article;
    }

    /**
     * Remove article entry.
     */
    public function delete($id)
    {
        $article = $this->findById($id);

        if (Request::has('force') && (int)Request::get('force')) {
            $article->forceDelete();

            return 'deleted';
        }
        else {
            $article->delete();

            return 'marked_deleted';
        }
    }

    /**
     * Return collection limit.
     */
    public function perPage()
    {
        return Request::get('limit', config('articles.pagination.limit', 10));
    }

    /**
     * Return search results.
     */
    public function allWithTerm($context = null)
    {
    }
    public function search($context)
    {
    }

    /**
     * Get all entries.
     */
    public function getAll()
    {
        #
        # get options
        #
        $offset = Request::get('offset', 0);
        $sort = Request::get('sort', 'created_at');
        $sort_dir = Request::get('order', 'asc');
        $all_domains = (int)Request::get('all_domains', false);

        $repository = $this->getModel()
            ->select('articles.*')
            ->leftJoin('article_domain_data', 'article_domain_data.article_id', '=', 'articles.id');

        // publishing
        $published = Request::get('published', null);
        if (!is_null($published)) {
            $repository->published($published);
        }

        // deleted only
        if ((int)Request::get('hidden', false)) {
            $repository->hidden();
        }

        $total = $repository->count();
        $articles = $repository
            ->orderBy($sort, $sort_dir)
            ->take($this->perPage())
            ->skip($offset);

        $list = new Collection($articles->get(), function(Article $item) use ($all_domains) {
            $data = [
                'id' => (int)$item->id,
                'title' => $item->title,
                'slug' => $item->slug,
                'created_at' => (string)$item->created_at,
                'modified_at' => (string)$item->modified_at,
                'deleted_at' => (string)$item->deleted_at,
                'access' => is_null($item->data['access']) ? $item->access : $item->data['access'],
                'published' => is_null($item->data['published']) ? $item->published : $item->data['published']
            ];

            $all_domains and $data['domain'] = $item->domain['name'];

            return $data;
        });

        $manager = new Manager();
        $rows = $manager->createData($list)->toArray();

        // get listing count
        $count = count($list->getData());

        return compact('total', 'count') + $rows;
    }

    /**
     * Search by ID
     */
    public function findById($id)
    {
        $article = $this->getModel()->find($id);
        if (is_null($article)) {
            throw new \Exception('article not found', 404);
        }

        return $article;
    }

    /**
     * Search by specified column/field.
     */
    public function findBy($key, $value, $operator = '=')
    {
        $article = $this->getModel()
            ->leftJoin('article_domain_data', 'article_domain_data.article_id', '=', 'articles.id')
            ->where($key, $operator, $value)
            ->where(function($query) {
                $query->whereNull('article_domain_data.publish_up')
                    ->orWhere('article_domain_data.publish_up', '<=', 'now()');
            })
            ->where(function($query) {
                $query->whereNull('article_domain_data.publish_down')
                    ->orWhere('article_domain_data.publish_down', '>=', 'now()');
            })
            ->first();
            
        if (is_null($article)) {
            throw new \Exception('article not found', 404);
        }

        return $article;
    }

    /**
     * Get single article by id/slug
     */
    public function one($id)
    {
        if (is_numeric($id)) {
            $article = $this->findById($id);
        }
        else {
            $article = $this->findBy('articles.slug', $id);
        }

        $this->mutate($article);
        unset($article->data);
        unset($article->domain);

        return $article;
    }

    /**
     * Return paginate listing.
     */ 
    public function paginate($data) {}

    /**
     * Validate request data
     */
    protected function validate($data = null, $id = null)
    {
        // create slug
        if (Arr::has($data, 'slug')) {
            $data = array_merge($data, [
                'slug' => Str::slug($data['slug'])
            ]);
        }

        $validator = \Validator::make($data, [
            'title' => 'required|min:5',
            'slug' => 'unique:articles,slug' . ($id ? ','.$id : ''),
            'description' => 'required'
        ]);

        if ($validator->fails()) {
            throw new FormValidationException($validator);
        }

        return $data;
    }

    /**
     * Mutate data.
     */
    protected function mutate(&$item)
    {
        if (is_null($item)) {
            return false;
        }

        if (is_null($item->data)) {
            return false;
        }

        $item->access = $item->data->access;
        $item->publish_up = $item->data->publish_up;
        $item->publish_down = $item->data->publish_down;
        $item->published = $item->data->published;
    }
}
