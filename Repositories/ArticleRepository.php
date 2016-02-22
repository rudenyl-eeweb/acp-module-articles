<?php
namespace Modules\Articles\Repositories;

use CoreRequest;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Database\Query\Expression;
use API\Core\Traits\HashedidsTrait;
use Illuminate\Support\Facades\Request;
use Modules\Articles\Entities\Article;
use Modules\Articles\Entities\ArticleDomainData;
use Modules\Articles\Repositories\ArticleDomainDataRepository;
use Modules\Articles\Exceptions\FormValidationException;
use API\Core\Contracts\EntityRepositoryInterface;

class ArticleRepository implements EntityRepositoryInterface
{
    use HashedidsTrait;

    /**
     * @var Array
     */
    protected $publishing_columns = ['publish_up', 'publish_down', 'access', 'published'];

    /**
     * Get repository model.
     *
     * @return \Modules\Articles\Entities\Article
     */
    public function getModel()
    {
        $all_domains = (int)Request::header('allDomains', false);
        if ($all_domains) {
            $repository = Article::allDomains()->with('domain')
                ->with('data')
                ->not_from_this_domain();
        }
        else {
            $repository = Article::with('domain')
                ->with('data');
        }

        $with_trashed = (int)Request::header('withTrashed', false);
        $with_trashed and $repository->withTrashed();

        $trash_only = (int)Request::header('onlyTrashed', false);
        $trash_only and $repository->onlyTrashed();

        return $repository;
    }

    /**
     * Store article.
     *
     * @return \Modules\Articles\Entities\Article
     */
    public function create(array $data = null)
    {
        $publishing_data = Request::only($this->publishing_columns);

        //
        // copy resource
        //
        if (Request::has('source')) {
            $id = Request::get('source');

            // get article
            $article = $this->findById($id);

            // set source
            $article->source_id = $this->decode_id($id, 0);

            $data = $article->toArray();
        }

        if (is_null($data)) {
            $data = Request::except('id');
        }

        // slug is slug
        $data['slug'] = Str::slug( Arr::get($data, 'slug') ?: $data['title'] );

        // validate
        $this->validate($data);

        // store
        $article = Article::create($data);

        // create domain data
        $domain_data = array_merge($article->toArray(), $publishing_data);
        (new ArticleDomainDataRepository)->create($domain_data);

        return $article;
    }

    /**
     * Update item.
     *
     * @return \Modules\Articles\Entities\Article
     */
    public function update($id, &$context)
    {
        $article = $this->findById($id);

        if (Request::method() == 'PATCH') {
            // restore
            if (Request::has('restore') && (int)Request::get('restore')) {
                $article->restore();

                $context = 'restored';

                return $article;
            }
            else {
                $data = array_merge($article->toArray(), Request::except($this->publishing_columns));

                // exclude missing keys
                $domain_data = CoreRequest::only($this->publishing_columns);

                // process domain data
                $article_data = is_null($article->data) ? [] : $article->data->toArray();
                $domain_data = array_merge( Arr::only($article_data, $this->publishing_columns), $domain_data);
            }
        }
        else {
            $input = Request::except($this->publishing_columns);
            $domain_data = Request::only($this->publishing_columns);

            // validate
            $data = $this->validate($input, $article->id);

            // slug is slug
            $data['slug'] = Str::slug( Arr::get($data, 'slug') ?: $data['title'] );
        }

        // update
        $article->update($data);

        //
        // update data
        //
        // create
        if (is_null($article->data)) {
            (new ArticleDomainDataRepository)->create($article->toArray() + $domain_data);
        }
        // update
        else {
            $article->data()->update(
                ArticleDomainDataRepository::validate($domain_data) + [
                    'modified_by' => Request::header('UID', 0)
                ]
            );
            $article->data()->touch();
        }

        return $article;
    }

