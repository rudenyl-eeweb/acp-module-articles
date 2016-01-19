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
            $table->integer('domain_id')->unsigned()->nullable();
            $table->string('title', 80)->unique();
            $table->string('slug', 80);
            $table->text('description');
            $table->timestamps();
            $table->softDeletes();
            $table->boolean('access');
            $table->boolean('published');

            $table->foreign('domain_id')
                ->references('id')
                ->on('domains');
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
