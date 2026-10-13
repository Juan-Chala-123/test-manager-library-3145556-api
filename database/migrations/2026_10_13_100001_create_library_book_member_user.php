<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// T-02 (2/3): tablas book, member y "user" (data-model.md §3).
// Solo restricciones marcadas "motor" en §4: ck_book_total_positive y
// ck_user_role quedan pendientes para T-18 (hoy solo dominio).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library.book', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('title', 250);
            $table->string('author', 200);
            $table->char('isbn', 13);
            $table->uuid('category_id');
            $table->integer('total_copies');
            $table->integer('available_copies');
            $table->string('cover_key', 512)->nullable();
            $table->timestampTz('deleted_at')->nullable();

            $table->foreign('category_id', 'fk_book_category')
                ->references('id')->on('library.category')
                ->onDelete('restrict')->onUpdate('no action');
        });

        // Última barrera de concurrencia (ADR-002): el motor la impone.
        DB::statement(
            'ALTER TABLE library.book ADD CONSTRAINT ck_book_available_range '
            . 'CHECK (0 <= available_copies AND available_copies <= total_copies)'
        );

        // ISBN único solo entre activos: un ISBN dado de baja puede repetirse.
        DB::statement(
            'CREATE UNIQUE INDEX uq_book_isbn_active ON library.book (isbn) '
            . 'WHERE deleted_at IS NULL'
        );

        // Búsqueda del catálogo (Q1): solo activos, orden por título.
        DB::statement(
            'CREATE INDEX ix_book_category_title ON library.book (category_id, title) '
            . 'WHERE deleted_at IS NULL'
        );

        Schema::create('library.member', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('full_name', 200);
            $table->string('document_number', 30)->unique('uq_member_document');
        });

        // "user" es palabra reservada: Eloquent/SQL la entrecomillan sola.
        Schema::create('library.user', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('username', 120)->unique('uq_user_username');
            $table->string('password_hash', 512);
            $table->string('role', 40);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library.user');
        Schema::dropIfExists('library.member');
        Schema::dropIfExists('library.book');
    }
};
