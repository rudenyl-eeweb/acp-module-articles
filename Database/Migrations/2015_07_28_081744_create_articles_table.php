<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('articles', function($table) {
            $table->increments('id');
            $table->string('title', 80)->unique();
            $table->string('slug', 80);
            $table->text('summary');
            $table->text('description');
            $table->integer('hits');
            $table->timestamp('last_access');
            $table->timestamps();
            $table->softDeletes();
            $table->boolean('is_featured');
            $table->integer('ordering')->unsigned();
            $table->boolean('access');
            $table->boolean('published');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('articles');
    }
}
