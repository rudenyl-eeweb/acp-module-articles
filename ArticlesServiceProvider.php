<?php
namespace Modules\Articles;

use Illuminate\Support\ServiceProvider;

class ArticlesServiceProvider extends ServiceProvider
{
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Boot the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
        //
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		#
		# Load up config
		#
		$configPath = __DIR__.'/Config/config.php';
        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, 'articles');
        }
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [];
	}

}
