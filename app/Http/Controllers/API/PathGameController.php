<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PathGameController extends Controller
{
    /**
 * Resolve current actor (id + role) from middleware attributes or auth user
 * Works with your CheckRole middleware attributes.
 */
private function actor(Request $r): array
{
    $id = (int) (
        $r->attributes->get('auth_tokenable_id')
        ?? optional($r->user())->id
        ?? 0
    );

    $role = (string) (
        $r->attributes->get('auth_role')
        ?? optional($r->user())->role
        ?? ''
    );

    $type = (string) (
        $r->attributes->get('auth_tokenable_type')
        ?? (optional($r->user()) ? get_class($r->user()) : '')
    );

    // ✅ optional debug log (same style you already use)
    \Log::debug('PathGame.actor', [
        'role' => $role,
        'type' => $type,
        'id'   => $id,
    ]);

    return [
        'id'   => $id,
        'role' => $role,
        'type' => $type,
    ];
}

    /* ===========================
     | Helpers
     =========================== */

    private function actorId(Request $request): ?int
    {
        // if your middleware sets auth info:
        $id = $request->attributes->get('auth_user_id')
           ?? $request->attributes->get('auth_id')
           ?? $request->attributes->get('auth_tokenable_id');

        if ($id) return (int) $id;

        // fallback if using Auth
        return $request->user()?->id;
    }

    private function findGame(string $idOrUuid)
    {
        return DB::table('path_games')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($idOrUuid) {
                $q->where('id', $idOrUuid)
                  ->orWhere('uuid', $idOrUuid);
            })
            ->first();
    }

    private function validateGridJson(array $data, callable $fail): void
{
    $gridDim = (int)($data['grid_dim'] ?? 3);
    $expectedGrids = $gridDim * $gridDim;

    $grid = $data['grid_json'] ?? null;

    if (!is_array($grid)) {
        $fail("grid_json must be a valid JSON object/array.");
        return;
    }

    // ✅ NEW FORMAT: grid_json.grids[]
    if (!isset($grid['grids']) || !is_array($grid['grids'])) {
        $fail("grid_json.grids is required and must be an array.");
        return;
    }

    if (count($grid['grids']) !== $expectedGrids) {
        $fail("grid_json.grids must contain exactly {$expectedGrids} grids for grid_dim={$gridDim}.");
        return;
    }

    $allowedArrows = ['L', 'R', 'T', 'B']; // ✅ NEW arrows
    $allowedRotation = ['cw', 'ccw'];

    foreach ($grid['grids'] as $gIndex => $g) {

        if (!is_array($g)) {
            $fail("Each grid must be an object/array. Problem at grids[$gIndex].");
            return;
        }

        // ✅ grid_index for big tile (1..N*N)
        if (!isset($g['grid_index']) || !is_numeric($g['grid_index'])) {
            $fail("grids[$gIndex].grid_index is required and must be a number.");
            return;
        }

        $gridIndex = (int)$g['grid_index'];
        if ($gridIndex < 1 || $gridIndex > $expectedGrids) {
            $fail("grids[$gIndex].grid_index must be between 1 and {$expectedGrids}.");
            return;
        }

        // ✅ rotatable boolean
        $rotatable = isset($g['rotatable'])
            ? filter_var($g['rotatable'], FILTER_VALIDATE_BOOLEAN)
            : false;

        // ✅ rotation_type if rotatable
        if ($rotatable) {
            $rt = strtolower((string)($g['rotation_type'] ?? ''));
            if (!in_array($rt, $allowedRotation, true)) {
                $fail("grids[$gIndex].rotation_type must be cw or ccw when rotatable=true.");
                return;
            }
        }

        // ✅ mini tiles inside each grid => exactly 9
        if (!isset($g['tiles']) || !is_array($g['tiles'])) {
            $fail("grids[$gIndex].tiles is required and must be an array of 9 mini cells.");
            return;
        }

        if (count($g['tiles']) !== 9) {
            $fail("grids[$gIndex].tiles must contain exactly 9 mini cells.");
            return;
        }

        foreach ($g['tiles'] as $tIndex => $tile) {
            if (!is_array($tile)) {
                $fail("Each mini cell must be an object/array. Problem at grids[$gIndex].tiles[$tIndex].");
                return;
            }

            // ✅ index of mini cell (1..9)
            if (!isset($tile['index']) || !is_numeric($tile['index'])) {
                $fail("grids[$gIndex].tiles[$tIndex].index is required and must be a number.");
                return;
            }

            $miniIndex = (int)$tile['index'];
            if ($miniIndex < 1 || $miniIndex > 9) {
                $fail("grids[$gIndex].tiles[$tIndex].index must be between 1 and 9.");
                return;
            }

            // ✅ arrow can be EMPTY "" (toggle OFF)
            $arrow = strtoupper((string)($tile['arrow'] ?? ''));

            if ($arrow !== '' && !in_array($arrow, $allowedArrows, true)) {
                $fail("grids[$gIndex].tiles[$tIndex].arrow must be one of: L, R, T, B (or empty).");
                return;
            }
        }
    }
}

    private function normalizeGridJson(&$payload): void
    {
        // If grid_json comes as string from form-data, decode it
        if (isset($payload['grid_json']) && is_string($payload['grid_json'])) {
            $decoded = json_decode($payload['grid_json'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $payload['grid_json'] = $decoded;
            }
        }
    }

    private function mapGameRow($row)
    {
        if (!$row) return null;

        // decode JSON column for clean response
        $row->grid_json = is_string($row->grid_json)
            ? json_decode($row->grid_json, true)
            : $row->grid_json;

        $row->rotation_enabled = (int)$row->rotation_enabled === 1;

        return $row;
    }

    /* ===========================
     | Activity Logs + Notification (POST/PUT/PATCH/DELETE only)
     | Non-blocking: will never break your API if tables are missing
     =========================== */

    private function toPlainArray($row): array
    {
        if (!$row) return [];
        $arr = json_decode(json_encode($row), true);
        return is_array($arr) ? $arr : [];
    }

    private function tableColumnsCached(string $table): array
    {
        static $cache = [];
        if (!array_key_exists($table, $cache)) {
            $cache[$table] = Schema::hasTable($table) ? Schema::getColumnListing($table) : [];
        }
        return $cache[$table];
    }

    private function safeInsertByAvailableColumns(string $table, array $data): void
    {
        if (!Schema::hasTable($table)) return;

        $cols = $this->tableColumnsCached($table);
        if (empty($cols)) return;

        $filtered = [];
        foreach ($data as $k => $v) {
            if (in_array($k, $cols, true)) $filtered[$k] = $v;
        }

        if (!empty($filtered)) {
            DB::table($table)->insert($filtered);
        }
    }

    private function writeActivityLog(Request $r, string $action, array $meta = []): void
    {
        try {
            $actor = $this->actor($r);

            $performedBy = (int) ($actor['id'] ?? 0);
            if ($performedBy <= 0) {
                $performedBy = (int) ($this->actorId($r) ?? 0);
            }

            $now = Carbon::now();

            $payload = [
                'uuid'              => (string) Str::uuid(),
                'performed_by'      => $performedBy ?: null,
                'performed_by_role' => (string) ($actor['role'] ?? ''),
                'performed_by_type' => (string) ($actor['type'] ?? ''),
                'module'            => 'PathGame',
                'module_name'       => 'PathGame',
                'action'            => $action,
                'event'             => $action,
                'method'            => strtoupper((string) $r->method()),
                'request_method'    => strtoupper((string) $r->method()),
                'url'               => (string) $r->fullUrl(),
                'request_url'       => (string) $r->fullUrl(),
                'ip'                => (string) $r->ip(),
                'user_agent'        => substr((string) $r->userAgent(), 0, 500),
                'entity_table'      => 'path_games',
                'entity_id'         => $meta['entity_id'] ?? null,
                'entity_uuid'       => $meta['entity_uuid'] ?? null,
                'record_id'         => $meta['entity_id'] ?? null,
                'record_uuid'       => $meta['entity_uuid'] ?? null,
                'changed_fields'    => isset($meta['changed_fields']) ? json_encode($meta['changed_fields']) : null,
                'old_values'        => isset($meta['old_values']) ? json_encode($meta['old_values']) : null,
                'new_values'        => isset($meta['new_values']) ? json_encode($meta['new_values']) : null,
                'log_note'          => $meta['log_note'] ?? null,
                'note'              => $meta['log_note'] ?? null,
                'created_at'        => $now,
                'updated_at'        => $now,
            ];

            if (Schema::hasTable('user_data_activity_log')) {
                $this->safeInsertByAvailableColumns('user_data_activity_log', $payload);
            } elseif (Schema::hasTable('activity_logs')) {
                $this->safeInsertByAvailableColumns('activity_logs', $payload);
            } elseif (Schema::hasTable('activity_log')) {
                $this->safeInsertByAvailableColumns('activity_log', $payload);
            } else {
                // fallback: file log only
                Log::info('ActivityLog(PathGame)', $payload);
            }

        } catch (\Throwable $e) {
            // never break API
            Log::debug('ActivityLog(PathGame) failed', ['error' => $e->getMessage()]);
        }
    }

    private function writeNotificationLog(Request $r, string $action, array $meta = []): void
    {
        try {
            $actor = $this->actor($r);

            $userId = (int) ($actor['id'] ?? 0);
            if ($userId <= 0) {
                $userId = (int) ($this->actorId($r) ?? 0);
            }

            $now = Carbon::now();

            $title = $meta['title'] ?? ('Path Game ' . ucfirst($action));
            $message = $meta['message'] ?? ($meta['log_note'] ?? '');

            $payload = [
                'uuid'        => (string) Str::uuid(),
                'user_id'     => $userId ?: null,
                'title'       => $title,
                'message'     => $message,
                'body'        => $message,
                'type'        => 'path_game',
                'module'      => 'PathGame',
                'action'      => $action,
                'data'        => isset($meta['data']) ? json_encode($meta['data']) : null,
                'meta'        => isset($meta['data']) ? json_encode($meta['data']) : null,
                'entity_id'   => $meta['entity_id'] ?? null,
                'entity_uuid' => $meta['entity_uuid'] ?? null,
                'ip'          => (string) $r->ip(),
                'user_agent'  => substr((string) $r->userAgent(), 0, 500),
                'created_at'  => $now,
                'updated_at'  => $now,
            ];

            if (Schema::hasTable('notification_logs')) {
                $this->safeInsertByAvailableColumns('notification_logs', $payload);
            } elseif (Schema::hasTable('notifications')) {
                $this->safeInsertByAvailableColumns('notifications', $payload);
            } elseif (Schema::hasTable('user_notifications')) {
                $this->safeInsertByAvailableColumns('user_notifications', $payload);
            } else {
                // fallback: file log only
                Log::info('Notification(PathGame)', $payload);
            }

        } catch (\Throwable $e) {
            // never break API
            Log::debug('Notification(PathGame) failed', ['error' => $e->getMessage()]);
        }
    }

    /* ===========================
     | CRUD
     =========================== */

    // GET /api/path-games
    public function index(Request $request)
    {
        $q       = trim((string) $request->query('q', ''));
        $status  = $request->query('status', null); // active/inactive
        $perPage = (int) $request->query('per_page', 20);
        $page    = (int) $request->query('page', 1);

        $perPage = max(5, min($perPage, 100));
        $page = max(1, $page);

        $query = DB::table('path_games')
            ->whereNull('deleted_at')
            ->orderByDesc('id');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                  ->orWhere('uuid', 'like', "%{$q}%");
            });
        }

        if (!is_null($status) && $status !== '') {
            $query->where('status', $status);
        }

        $total = (clone $query)->count();
        $items = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

        $items = $items->map(fn($r) => $this->mapGameRow($r));

        return response()->json([
            'success' => true,
            'message' => 'Path games fetched successfully.',
            'data' => [
                'items' => $items,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => (int) ceil($total / $perPage),
                ]
            ]
        ]);
    }

    // GET /api/path-games/{idOrUuid}
    public function show(string $idOrUuid)
    {
        $game = $this->findGame($idOrUuid);

        if (!$game) {
            return response()->json([
                'success' => false,
                'message' => 'Path game not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Path game fetched successfully.',
            'data' => $this->mapGameRow($game)
        ]);
    }

    // POST /api/path-games
    public function store(Request $request)
{
    $payload = $request->all();
    $this->normalizeGridJson($payload);

    $validator = Validator::make($payload, [
        'title' => ['required', 'string', 'max:180'],
        'description' => ['nullable', 'string'],
        'instructions_html' => ['nullable', 'string'],

        'show_solution_after' => ['required', 'in:never,after_each,after_finish'],

        'grid_dim' => ['required', 'integer', 'min:1', 'max:6'],
        'grid_json' => ['required', 'array'],

        'time_limit_sec' => ['required', 'integer', 'min:1', 'max:600'],
        'max_attempts' => ['required', 'integer', 'min:1', 'max:50'],

        // ✅ NOT required from frontend now
        'rotation_enabled' => ['nullable'],
        'rotation_mode' => ['nullable', 'in:cw,ccw,both'],

'status' => ['nullable', 'in:active,inactive,archived'],
    ]);

    // ✅ validate grid_json using your new format (grid_json.grids[])
    $validator->after(function ($v) use ($payload) {
        $this->validateGridJson($payload, function ($msg) use ($v) {
            $v->errors()->add('grid_json', $msg);
        });
    });

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $validator->errors()
        ], 422);
    }

    $actorId = $this->actorId($request);
    $now = Carbon::now();

    // ✅ derive rotation_enabled & rotation_mode from grid_json.grids[]
    $rotAny = false;
    $rotModes = [];

    $grids = $payload['grid_json']['grids'] ?? [];
    if (is_array($grids)) {
        foreach ($grids as $g) {
            $rotatable = isset($g['rotatable'])
                ? filter_var($g['rotatable'], FILTER_VALIDATE_BOOLEAN)
                : false;

            if ($rotatable) {
                $rotAny = true;

                $rt = strtolower((string)($g['rotation_type'] ?? ''));
                if (in_array($rt, ['cw', 'ccw'], true)) {
                    $rotModes[] = $rt;
                }
            }
        }
    }

    $rotModes = array_values(array_unique(array_filter($rotModes)));
    $rotMode = 'both'; // default
    if (count($rotModes) === 1) {
        $rotMode = $rotModes[0]; // cw OR ccw
    }

    DB::beginTransaction();
    try {
        $uuid = (string) Str::uuid();

        $insertId = DB::table('path_games')->insertGetId([
            'uuid' => $uuid,

            'title' => $payload['title'],
            'description' => $payload['description'] ?? null,
            'instructions_html' => $payload['instructions_html'] ?? null,

            'show_solution_after' => $payload['show_solution_after'],

            'grid_dim' => (int) $payload['grid_dim'],
            'grid_json' => json_encode($payload['grid_json']),

            'time_limit_sec' => (int) $payload['time_limit_sec'],
            'max_attempts' => (int) $payload['max_attempts'],

            // ✅ auto-filled from grid_json
            'rotation_enabled' => $rotAny ? 1 : 0,
            'rotation_mode' => $rotMode,

            'status' => $payload['status'] ?? 'active',

            'created_by' => $actorId,

            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ]);

        DB::commit();

        $game = DB::table('path_games')->where('id', $insertId)->first();

        // ✅ Activity + Notification logs (POST)
        $newArr = $this->toPlainArray($game);
        $this->writeActivityLog($request, 'create', [
            'entity_id'      => (int) $insertId,
            'entity_uuid'    => (string) $uuid,
            'changed_fields' => array_keys($newArr),
            'old_values'     => null,
            'new_values'     => $newArr,
            'log_note'       => 'Path game created',
        ]);
        $this->writeNotificationLog($request, 'create', [
            'entity_id'   => (int) $insertId,
            'entity_uuid' => (string) $uuid,
            'title'       => 'Path Game Created',
            'message'     => (string) (($game->title ?? 'Path Game') . ' created successfully.'),
            'data'        => ['id' => (int)$insertId, 'uuid' => (string)$uuid, 'title' => (string)($game->title ?? '')],
            'log_note'    => 'Path game created',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Path game created successfully.',
            'data' => $this->mapGameRow($game)
        ], 201);

    } catch (\Throwable $e) {
        DB::rollBack();

        // ✅ Activity + Notification logs (POST failed)
        $this->writeActivityLog($request, 'create_failed', [
            'entity_id'   => null,
            'entity_uuid' => null,
            'old_values'  => null,
            'new_values'  => $payload,
            'log_note'    => 'Path game create failed: ' . $e->getMessage(),
        ]);
        $this->writeNotificationLog($request, 'create_failed', [
            'title'    => 'Path Game Create Failed',
            'message'  => 'Failed to create path game.',
            'data'     => ['error' => $e->getMessage()],
            'log_note' => 'Path game create failed',
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to create path game.',
            'error' => $e->getMessage()
        ], 500);
    }
}

    // PUT /api/path-games/{idOrUuid}
    public function update(Request $request, string $idOrUuid)
    {
        $existing = $this->findGame($idOrUuid);

        if (!$existing) {
            return response()->json([
                'success' => false,
                'message' => 'Path game not found.',
            ], 404);
        }

        $payload = $request->all();
        $this->normalizeGridJson($payload);

        $validator = Validator::make($payload, [
            'title' => ['sometimes', 'required', 'string', 'max:180'],
            'description' => ['sometimes', 'nullable', 'string'],
            'instructions_html' => ['sometimes', 'nullable', 'string'],

            'show_solution_after' => ['sometimes', 'required', 'in:never,after_each,after_finish'],

            'grid_dim' => ['sometimes', 'required', 'integer', 'min:1', 'max:6'],
            'grid_json' => ['sometimes', 'required', 'array'],

            'time_limit_sec' => ['sometimes', 'required', 'integer', 'min:1', 'max:600'],
            'max_attempts' => ['sometimes', 'required', 'integer', 'min:1', 'max:50'],

            'rotation_enabled' => ['sometimes', 'required'],
            'rotation_mode' => ['sometimes', 'required', 'in:cw,ccw,both'],

            'status' => ['sometimes', 'required', 'in:active,inactive'],
        ]);

        $validator->after(function ($v) use ($payload, $existing) {
            $needsGridValidation = array_key_exists('grid_json', $payload) || array_key_exists('grid_dim', $payload);

            if ($needsGridValidation) {
                $merged = [
                    'grid_dim' => $payload['grid_dim'] ?? $existing->grid_dim,
                    'grid_json' => $payload['grid_json'] ?? json_decode($existing->grid_json, true),
                ];

                $this->validateGridJson($merged, function ($msg) use ($v) {
                    $v->errors()->add('grid_json', $msg);
                });
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $actorId = $this->actorId($request);
        $now = Carbon::now();

        $update = [];
        $valid = $validator->validated();

        if (array_key_exists('title', $valid)) $update['title'] = $valid['title'];
        if (array_key_exists('description', $valid)) $update['description'] = $valid['description'];
        if (array_key_exists('instructions_html', $valid)) $update['instructions_html'] = $valid['instructions_html'];

        if (array_key_exists('show_solution_after', $valid)) $update['show_solution_after'] = $valid['show_solution_after'];

        if (array_key_exists('grid_dim', $valid)) $update['grid_dim'] = (int)$valid['grid_dim'];
        if (array_key_exists('grid_json', $valid)) $update['grid_json'] = json_encode($valid['grid_json']);

        if (array_key_exists('time_limit_sec', $valid)) $update['time_limit_sec'] = (int)$valid['time_limit_sec'];
        if (array_key_exists('max_attempts', $valid)) $update['max_attempts'] = (int)$valid['max_attempts'];

        if (array_key_exists('rotation_enabled', $valid)) {
            $update['rotation_enabled'] = filter_var($valid['rotation_enabled'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        }

        if (array_key_exists('rotation_mode', $valid)) $update['rotation_mode'] = $valid['rotation_mode'];
        if (array_key_exists('status', $valid)) $update['status'] = $valid['status'];

        // audit
        $update['created_by'] = $actorId; // your field: "Created/updated by"
        $update['updated_at'] = $now;

        $oldArr = $this->toPlainArray($existing);

        DB::beginTransaction();
        try {
            DB::table('path_games')
                ->where('id', $existing->id)
                ->update($update);

            DB::commit();

            $game = DB::table('path_games')->where('id', $existing->id)->first();

            // ✅ Activity + Notification logs (PUT/PATCH)
            $newArr = $this->toPlainArray($game);
            $changedFields = array_keys($update);
            $this->writeActivityLog($request, 'update', [
                'entity_id'      => (int) $existing->id,
                'entity_uuid'    => (string) ($game->uuid ?? $existing->uuid ?? ''),
                'changed_fields' => array_values(array_unique($changedFields)),
                'old_values'     => $oldArr,
                'new_values'     => $newArr,
                'log_note'       => 'Path game updated',
            ]);
            $this->writeNotificationLog($request, 'update', [
                'entity_id'   => (int) $existing->id,
                'entity_uuid' => (string) ($game->uuid ?? $existing->uuid ?? ''),
                'title'       => 'Path Game Updated',
                'message'     => (string) (($game->title ?? $existing->title ?? 'Path Game') . ' updated successfully.'),
                'data'        => ['id' => (int)$existing->id, 'uuid' => (string)($game->uuid ?? $existing->uuid ?? ''), 'title' => (string)($game->title ?? $existing->title ?? '')],
                'log_note'    => 'Path game updated',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Path game updated successfully.',
                'data' => $this->mapGameRow($game)
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            // ✅ Activity + Notification logs (PUT/PATCH failed)
            $this->writeActivityLog($request, 'update_failed', [
                'entity_id'   => (int) $existing->id,
                'entity_uuid' => (string) ($existing->uuid ?? ''),
                'changed_fields' => array_keys($update),
                'old_values'  => $oldArr,
                'new_values'  => $payload,
                'log_note'    => 'Path game update failed: ' . $e->getMessage(),
            ]);
            $this->writeNotificationLog($request, 'update_failed', [
                'entity_id'   => (int) $existing->id,
                'entity_uuid' => (string) ($existing->uuid ?? ''),
                'title'       => 'Path Game Update Failed',
                'message'     => 'Failed to update path game.',
                'data'        => ['id' => (int)$existing->id, 'uuid' => (string)($existing->uuid ?? ''), 'error' => $e->getMessage()],
                'log_note'    => 'Path game update failed',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update path game.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // DELETE /api/path-games/{idOrUuid}  (Soft delete)
    public function destroy(Request $request, string $idOrUuid)
    {
        $existing = $this->findGame($idOrUuid);

        if (!$existing) {
            return response()->json([
                'success' => false,
                'message' => 'Path game not found.',
            ], 404);
        }

        $now = Carbon::now();

        $oldArr = $this->toPlainArray($existing);

        try {
            DB::table('path_games')
                ->where('id', $existing->id)
                ->update([
                    'deleted_at' => $now,
                    'updated_at' => $now,
                ]);

            // ✅ Activity + Notification logs (DELETE)
            $newArr = $oldArr;
            $newArr['deleted_at'] = $now->toDateTimeString();
            $newArr['updated_at'] = $now->toDateTimeString();

            $this->writeActivityLog($request, 'delete', [
                'entity_id'      => (int) $existing->id,
                'entity_uuid'    => (string) ($existing->uuid ?? ''),
                'changed_fields' => ['deleted_at','updated_at'],
                'old_values'     => $oldArr,
                'new_values'     => $newArr,
                'log_note'       => 'Path game deleted (soft delete)',
            ]);
            $this->writeNotificationLog($request, 'delete', [
                'entity_id'   => (int) $existing->id,
                'entity_uuid' => (string) ($existing->uuid ?? ''),
                'title'       => 'Path Game Deleted',
                'message'     => (string) (($existing->title ?? 'Path Game') . ' deleted successfully.'),
                'data'        => ['id' => (int)$existing->id, 'uuid' => (string)($existing->uuid ?? ''), 'title' => (string)($existing->title ?? '')],
                'log_note'    => 'Path game deleted (soft delete)',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Path game deleted successfully.',
            ]);
        } catch (\Throwable $e) {

            // ✅ Activity + Notification logs (DELETE failed)
            $this->writeActivityLog($request, 'delete_failed', [
                'entity_id'   => (int) $existing->id,
                'entity_uuid' => (string) ($existing->uuid ?? ''),
                'old_values'  => $oldArr,
                'new_values'  => null,
                'log_note'    => 'Path game delete failed: ' . $e->getMessage(),
            ]);
            $this->writeNotificationLog($request, 'delete_failed', [
                'entity_id'   => (int) $existing->id,
                'entity_uuid' => (string) ($existing->uuid ?? ''),
                'title'       => 'Path Game Delete Failed',
                'message'     => 'Failed to delete path game.',
                'data'        => ['id' => (int)$existing->id, 'uuid' => (string)($existing->uuid ?? ''), 'error' => $e->getMessage()],
                'log_note'    => 'Path game delete failed',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete path game.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
  public function myPathGames(Request $r)
{
    $reqId = (string) Str::uuid();
    $t0 = microtime(true);

    try {
        Log::info("[myPathGames][$reqId] START", [
            'ip' => $r->ip(),
            'url' => $r->fullUrl(),
            'query' => $r->query(),
            'ua' => substr((string) $r->userAgent(), 0, 150),
        ]);

        $actor  = $this->actor($r);
        $userId = (int) ($actor['id'] ?? 0);
        $role   = strtolower((string) ($actor['role'] ?? ''));

        Log::info("[myPathGames][$reqId] ACTOR", [
            'user_id' => $userId,
            'role' => $role,
            'actor_raw' => $actor,
        ]);

        if (!$userId) {
            Log::warning("[myPathGames][$reqId] BLOCKED: userId not resolved");
            return response()->json(['success' => false, 'message' => 'Unable to resolve user from token'], 403);
        }

        $page       = max(1, (int) $r->query('page', 1));
        $reqPerPage = (int) $r->query('per_page', 12);

        $perPage = in_array($role, ['admin','super_admin'], true)
            ? max(1, min(2000, $reqPerPage))
            : max(1, min(50, $reqPerPage));

        $search  = trim((string) $r->query('q', ''));

        Log::info("[myPathGames][$reqId] PAGINATION_INPUT", [
            'page' => $page,
            'req_per_page' => $reqPerPage,
            'final_per_page' => $perPage,
            'search' => $search,
        ]);

        $gameTable   = 'path_games';
        $resultTable = 'path_game_results';
        $assignTable = 'user_path_game_assignments';

        // ✅ columns exist?
        $gameHasDeleted   = Schema::hasColumn($gameTable, 'deleted_at');
        $resultHasDeleted = Schema::hasColumn($resultTable, 'deleted_at');
        $assignHasDeleted = Schema::hasColumn($assignTable, 'deleted_at');

        $assignHasAssignedAt = Schema::hasColumn($assignTable, 'assigned_at');
        $gameHasStatus = Schema::hasColumn($gameTable, 'status');

        $gameTitleCol = Schema::hasColumn($gameTable, 'title')
            ? 'pg.title'
            : (Schema::hasColumn($gameTable, 'name') ? 'pg.name' : null);

        $gameDescCol = Schema::hasColumn($gameTable, 'description')
            ? 'pg.description'
            : (Schema::hasColumn($gameTable, 'details') ? 'pg.details' : null);

        $gameMaxAttemptsCol = Schema::hasColumn($gameTable, 'max_attempts') ? 'pg.max_attempts' : null;
        $gameTimeLimitCol   = Schema::hasColumn($gameTable, 'time_limit_sec') ? 'pg.time_limit_sec' : null;

        Log::info("[myPathGames][$reqId] SCHEMA_CHECK", [
            'tables' => compact('gameTable','resultTable','assignTable'),

            'gameHasDeleted' => $gameHasDeleted,
            'resultHasDeleted' => $resultHasDeleted,
            'assignHasDeleted' => $assignHasDeleted,

            'assignHasAssignedAt' => $assignHasAssignedAt,
            'gameHasStatus' => $gameHasStatus,

            'gameTitleCol' => $gameTitleCol,
            'gameDescCol' => $gameDescCol,
            'gameMaxAttemptsCol' => $gameMaxAttemptsCol,
            'gameTimeLimitCol' => $gameTimeLimitCol,
        ]);

        /* ============================
         | Subquery: latest result per game
         ============================ */
        $resultSub = DB::table($resultTable)
            ->select('path_game_id', DB::raw('MAX(id) as latest_id'))
            ->where('user_id', $userId);

        if ($resultHasDeleted) $resultSub->whereNull('deleted_at');

        $resultSub->groupBy('path_game_id');

        Log::debug("[myPathGames][$reqId] resultSub SQL", [
            'sql' => $resultSub->toSql(),
            'bindings' => $resultSub->getBindings(),
        ]);

        /* ============================
         | Attempts stats
         ============================ */
        $attemptStatSub = DB::table($resultTable)
            ->select([
                'path_game_id',
                DB::raw('COUNT(*) as attempts_count'),
                DB::raw('COALESCE(MAX(attempt_no), 0) as max_attempt_no'),
            ])
            ->where('user_id', $userId);

        if ($resultHasDeleted) $attemptStatSub->whereNull('deleted_at');

        $attemptStatSub->groupBy('path_game_id');

        Log::debug("[myPathGames][$reqId] attemptStatSub SQL", [
            'sql' => $attemptStatSub->toSql(),
            'bindings' => $attemptStatSub->getBindings(),
        ]);

        /* ============================
         | Assignment info (assigned_at OR created_at)
         ============================ */
        $assignedAtExpr = $assignHasAssignedAt
            ? 'MAX(uga.assigned_at) as assigned_at'
            : 'MAX(uga.created_at) as assigned_at';

        $assignSub = DB::table($assignTable . ' as uga')
            ->select([
                'uga.path_game_id',
                DB::raw($assignedAtExpr),
            ])
            ->where('uga.user_id', '=', $userId)
            ->where('uga.status', '=', 'active');

        if ($assignHasDeleted) $assignSub->whereNull('uga.deleted_at');

        $assignSub->groupBy('uga.path_game_id');

        Log::debug("[myPathGames][$reqId] assignSub SQL", [
            'sql' => $assignSub->toSql(),
            'bindings' => $assignSub->getBindings(),
        ]);

        /* ============================
         | Main Query
         ============================ */
        $q = DB::table($gameTable . ' as pg')
            ->leftJoinSub($resultSub, 'lr', function ($join) {
                $join->on('lr.path_game_id', '=', 'pg.id');
            })
            ->leftJoin($resultTable . ' as pgr', 'pgr.id', '=', 'lr.latest_id')
            ->leftJoinSub($attemptStatSub, 'ats', function ($join) {
                $join->on('ats.path_game_id', '=', 'pg.id');
            })
            ->leftJoinSub($assignSub, 'asgn', function ($join) {
                $join->on('asgn.path_game_id', '=', 'pg.id');
            });

        // ✅ soft delete safe
        if ($gameHasDeleted) $q->whereNull('pg.deleted_at');

        // ✅ status safe
        if ($gameHasStatus) $q->where('pg.status', '=', 'active');

        // ✅ student visibility (only assigned)
        $isAdmin = in_array($role, ['admin','super_admin'], true);
        if (!$isAdmin) {
            $q->whereNotNull('asgn.assigned_at');
        }

        Log::info("[myPathGames][$reqId] VISIBILITY_MODE", [
            'is_admin' => $isAdmin,
            'student_filter_assigned_only' => !$isAdmin,
        ]);

        // ✅ search safe
        if ($search !== '' && $gameTitleCol) {
            $q->where(function ($w) use ($search, $gameTitleCol, $gameDescCol) {
                $w->where($gameTitleCol, 'like', "%{$search}%");
                if ($gameDescCol) {
                    $w->orWhere($gameDescCol, 'like', "%{$search}%");
                }
            });
        }

        $select = [
            'pg.id',
            'pg.uuid',
            DB::raw(($gameTitleCol ? "$gameTitleCol as title" : "CONCAT('Path Game #', pg.id) as title")),
            DB::raw(($gameDescCol ? "$gameDescCol as description" : "'' as description")),
            DB::raw(($gameMaxAttemptsCol ? "$gameMaxAttemptsCol as max_attempts" : "1 as max_attempts")),
            DB::raw(($gameTimeLimitCol ? "$gameTimeLimitCol as time_limit_sec" : "0 as time_limit_sec")),
            'asgn.assigned_at as assigned_at',
            DB::raw('COALESCE(ats.attempts_count, 0) as attempts_count'),
            DB::raw('COALESCE(ats.max_attempt_no, 0) as max_attempt_no'),

            'pgr.id as result_id',
            'pgr.created_at as result_created_at',
            'pgr.score as result_score',
            'pgr.attempt_no as result_attempt_no',
            'pgr.status as result_status',
            'pgr.time_taken_ms as result_time_taken_ms',
        ];

        // ✅ log final SQL (before paginate runs)
        $qSql = clone $q;
        $qSql->select($select)->orderBy('pg.created_at', 'desc');

        Log::debug("[myPathGames][$reqId] FINAL_SQL", [
            'sql' => $qSql->toSql(),
            'bindings' => $qSql->getBindings(),
        ]);

        $paginator = $q->select($select)
            ->orderBy('pg.created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        Log::info("[myPathGames][$reqId] PAGINATOR_META", [
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'returned_items' => count($paginator->items()),
        ]);

        $items = collect($paginator->items())->map(function ($row) {

            $allowed = (int) ($row->max_attempts ?? 1);

            $usedByCount = (int) ($row->attempts_count ?? 0);
            $usedByMaxNo = (int) ($row->max_attempt_no ?? 0);
            $used = max($usedByCount, $usedByMaxNo);

            $remaining = $allowed > 0 ? max($allowed - $used, 0) : 0;
            $maxReached = ($allowed > 0) ? ($used >= $allowed) : false;

            $latestStatus = (string) ($row->result_status ?? '');
            if ($row->result_id && $latestStatus === 'in_progress') $myStatus = 'in_progress';
            elseif ($row->result_id) $myStatus = 'completed';
            else $myStatus = 'upcoming';

            $limitSec = (int) ($row->time_limit_sec ?? 0);
            $totalMinutes = $limitSec > 0 ? (int) ceil($limitSec / 60) : null;

            return [
                'id' => (int) $row->id,
                'uuid' => (string) $row->uuid,

                'assigned_at' => $row->assigned_at
                    ? \Carbon\Carbon::parse($row->assigned_at)->toDateTimeString()
                    : null,

                'title' => (string) ($row->title ?? 'Path Game'),
                'excerpt' => (string) ($row->description ?? ''),
                'total_time' => $totalMinutes,
                'total_questions' => 0,

                'total_attempts' => $allowed,
                'my_attempts' => $used,
                'remaining_attempts' => $remaining,
                'max_attempt_reached' => $maxReached,
                'can_attempt' => !$maxReached,

                'is_public' => false,
                'status' => 'active',
                'my_status' => $myStatus,

                'result' => $row->result_id ? [
                    'id' => (int) $row->result_id,
                    'created_at' => $row->result_created_at
                        ? \Carbon\Carbon::parse($row->result_created_at)->toDateTimeString()
                        : null,
                    'score' => $row->result_score !== null ? (int) $row->result_score : null,
                    'attempt_no' => $row->result_attempt_no !== null ? (int) $row->result_attempt_no : null,
                    'status' => (string) ($row->result_status ?? null),
                    'time_taken_ms' => $row->result_time_taken_ms !== null ? (int) $row->result_time_taken_ms : null,
                ] : null,
            ];
        })->values();

        $ms = (int) round((microtime(true) - $t0) * 1000);

        Log::info("[myPathGames][$reqId] SUCCESS", [
            'time_ms' => $ms,
            'items_count' => $items->count(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $items,
            'pagination' => [
                'total' => (int) $paginator->total(),
                'per_page' => (int) $paginator->perPage(),
                'current_page' => (int) $paginator->currentPage(),
                'last_page' => (int) $paginator->lastPage(),
            ],
        ]);

    } catch (\Throwable $e) {

        $ms = (int) round((microtime(true) - $t0) * 1000);

        Log::error("[myPathGames][$reqId] ERROR", [
            'time_ms' => $ms,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Internal server error',
            'error'   => $e->getMessage(), // ✅ remove in production
            'req_id'  => $reqId,
        ], 500);
    }
}

}
