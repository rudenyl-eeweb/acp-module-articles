<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateArticleDomainDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('article_domain_data', function($table) {
            $table->increments('id');
            $table->integer('article_id')->unsigned();
            $table->integer('domain_id')->unsigned();
            $table->integer('created_by')->unsigned();
            $table->integer('modified_by')->unsigned();
            $table->timestamp('accessed_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('publish_up')->nullable();
            $table->timestamp('publish_down')->nullable();
            $table->boolean('access')->nullable();
            $table->boolean('published')->nullable();

            $table->foreign('article_id')
                ->references('id')
                ->on('articles')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('article_domain_data');
    }
}
