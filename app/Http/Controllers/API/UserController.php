<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UserController extends Controller
{
    /** FQCN stored in personal_access_tokens.tokenable_type */
    private const USER_TYPE = 'App\\Models\\User';

    /** Canonical roles for Unzip Exam */
    private const ROLES = ['super_admin','admin','examiner','student'];

    /** Short codes for roles */
    private const ROLE_SHORT = [
        'super_admin' => 'SA',
        'admin'       => 'ADM',
        'examiner'    => 'EXM',
        'student'     => 'STD',
    ];

    /* =========================================================
     |                       AUTH
     |=========================================================*/

    /**
     * POST /api/auth/login
     * Body: { email, password, remember?: bool }
     * Returns: { access_token, token_type, expires_at?, user: {...} }
     */
    public function login(Request $request)
    {
        Log::info('[UnzipExam Auth Login] begin', ['ip' => $request->ip()]);

        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
            'remember' => 'sometimes|boolean',
        ]);

        $user = DB::table('users')
            ->where('email', $validated['email'])
            ->whereNull('deleted_at')
            ->first();

        if (!$user) {
            Log::warning('[UnzipExam Auth Login] user not found', ['email' => $validated['email']]);
            return response()->json(['status'=>'error','message'=>'Invalid credentials'], 401);
        }

        if (isset($user->status) && $user->status !== 'active') {
            Log::warning('[UnzipExam Auth Login] inactive user', [
                'user_id'=>$user->id,
                'status'=>$user->status
            ]);
            return response()->json(['status'=>'error','message'=>'Account is not active'], 403);
        }

        if (!Hash::check($validated['password'], $user->password)) {
            Log::warning('[UnzipExam Auth Login] password mismatch', ['user_id'=>$user->id]);
            return response()->json(['status'=>'error','message'=>'Invalid credentials'], 401);
        }

        // Remember-me -> longer expiry. Otherwise, short TTL.
        $remember  = (bool)($validated['remember'] ?? false);
        $expiresAt = $remember ? now()->addDays(30) : now()->addHours(12);

        $plainToken = $this->issueToken((int)$user->id, $expiresAt);

        // Update last login markers
        DB::table('users')->where('id', $user->id)->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'updated_at'    => now(),
        ]);

        $payloadUser = $this->publicUserPayload($user);

        Log::info('[UnzipExam Auth Login] success', [
            'user_id'=>$user->id,
            'role'=>$payloadUser['role'] ?? null
        ]);

        return response()->json([
            'status'       => 'success',
            'message'      => 'Login successful',
            'access_token' => $plainToken,
            'token_type'   => 'Bearer',
            'expires_at'   => $expiresAt->toIso8601String(),
            'user'         => $payloadUser,
        ]);
    }

    /**
     * POST /api/auth/logout
     * Header: Authorization: Bearer <token>
     */
    public function logout(Request $request)
    {
        Log::info('[UnzipExam Auth Logout] begin', ['ip' => $request->ip()]);

        $plain = $this->extractToken($request);
        if (!$plain) {
            Log::warning('[UnzipExam Auth Logout] missing token');
            return response()->json(['status'=>'error','message'=>'Token not provided'], 401);
        }

        $deleted = DB::table('personal_access_tokens')
            ->where('token', hash('sha256', $plain))
            ->where('tokenable_type', self::USER_TYPE)
            ->delete();

        Log::info('[UnzipExam Auth Logout] token removed', ['deleted'=>(bool)$deleted]);

        return response()->json([
            'status'  => $deleted ? 'success' : 'error',
            'message' => $deleted ? 'Logged out successfully' : 'Invalid token',
        ], $deleted ? 200 : 401);
    }

    /**
     * GET /api/auth/check
     * Header: Authorization: Bearer <token>
     * Returns user if token valid (and not expired).
     */
    public function authenticateToken(Request $request)
    {
        $plain = $this->extractToken($request);
        if (!$plain) {
            return response()->json(['status'=>'error','message'=>'Token not provided'], 401);
        }

        $rec = DB::table('personal_access_tokens')
            ->where('token', hash('sha256', $plain))
            ->where('tokenable_type', self::USER_TYPE)
            ->first();

        if (!$rec) {
            return response()->json(['status'=>'error','message'=>'Invalid token'], 401);
        }

        // Expiration check (if set)
        if (!empty($rec->expires_at) && Carbon::parse($rec->expires_at)->isPast()) {
            DB::table('personal_access_tokens')->where('id', $rec->id)->delete();
            return response()->json(['status'=>'error','message'=>'Token expired'], 401);
        }

        $user = DB::table('users')
            ->where('id', $rec->tokenable_id)
            ->whereNull('deleted_at')
            ->first();

        if (!$user || (isset($user->status) && $user->status !== 'active')) {
            return response()->json(['status'=>'error','message'=>'Unauthorized'], 401);
        }

        return response()->json([
            'status' => 'success',
            'user'   => $this->publicUserPayload($user),
        ]);
    }

    /* =========================================================
     |                       USERS CRUD
     |=========================================================*/

    /**
     * POST /api/users
     * Create user (with optional image). Stores image in /Public/UserProfileImage.
     */
    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'name'                     => 'required|string|max:150',
            'email'                    => 'required|email|max:255',
            'password'                 => 'required|string|min:8',
            'phone_number'             => 'sometimes|nullable|string|max:32',
            'alternative_email'        => 'sometimes|nullable|email|max:255',
            'alternative_phone_number' => 'sometimes|nullable|string|max:32',
            'whatsapp_number'          => 'sometimes|nullable|string|max:32',
            'address'                  => 'sometimes|nullable|string',
            'role'                     => 'sometimes|nullable|string|max:50',
            'role_short_form'          => 'sometimes|nullable|string|max:10',
            'status'                   => 'sometimes|in:active,inactive',
            'image'                    => 'sometimes|file|mimes:jpg,jpeg,png,webp,gif,svg|max:5120',
        ]);
        if ($v->fails()) {
            return response()->json(['status'=>'error','errors'=>$v->errors()], 422);
        }
        $data = $v->validated();

        // Uniqueness pre-checks
        if (DB::table('users')->where('email', $data['email'])->exists()) {
            return response()->json(['status'=>'error','message'=>'Email already exists'], 422);
        }
        if (!empty($data['phone_number']) &&
            DB::table('users')->where('phone_number', $data['phone_number'])->exists()) {
            return response()->json(['status'=>'error','message'=>'Phone number already exists'], 422);
        }

        // UUID & unique slug
        do { $uuid = (string) Str::uuid(); }
        while (DB::table('users')->where('uuid', $uuid)->exists());

        $base = Str::slug($data['name']);
        do { $slug = $base . '-' . Str::lower(Str::random(24)); }
        while (DB::table('users')->where('slug', $slug)->exists());

        // Role normalization (Unzip Exam)
        [$role, $roleShort] = $this->normalizeRole(
            $data['role'] ?? 'student',
            $data['role_short_form'] ?? null
        );

        // Optional image upload
        $imageUrl = null;
        if ($request->hasFile('image')) {
            $imageUrl = $this->saveProfileImage($request->file('image'));
            if ($imageUrl === false) {
                return response()->json(['status'=>'error','message'=>'Invalid image upload'], 422);
            }
        }

        // Creator (from token)
        $createdBy = $this->currentUserId($request);

        try {
            $now = now();
            DB::table('users')->insert([
                'uuid'                     => $uuid,
                'name'                     => $data['name'],
                'email'                    => $data['email'],
                'phone_number'             => $data['phone_number'] ?? null,
                'alternative_email'        => $data['alternative_email'] ?? null,
                'alternative_phone_number' => $data['alternative_phone_number'] ?? null,
                'whatsapp_number'          => $data['whatsapp_number'] ?? null,
                'password'                 => Hash::make($data['password']),
                'image'                    => $imageUrl,
                'address'                  => $data['address'] ?? null,
                'role'                     => $role,
                'role_short_form'          => $roleShort,
                'slug'                     => $slug,
                'status'                   => $data['status'] ?? 'active',
                'remember_token'           => Str::random(60),
                'created_by'               => $createdBy,
                'created_at'               => $now,
                'created_at_ip'            => $request->ip(),
                'updated_at'               => $now,
                'metadata'                 => json_encode([
                    'timezone' => 'Asia/Kolkata',
                    'source'   => 'unzip_exam_api_store',
                ], JSON_UNESCAPED_UNICODE),
            ]);

            $user = DB::table('users')->where('email', $data['email'])->first();

            return response()->json([
                'status'  => 'success',
                'message' => 'User created',
                'user'    => $this->publicUserPayload($user),
            ], 201);
        } catch (\Throwable $e) {
            if ($imageUrl) $this->deleteManagedProfileImage($imageUrl);
            Log::error('[UnzipExam Users Store] failed', ['error'=>$e->getMessage()]);
            return response()->json(['status'=>'error','message'=>'Could not create user'], 500);
        }
    }

    /**
     * GET /api/users/all?q=&status=&limit=
     * Lightweight list (no pagination).
     */
    public function all(Request $request)
    {
        $q      = trim((string)$request->query('q', ''));
        $status = (string)$request->query('status', 'active'); // '' to disable filter
        $limit  = min(1000, max(1, (int)$request->query('limit', 1000)));

        $rows = DB::table('users')
            ->whereNull('deleted_at')
            ->when($status !== '', fn($w) => $w->where('status', $status))
            ->when($q !== '', function($w) use ($q){
                $like = "%{$q}%";
                $w->where(function($x) use ($like){
                    $x->where('name','LIKE',$like)->orWhere('email','LIKE',$like);
                });
            })
            ->select('id','name','email','image','role','role_short_form','status')
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return response()->json([
            'status'=>'success',
            'data'  => $rows,
            'meta'  => ['count' => $rows->count()],
        ]);
    }

    /**
     * GET /api/users?page=&per_page=&q=&status=
     * Paginated list.
     */
    public function index(Request $request)
    {
        $page   = max(1, (int)$request->query('page', 1));
        $pp     = min(100, max(1, (int)$request->query('per_page', 20)));
        $q      = trim((string)$request->query('q', ''));
        // If the param is absent, default to 'active'; if it's 'all', apply no filter
        $status = $request->has('status') ? (string)$request->query('status') : 'active';

        $base = DB::table('users')->whereNull('deleted_at');
        if ($status !== 'all' && $status !== '') {
            $base->where('status', $status);
        }
        if ($q !== '') {
            $like = "%{$q}%";
            $base->where(function($w) use ($like){
                $w->where('name','LIKE',$like)->orWhere('email','LIKE',$like);
            });
        }

        $total = (clone $base)->count();
        $rows  = $base->orderBy('name')
            ->offset(($page-1)*$pp)->limit($pp)
            ->select('id','name','email','image','role','role_short_form','status')
            ->get();

        return response()->json([
            'status'=>'success',
            'data'  =>$rows,
            'meta'  =>[
                'page'        =>$page,
                'per_page'    =>$pp,
                'total'       =>$total,
                'total_pages' =>(int)ceil($total/$pp)
            ],
        ]);
    }

    /**
     * GET /api/users/{id}
     */
    public function show(Request $request, int $id)
    {
        $user = DB::table('users')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$user) {
            return response()->json(['status'=>'error','message'=>'User not found'], 404);
        }

        return response()->json([
            'status'=>'success',
            'user'  => [
                'id'                        => (int)$user->id,
                'uuid'                      => (string)($user->uuid ?? ''),
                'name'                      => (string)($user->name ?? ''),
                'email'                     => (string)($user->email ?? ''),
                'phone_number'              => (string)($user->phone_number ?? ''),
                'alternative_email'         => (string)($user->alternative_email ?? ''),
                'alternative_phone_number'  => (string)($user->alternative_phone_number ?? ''),
                'whatsapp_number'           => (string)($user->whatsapp_number ?? ''),
                'image'                     => (string)($user->image ?? ''),
                'address'                   => (string)($user->address ?? ''),
                'role'                      => (string)($user->role ?? ''),
                'role_short_form'           => (string)($user->role_short_form ?? ''),
                'slug'                      => (string)($user->slug ?? ''),
                'status'                    => (string)($user->status ?? ''),
                'last_login_at'             => (string)($user->last_login_at ?? ''),
                'last_login_ip'             => (string)($user->last_login_ip ?? ''),
                'created_by'                => $user->created_by,
                'created_at'                => (string)$user->created_at,
                'updated_at'                => (string)$user->updated_at,
                'deleted_at'                => (string)($user->deleted_at ?? ''),
            ],
        ]);
    }

        /**
     * GET /api/users/{id}/quizzes
     * For ADMIN / SUPER_ADMIN.
     * Returns all quizzes + whether this user is assigned to each.
     */
    public function userQuizzes(Request $request, int $id)
    {
        // Ensure user exists & not deleted
        $user = DB::table('users')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found',
            ], 404);
        }

        // All quizzes (excluding soft-deleted)
        $quizzes = DB::table('quizz')
            ->whereNull('deleted_at')
            ->orderBy('quiz_name')
            ->get();

        // Existing assignments (any status, not hard deleted)
        $assignments = DB::table('user_quiz_assignments')
            ->where('user_id', $id)
            ->whereNull('deleted_at')
            ->get()
            ->keyBy('quiz_id');

        $data = $quizzes->map(function ($q) use ($assignments) {
            $a = $assignments->get($q->id);

            return [
                'quiz_id'         => (int) $q->id,
                'quiz_uuid'       => (string) ($q->uuid ?? ''),
                'quiz_name'       => (string) ($q->quiz_name ?? ''),
                'total_time'      => $q->total_time,
                'total_questions' => $q->total_questions,
                'is_public'       => (string) ($q->is_public ?? 'no'),
                'status'          => (string) ($q->status ?? 'active'),

                'assigned'        => $a && $a->status === 'active',
                'assignment_code' => $a && $a->status === 'active'
                                        ? (string) $a->assignment_code
                                        : null,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $data,
        ]);
    }


    /**
     * PUT/PATCH /api/users/{id}
     * Partial update. If name changes, slug is regenerated.
     */
    public function update(Request $request, int $id)
    {
        $v = Validator::make($request->all(), [
            'name'                     => 'sometimes|string|max:150',
            'email'                    => 'sometimes|email|max:255',
            'phone_number'             => 'sometimes|nullable|string|max:32',
            'alternative_email'        => 'sometimes|nullable|email|max:255',
            'alternative_phone_number' => 'sometimes|nullable|string|max:32',
            'whatsapp_number'          => 'sometimes|nullable|string|max:32',
            'address'                  => 'sometimes|nullable|string',
            'role'                     => 'sometimes|nullable|string|max:50',
            'role_short_form'          => 'sometimes|nullable|string|max:10',
            'status'                   => 'sometimes|in:active,inactive',
            'image'                    => 'sometimes|file|mimes:jpg,jpeg,png,webp,gif,svg|max:5120',
        ]);
        if ($v->fails()) {
            return response()->json(['status'=>'error','errors'=>$v->errors()], 422);
        }
        $data = $v->validated();

        $existing = DB::table('users')->where('id', $id)->whereNull('deleted_at')->first();
        if (!$existing) {
            return response()->json(['status'=>'error','message'=>'User not found'], 404);
        }

        // Uniqueness if changed
        if (array_key_exists('email', $data)) {
            if (DB::table('users')->where('email', $data['email'])->where('id','!=',$id)->exists()) {
                return response()->json(['status'=>'error','message'=>'Email already exists'], 422);
            }
        }
        if (array_key_exists('phone_number', $data) && !empty($data['phone_number'])) {
            if (DB::table('users')->where('phone_number', $data['phone_number'])->where('id','!=',$id)->exists()) {
                return response()->json(['status'=>'error','message'=>'Phone number already exists'], 422);
            }
        }

        $updates = [];
        foreach ([
            'name','email','phone_number','alternative_email','alternative_phone_number',
            'whatsapp_number','address','status'
        ] as $key) {
            if (array_key_exists($key, $data)) {
                $updates[$key] = $data[$key];
            }
        }

        // Role normalization if provided
        if (array_key_exists('role', $data) || array_key_exists('role_short_form', $data)) {
            [$normRole, $normShort] = $this->normalizeRole(
                $data['role'] ?? $existing->role,
                $data['role_short_form'] ?? $existing->role_short_form
            );
            $updates['role'] = $normRole;
            $updates['role_short_form'] = $normShort;
        }

        // Regenerate slug if name changed
        if (array_key_exists('name', $updates) && $updates['name'] !== $existing->name) {
            $base = Str::slug($updates['name']);
            do { $slug = $base . '-' . Str::lower(Str::random(24)); }
            while (DB::table('users')->where('slug', $slug)->where('id','!=',$id)->exists());
            $updates['slug'] = $slug;
        }

        // Optional image update
        if ($request->hasFile('image')) {
            $newUrl = $this->saveProfileImage($request->file('image'));
            if ($newUrl === false) {
                return response()->json(['status'=>'error','message'=>'Invalid image upload'], 422);
            }

            $this->deleteManagedProfileImage($existing->image);
            $updates['image'] = $newUrl;
        }

        if (empty($updates)) {
            return response()->json(['status'=>'error','message'=>'Nothing to update'], 400);
        }

        $updates['updated_at'] = now();

        DB::table('users')->where('id', $id)->update($updates);

        $fresh = DB::table('users')->where('id', $id)->first();
        return response()->json([
            'status'=>'success',
            'message'=>'User updated',
            'user'=>$this->publicUserPayload($fresh),
        ]);
    }

    /**
     * DELETE /api/users/{id}
     * Soft delete (prevents self-delete).
     */
    public function destroy(Request $request, int $id)
    {
        $actorId = $this->currentUserId($request);
        if ($actorId !== null && $actorId === $id) {
            return response()->json(['status'=>'error','message'=>"You can't delete your own account"], 422);
        }

        $user = DB::table('users')->where('id', $id)->whereNull('deleted_at')->first();
        if (!$user) {
            return response()->json(['status'=>'error','message'=>'User not found'], 404);
        }

        DB::table('users')->where('id', $id)->update([
            'deleted_at' => now(),
            'status'     => 'inactive',
            'updated_at' => now(),
        ]);

        return response()->json(['status'=>'success','message'=>'User soft-deleted']);
    }

    /**
     * POST /api/users/{id}/restore
     */
    public function restore(Request $request, int $id)
    {
        $user = DB::table('users')->where('id', $id)->whereNotNull('deleted_at')->first();
        if (!$user) {
            return response()->json(['status'=>'error','message'=>'User not found or not deleted'], 404);
        }

        DB::table('users')->where('id', $id)->update([
            'deleted_at' => null,
            'status'     => 'active',
            'updated_at' => now(),
        ]);

        return response()->json(['status'=>'success','message'=>'User restored']);
    }

    /**
     * DELETE /api/users/{id}/force
     * Permanently delete (also removes managed profile image).
     */
    public function forceDelete(Request $request, int $id)
    {
        $actorId = $this->currentUserId($request);
        if ($actorId !== null && $actorId === $id) {
            return response()->json(['status'=>'error','message'=>"You can't delete your own account"], 422);
        }

        $user = DB::table('users')->where('id', $id)->first();
        if (!$user) {
            return response()->json(['status'=>'error','message'=>'User not found'], 404);
        }

        $this->deleteManagedProfileImage($user->image);

        DB::table('users')->where('id', $id)->delete();

        return response()->json(['status'=>'success','message'=>'User permanently deleted']);
    }

    /**
     * PATCH /api/users/{id}/password
     * Body: { password }
     */
    public function updatePassword(Request $request, int $id)
    {
        $v = Validator::make($request->all(), [
            'password' => 'required|string|min:8',
        ]);
        if ($v->fails()) {
            return response()->json(['status'=>'error','errors'=>$v->errors()], 422);
        }

        $user = DB::table('users')->where('id', $id)->whereNull('deleted_at')->first();
        if (!$user) {
            return response()->json(['status'=>'error','message'=>'User not found'], 404);
        }

        DB::table('users')->where('id', $id)->update([
            'password'   => Hash::make($v->validated()['password']),
            'updated_at' => now(),
        ]);

        return response()->json(['status'=>'success','message'=>'Password updated']);
    }

    /**
     * POST /api/users/{id}/image
     * file: image (multipart/form-data)
     */
    public function updateImage(Request $request, int $id)
    {
        $v = Validator::make($request->all(), [
            'image' => 'required|file|mimes:jpg,jpeg,png,webp,gif,svg|max:5120',
        ]);
        if ($v->fails()) {
            return response()->json(['status'=>'error','errors'=>$v->errors()], 422);
        }

        $user = DB::table('users')->where('id', $id)->whereNull('deleted_at')->first();
        if (!$user) {
            return response()->json(['status'=>'error','message'=>'User not found'], 404);
        }

        $newUrl = $this->saveProfileImage($request->file('image'));
        if ($newUrl === false) {
            return response()->json(['status'=>'error','message'=>'Invalid image upload'], 422);
        }

        $this->deleteManagedProfileImage($user->image);

        DB::table('users')->where('id', $id)->update([
            'image'      => $newUrl,
            'updated_at' => now(),
        ]);

        $fresh = DB::table('users')->where('id', $id)->first();

        return response()->json([
            'status'=>'success',
            'message'=>'Image updated',
            'user'=>$this->publicUserPayload($fresh),
        ]);
    }

    /* =========================================================
     |                     Helper methods
     |=========================================================*/

    /** Issue a personal access token; returns the plain token. */
    protected function issueToken(int $userId, ?Carbon $expiresAt = null): string
    {
        $plain = bin2hex(random_bytes(40));

        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => self::USER_TYPE,
            'tokenable_id'   => $userId,
            'name'           => 'unzip_exam_user_token',
            'token'          => hash('sha256', $plain),
            'abilities'      => json_encode(['*']),
            'last_used_at'   => null,
            'expires_at'     => $expiresAt ? $expiresAt->toDateTimeString() : null,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return $plain;
    }

    /** Extract Bearer token from Authorization header. */
    protected function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization');
        if (!$header || !preg_match('/Bearer\s(\S+)/', $header, $m)) {
            return null;
        }
        return $m[1];
    }

    /** Resolve current user id from the provided Bearer token. */
    protected function currentUserId(Request $request): ?int
    {
        $plain = $this->extractToken($request);
        if (!$plain) return null;

        $rec = DB::table('personal_access_tokens')
            ->where('token', hash('sha256', $plain))
            ->where('tokenable_type', self::USER_TYPE)
            ->first();

        return $rec ? (int)$rec->tokenable_id : null;
    }

    /** Public payload sent to FE (no sensitive fields). */
    protected function publicUserPayload(object $user): array
    {
        return [
            'id'              => (int)$user->id,
            'uuid'            => (string)($user->uuid ?? ''),
            'name'            => (string)($user->name ?? ''),
            'email'           => (string)($user->email ?? ''),
            'role'            => (string)($user->role ?? ''),
            'role_short_form' => (string)($user->role_short_form ?? ''),
            'slug'            => (string)($user->slug ?? ''),
            'image'           => (string)($user->image ?? ''),
            'status'          => (string)($user->status ?? ''),
        ];
    }

        /** Generate unique 10-char UPPERCASE alphanumeric assignment code. */
    protected function generateAssignmentCode(): string
    {
        do {
            $code = strtoupper(Str::random(10)); // A–Z + 0–9
        } while (
            DB::table('user_quiz_assignments')
              ->where('assignment_code', $code)
              ->exists()
        );

        return $code;
    }


    /**
     * Normalize role & short code against allowed set.
     * Accepts synonyms like "super admin", "super-admin", "sa",
     * "invigilator", "proctor" -> "examiner", "students" -> "student".
     */
    protected function normalizeRole(?string $role, ?string $short = null): array
    {
        $r = Str::of((string)$role)->lower()->trim()->toString();

        // common synonyms for Unzip Exam
        $map = [
            'super admin'   => 'super_admin',
            'super-admin'   => 'super_admin',
            'superadmin'    => 'super_admin',
            'sa'            => 'super_admin',
            'administrator' => 'admin',
            'admin'         => 'admin',
            'students'      => 'student',
            'std'           => 'student',
            'candidate'     => 'student',
            'examiner'      => 'examiner',
            'invigilator'   => 'examiner',
            'proctor'       => 'examiner',
        ];

        if (isset($map[$r])) $r = $map[$r];

        if (!in_array($r, self::ROLES, true)) {
            // fallback to student
            $r = 'student';
        }

        $short = $short ?: self::ROLE_SHORT[$r] ?? 'STD';
        return [$r, strtoupper($short)];
    }

    /** Save profile image into /Public/UserProfileImage and return absolute URL (or false on failure). */
    protected function saveProfileImage($uploadedFile)
    {
        if (!$uploadedFile || !$uploadedFile->isValid()) return false;

        $destDir = public_path('UserProfileImage');
        if (!File::isDirectory($destDir)) {
            File::makeDirectory($destDir, 0755, true);
        }

        $ext      = strtolower($uploadedFile->getClientOriginalExtension() ?: 'bin');
        $filename = 'usr_' . date('Ymd_His') . '_' . Str::lower(Str::random(16)) . '.' . $ext;

        $uploadedFile->move($destDir, $filename);

        return rtrim(config('app.url'), '/') . '/UserProfileImage/' . $filename;
    }

    /** Delete a managed profile image if it resides in /Public/UserProfileImage. */
    protected function deleteManagedProfileImage(?string $url): void
    {
        if (empty($url)) return;
        $pathUrl = parse_url($url, PHP_URL_PATH) ?? '';
        if (Str::startsWith($pathUrl, '/UserProfileImage/')) {
            $abs = public_path(ltrim($pathUrl, '/'));
            if (File::exists($abs)) {
                @File::delete($abs);
            }
        }
    }

        /**
     * POST /api/users/{id}/quizzes/assign
     * Body: { quiz_id:int }
     * Admin/Super Admin only.
     */
    public function assignQuiz(Request $request, int $id)
    {
        // Confirm user exists
        $user = DB::table('users')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found',
            ], 404);
        }

        $validated = $request->validate([
            'quiz_id' => 'required|integer',
        ]);
        $quizId = (int) $validated['quiz_id'];

        $quiz = DB::table('quizz')
            ->where('id', $quizId)
            ->whereNull('deleted_at')
            ->first();

        if (!$quiz) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Quiz not found',
            ], 404);
        }

        $now        = now();
        $assignedBy = $this->currentUserId($request);

        // We want at most one row per (user, quiz), even if soft-deleted.
        $existing = DB::table('user_quiz_assignments')
            ->where('user_id', $id)
            ->where('quiz_id', $quizId)
            ->first();

        if ($existing) {
            // If already active, just return its code
            if ($existing->status === 'active' && !$existing->deleted_at) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Already assigned',
                    'data'    => [
                        'assignment_code' => (string) $existing->assignment_code,
                    ],
                ]);
            }

            // Reactivate existing row (even if soft-deleted / revoked)
            $code = $existing->assignment_code ?: $this->generateAssignmentCode();

            DB::table('user_quiz_assignments')
                ->where('id', $existing->id)
                ->update([
                    'assignment_code' => $code,
                    'status'          => 'active',
                    'assigned_by'     => $assignedBy,
                    'assigned_at'     => $now,
                    'deleted_at'      => null,
                    'updated_at'      => $now,
                ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Assignment updated',
                'data'    => [
                    'assignment_code' => $code,
                ],
            ]);
        }

        // Fresh assignment
        $code = $this->generateAssignmentCode();

        DB::table('user_quiz_assignments')->insert([
            'uuid'            => (string) Str::uuid(),
            'user_id'         => $id,
            'quiz_id'         => $quizId,
            'assignment_code' => $code,
            'status'          => 'active',
            'assigned_by'     => $assignedBy,
            'assigned_at'     => $now,
            'metadata'        => json_encode(new \stdClass()),
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Quiz assigned to user',
            'data'    => [
                'assignment_code' => $code,
            ],
        ], 201);
    }

        /**
     * POST /api/users/{id}/quizzes/unassign
     * Body: { quiz_id:int }
     * Marks assignment as revoked (keeps row for audit).
     */
    public function unassignQuiz(Request $request, int $id)
    {
        // Confirm user exists
        $user = DB::table('users')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found',
            ], 404);
        }

        $validated = $request->validate([
            'quiz_id' => 'required|integer',
        ]);
        $quizId = (int) $validated['quiz_id'];

        $existing = DB::table('user_quiz_assignments')
            ->where('user_id', $id)
            ->where('quiz_id', $quizId)
            ->whereNull('deleted_at')
            ->first();

        if (!$existing) {
            // no-op, but not an error (helps keep FE simple)
            return response()->json([
                'status'  => 'noop',
                'message' => 'No active assignment found',
            ], 200);
        }

        DB::table('user_quiz_assignments')
            ->where('id', $existing->id)
            ->update([
                'status'     => 'revoked',
                'updated_at' => now(),
            ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Assignment revoked',
        ]);
    }

    /**
 * POST /api/users/import-csv
 * multipart/form-data:
 *  - file: CSV (required)
 *  - default_password: optional (used when password column is empty)
 *  - default_role: optional (used when role column is empty)
 *
 * CSV header required:
 *  name,email,password,role
 *
 * Example row:
 *  John Doe,john@gmail.com,Pass@123,student
 */
