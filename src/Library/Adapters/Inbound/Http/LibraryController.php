<?php

declare(strict_types=1);

namespace Library\Adapters\Inbound\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Library\Domain\DateRange;
use Throwable;

final class LibraryController
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate(['username' => ['required', 'string'], 'password' => ['required', 'string']]);
        $user = DB::table('library.user')->where('username', strtolower(trim($data['username'])))->first();
        if ($user === null || !password_verify($data['password'], $user->password_hash)) {
            throw new ApiException('INVALID_CREDENTIALS', 'Las credenciales no son válidas.', 401);
        }

        return response()->json([
            'access_token' => ApiToken::issue($user->id, $user->role),
            'token_type' => 'Bearer',
            'expires_in' => (int) env('JWT_TTL', 28800),
            'role' => $user->role,
        ]);
    }

    public function createUser(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:librarian'],
        ]);
        $username = strtolower(trim($data['username']));
        if (DB::table('library.user')->where('username', $username)->exists()) {
            throw new ApiException('USERNAME_TAKEN', 'El nombre de usuario ya está en uso.', 409);
        }
        $user = ['id' => self::uuid(), 'username' => $username, 'password_hash' => password_hash($data['password'], PASSWORD_ARGON2ID), 'role' => 'librarian'];
        DB::table('library.user')->insert($user);

        return response()->json(['id' => $user['id'], 'username' => $username, 'role' => 'librarian'], 201);
    }

    public function categories(): JsonResponse
    {
        return response()->json(['items' => DB::table('library.category')->orderBy('name')->get()]);
    }

    public function books(Request $request): JsonResponse
    {
        $size = min(max((int) $request->integer('size', 20), 1), 50);
        $page = max((int) $request->integer('page', 1), 1);
        $query = DB::table('library.book as b')->join('library.category as c', 'c.id', '=', 'b.category_id')->whereNull('b.deleted_at');
        if ($request->filled('q')) {
            $term = '%' . trim((string) $request->input('q')) . '%';
            $query->where(fn ($q) => $q->where('b.title', 'ILIKE', $term)->orWhere('b.author', 'ILIKE', $term));
        }
        if ($request->filled('category_id')) {
            $query->where('b.category_id', $request->input('category_id'));
        }
        $total = (clone $query)->count();
        $items = $query->orderBy('b.title')->forPage($page, $size)->get()->map(fn ($book) => self::bookPayload($book))->values();

        return response()->json(compact('items', 'page', 'size', 'total'));
    }

    public function book(string $id): JsonResponse
    {
        $book = DB::table('library.book as b')->join('library.category as c', 'c.id', '=', 'b.category_id')->where('b.id', $id)->whereNull('b.deleted_at')->first();
        if ($book === null) {
            throw new ApiException('NOT_FOUND', 'El libro no existe.', 404);
        }
        return response()->json(self::bookPayload($book));
    }

    public function createBook(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:250'], 'author' => ['required', 'string', 'max:200'],
            'isbn' => ['required', 'regex:/^\d{13}$/'], 'category_id' => ['required', 'uuid'], 'total_copies' => ['required', 'integer', 'min:1'],
        ]);
        if (DB::table('library.book')->where('isbn', $data['isbn'])->whereNull('deleted_at')->exists()) {
            throw new ApiException('ISBN_TAKEN', 'El ISBN ya está registrado.', 409);
        }
        if (!DB::table('library.category')->where('id', $data['category_id'])->exists()) {
            throw new ApiException('VALIDATION_ERROR', 'La categoría no existe.', 422);
        }
        $book = ['id' => self::uuid(), 'title' => trim($data['title']), 'author' => trim($data['author']), 'isbn' => $data['isbn'], 'category_id' => $data['category_id'], 'total_copies' => $data['total_copies'], 'available_copies' => $data['total_copies']];
        DB::table('library.book')->insert($book);
        return response()->json($this->book($book['id'])->getData(true), 201);
    }

    public function updateBook(Request $request, string $id): JsonResponse
    {
        $book = DB::table('library.book')->where('id', $id)->whereNull('deleted_at')->first();
        if ($book === null) throw new ApiException('NOT_FOUND', 'El libro no existe.', 404);
        $data = $request->validate(['title' => ['required', 'string', 'max:250'], 'author' => ['required', 'string', 'max:200'], 'category_id' => ['required', 'uuid'], 'total_copies' => ['required', 'integer', 'min:1']]);
        $borrowed = $book->total_copies - $book->available_copies;
        $available = $data['total_copies'] - $borrowed;
        if ($available < 0) throw new ApiException('VALIDATION_ERROR', 'El total no puede ser menor que los ejemplares prestados.', 422);
        DB::table('library.book')->where('id', $id)->update(['title' => trim($data['title']), 'author' => trim($data['author']), 'category_id' => $data['category_id'], 'total_copies' => $data['total_copies'], 'available_copies' => $available]);
        return $this->book($id);
    }

    public function deleteBook(string $id): JsonResponse
    {
        if (!DB::table('library.book')->where('id', $id)->whereNull('deleted_at')->exists()) throw new ApiException('NOT_FOUND', 'El libro no existe.', 404);
        if (DB::table('library.loan_item as li')->join('library.loan as l', 'l.id', '=', 'li.loan_id')->where('li.book_id', $id)->whereNull('li.returned_at')->exists()) throw new ApiException('BOOK_HAS_OPEN_LOANS', 'El libro tiene préstamos abiertos.', 409);
        DB::table('library.book')->where('id', $id)->update(['deleted_at' => now()]);
        return response()->json(null, 204);
    }

    public function members(Request $request): JsonResponse
    {
        $size = min(max((int) $request->integer('size', 20), 1), 50); $page = max((int) $request->integer('page', 1), 1);
        $query = DB::table('library.member');
        if ($request->filled('q')) { $term = '%' . trim((string) $request->input('q')) . '%'; $query->where(fn ($q) => $q->where('full_name', 'ILIKE', $term)->orWhere('document_number', 'ILIKE', $term)); }
        $total = (clone $query)->count(); $items = $query->orderBy('full_name')->forPage($page, $size)->get();
        foreach ($items as $item) $item->active_loans = DB::table('library.loan_item as li')->join('library.loan as l', 'l.id', '=', 'li.loan_id')->where('l.member_id', $item->id)->whereNull('li.returned_at')->count();
        return response()->json(compact('items', 'page', 'size', 'total'));
    }

    public function createMember(Request $request): JsonResponse
    {
        $data = $request->validate(['full_name' => ['required', 'string', 'max:200'], 'document_number' => ['required', 'string', 'max:30']]);
        if (DB::table('library.member')->where('document_number', $data['document_number'])->exists()) throw new ApiException('DOCUMENT_TAKEN', 'El documento ya está registrado.', 409);
        $member = ['id' => self::uuid(), 'full_name' => trim($data['full_name']), 'document_number' => trim($data['document_number'])]; DB::table('library.member')->insert($member);
        return response()->json($member, 201);
    }

    public function loans(Request $request): JsonResponse
    {
        $size = min(max((int) $request->integer('size', 20), 1), 50); $page = max((int) $request->integer('page', 1), 1);
        $query = DB::table('library.loan as l')->join('library.member as m', 'm.id', '=', 'l.member_id')->select('l.*', 'm.full_name as member_name')->orderByDesc('l.loaned_at');
        if ($request->filled('member_id')) $query->where('l.member_id', $request->input('member_id'));
        $total = (clone $query)->count(); $loans = $query->forPage($page, $size)->get()->map(fn ($loan) => $this->loan($loan))->values();
        if ($request->filled('status')) $loans = $loans->filter(fn ($loan) => $loan['status'] === $request->input('status'))->values();
        return response()->json(['items' => $loans, 'page' => $page, 'size' => $size, 'total' => $total]);
    }

    public function createLoan(Request $request): JsonResponse
    {
        $data = $request->validate(['member_id' => ['required', 'uuid'], 'items' => ['required', 'array', 'min:1', 'max:3'], 'items.*.book_id' => ['required', 'uuid']]);
        $bookIds = array_column($data['items'], 'book_id');
        if (count($bookIds) !== count(array_unique($bookIds))) throw new ApiException('DUPLICATE_BOOK_IN_LOAN', 'No puedes repetir un libro en el mismo préstamo.', 422);
        $member = DB::table('library.member')->where('id', $data['member_id'])->first(); if ($member === null) throw new ApiException('NOT_FOUND', 'El socio no existe.', 404);
        $active = DB::table('library.loan_item as li')->join('library.loan as l', 'l.id', '=', 'li.loan_id')->where('l.member_id', $member->id)->whereNull('li.returned_at')->count();
        if ($active + count($bookIds) > 3) throw new ApiException('MEMBER_LIMIT_EXCEEDED', 'El socio superaría el límite de tres libros.', 422);
        $now = now()->utc(); $loanId = self::uuid(); $books = DB::table('library.book as b')->join('library.category as c', 'c.id', '=', 'b.category_id')->whereIn('b.id', $bookIds)->whereNull('b.deleted_at')->lockForUpdate()->get()->keyBy('id');
        if ($books->count() !== count($bookIds)) throw new ApiException('NOT_FOUND', 'Uno de los libros no existe.', 404);
        DB::beginTransaction();
        try {
            foreach ($bookIds as $bookId) { $book = $books[$bookId]; if ($book->available_copies < 1) throw new ApiException('BOOK_NOT_AVAILABLE', "No quedan ejemplares disponibles de «{$book->title}».", 409); }
            DB::table('library.loan')->insert(['id' => $loanId, 'member_id' => $member->id, 'loaned_by_user_id' => $request->attributes->get('auth_claims')['sub'], 'loaned_at' => $now]);
            foreach ($bookIds as $bookId) { $book = $books[$bookId]; DB::table('library.book')->where('id', $bookId)->decrement('available_copies'); DB::table('library.loan_item')->insert(['id' => self::uuid(), 'loan_id' => $loanId, 'book_id' => $bookId, 'book_title' => $book->title, 'isbn' => $book->isbn, 'category_name' => $book->name, 'due_at' => $now->copy()->addDays(14)]); }
            DB::commit();
        } catch (Throwable $error) { DB::rollBack(); throw $error; }
        return response()->json($this->loan(DB::table('library.loan')->where('id', $loanId)->first()), 201);
    }

    public function showLoan(string $id): JsonResponse
    {
        $loan = DB::table('library.loan as l')->join('library.member as m', 'm.id', '=', 'l.member_id')->select('l.*', 'm.full_name as member_name')->where('l.id', $id)->first();
        if ($loan === null) throw new ApiException('NOT_FOUND', 'El préstamo no existe.', 404);
        return response()->json($this->loan($loan));
    }

    public function returnItem(string $loanId, string $itemId): JsonResponse
    {
        $item = DB::table('library.loan_item')->where('id', $itemId)->where('loan_id', $loanId)->first();
        if ($item === null) throw new ApiException('NOT_FOUND', 'La línea de préstamo no existe.', 404);
        if ($item->returned_at !== null) throw new ApiException('ITEM_ALREADY_RETURNED', 'La línea ya fue devuelta.', 409);
        DB::transaction(fn () => [DB::table('library.loan_item')->where('id', $itemId)->whereNull('returned_at')->update(['returned_at' => now()->utc()]), DB::table('library.book')->where('id', $item->book_id)->increment('available_copies')]);
        return response()->json(DB::table('library.loan_item')->where('id', $itemId)->first());
    }

    public function overdue(): JsonResponse
    {
        $items = DB::table('library.loan_item as li')->join('library.loan as l', 'l.id', '=', 'li.loan_id')->join('library.member as m', 'm.id', '=', 'l.member_id')->whereNull('li.returned_at')->where('li.due_at', '<', now()->utc())->orderBy('li.due_at')->get()->map(fn ($item) => ['loan_id' => $item->loan_id, 'item_id' => $item->id, 'book_title' => $item->book_title, 'member_name' => $item->full_name, 'due_at' => $item->due_at, 'days_late' => now()->utc()->diffInDays($item->due_at)])->values();
        return response()->json(['items' => $items]);
    }

    public function report(Request $request): JsonResponse
    {
        $data = $request->validate(['from' => ['required', 'date'], 'to' => ['required', 'date']]);
        try { $range = new DateRange(new \DateTimeImmutable($data['from']), new \DateTimeImmutable($data['to'])); } catch (Throwable) { throw new ApiException('INVALID_DATE_RANGE', 'El rango de fechas no es válido.', 422); }
        $rows = DB::table('library.loan_item as li')->join('library.loan as l', 'l.id', '=', 'li.loan_id')->whereBetween('l.loaned_at', [$data['from'] . ' 00:00:00+00', $data['to'] . ' 23:59:59+00'])->select('li.book_id', 'li.book_title', 'li.category_name')->selectRaw('COUNT(*) as times_borrowed')->groupBy('li.book_id', 'li.book_title', 'li.category_name')->orderByDesc('times_borrowed')->orderBy('li.book_title')->get();
        return response()->json(['from' => $data['from'], 'to' => $data['to'], 'rows' => $rows]);
    }

    private function loan(object $loan): array
    {
        $items = DB::table('library.loan_item')->where('loan_id', $loan->id)->orderBy('id')->get();
        $overdue = $items->contains(fn ($item) => $item->returned_at === null && now()->utc()->gt($item->due_at));
        $status = $items->every(fn ($item) => $item->returned_at !== null) ? 'closed' : ($overdue ? 'overdue' : 'open');
        return ['id' => $loan->id, 'member_id' => $loan->member_id, 'member_name' => $loan->member_name ?? null, 'loaned_at' => $loan->loaned_at, 'status' => $status, 'items' => $items->map(fn ($item) => ['id' => $item->id, 'book_id' => $item->book_id, 'book_title' => $item->book_title, 'isbn' => $item->isbn, 'category_name' => $item->category_name, 'due_at' => $item->due_at, 'returned_at' => $item->returned_at])->values()];
    }

    private static function bookPayload(object|array $book): array
    {
        $book = (object) $book;
        return ['id' => $book->id, 'title' => $book->title, 'author' => $book->author, 'isbn' => $book->isbn, 'category' => ['id' => $book->category_id, 'name' => $book->name ?? null], 'total_copies' => $book->total_copies, 'available_copies' => $book->available_copies, 'cover_key' => $book->cover_key ?? null];
    }

    private static function uuid(): string
    {
        return (string) \Illuminate\Support\Str::uuid();
    }
}
