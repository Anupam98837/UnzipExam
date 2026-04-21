<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
     * POST /api/auth/student-register
     * Body:
     * {
     *   "user_folder_id": 5,
     *   "email": "student@gmail.com",
     *   "phone_number": "9876543210",
     *   "password": "Student@123",
     *   "password_confirmation": "Student@123"
     * }
     *
     * ✅ Group = Folder (user_folder_id)
     * ✅ Registers STUDENT only
     */
    public function studentRegister(Request $request)
{
    Log::info('[Student Register] begin', ['ip' => $request->ip()]);

    // ✅ Normalize folder id (in case FE sends null/undefined)
    if ($request->has('user_folder_id')) {
        $raw = $request->input('user_folder_id');

        if ($raw === '' || $raw === null || $raw === 'null' || $raw === 'undefined') {
            $request->merge(['user_folder_id' => null]);
        } else {
            $request->merge(['user_folder_id' => (int)$raw]);
        }
    }

    $v = Validator::make($request->all(), [
        'user_folder_id'        => [
            'required',
            'integer',
            Rule::exists('user_folders', 'id')->whereNull('deleted_at'),
        ],

        // ✅ ADDED: name comes from FE
        'name'                 => 'required|string|max:255',

        'email'                 => 'required|email|max:255',
        'phone_number'          => 'required|string|max:32',
        'password'              => 'required|string|min:8|confirmed',
        // password_confirmation must be sent ✅
    ]);

    if ($v->fails()) {
        return response()->json([
            'status' => 'error',
            'errors' => $v->errors(),
        ], 422);
    }

    $data = $v->validated();

    // ✅ Duplicate checks (ignore soft-deleted)
    if (DB::table('users')->where('email', $data['email'])->whereNull('deleted_at')->exists()) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Email already exists',
        ], 422);
    }

    if (DB::table('users')->where('phone_number', $data['phone_number'])->whereNull('deleted_at')->exists()) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Phone number already exists',
        ], 422);
    }

    // ✅ UUID unique
    do { $uuid = (string) Str::uuid(); }
    while (DB::table('users')->where('uuid', $uuid)->exists());

    // ✅ Name from request (trim + clean)
    $name = trim($data['name']);

    // ✅ Unique slug
    $base = Str::slug($name ?: 'student');
    do { $slug = $base . '-' . Str::lower(Str::random(24)); }
    while (DB::table('users')->where('slug', $slug)->exists());

    // ✅ Force student role
    [$role, $roleShort] = $this->normalizeRole('student', null);

    $now = now();

    try {
        DB::table('users')->insert([
            'uuid'            => $uuid,
            'name'            => $name, // ✅ name from FE
            'email'           => $data['email'],
            'phone_number'    => $data['phone_number'],
            'password'        => Hash::make($data['password']),

            // ✅ Group = folder
            'user_folder_id'  => (int)$data['user_folder_id'],

            'role'            => $role,
            'role_short_form' => $roleShort,
            'slug'            => $slug,
            'status'          => 'active',
            'remember_token'  => Str::random(60),
            'created_by'      => null,
            'created_at'      => $now,
            'created_at_ip'   => $request->ip(),
            'updated_at'      => $now,
            'metadata'        => json_encode([
                'timezone' => 'Asia/Kolkata',
                'source'   => 'student_register_api',
            ], JSON_UNESCAPED_UNICODE),
        ]);

        $user = DB::table('users')->where('email', $data['email'])->first();

        // ✅ Auto login after register
        $expiresAt  = now()->addDays(30);
        $plainToken = $this->issueToken((int)$user->id, $expiresAt);

        return response()->json([
            'status'       => 'success',
            'message'      => 'Student registered successfully',
            'access_token' => $plainToken,
            'token_type'   => 'Bearer',
            'expires_at'   => $expiresAt->toIso8601String(),
            'user'         => $this->publicUserPayload($user),
        ], 201);

    } catch (\Throwable $e) {
        Log::error('[Student Register] failed', ['error' => $e->getMessage()]);
        return response()->json([
            'status'  => 'error',
            'message' => 'Student registration failed',
        ], 500);
    }
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
     * GET /api/auth/my-role
     * Header: Authorization: Bearer <token>
     *
     * Returns:
     * {
     *   "status": "success",
     *   "role": "admin",
     *   "role_short_form": "ADM",
     *   "user": { ... public payload ... }
     * }
     */
    public function getMyRole(Request $request)
    {
        $plain = $this->extractToken($request);
        if (!$plain) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Token not provided',
            ], 401);
        }

        $rec = DB::table('personal_access_tokens')
            ->where('token', hash('sha256', $plain))
            ->where('tokenable_type', self::USER_TYPE)
            ->first();

        if (!$rec) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid token',
            ], 401);
        }

        // Check expiry (same logic as authenticateToken)
        if (!empty($rec->expires_at) && Carbon::parse($rec->expires_at)->isPast()) {
            DB::table('personal_access_tokens')->where('id', $rec->id)->delete();

            return response()->json([
                'status'  => 'error',
                'message' => 'Token expired',
            ], 401);
        }

        $user = DB::table('users')
            ->where('id', $rec->tokenable_id)
            ->whereNull('deleted_at')
            ->first();

        if (!$user || (isset($user->status) && $user->status !== 'active')) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        return response()->json([
            'status'          => 'success',
            'role'            => (string)($user->role ?? ''),
            'role_short_form' => (string)($user->role_short_form ?? ''),
            'user'            => $this->publicUserPayload($user),
        ]);
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
    // ✅ Normalize folder id coming from FormData/JSON
    if ($request->has('user_folder_id')) {
        $raw = $request->input('user_folder_id');

        if ($raw === '' || $raw === null || $raw === 'null' || $raw === 'undefined') {
            $request->merge(['user_folder_id' => null]);
        } else {
            $request->merge(['user_folder_id' => (int)$raw]);
        }
    }

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

        // ✅ Folder assignment: exists + not deleted
        'user_folder_id' => [
            'sometimes',
            'nullable',
            'integer',
            Rule::exists('user_folders', 'id')->whereNull('deleted_at'),
        ],
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

            // ✅ Saves properly
            'user_folder_id'           => $data['user_folder_id'] ?? null,

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
     * POST /api/users/{uuid}/cv
     * multipart/form-data:
     *   - cv (file)
     *
     * Uploads CV to: /public/assets/images/usercv
     * Saves relative path in users.cv (e.g. /assets/images/usercv/cv_xxx.pdf)
     */
    public function uploadCvByUuid(Request $request, string $uuid)
    {
        // ✅ Validate file (CV)
        $v = Validator::make($request->all(), [
            'cv' => 'required|file|max:10240|mimes:pdf,doc,docx',
        ]);

        if ($v->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $v->errors(),
            ], 422);
        }

        // ✅ Find user by UUID (ignore soft deleted)
        $user = DB::table('users')
            ->where('uuid', $uuid)
            ->whereNull('deleted_at')
            ->first();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found',
            ], 404);
        }

        $file = $request->file('cv');
        if (!$file || !$file->isValid()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid CV upload',
            ], 422);
        }

        // ✅ Destination: public/assets/images/usercv
        $destDir = public_path('assets/images/usercv');
        if (!File::isDirectory($destDir)) {
            File::makeDirectory($destDir, 0755, true);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $filename = 'cv_' . date('Ymd_His') . '_' . Str::lower(Str::random(18)) . '.' . $ext;

        try {
            DB::beginTransaction();

            // Lock row (avoid race conditions)
            $locked = DB::table('users')
                ->where('id', $user->id)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if (!$locked) {
                DB::rollBack();
                return response()->json([
                    'status'  => 'error',
                    'message' => 'User not found',
                ], 404);
            }

            // ✅ Move file
            $file->move($destDir, $filename);

            // ✅ Store relative path in DB
            $relativePath = '/assets/images/usercv/' . $filename;

            // ✅ Delete old CV (if any) AFTER new file is saved
            $oldCv = $locked->cv ?? null;

            DB::table('users')->where('id', $locked->id)->update([
                'cv'           => $relativePath,
                'updated_at'   => now(),
            ]);

            DB::commit();

            // ✅ Remove previous CV file if it's managed by us
            if (!empty($oldCv)) {
                $this->deleteManagedCv($oldCv);
            }

            $fresh = DB::table('users')->where('id', $locked->id)->first();

            return response()->json([
                'status'  => 'success',
                'message' => 'CV uploaded successfully',
                'data'    => [
                    'user_id' => (int) $fresh->id,
                    'uuid'    => (string) ($fresh->uuid ?? ''),
                    'cv'      => $this->publicFileUrl($fresh->cv ?? null),
                    'cv_path' => (string) ($fresh->cv ?? ''),
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            // Cleanup uploaded file if it exists
            $maybeAbs = $destDir . DIRECTORY_SEPARATOR . $filename;
            if (File::exists($maybeAbs)) {
                @File::delete($maybeAbs);
            }

            Log::error('[Upload CV] failed', [
                'uuid'  => $uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to upload CV',
            ], 500);
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
            ->select('id','name','email','image','role','role_short_form','status','user_folder_id')
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
            ->select('id','uuid','cv','name','email','image','role','role_short_form','status','user_folder_id')
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
                'user_folder_id' => isset($user->user_folder_id) ? (int)$user->user_folder_id : null,
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
/**
 * PUT/PATCH /api/users/{id}
 * Partial update. If name changes, slug is regenerated.
 * ✅ Supports admin-driven password change using:
 *   - current_password (admin's current password)
 *   - new_password     (target user's new password)
 */
public function update(Request $request, int $id)
{
    // ✅ Normalize folder id coming from FormData/JSON
    if ($request->has('user_folder_id')) {
        $raw = $request->input('user_folder_id');

        if ($raw === '' || $raw === null || $raw === 'null' || $raw === 'undefined') {
            $request->merge(['user_folder_id' => null]);
        } else {
            $request->merge(['user_folder_id' => (int)$raw]);
        }
    }

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

        // ✅ Folder assignment: exists + not deleted
        'user_folder_id' => [
            'sometimes',
            'nullable',
            'integer',
            Rule::exists('user_folders', 'id')->whereNull('deleted_at'),
        ],

        // ✅ Password change (admin confirms their own password)
        'current_password' => 'sometimes|required_with:new_password|string',
        'new_password'     => 'sometimes|string|min:8|max:255',
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
        'whatsapp_number','address','status',
    ] as $key) {
        if (array_key_exists($key, $data)) {
            $updates[$key] = $data[$key];
        }
    }

    // ✅ Folder update (supports unassign: null)
    if (array_key_exists('user_folder_id', $data)) {
        $updates['user_folder_id'] = $data['user_folder_id']; // null allowed ✅
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

    /* ============================
     * ✅ PASSWORD UPDATE (ADMIN)
     * ============================ */
    if (!empty($data['new_password'] ?? null)) {

        // Role check (from middleware attributes OR fallback to logged in user)
        $actorRole = strtolower((string)($request->attributes->get('auth_role') ?? ''));
        if (!$actorRole && auth()->check()) {
            $actorRole = strtolower((string)(auth()->user()->role ?? ''));
        }

        if (!in_array($actorRole, ['admin','super_admin'], true)) {
            return response()->json(['status'=>'error','message'=>'You are not allowed to change user password'], 403);
        }

        // Get actor/admin id (from middleware attributes OR fallback auth)
        $actorId = (int)($request->attributes->get('auth_tokenable_id') ?? 0);
        if ($actorId <= 0 && auth()->check()) {
            $actorId = (int)auth()->id();
        }

        if ($actorId <= 0) {
            return response()->json(['status'=>'error','message'=>'Unauthorized'], 401);
        }

        // Verify admin current password
        $actor = DB::table('users')->where('id', $actorId)->whereNull('deleted_at')->first();
        if (!$actor || empty($actor->password)) {
            return response()->json(['status'=>'error','message'=>'Unauthorized'], 401);
        }

        $currentPw = (string)($data['current_password'] ?? '');
        if (!Hash::check($currentPw, $actor->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Current password is incorrect'
            ], 422);
        }

        // ✅ Update target user's password
        $updates['password'] = Hash::make((string)$data['new_password']);

        // Optional: track password change time if column exists
        if (Schema::hasColumn('users', 'password_changed_at')) {
            $updates['password_changed_at'] = now();
        }
    }

    if (empty($updates)) {
        return response()->json(['status'=>'error','message'=>'Nothing to update'], 400);
    }

    $updates['updated_at'] = now();

    DB::table('users')->where('id', $id)->update($updates);

    $fresh = DB::table('users')->where('id', $id)->first();

    return response()->json([
        'status'  => 'success',
        'message' => 'User updated',
        'user'    => $this->publicUserPayload($fresh),
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
        // If your saveProfileImage() cannot handle SVG, REMOVE svg from here.
        'image' => 'required|file|max:5120|mimes:jpg,jpeg,png,webp,gif,svg',
    ]);

    if ($v->fails()) {
        return response()->json(['status' => 'error', 'errors' => $v->errors()], 422);
    }

    $file = $request->file('image');
    if (!$file || !$file->isValid()) {
        return response()->json(['status' => 'error', 'message' => 'Invalid image upload'], 422);
    }

    $newUrl = $this->saveProfileImage($file);
    if ($newUrl === false) {
        // Common cause: saveProfileImage() can’t process SVG/GIF/WebP if it tries to resize.
        return response()->json(['status' => 'error', 'message' => 'Invalid image upload'], 422);
    }

    $oldUrl = null;

    try {
        DB::beginTransaction();

        // Lock row to avoid race updates
        $user = DB::table('users')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->first();

        if (!$user) {
            DB::rollBack();
            // Cleanup newly uploaded file since user doesn't exist
            $this->deleteManagedProfileImage($newUrl);

            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        $oldUrl = $user->image;

        DB::table('users')->where('id', $id)->update([
            'image'      => $newUrl,
            'updated_at' => now(),
        ]);

        DB::commit();
    } catch (\Throwable $e) {
        DB::rollBack();

        // If DB failed, remove newly uploaded file so you don't leave junk
        $this->deleteManagedProfileImage($newUrl);

        report($e);
        return response()->json([
            'status'  => 'error',
            'message' => 'Failed to update image. Please try again.',
        ], 500);
    }

    // Delete old image ONLY after DB commit succeeds
    if (!empty($oldUrl)) {
        $this->deleteManagedProfileImage($oldUrl);
    }

    $fresh = DB::table('users')->where('id', $id)->first();

    return response()->json([
        'status'  => 'success',
        'message' => 'Image updated',
        'user'    => $this->publicUserPayload($fresh),
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
        'image'           => $this->publicImageUrl($user->image ?? null),
        'status'          => (string)($user->status ?? ''),

        // ✅ ADD THIS
        'user_folder_id'  => isset($user->user_folder_id) ? (int)$user->user_folder_id : null,
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
    /** Save profile image into /public/UserProfileImage and return RELATIVE path (or false). */
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

    // ✅ store relative path (works on any port/domain)
    return '/UserProfileImage/' . $filename;
}
/** Convert stored image (absolute/relative) into current-host absolute URL. */
protected function publicImageUrl(?string $value): string
{
    if (empty($value)) return '';

    // If DB contains absolute url, extract only path
    $path = parse_url($value, PHP_URL_PATH);
    $path = $path ?: $value;

    // force leading slash
    $path = '/' . ltrim($path, '/');

    // ✅ uses current request host/port automatically
    return url($path);
}

    /** Delete a managed profile image if it resides in /Public/UserProfileImage. */
    protected function deleteManagedProfileImage(?string $url): void
{
    if (empty($url)) return;

    $path = parse_url($url, PHP_URL_PATH);
    $path = $path ?: $url; // if it's already relative
    $path = '/' . ltrim($path, '/');

    if (Str::startsWith($path, '/UserProfileImage/')) {
        $abs = public_path(ltrim($path, '/'));
        if (File::exists($abs)) @File::delete($abs);
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
 * ✅ CSV header required:
 *  name,email,password,role,folder_uuid
 *
 * ✅ folder_uuid is optional per row:
 *  - If provided: it is converted into user_folder_id (int FK)
 *  - If missing/blank: user_folder_id remains NULL
 *
 * Example row:
 *  John Doe,john@gmail.com,Pass@123,student,58f1040d-c0b3-4076-88c8-2edb5a5792f2
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

    // ✅ Read header
    $header = fgetcsv($handle);
    if (!$header || !is_array($header)) {
        fclose($handle);
        return response()->json(['status'=>'error','message'=>'CSV header missing'], 422);
    }

    // ✅ Normalize header keys (spaces -> underscore, lowercase)
    $header = array_map(function ($h) {
        $h = strtolower(trim((string)$h));
        $h = preg_replace('/\s+/', '_', $h);
        return $h;
    }, $header);

    // ✅ Required columns (minimum)
    foreach (['name','email'] as $req) {
        if (!in_array($req, $header, true)) {
            fclose($handle);
            return response()->json([
                'status'  => 'error',
                'message' => "CSV must contain '{$req}' column in header",
            ], 422);
        }
    }

    // ✅ Detect folder uuid column name (support both variants)
    $folderCol = null;
    if (in_array('folder_uuid', $header, true)) {
        $folderCol = 'folder_uuid';
    } elseif (in_array('user_folder_uuid', $header, true)) {
        $folderCol = 'user_folder_uuid';
    }

    // ✅ Preload folder_uuid => id map (fast lookup)
    // only non-deleted folders
    $folderMap = [];
    if ($folderCol) {
        $folderMap = DB::table('user_folders')
            ->whereNull('deleted_at')
            ->pluck('id', 'uuid')  // [uuid => id]
            ->toArray();
    }

    $actorId = $this->currentUserId($request);
    $now     = now();

    $imported = 0;
    $skipped  = 0;
    $errors   = [];

    DB::beginTransaction();

    try {
        $rowIndex = 1; // header = row 1

        while (($data = fgetcsv($handle)) !== false) {
            $rowIndex++;

            // ✅ skip blank lines
            if (!is_array($data) || count(array_filter($data, fn($x)=>trim((string)$x)!=='')) === 0) {
                continue;
            }

            // ✅ map row => associative by header
            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = $data[$i] ?? null;
            }

            $name     = trim((string)($row['name'] ?? ''));
            $email    = trim((string)($row['email'] ?? ''));
            $password = (string)($row['password'] ?? '');
            $roleIn   = (string)($row['role'] ?? '');

            // ✅ folder_uuid -> convert to folder id
            $folderUuid = null;
            $folderId   = null;

            if ($folderCol) {
                $folderUuid = trim((string)($row[$folderCol] ?? ''));

                // normalize nullish values
                if ($folderUuid === '' || in_array(strtolower($folderUuid), ['null','undefined','none'], true)) {
                    $folderUuid = null;
                }
            }

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

            // ✅ duplicate email (ignore soft-deleted)
            if (DB::table('users')->where('email', $email)->whereNull('deleted_at')->exists()) {
                $skipped++;
                $errors[] = "Row {$rowIndex}: email already exists {$email}";
                continue;
            }

            // ✅ Resolve folder_uuid -> id (FK)
            if ($folderUuid) {
                // if someone mistakenly puts numeric id inside folder_uuid column, accept it
                if (ctype_digit($folderUuid)) {
                    $folderId = (int)$folderUuid;

                    $exists = DB::table('user_folders')
                        ->where('id', $folderId)
                        ->whereNull('deleted_at')
                        ->exists();

                    if (!$exists) {
                        $skipped++;
                        $errors[] = "Row {$rowIndex}: folder id not found ({$folderUuid})";
                        continue;
                    }
                } else {
                    $folderId = $folderMap[$folderUuid] ?? null;
                    if (!$folderId) {
                        $skipped++;
                        $errors[] = "Row {$rowIndex}: invalid folder_uuid ({$folderUuid})";
                        continue;
                    }
                }
            }

            $finalPassword = trim($password) !== '' ? $password : $defaultPassword;

            // ✅ role
            if (trim($roleIn) !== '') {
                [$role, $roleShort] = $this->normalizeRole($roleIn, null);
            } else {
                $role      = $defaultRole;
                $roleShort = $defaultRoleShort;
            }

            // ✅ uuid + slug
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

                // ✅ HERE: folder_uuid converted to FK id
                'user_folder_id'  => $folderId,

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
                    'import'   => [
                        'row'         => $rowIndex,
                        'folder_uuid' => $folderUuid, // keeps audit info
                    ],
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
                'supports' => [
                    'folder_uuid_column' => $folderCol ? true : false,
                    'folder_uuid_to_id'  => true,
                ],
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

public function getProfile(Request $request)
{
    $userId = $this->currentUserId($request);

    if (!$userId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized'
        ], 401);
    }

    $user = DB::table('users')->where('id', $userId)->first();

    if (!$user) {
        return response()->json([
            'status' => 'error',
            'message' => 'User not found'
        ], 404);
    }

    // Role-based frontend permissions
    $isEditable = in_array($user->role, ['admin', 'super_admin','student','instructor']);

    $permissions = [
        'can_edit_profile'   => $isEditable,
        'can_change_image'   => $isEditable,
        'can_change_password'=> $isEditable,
        'can_view_profile'   => true
    ];

    // API endpoints to be used by frontend
    $endpoints = [
        'update_profile' => "/api/users/{$user->id}",
        'update_image'   => "/api/users/{$user->id}/image",
        'update_password'=> "/api/users/{$user->id}/password"
    ];

    return response()->json([
        'status' => 'success',
        'user' => [
            'id'              => $user->id,
            'name'            => $user->name,
            'email'           => $user->email,
            'phone_number'    => $user->phone_number,
            'address'         => $user->address,
            'role'            => $user->role,
            'role_short_form' => $user->role_short_form,
'image' => $this->publicImageUrl($user->image ?? null), // ✅ FIXED
            'status'          => $user->status,
        ],
        'permissions' => $permissions,
        'endpoints' => $endpoints
    ]);
}

    /**
     * POST /api/users/{id}/bubble-games/assign
     * Body: { bubble_game_id:int }
     * Admin/Super Admin only.
     */
    public function assignBubbleGame(Request $request, int $id)
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
            'bubble_game_id' => 'required|integer',
        ]);
        $gameId = (int) $validated['bubble_game_id'];

        // Confirm bubble game exists
        $game = DB::table('bubble_game')
            ->where('id', $gameId)
            ->whereNull('deleted_at')
            ->first();

        if (!$game) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Bubble game not found',
            ], 404);
        }

        $now        = now();
        $assignedBy = $this->currentUserId($request);

        // We want at most one row per (user, bubble_game), even if soft-deleted.
        $existing = DB::table('user_bubble_game_assignments')
            ->where('user_id', $id)
            ->where('bubble_game_id', $gameId)
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

            DB::table('user_bubble_game_assignments')
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

        DB::table('user_bubble_game_assignments')->insert([
            'uuid'            => (string) Str::uuid(),
            'user_id'         => $id,
            'bubble_game_id'  => $gameId,
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
            'message' => 'Bubble game assigned to user',
            'data'    => [
                'assignment_code' => $code,
            ],
        ], 201);
    }

    /**
     * POST /api/users/{id}/bubble-games/unassign
     * Body: { bubble_game_id:int }
     * Marks assignment as revoked (keeps row for audit).
     */
    public function unassignBubbleGame(Request $request, int $id)
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
            'bubble_game_id' => 'required|integer',
        ]);
        $gameId = (int) $validated['bubble_game_id'];

        $existing = DB::table('user_bubble_game_assignments')
            ->where('user_id', $id)
            ->where('bubble_game_id', $gameId)
            ->whereNull('deleted_at')
            ->first();

        if (!$existing) {
            // no-op, but not an error (helps keep FE simple)
            return response()->json([
                'status'  => 'noop',
                'message' => 'No active assignment found',
            ], 200);
        }

        DB::table('user_bubble_game_assignments')
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
 * GET /api/users/{id}/bubble-games
 * For ADMIN / SUPER_ADMIN.
 * Returns all bubble games + whether this user is assigned to each.
 */