public function importUsersCsv(Request $request)
{
    $v = Validator::make($request->all(), [
        'file'             => 'required|file|max:10240|mimes:csv,txt',
        'default_password' => 'sometimes|nullable|string|min:6|max:100',
        'default_role'     => 'sometimes|nullable|string|max:50',
    ]);

    if ($v->fails()) {
        return response()->json(['status' => 'error', 'errors' => $v->errors()], 422);
    }

    $file = $request->file('file');

    $defaultPassword = (string)($request->input('default_password') ?: 'Student@123');
    $defaultRoleIn   = (string)($request->input('default_role') ?: 'student');
    [$defaultRole, $defaultRoleShort] = $this->normalizeRole($defaultRoleIn, null);

    $path = $file->getRealPath();
    if (!$path || !file_exists($path)) {
        return response()->json(['status'=>'error','message'=>'Uploaded file not found'], 422);
    }

    $handle = fopen($path, 'r');
    if (!$handle) {
        return response()->json(['status'=>'error','message'=>'Unable to read CSV'], 422);
    }

    // Read header
    $header = fgetcsv($handle);
    if (!$header || !is_array($header)) {
        fclose($handle);
        return response()->json(['status'=>'error','message'=>'CSV header missing'], 422);
    }

    // Normalize header keys
    $header = array_map(function ($h) {
        $h = strtolower(trim((string)$h));
        $h = preg_replace('/\s+/', '_', $h);
        return $h;
    }, $header);

    $required = ['name','email','password','role'];
    foreach (['name','email'] as $req) {
        if (!in_array($req, $header, true)) {
            fclose($handle);
            return response()->json([
                'status'  => 'error',
                'message' => "CSV must contain '{$req}' column in header",
            ], 422);
        }
    }

    $actorId = $this->currentUserId($request);
    $now     = now();

    $imported = 0;
    $skipped  = 0;
    $errors   = [];

    DB::beginTransaction();

    try {
        $rowIndex = 1; // header is row 1

        while (($data = fgetcsv($handle)) !== false) {
            $rowIndex++;

            // skip blank lines
            if (!is_array($data) || count(array_filter($data, fn($x)=>trim((string)$x)!=='')) === 0) {
                continue;
            }

            // map row => associative by header
            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = $data[$i] ?? null;
            }

            $name     = trim((string)($row['name'] ?? ''));
            $email    = trim((string)($row['email'] ?? ''));
            $password = (string)($row['password'] ?? '');
            $roleIn   = (string)($row['role'] ?? '');

            if ($name === '' || $email === '') {
                $skipped++;
                $errors[] = "Row {$rowIndex}: name/email missing";
                continue;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                $errors[] = "Row {$rowIndex}: invalid email {$email}";
                continue;
            }

            // duplicate email
            if (DB::table('users')->where('email', $email)->whereNull('deleted_at')->exists()) {
                $skipped++;
                $errors[] = "Row {$rowIndex}: email already exists {$email}";
                continue;
            }

            $finalPassword = trim($password) !== '' ? $password : $defaultPassword;

            // role
            if (trim($roleIn) !== '') {
                [$role, $roleShort] = $this->normalizeRole($roleIn, null);
            } else {
                $role      = $defaultRole;
                $roleShort = $defaultRoleShort;
            }

            // uuid + slug
            do { $uuid = (string) Str::uuid(); }
            while (DB::table('users')->where('uuid', $uuid)->exists());

            $base = Str::slug($name);
            do { $slug = $base . '-' . Str::lower(Str::random(24)); }
            while (DB::table('users')->where('slug', $slug)->exists());

            DB::table('users')->insert([
                'uuid'            => $uuid,
                'name'            => $name,
                'email'           => $email,
                'password'        => Hash::make($finalPassword),
                'role'            => $role,
                'role_short_form' => $roleShort,
                'slug'            => $slug,
                'status'          => 'active',
                'remember_token'  => Str::random(60),
                'created_by'      => $actorId,
                'created_at'      => $now,
                'created_at_ip'   => $request->ip(),
                'updated_at'      => $now,
                'metadata'        => json_encode([
                    'timezone' => 'Asia/Kolkata',
                    'source'   => 'unzip_exam_api_import_csv',
                    'import'   => ['row' => $rowIndex],
                ], JSON_UNESCAPED_UNICODE),
            ]);

            $imported++;
        }

        fclose($handle);
        DB::commit();

        return response()->json([
            'status'  => 'success',
            'message' => 'CSV import completed',
            'meta'    => [
                'imported' => $imported,
                'skipped'  => $skipped,
                'errors'   => $errors,
            ],
        ]);
    } catch (\Throwable $e) {
        fclose($handle);
        DB::rollBack();
        Log::error('[UnzipExam Users Import CSV] failed', ['error' => $e->getMessage()]);

        return response()->json([
            'status'  => 'error',
            'message' => 'Import failed',
        ], 500);
    }
}



}