    /**
     * Remove article entry.
     *
     * @return string
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
     *
     * @return integer
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
     *
     * @return Array
     */
    public function getAll()
    {
        #
        # get options
        #
        $offset = Request::get('offset', 0);
        $sort = Request::get('sort', 'created_at');
        $sort_dir = Request::get('order', 'desc');
        $all_domains = (int)Request::header('allDomains', false);

        // build
        $repository = $this->articleQuery($sql_publishing);

        // publishing
        $published = Request::get('published', null);
        if (!is_null($published)) {
            $repository->where(new Expression($sql_publishing), $published);
        }

        // has search context
        $search = Request::get('search', false);
        if ($search) {
            $repository->where('title', 'LIKE', "%{$search}%");
        }

        // if allDomains, tag cloned articles
        if ($all_domains) {
            $domain_id = $this->getDomainId();

            $sql = "articles.*, 
                (
                    SELECT count(*) 
                    FROM articles a 
                    WHERE a.source_id = articles.id AND a.domain_id = {$domain_id}
                ) as cloned
                ";
            $repository->select(new Expression($sql));
        }

        $total = $repository->count();
        $articles = $repository
            ->orderBy($sort, $sort_dir)
            ->take($this->perPage())
            ->skip($offset);

        //
        // serialize
        //
        $list = new Collection($articles->get(), function(Article $item) use ($all_domains) {
            $data = [
                'id' => $this->encode_id($item->id),
                'title' => $item->title,
                'slug' => $item->slug,
                'created_at' => (string)$item->created_at,
                'modified_at' => (string)$item->modified_at,
                'deleted_at' => (string)$item->deleted_at,
                'publish_up' => $item->data['publish_up'],
                'publish_down' => $item->data['publish_down'],
                'access' => (int)$item->data['access'],
                'created_by' => (int)$item->data['created_by'],
                'modified_by' => (int)$item->data['modified_by'],
                'published' => (int)$item->status
            ];

            $all_domains and $data = array_merge($data, [
                'cloned' => (int)$item->cloned,
                'domain' => $item->domain['name']
            ]);

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
     *
     * @return \Modules\Articles\Entities\Article
     */
    public function findById($id)
    {
        $id = $this->decode_id($id, 0);

        $article = $this->getModel()->find($id);
        if (is_null($article)) {
            throw new \Exception('article not found', 404);
        }

        return $article;
    }

    /**
     * Search by specified column/field.
     *
     * @return \Modules\Articles\Entities\Article
     */
    public function findBy($key, $value, $operator = '=')
    {
        if ($key == 'id') {
            $value = $this->decode_id($value, 0);
        }
        
        $article = $this->getModel()
            ->leftJoin('article_domain_data', 'article_domain_data.article_id', '=', 'articles.id')
            ->where($key, $operator, $value)
            ->where(function($query) {
                $query->whereNull('article_domain_data.publish_up')
                    ->orWhereRaw('article_domain_data.publish_up <= now()');
            })
            ->where(function($query) {
                $query->whereNull('article_domain_data.publish_down')
                    ->orWhereRaw('article_domain_data.publish_down >= now()');
            })
            ->first();
            
        if (is_null($article)) {
            throw new \Exception('article not found', 404);
        }

        return $article;
    }

    /**
     * Get single article by id/slug
     *
     * @return mixed
     */
    public function one($id, $extended = false)
    {
        $is_hashed =  $this->is_hashed($id);

        if (is_numeric($id) || $is_hashed) {
            $_id = $this->decode_id($id, 0);

            $article = $this->articleQuery()
                ->where('articles.id', $_id)
                ->first();
        }
        else {
            $article = $this->findBy('articles.slug', $id);
        }

        if ($extended) {
            $article = $this->mutate($article);

            $exclude = ['source_id', 'domain', 'data'];
            foreach ($exclude as $k) {
                if (isset($article[$k])) {
                    unset($article[$k]);
                }
            }

            // set hashed id back if enabled
            $is_hashed and $article['id'] = $id;

            // set correct published status
            if (isset($article['status'])) {
                $article['published'] = $article['status'];
                unset($article['status']);
            }

            return compact('article');
        }

        return $article;
    }

    /**
     * Return paginated listing.
     */ 
    public function paginate($data) {}

    /**
     * Validate request data
     *
     * @return Array
     */
    protected function validate($data = null, $id = null)
    {
        // create slug
        if (Arr::has($data, 'slug')) {
            $data = array_merge($data, [
                'slug' => Str::slug($data['slug'])
            ]);
        }

        #
        # set unique columns
        #
        $unique_column = sprintf(",%s,id,domain_id,%s", $id ?: 'NULL', $this->getDomainId());

        $validator = \Validator::make($data, [
            'title' => 'required|min:5|unique:articles,title' . $unique_column,
            'slug' => 'unique:articles,slug' . $unique_column,
            'description' => 'required'
        ]);

        if ($validator->fails()) {
            throw new FormValidationException($validator);
        }

        return $data;
    }

    /**
     * Mutate data.
     *
     * @return Array
     */
    protected function mutate($article)
    {
        if (is_null($article)) {
            return false;
        }

        $result = $article->toArray();

        if (isset($article->data) && !is_null($article->data)) {
            $data = Arr::except($article->data->toArray(), ['id', 'article_id']);
            foreach ($data as $k => $v) {
                $result[$k] = $v;
            }
        }

        return $result;
    }

    /**
     * Query builder
     *
     * Returns formatted result
     */
    private function articleQuery(&$sql_publishing = '')
    {
        // build
        $sql_publishing = "
            CASE WHEN
                (
                    article_domain_data.published = '1' AND
                    (article_domain_data.publish_up IS NULL OR article_domain_data.publish_up <= now()) AND
                    (article_domain_data.publish_down IS NULL OR article_domain_data.publish_down >= now())
                ) THEN 1
                WHEN (article_domain_data.published = '1' AND article_domain_data.publish_up IS NOT NULL AND article_domain_data.publish_up >= now()) THEN 2
                WHEN (article_domain_data.published = '1' AND article_domain_data.publish_down IS NOT NULL AND article_domain_data.publish_down <= now()) THEN 3
                ELSE 0
            END     
        ";
        $query = $this->getModel()
            #
            # article data first
            #
            ->select(new Expression("articles.*, ({$sql_publishing}) as status"))

            #
            # with(['data' => Closure]) won't fetch properties on empty data
            #
            ->leftJoin('article_domain_data', 'article_domain_data.article_id', '=', 'articles.id');

        return $query;
    }

    /**
     * Get domain id
     */
    private function getDomainId()
    {
        static $domain_id;

        if (is_null($domain_id)) {
            $domain_id = (new Article)->getDomainId();
        }

        return $domain_id;
    }
}