public function userBubbleGames(Request $request, int $id)
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

    // All bubble games (excluding soft-deleted)
    $games = DB::table('bubble_game')
        ->whereNull('deleted_at')
        ->orderBy('title') // change to 'title' if that's your column
        ->get();

    // Existing assignments (any status, not hard deleted)
    $assignments = DB::table('user_bubble_game_assignments')
        ->where('user_id', $id)
        ->whereNull('deleted_at')
        ->get()
        ->keyBy('bubble_game_id');

    $data = $games->map(function ($g) use ($assignments) {
        $a = $assignments->get($g->id);

        return [
            'bubble_game_id'   => (int) $g->id,
            'bubble_game_uuid' => (string) ($g->uuid ?? ''),
            'bubble_game_name' => (string) (($g->game_name ?? $g->title ?? '') ),

            // keep these aligned with your UI columns
            'total_time'       => $g->total_time ?? null,       // duration/minutes
            'total_questions'  => $g->total_questions ?? null,
            'is_public'        => (string) ($g->is_public ?? 'no'),
            'status'           => (string) ($g->status ?? 'active'),

            'assigned'         => $a && $a->status === 'active',
            'assignment_code'  => $a && $a->status === 'active'
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
 * POST /api/users/{id}/door-games/assign
 * Body: { door_game_id:int }
 * Admin/Super Admin only.
 */
public function assignDoorGame(Request $request, int $id)
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
        'door_game_id' => 'required|integer',
    ]);
    $gameId = (int) $validated['door_game_id'];

    // Confirm door game exists
    $game = DB::table('door_game')
        ->where('id', $gameId)
        ->whereNull('deleted_at')
        ->first();

    if (!$game) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Door game not found',
        ], 404);
    }

    $now        = now();
    $assignedBy = $this->currentUserId($request);

    // One row per (user, door_game) even if soft-deleted
    $existing = DB::table('user_door_game_assignments')
        ->where('user_id', $id)
        ->where('door_game_id', $gameId)
        ->first();

    if ($existing) {
        // If already active and not deleted
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

        DB::table('user_door_game_assignments')
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

    DB::table('user_door_game_assignments')->insert([
        'uuid'            => (string) Str::uuid(),
        'user_id'         => $id,
        'door_game_id'    => $gameId,
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
        'message' => 'Door game assigned to user',
        'data'    => [
            'assignment_code' => $code,
        ],
    ], 201);
}

