<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// T-02 (3/3): tablas loan y loan_item (data-model.md §3).
// loan_item.category_name va sin FK a propósito (ADR-004).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library.loan', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('member_id');
            $table->uuid('loaned_by_user_id');
            $table->timestampTz('loaned_at');

            $table->foreign('member_id', 'fk_loan_member')
                ->references('id')->on('library.member')
                ->onDelete('restrict')->onUpdate('no action');
            $table->foreign('loaned_by_user_id', 'fk_loan_user')
                ->references('id')->on('library.user')
                ->onDelete('restrict')->onUpdate('no action');
        });

        DB::statement('CREATE INDEX ix_loan_loaned_at ON library.loan (loaned_at)');

        Schema::create('library.loan_item', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('loan_id');
            $table->uuid('book_id');
            $table->string('book_title', 250);
            $table->char('isbn', 13);
            $table->string('category_name', 120);
            $table->timestampTz('due_at');
            $table->timestampTz('returned_at')->nullable();

            // Composición: la línea no vive sin su préstamo (nunca se dispara).
            $table->foreign('loan_id', 'fk_loan_item_loan')
                ->references('id')->on('library.loan')
                ->onDelete('cascade')->onUpdate('no action');
            // Barrera de última instancia del histórico (ADR-003).
            $table->foreign('book_id', 'fk_loan_item_book')
                ->references('id')->on('library.book')
                ->onDelete('restrict')->onUpdate('no action');

            $table->unique(['loan_id', 'book_id'], 'uq_loan_item_loan_book');
        });

        DB::statement('CREATE INDEX ix_loan_item_book ON library.loan_item (book_id)');

        // Vencidos globales (Q8): solo líneas sin devolver.
        DB::statement(
            'CREATE INDEX ix_loan_item_due_open ON library.loan_item (due_at) '
            . 'WHERE returned_at IS NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('library.loan_item');
        Schema::dropIfExists('library.loan');
    }
};
