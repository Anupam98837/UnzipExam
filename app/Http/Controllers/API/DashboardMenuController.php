<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class DashboardMenuController extends Controller
{
    /**
     * Normalize actor information from request (compatible with previous pattern)
     */
    private function actor(Request $r): array
    {
        return [
            'id'   => (int) ($r->attributes->get('auth_tokenable_id') ?? optional($r->user())->id ?? 0),
            'role' => (string) ($r->attributes->get('auth_role') ?? (optional($r->user())->role ?? '')),
            'type' => (string) ($r->attributes->get('auth_tokenable_type') ?? ($r->user() ? get_class($r->user()) : '')),
            'uuid' => (string) ($r->attributes->get('auth_tokenable_uuid') ?? (optional($r->user())->uuid ?? '')),
        ];
    }

    /* =========================================================
     | ✅ Activity Log (DB Facade) - robust to column differences
     * ========================================================= */
    private function diffChangedKeys(array $old, array $new, array $ignore = []): array
    {
        $ignoreFlip = array_flip($ignore);
        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
        $changed = [];

        foreach ($keys as $k) {
            if (isset($ignoreFlip[$k])) continue;

            $ov = $old[$k] ?? null;
            $nv = $new[$k] ?? null;

            // normalize common json/text differences
            if (is_string($ov) && is_string($nv)) {
                if (trim($ov) !== trim($nv)) $changed[] = $k;
            } else {
                if ($ov !== $nv) $changed[] = $k;
            }
        }

        return array_values($changed);
    }

    private function writeActivityLog(
        Request $request,
        string $action,
        int|string|null $entityId,
        ?array $old = null,
        ?array $new = null,
        ?string $note = null,
        ?string $moduleName = 'DashboardMenu',
        ?string $tableName = 'dashboard_menu'
    ): void {
        try {
            if (!Schema::hasTable('user_data_activity_log')) {
                return;
            }

            $a = $this->actor($request);
            $actorId = (int)($a['id'] ?? 0);
            $actorRole = (string)($a['role'] ?? '');

            $log = [];

            // uuid (optional)
            if (Schema::hasColumn('user_data_activity_log', 'uuid')) {
                $log['uuid'] = (string) Str::uuid();
            }

            // action / activity (supports multiple schemas)
            if (Schema::hasColumn('user_data_activity_log', 'action')) {
                $log['action'] = $action;
            }
            if (Schema::hasColumn('user_data_activity_log', 'activity')) {
                $log['activity'] = $action;
            }

            // entity fields
            if (Schema::hasColumn('user_data_activity_log', 'entity_type')) {
                $log['entity_type'] = $tableName ?: 'dashboard_menu';
            }
            if (Schema::hasColumn('user_data_activity_log', 'entity_id')) {
                $log['entity_id'] = is_null($entityId) ? null : (int)$entityId;
            }

            // alternative entity fields used in some schemas
            if (Schema::hasColumn('user_data_activity_log', 'table_name')) {
                $log['table_name'] = $tableName ?: 'dashboard_menu';
            }
            if (Schema::hasColumn('user_data_activity_log', 'record_id')) {
                $log['record_id'] = is_null($entityId) ? null : (int)$entityId;
            }
            if (Schema::hasColumn('user_data_activity_log', 'module')) {
                $log['module'] = $moduleName ?: 'DashboardMenu';
            }

            // actor fields
            if (Schema::hasColumn('user_data_activity_log', 'actor_id')) {
                $log['actor_id'] = $actorId ?: null;
            }
            if (Schema::hasColumn('user_data_activity_log', 'performed_by')) {
                $log['performed_by'] = $actorId ?: 0;
            }
            if (Schema::hasColumn('user_data_activity_log', 'performed_by_role')) {
                $log['performed_by_role'] = $actorRole ?: null;
            }
            if (Schema::hasColumn('user_data_activity_log', 'user_id')) {
                $log['user_id'] = $actorId ?: null;
            }

            // old/new
            if (Schema::hasColumn('user_data_activity_log', 'old_values')) {
                $log['old_values'] = $old ? json_encode($old, JSON_UNESCAPED_UNICODE) : null;
            }
            if (Schema::hasColumn('user_data_activity_log', 'new_values')) {
                $log['new_values'] = $new ? json_encode($new, JSON_UNESCAPED_UNICODE) : null;
            }

            // payload (some schemas)
            if (Schema::hasColumn('user_data_activity_log', 'payload')) {
                $log['payload'] = json_encode([
                    'old' => $old,
                    'new' => $new,
                    'note' => $note,
                    'table' => $tableName,
                    'module' => $moduleName,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            // changed_fields (optional)
            if (Schema::hasColumn('user_data_activity_log', 'changed_fields')) {
                $changed = [];
                if (is_array($old) && is_array($new)) {
                    $changed = $this->diffChangedKeys(
                        $old,
                        $new,
                        ['created_at','created_at_ip','created_by','updated_at','updated_at_ip','updated_by','deleted_at']
                    );
                }
                $log['changed_fields'] = $changed ? json_encode($changed, JSON_UNESCAPED_UNICODE) : null;
            }

            // ip + agent (supports multiple column names)
            $ip = $request->ip();
            $ua = substr((string)$request->userAgent(), 0, 1000);

            if (Schema::hasColumn('user_data_activity_log', 'ip')) {
                $log['ip'] = $ip;
            }
            if (Schema::hasColumn('user_data_activity_log', 'ip_address')) {
                $log['ip_address'] = $ip;
            }
            if (Schema::hasColumn('user_data_activity_log', 'user_agent')) {
                $log['user_agent'] = $ua;
            }

            // note column (optional)
            if ($note && Schema::hasColumn('user_data_activity_log', 'log_note')) {
                $log['log_note'] = $note;
            }

            // timestamps
            if (Schema::hasColumn('user_data_activity_log', 'created_at')) {
                $log['created_at'] = now();
            }
            if (Schema::hasColumn('user_data_activity_log', 'updated_at')) {
                $log['updated_at'] = now();
            }

            // if table requires minimal columns and we didn't set anything meaningful, skip
            if (empty($log)) return;

            DB::table('user_data_activity_log')->insert($log);

        } catch (\Throwable $e) {
            Log::error('DashboardMenu activity log failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Normalize an incoming href for storage:
     * - strip leading slashes
     * - strip a leading 'admin' or 'admin/module' or 'admin/dashboard-menu' prefix (case-insensitive)
     * - trim and limit to 255 chars
     * Returns the normalized suffix (no leading slash).
     */
    private function normalizeHrefForStorage($rawHref)
    {
        $rawHref = (string) ($rawHref ?? '');
        $normalized = preg_replace('#^/+#', '', trim($rawHref));
        // remove "admin", "admin/module", "admin/dashboard-menu"
        $normalized = preg_replace('#^admin(?:/(?:module|dashboard-menu))?/?#i', '', $normalized);
        return mb_substr($normalized, 0, 255);
    }

    /**
     * Convert stored href suffix into a response-friendly href:
     * - If empty -> return empty string
     * - If absolute http(s) URL -> return as-is
     * - Otherwise prepend a single leading slash so it's root-relative (e.g. "/coursesModule/manage")
     */
    private function normalizeHrefForResponse($href)
    {
        $href = (string) ($href ?? '');
        if ($href === '') return '';
        if (preg_match('#^https?://#i', $href)) {
            return $href;
        }
        return '/' . ltrim($href, '/');
    }

    /**
     * Build base query for dashboard menu with common filters
     */
    protected function baseQuery(Request $request, $includeDeleted = false)
    {
        $q = DB::table('dashboard_menu');
        if (! $includeDeleted) {
            $q->whereNull('deleted_at');
        }

        // search q -> name or description
        if ($request->filled('q')) {
            $term = '%' . trim($request->query('q')) . '%';
            $q->where(function ($sub) use ($term) {
                $sub->where('name', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        // status explicit
        if ($request->filled('status')) {
            $q->where('status', $request->query('status'));
        }

        // sort
        $sort = $request->query('sort', '-created_at');
        $dir = 'desc';
        $col = 'created_at';
        if (is_string($sort) && $sort !== '') {
            if ($sort[0] === '-') {
                $col = ltrim($sort, '-'); $dir = 'desc';
            } else { $col = $sort; $dir = 'asc'; }
        }

        // whitelist sortable columns
        $allowed = ['created_at','name','id'];
        if (! in_array($col, $allowed, true)) { $col = 'created_at'; }
        $q->orderBy($col, $dir);

        return $q;
    }

    /**
     * Format paginator->toArray style response similar to front-end expectations
     */
    protected function paginatorToArray($paginator)
    {
        return [
            'data' => $paginator->items(),
            'pagination' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }

    /**
     * Helper to build a safe select array including href only if column exists.
     */
    protected function moduleSelectColumns($includeDeletedAt = false)
    {
        $cols = [
            'id',
            'uuid',

            // ✅ REQUIRED for tree structure
            'parent_id',

            'name',
            'description',
            'status',
            'created_by',
            'created_at_ip',
            'created_at',
            'updated_at',
        ];

        // ✅ Put href after name
        if (Schema::hasColumn('dashboard_menu', 'href')) {
            $nameIndex = array_search('name', $cols, true);
            if ($nameIndex !== false) {
                array_splice($cols, $nameIndex + 1, 0, ['href']);
            } else {
                $cols[] = 'href';
            }
        }

        // ✅ Keep dropdown flag if exists
        if (Schema::hasColumn('dashboard_menu', 'is_dropdown_head')) {
            $pidIndex = array_search('parent_id', $cols, true);
            array_splice($cols, ($pidIndex !== false ? $pidIndex + 1 : 2), 0, ['is_dropdown_head']);
        }

        // ✅ Keep position if exists (helps order children properly)
        if (Schema::hasColumn('dashboard_menu', 'position')) {
            $after = array_search('is_dropdown_head', $cols, true);
            if ($after === false) $after = array_search('parent_id', $cols, true);
            array_splice($cols, ($after !== false ? $after + 1 : 2), 0, ['position']);
        }

        if ($includeDeletedAt) {
            $cols[] = 'deleted_at';
        }

        return $cols;
    }

    /**
     * List dashboard menu items (GET)
     */
    public function index(Request $request)
    {
        $perPage = max(1, min(200, (int) $request->query('per_page', 20)));
        $includePrivileges = filter_var($request->query('with_privileges', false), FILTER_VALIDATE_BOOLEAN);

        $query = $this->baseQuery($request, false);

        // default: exclude archived unless explicitly requested
        if ($request->filled('status')) {
            $status = $request->query('status');
            if ($status === 'archived') {
                $query->where('status', 'archived');
            } else {
                $query->where('status', $status);
            }
        } else {
            $query->where(function ($q) {
                $q->whereNull('status')
                  ->orWhere('status', '!=', 'archived');
            });
        }

        $selectCols = $this->moduleSelectColumns(false);
        $query = $query->select($selectCols);

        $paginator = $query->paginate($perPage);
        $out = $this->paginatorToArray($paginator);

        if (Schema::hasColumn('dashboard_menu', 'href') && !empty($out['data'])) {
            foreach ($out['data'] as &$m) {
                $m->href = $this->normalizeHrefForResponse($m->href ?? '');
            }
        }

        if ($includePrivileges && !empty($out['data'])) {
            $ids = collect($out['data'])->pluck('id')->filter()->all();

            $privs = DB::table('privileges')
                ->whereIn('module_id', $ids)
                ->whereNull('deleted_at')
                ->get()
                ->groupBy('module_id');

            foreach ($out['data'] as &$m) {
                $m->privileges = $privs->has($m->id) ? $privs[$m->id] : [];
            }
        }

        return response()->json($out);
    }

    public function archived(Request $request)
    {
        $request->merge(['status' => $request->query('status', 'archived')]);
        return $this->index($request);
    }

    public function bin(Request $request)
    {
        $perPage = max(1, min(200, (int) $request->query('per_page', 20)));
        $includePrivileges = filter_var($request->query('with_privileges', false), FILTER_VALIDATE_BOOLEAN);

        $query = $this->baseQuery($request, true)
            ->whereNotNull('dashboard_menu.deleted_at')
            ->select($this->moduleSelectColumns(true));

        $paginator = $query->paginate($perPage);
        $out = $this->paginatorToArray($paginator);

        if (Schema::hasColumn('dashboard_menu', 'href') && !empty($out['data'])) {
            foreach ($out['data'] as &$m) {
                $m->href = $this->normalizeHrefForResponse($m->href ?? '');
            }
        }

        if ($includePrivileges && !empty($out['data'])) {
            $ids = collect($out['data'])->pluck('id')->filter()->all();
            $privs = DB::table('privileges')
                ->whereIn('module_id', $ids)
                ->whereNull('deleted_at')
                ->get()
                ->groupBy('module_id');

            foreach ($out['data'] as &$m) {
                $m->privileges = $privs->has($m->id) ? $privs[$m->id] : [];
            }
        }

        return response()->json($out);
    }

    /**
     * Store a new dashboard menu item (POST) ✅ activity log added
     */
    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('dashboard_menu', 'id')->whereNull('deleted_at'),
            ],
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('dashboard_menu', 'name')->whereNull('deleted_at'),
            ],
            'href'            => 'nullable|string|max:255',
            'description'     => 'nullable|string',
            'status'          => 'nullable|string|max:20',
            'icon_class'      => 'nullable|string|max:120',
            'is_dropdown_head'=> 'nullable|in:0,1',
            'position'        => 'nullable|integer|min:0',
        ]);

        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $actor = $this->actor($request);
        $ip    = $request->ip();

        try {
            $id = DB::transaction(function () use ($request, $actor, $ip) {

                $hrefNorm = null;
                if ($request->has('href')) {
                    $tmp = $this->normalizeHrefForStorage($request->input('href'));
                    $hrefNorm = ($tmp === '') ? null : $tmp;
                }

                $parentId = $request->filled('parent_id') ? (int) $request->input('parent_id') : null;

                $position = null;
                if ($request->filled('position')) {
                    $position = (int) $request->input('position');
                } else {
                    $maxPos = DB::table('dashboard_menu')
                        ->whereNull('deleted_at')
                        ->where(function ($q) use ($parentId) {
                            if ($parentId === null) $q->whereNull('parent_id');
                            else $q->where('parent_id', $parentId);
                        })
                        ->max('position');

                    $position = is_null($maxPos) ? 0 : ((int)$maxPos + 1);
                }

                $payload = [
                    'uuid'            => (string) Str::uuid(),
                    'parent_id'       => $parentId,
                    'position'        => $position,

                    'name'            => trim((string) $request->input('name')),
                    'href'            => $hrefNorm,
                    'description'     => $request->input('description'),
                    'status'          => $request->input('status', 'Active'),
                    'icon_class'      => $request->input('icon_class'),
                    'is_dropdown_head'=> (int) $request->input('is_dropdown_head', 0),

                    'created_by'      => $actor['id'] ?: null,
                    'created_at_ip'   => $ip,
                    'updated_at_ip'   => $ip,

                    'created_at'      => now(),
                    'updated_at'      => now(),
                    'deleted_at'      => null,
                ];

                return DB::table('dashboard_menu')->insertGetId($payload);
            });

            $module = DB::table('dashboard_menu')->where('id', $id)->first();

            if ($module && property_exists($module, 'href')) {
                $module->href = $this->normalizeHrefForResponse($module->href);
            }

            // ✅ activity log
            $this->writeActivityLog(
                $request,
                'create',
                (int)$id,
                null,
                $module ? (array)$module : ['id' => (int)$id],
                'Created dashboard menu item'
            );

            return response()->json(['module' => $module], 201);

        } catch (Exception $e) {
            // log failed attempt (optional)
            $this->writeActivityLog(
                $request,
                'create_failed',
                null,
                null,
                ['error' => $e->getMessage()],
                'Failed to create dashboard menu item'
            );

            return response()->json([
                'message' => 'Could not create menu item',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Attempt to resolve dashboard menu item by id, uuid or slug
     */
    protected function resolveModule($identifier, $includeDeleted = false)
    {
        $query = DB::table('dashboard_menu');
        if (! $includeDeleted) $query->whereNull('deleted_at');

        if (ctype_digit((string)$identifier)) {
            $query->where('id', (int)$identifier);
        } elseif (Str::isUuid((string)$identifier)) {
            $query->where('uuid', (string)$identifier);
        } else {
            if (Schema::hasColumn('dashboard_menu', 'slug')) {
                $query->where('slug', (string)$identifier);
            } else {
                return null;
            }
        }

        return $query->first();
    }

    /**
     * Show single dashboard menu item (GET)
     */
    public function show(Request $request, $identifier)
    {
        $module = $this->resolveModule($identifier, false);
        if (! $module) return response()->json(['message' => 'Menu item not found'], 404);

        if (isset($module->href)) {
            $module->href = $this->normalizeHrefForResponse($module->href);
        }

        if (filter_var($request->query('with_privileges', false), FILTER_VALIDATE_BOOLEAN)) {
            $privileges = DB::table('privileges')
                ->where('module_id', $module->id)
                ->whereNull('deleted_at')
                ->get();
            $module->privileges = $privileges;
        }

        return response()->json(['module' => $module]);
    }

    /**
     * Full update (PATCH/PUT) ✅ activity log added
     */
    public function update(Request $request, $identifier)
    {
        $module = $this->resolveModule($identifier, false);
        if (! $module) {
            $this->writeActivityLog($request, 'update_failed', null, null, ['identifier' => $identifier], 'Menu item not found');
            return response()->json(['message' => 'Menu item not found'], 404);
        }

        $v = Validator::make($request->all(), [
            'parent_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('dashboard_menu', 'id')->whereNull('deleted_at'),
                function ($attr, $val, $fail) use ($module) {
                    if ($val !== null && (int)$val === (int)$module->id) {
                        $fail('parent_id cannot be same as the item id.');
                    }
                }
            ],
            'name' => [
                'sometimes', 'required', 'string', 'max:150',
                Rule::unique('dashboard_menu', 'name')
                    ->ignore($module->id)
                    ->whereNull('deleted_at'),
            ],
            'href'            => 'sometimes|nullable|string|max:255',
            'description'     => 'sometimes|nullable|string',
            'status'          => 'sometimes|nullable|string|max:20',
            'icon_class'      => 'sometimes|nullable|string|max:120',
            'is_dropdown_head'=> 'sometimes|nullable|in:0,1',
            'position'        => 'sometimes|integer|min:0',
        ]);

        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);

        $actor = $this->actor($request);
        $ip    = $request->ip();

        $oldRow = (array)$module;

        try {
            DB::transaction(function () use ($request, $module, $actor, $ip) {

                $update = [];

                if ($request->has('parent_id')) {
                    $update['parent_id'] = $request->filled('parent_id')
                        ? (int) $request->input('parent_id')
                        : null;
                }

                if ($request->has('name')) {
                    $update['name'] = trim((string) $request->input('name'));
                }

                if ($request->has('description')) {
                    $update['description'] = $request->input('description');
                }

                if ($request->has('status')) {
                    $update['status'] = $request->input('status');
                }

                if ($request->has('icon_class')) {
                    $update['icon_class'] = $request->input('icon_class');
                }

                if ($request->has('is_dropdown_head')) {
                    $update['is_dropdown_head'] = (int) $request->input('is_dropdown_head', 0);
                }

                if ($request->has('position')) {
                    $update['position'] = (int) $request->input('position', 0);
                }

                if ($request->has('href')) {
                    $tmp = $this->normalizeHrefForStorage($request->input('href'));
                    $update['href'] = ($tmp === '') ? null : $tmp;
                }

                if (empty($update)) {
                    throw new \RuntimeException('Nothing to update');
                }

                if (Schema::hasColumn('dashboard_menu', 'updated_by')) {
                    $update['updated_by'] = $actor['id'] ?: null;
                }
                if (Schema::hasColumn('dashboard_menu', 'updated_at_ip')) {
                    $update['updated_at_ip'] = $ip;
                }

                $update['updated_at'] = now();

                DB::table('dashboard_menu')
                    ->where('id', $module->id)
                    ->whereNull('deleted_at')
                    ->update($update);
            });

            $moduleNew = DB::table('dashboard_menu')->where('id', $module->id)->first();

            if ($moduleNew && property_exists($moduleNew, 'href')) {
                $moduleNew->href = $this->normalizeHrefForResponse($moduleNew->href);
            }

            // ✅ activity log
            $this->writeActivityLog(
                $request,
                'update',
                (int)$module->id,
                $oldRow,
                $moduleNew ? (array)$moduleNew : null,
                'Updated dashboard menu item'
            );

            return response()->json(['module' => $moduleNew]);

        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'Nothing to update') {
                $this->writeActivityLog($request, 'update_noop', (int)$module->id, $oldRow, $oldRow, 'Nothing to update');
                return response()->json(['message' => 'Nothing to update'], 400);
            }

            $this->writeActivityLog($request, 'update_failed', (int)$module->id, $oldRow, ['error' => $e->getMessage()], 'Failed to update dashboard menu item');
            return response()->json(['message' => 'Could not update menu item', 'error' => $e->getMessage()], 500);

        } catch (Exception $e) {
            $this->writeActivityLog($request, 'update_failed', (int)$module->id, $oldRow, ['error' => $e->getMessage()], 'Failed to update dashboard menu item');
            return response()->json(['message' => 'Could not update menu item', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Archive a dashboard menu item (PATCH/PUT) ✅ activity log added
     */
    public function archive(Request $request, $identifier)
    {
        $module = $this->resolveModule($identifier, false);
        if (! $module) {
            $this->writeActivityLog($request, 'archive_failed', null, null, ['identifier' => $identifier], 'Menu item not found');
            return response()->json(['message' => 'Menu item not found'], 404);
        }

        $oldRow = (array)$module;
        $actor = $this->actor($request);
        $ip = $request->ip();

        try {
            $upd = ['status' => 'archived', 'updated_at' => now()];
            if (Schema::hasColumn('dashboard_menu', 'updated_by')) $upd['updated_by'] = $actor['id'] ?: null;
            if (Schema::hasColumn('dashboard_menu', 'updated_at_ip')) $upd['updated_at_ip'] = $ip;

            DB::table('dashboard_menu')->where('id', $module->id)->update($upd);

            $newRowObj = DB::table('dashboard_menu')->where('id', $module->id)->first();
            $this->writeActivityLog($request, 'archive', (int)$module->id, $oldRow, $newRowObj ? (array)$newRowObj : null, 'Archived dashboard menu item');

            return response()->json(['message' => 'Menu item archived']);
        } catch (Exception $e) {
            $this->writeActivityLog($request, 'archive_failed', (int)$module->id, $oldRow, ['error' => $e->getMessage()], 'Could not archive dashboard menu item');
            return response()->json(['message' => 'Could not archive', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Unarchive (PATCH/PUT) ✅ activity log added
     */
    public function unarchive(Request $request, $identifier)
    {
        $module = $this->resolveModule($identifier, false);
        if (! $module) {
            $this->writeActivityLog($request, 'unarchive_failed', null, null, ['identifier' => $identifier], 'Menu item not found');
            return response()->json(['message' => 'Menu item not found'], 404);
        }

        $oldRow = (array)$module;
        $actor = $this->actor($request);
        $ip = $request->ip();

        try {
            $upd = ['status' => 'Active', 'updated_at' => now()];
            if (Schema::hasColumn('dashboard_menu', 'updated_by')) $upd['updated_by'] = $actor['id'] ?: null;
            if (Schema::hasColumn('dashboard_menu', 'updated_at_ip')) $upd['updated_at_ip'] = $ip;

            DB::table('dashboard_menu')->where('id', $module->id)->update($upd);

            $newRowObj = DB::table('dashboard_menu')->where('id', $module->id)->first();
            $this->writeActivityLog($request, 'unarchive', (int)$module->id, $oldRow, $newRowObj ? (array)$newRowObj : null, 'Unarchived dashboard menu item');

            return response()->json(['message' => 'Menu item unarchived']);
        } catch (Exception $e) {
            $this->writeActivityLog($request, 'unarchive_failed', (int)$module->id, $oldRow, ['error' => $e->getMessage()], 'Could not unarchive dashboard menu item');
            return response()->json(['message' => 'Could not unarchive', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Soft-delete menu item (DELETE) ✅ activity log added
     */
    public function destroy(Request $request, $identifier)
    {
        $module = $this->resolveModule($identifier, false);
        if (! $module) {
            $this->writeActivityLog($request, 'delete_failed', null, null, ['identifier' => $identifier], 'Menu item not found or already deleted');
            return response()->json(['message' => 'Menu item not found or already deleted'], 404);
        }

        $oldRow = (array)$module;
        $actor = $this->actor($request);
        $ip = $request->ip();

        try {
            DB::transaction(function () use ($module, $actor, $ip) {
                $now = now();

                $upd = [
                    'deleted_at' => $now,
                    'updated_at' => $now,
                ];
                if (Schema::hasColumn('dashboard_menu', 'updated_by')) $upd['updated_by'] = $actor['id'] ?: null;
                if (Schema::hasColumn('dashboard_menu', 'updated_at_ip')) $upd['updated_at_ip'] = $ip;

                DB::table('dashboard_menu')
                    ->where('id', $module->id)
                    ->update($upd);

                if (Schema::hasTable('page_privilege')) {
                    DB::table('page_privilege')
                        ->where('dashboard_menu_id', $module->id)
                        ->whereNull('deleted_at')
                        ->update([
                            'deleted_at' => $now,
                            'updated_at' => $now,
                        ]);
                }
            });

            $newRowObj = DB::table('dashboard_menu')->where('id', $module->id)->first();
            $this->writeActivityLog($request, 'delete', (int)$module->id, $oldRow, $newRowObj ? (array)$newRowObj : null, 'Soft-deleted dashboard menu item');

            return response()->json(['message' => 'Menu item soft-deleted']);
        } catch (Exception $e) {
            $this->writeActivityLog($request, 'delete_failed', (int)$module->id, $oldRow, ['error' => $e->getMessage()], 'Could not delete dashboard menu item');
            return response()->json([
                'message' => 'Could not delete menu item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore soft-deleted item (PATCH) ✅ activity log added
     */
    public function restore(Request $request, $identifier)
    {
        $module = $this->resolveModule($identifier, true);
        if (! $module || $module->deleted_at === null) {
            $this->writeActivityLog($request, 'restore_failed', null, null, ['identifier' => $identifier], 'Menu item not found or not deleted');
            return response()->json(['message' => 'Menu item not found or not deleted'], 404);
        }

        $oldRow = (array)$module;
        $actor = $this->actor($request);
        $ip = $request->ip();

        try {
            DB::transaction(function () use ($module, $actor, $ip) {
                $now = now();

                $upd = [
                    'deleted_at' => null,
                    'updated_at' => $now,
                ];
                if (Schema::hasColumn('dashboard_menu', 'updated_by')) $upd['updated_by'] = $actor['id'] ?: null;
                if (Schema::hasColumn('dashboard_menu', 'updated_at_ip')) $upd['updated_at_ip'] = $ip;

                DB::table('dashboard_menu')
                    ->where('id', $module->id)
                    ->update($upd);

                if (Schema::hasTable('page_privilege')) {
                    DB::table('page_privilege')
                        ->where('dashboard_menu_id', $module->id)
                        ->whereNotNull('deleted_at')
                        ->update([
                            'deleted_at' => null,
                            'updated_at' => $now,
                        ]);
                }
            });

            $moduleNew = DB::table('dashboard_menu')->where('id', $module->id)->first();
            if ($moduleNew && isset($moduleNew->href)) {
                $moduleNew->href = $this->normalizeHrefForResponse($moduleNew->href);
            }

            $this->writeActivityLog($request, 'restore', (int)$module->id, $oldRow, $moduleNew ? (array)$moduleNew : null, 'Restored dashboard menu item');

            return response()->json(['module' => $moduleNew, 'message' => 'Menu item restored']);
        } catch (Exception $e) {
            $this->writeActivityLog($request, 'restore_failed', (int)$module->id, $oldRow, ['error' => $e->getMessage()], 'Could not restore dashboard menu item');
            return response()->json([
                'message' => 'Could not restore menu item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hard delete (DELETE) ✅ activity log added
     */
    public function forceDelete(Request $request, $identifier)
    {
        $module = $this->resolveModule($identifier, true);
        if (! $module) {
            $this->writeActivityLog($request, 'force_delete_failed', null, null, ['identifier' => $identifier], 'Menu item not found');
            return response()->json(['message' => 'Menu item not found'], 404);
        }

        $oldRow = (array)$module;

        try {
            DB::transaction(function () use ($module) {
                if (Schema::hasTable('page_privilege')) {
                    DB::table('page_privilege')
                        ->where('dashboard_menu_id', $module->id)
                        ->delete();
                }

                DB::table('dashboard_menu')
                    ->where('id', $module->id)
                    ->delete();
            });

            $this->writeActivityLog($request, 'force_delete', (int)$module->id, $oldRow, null, 'Permanently deleted dashboard menu item');

            return response()->json(['message' => 'Menu item permanently deleted']);
        } catch (Exception $e) {
            $this->writeActivityLog($request, 'force_delete_failed', (int)$module->id, $oldRow, ['error' => $e->getMessage()], 'Could not permanently delete dashboard menu item');
            return response()->json([
                'message' => 'Could not permanently delete menu item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reorder menu items — expects { ids: [id1,id2,id3,...] }
     * (POST/PATCH) ✅ activity log added
     */
    public function reorder(Request $request)
    {
        $v = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|min:1',
        ]);
        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);

        $ids = $request->input('ids');

        // capture old ordering for log
        $oldMap = [];
        try {
            if (Schema::hasColumn('dashboard_menu', 'order_no')) {
                $rows = DB::table('dashboard_menu')->select('id', 'order_no')->whereIn('id', $ids)->get();
                foreach ($rows as $r) $oldMap[(int)$r->id] = (int)($r->order_no ?? 0);
            }
        } catch (\Throwable $e) {
            $oldMap = [];
        }

        try {
            DB::transaction(function () use ($ids) {
                foreach ($ids as $idx => $id) {
                    if (Schema::hasColumn('dashboard_menu', 'order_no')) {
                        DB::table('dashboard_menu')->where('id', $id)->update(['order_no' => $idx, 'updated_at' => now()]);
                    }
                }
            });

            // new ordering map
            $newMap = [];
            foreach ($ids as $idx => $id) $newMap[(int)$id] = (int)$idx;

            $this->writeActivityLog(
                $request,
                'reorder',
                null,
                ['order_no_map' => $oldMap],
                ['order_no_map' => $newMap],
                'Reordered dashboard menu items'
            );

            return response()->json(['message' => 'Order updated']);
        } catch (Exception $e) {
            $this->writeActivityLog(
                $request,
                'reorder_failed',
                null,
                ['order_no_map' => $oldMap],
                ['error' => $e->getMessage(), 'ids' => $ids],
                'Could not update order'
            );
            return response()->json(['message' => 'Could not update order', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Return dashboard menu as TREE with privileges attached ONLY to children (GET)
     */
    public function allWithPrivileges(Request $request)
    {
        $modules = DB::table('dashboard_menu')
            ->whereNull('deleted_at')
            ->select($this->moduleSelectColumns(false))
            ->orderBy('position', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($modules->isEmpty()) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $ids = $modules->pluck('id')->all();

        $privilegesByMenuId = DB::table('page_privilege')
            ->whereIn('dashboard_menu_id', $ids)
            ->whereNull('deleted_at')
            ->select(
                'id',
                'uuid',
                'dashboard_menu_id',
                DB::raw('action as name'),
                'action',
                'description',
                'created_at'
            )
            ->orderBy('action', 'asc')
            ->get()
            ->groupBy('dashboard_menu_id');

        $byId = [];
        $byParent = [];

        foreach ($modules as $m) {
            $m->href = $m->href ? $this->normalizeHrefForResponse($m->href) : '';
            $m->children = [];
            $m->privileges = [];

            $byId[$m->id] = $m;
            $byParent[$m->parent_id][] = $m->id;
        }

        $makeTree = function ($pid) use (&$makeTree, &$byParent, &$byId, $privilegesByMenuId) {
            $nodes = [];

            foreach ($byParent[$pid] ?? [] as $id) {
                $node = $byId[$id];

                $node->children = $makeTree($node->id);

                if ((int)($node->is_dropdown_head ?? 0) === 0) {
                    $node->privileges = $privilegesByMenuId[$node->id] ?? collect([]);
                }

                $nodes[] = $node;
            }

            return $nodes;
        };

        return response()->json([
            'success' => true,
            'data' => array_merge(
                $makeTree(null),
                $makeTree(0)
            ),
        ]);
    }

    public function tree(Request $r)
    {
        $onlyActive = (int) $r->query('only_active', 0) === 1;

        $q = DB::table('dashboard_menu')
            ->whereNull('deleted_at');

        if ($onlyActive) {
            $q->whereRaw('LOWER(status) = ?', ['active']);
        }

        $rows = $q->orderBy('position', 'asc')
                  ->orderBy('id', 'asc')
                  ->get();

        $byParent = [];
        foreach ($rows as $row) {
            $pid = $row->parent_id ?? 0;
            $byParent[$pid][] = $row;
        }

        $make = function ($pid) use (&$make, &$byParent) {
            $nodes = $byParent[$pid] ?? [];
            foreach ($nodes as $n) {
                $n->children = $make($n->id);
            }
            return $nodes;
        };

        return response()->json([
            'success' => true,
            'data' => $make(0),
        ]);
    }
}