/**
 * POST /api/users/{id}/door-games/unassign
 * Body: { door_game_id:int }
 * Marks assignment as revoked (keeps row for audit).
 */
public function unassignDoorGame(Request $request, int $id)
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
        'door_game_id' => 'required|integer',
    ]);
    $gameId = (int) $validated['door_game_id'];

    $existing = DB::table('user_door_game_assignments')
        ->where('user_id', $id)
        ->where('door_game_id', $gameId)
        ->whereNull('deleted_at')
        ->first();

    if (!$existing) {
        // no-op
        return response()->json([
            'status'  => 'noop',
            'message' => 'No active assignment found',
        ], 200);
    }

    DB::table('user_door_game_assignments')
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
 * GET /api/users/{id}/door-games
 * For ADMIN / SUPER_ADMIN.
 * Returns all door games + whether this user is assigned to each.
 */
public function userDoorGames(Request $request, int $id)
{
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

    // All door games (excluding soft-deleted)
    $games = DB::table('door_game')
        ->whereNull('deleted_at')
        ->orderBy('title')
        ->get();

    // Existing assignments (not hard deleted)
    $assignments = DB::table('user_door_game_assignments')
        ->where('user_id', $id)
        ->whereNull('deleted_at')
        ->get()
        ->keyBy('door_game_id');

    $data = $games->map(function ($g) use ($assignments) {
        $a = $assignments->get($g->id);

        // Map to your modal columns (same as bubble)
        $timeSec = $g->time_limit_sec ?? null;
        $durationMin = is_numeric($timeSec) ? (int) ceil(((int)$timeSec) / 60) : null;

        return [
            'door_game_id'   => (int) $g->id,
            'door_game_uuid' => (string) ($g->uuid ?? ''),
            'door_game_name' => (string) ($g->title ?? ''),

            // UI column compatibility
            'total_time'      => $durationMin, // minutes (like bubble total_time)
            'total_questions' => isset($g->grid_dim) ? ((int)$g->grid_dim * (int)$g->grid_dim) : null,
            'is_public'       => (string) ($g->is_public ?? 'no'), // if you don't have, stays 'no'
            'status'          => (string) ($g->status ?? 'active'),

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
 * GET /api/users/{id}/path-games
 * For ADMIN / SUPER_ADMIN.
 * Returns all path games + whether this user is assigned to each.
 */
public function userPathGames(Request $request, int $id)
{
    // ✅ Only admin / super_admin
    // if ($resp = $this->requireRole($request, ['admin','super_admin'])) return $resp;

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

    // ✅ All path games (excluding soft-deleted)
    $games = DB::table('path_games')
        ->whereNull('deleted_at')
        ->orderBy('title')
        ->get();

    // ✅ Existing assignments
    $assignments = DB::table('user_path_game_assignments')
        ->where('user_id', $id)
        ->whereNull('deleted_at')
        ->get()
        ->keyBy('path_game_id');

    $data = $games->map(function ($g) use ($assignments) {
        $a = $assignments->get($g->id);

        // ✅ time_limit_sec -> minutes
        $timeSec = $g->time_limit_sec ?? null;
        $durationMin = is_numeric($timeSec) ? (int) ceil(((int)$timeSec) / 60) : null;

        // ✅ total_questions (if grid_dim exists in path_games)
        $totalQuestions = null;
        if (isset($g->grid_dim) && is_numeric($g->grid_dim)) {
            $totalQuestions = ((int)$g->grid_dim * (int)$g->grid_dim);
        }

        return [
            'path_game_id'   => (int) $g->id,
            'path_game_uuid' => (string) ($g->uuid ?? ''),
            'path_game_name' => (string) ($g->title ?? ''),

            // UI column compatibility (same keys style)
            'total_time'      => $durationMin,      // minutes
            'total_questions' => $totalQuestions,   // optional
            'is_public'       => (string) ($g->is_public ?? 'no'),
            'status'          => (string) ($g->status ?? 'active'),

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
 * ✅ Role Guard Helper
 * usage:
 *   if ($resp = $this->requireRole($request, ['admin','super_admin'])) return $resp;
 */
private function requireRole(Request $r, array $allowed)
{
    // ✅ read role from middleware attrs OR user model
    $role = (string) (
        $r->attributes->get('auth_role')
        ?? optional($r->user())->role
        ?? ''
    );

    $role = strtolower(trim($role));
    $allowedNorm = array_map(fn($x) => strtolower(trim($x)), $allowed);

    \Log::info('UserController.requireRole: check', [
        'role'    => $role,
        'allowed' => $allowedNorm,
        'actor'   => [
            'id'   => (int) ($r->attributes->get('auth_tokenable_id') ?? optional($r->user())->id ?? 0),
            'type' => (string) ($r->attributes->get('auth_tokenable_type') ?? (optional($r->user()) ? get_class($r->user()) : '')),
        ],
    ]);

    if (!$role) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Authentication required.',
        ], 401);
    }

    if (!in_array($role, $allowedNorm, true)) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Access denied.',
        ], 403);
    }

    return null; // ✅ allowed
}

/**
 * POST /api/users/{id}/path-games/unassign
 * Body: { path_game_id:int }
 * Marks assignment as revoked (keeps row for audit).
 */
public function unassignPathGame(Request $request, int $id)
{
    // ✅ Only admin/super_admin
    // if ($resp = $this->requireRole($request, ['admin','super_admin'])) return $resp;

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
        'path_game_id' => 'required|integer',
    ]);
    $gameId = (int) $validated['path_game_id'];

    $existing = DB::table('user_path_game_assignments')
        ->where('user_id', $id)
        ->where('path_game_id', $gameId)
        ->whereNull('deleted_at')
        ->first();

    if (!$existing) {
        return response()->json([
            'status'  => 'noop',
            'message' => 'No active assignment found',
        ], 200);
    }

    DB::table('user_path_game_assignments')
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
 * POST /api/users/{id}/path-games/assign
 * Body: { path_game_id:int }
 * Creates/activates assignment row (keeps row for audit).
 */
public function assignPathGame(Request $request, int $id)
{
    // ✅ Only admin/super_admin
//    if ($resp = $this->requireRole($request, ['admin','super_admin'])) return $resp;

    // ✅ Confirm user exists
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

    // ✅ Validate input
    $validated = $request->validate([
        'path_game_id' => 'required|integer',
    ]);

    $gameId = (int) $validated['path_game_id'];

    // ✅ Confirm game exists
    $game = DB::table('path_games')
        ->where('id', $gameId)
        ->whereNull('deleted_at')
        ->first();

    if (!$game) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Path game not found',
        ], 404);
    }

    // ✅ Check existing assignment
    $existing = DB::table('user_path_game_assignments')
        ->where('user_id', $id)
        ->where('path_game_id', $gameId)
        ->whereNull('deleted_at')
        ->first();

    // ✅ generate assignment code (like others)
    $assignmentCode = 'PG-' . strtoupper(\Illuminate\Support\Str::random(8));

    // ✅ If already ACTIVE => no-op
    if ($existing && strtolower($existing->status) === 'active') {
        return response()->json([
            'status'  => 'noop',
            'message' => 'Already assigned',
            'data'    => [
                'assignment_code' => $existing->assignment_code,
            ]
        ], 200);
    }

    // ✅ If exists but revoked => reactivate
    if ($existing) {
        DB::table('user_path_game_assignments')
            ->where('id', $existing->id)
            ->update([
                'status'          => 'active',
                'assignment_code' => $assignmentCode,
                'assigned_by'     => $request->attributes->get('auth_tokenable_id') ?? null,
                'assigned_at'     => now(),
                'updated_at'      => now(),
            ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Assignment re-activated',
            'data'    => [
                'assignment_code' => $assignmentCode,
            ]
        ], 200);
    }

    // ✅ Insert fresh assignment
    DB::table('user_path_game_assignments')->insert([
        'uuid'            => (string) \Illuminate\Support\Str::uuid(),
        'user_id'         => $id,
        'path_game_id'    => $gameId,
        'assignment_code' => $assignmentCode,
        'status'          => 'active',
        'assigned_by'     => $request->attributes->get('auth_tokenable_id') ?? null,
        'assigned_at'     => now(),
        'metadata'        => null,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    return response()->json([
        'status'  => 'success',
        'message' => 'Path Game assigned successfully',
        'data'    => [
            'assignment_code' => $assignmentCode,
        ]
    ], 200);
}

/** Convert stored file path (absolute/relative) into current-host absolute URL. */
protected function publicFileUrl(?string $value): string
{
    if (empty($value)) return '';

    $path = parse_url($value, PHP_URL_PATH);
    $path = $path ?: $value;
    $path = '/' . ltrim($path, '/');

    return url($path);
}

/** Delete a managed CV if it resides in /public/assets/images/usercv */
protected function deleteManagedCv(?string $pathOrUrl): void
{
    if (empty($pathOrUrl)) return;

    $path = parse_url($pathOrUrl, PHP_URL_PATH);
    $path = $path ?: $pathOrUrl;
    $path = '/' . ltrim($path, '/');

    if (Str::startsWith($path, '/assets/images/usercv/')) {
        $abs = public_path(ltrim($path, '/'));
        if (File::exists($abs)) {
            @File::delete($abs);
        }
    }
}
}
