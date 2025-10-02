<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('quantity', 10, 2)->default(1.00)->after('date');
        });
    }
};
