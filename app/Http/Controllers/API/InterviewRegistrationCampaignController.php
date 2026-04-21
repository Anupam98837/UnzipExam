<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InterviewRegistrationCampaignController extends Controller
{
    /* =========================
     * Auth/Role + Activity Log + Notifications
     * ========================= */

    /** Actor (supports both request->user() and CheckRole attributes) */
    private function actor(Request $request): array
    {
        $attrRole = $request->attributes->get('auth_role');
        $attrId   = (int) ($request->attributes->get('auth_tokenable_id') ?? 0);

        $userId = 0;
        try {
            $userId = (int) (optional($request->user())->id ?? 0);
        } catch (\Throwable $e) {
            $userId = 0;
        }

        return [
            'role' => $attrRole ?: null,
            'id'   => $attrId > 0 ? $attrId : ($userId > 0 ? $userId : 0),
        ];
    }

    /**
     * Insert row into user_data_activity_log using DB facade.
     * Columns expected (DocumentType-style):
     * performed_by, performed_by_role, ip, user_agent, activity, module, table_name,
     * record_id, changed_fields (json), old_values (json), new_values (json),
     * log_note, created_at, updated_at
     */
    private function logActivity(
        Request $request,
        string $activity,                 // 'store'|'update'|'destroy'
        string $module,                   // 'InterviewRegistrationCampaigns'
        string $note,                     // human-readable note
        string $tableName,                // 'interview_registration_campaigns'
        ?int $recordId = null,
        ?array $changed = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $a = $this->actor($request);

        $changedFields = null;
        if (is_array($changed)) {
            $changedFields = array_values(array_unique(
                array_keys($changed) === range(0, count($changed)-1) ? $changed : array_keys($changed)
            ));
        }

        try {
            DB::table('user_data_activity_log')->insert([
                'performed_by'      => $a['id'] ?: 0,
                'performed_by_role' => $a['role'] ?: null,
                'ip'                => $request->ip(),
                'user_agent'        => (string) $request->userAgent(),
                'activity'          => $activity,
                'module'            => $module,
                'table_name'        => $tableName ?: 'unknown',
                'record_id'         => $recordId,
                'changed_fields'    => $changedFields ? json_encode($changedFields, JSON_UNESCAPED_UNICODE) : null,
                'old_values'        => $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
                'new_values'        => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
                'log_note'          => $note,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('user_data_activity_log insert failed', ['error' => $e->getMessage()]);
        }
    }

    /** Admin receivers: all admins (id, role=admin). */
    private function adminReceivers(array $excludeIds = []): array
    {
        $exclude = array_flip(array_map('intval', $excludeIds));
        $rows = DB::table('users')->select('id')->get();
        $out = [];
        foreach ($rows as $r) {
            $id = (int)$r->id;
            if (!isset($exclude[$id])) $out[] = ['id'=>$id, 'role'=>'admin', 'read'=>0];
        }
        return $out;
    }

    /** DB-only notification insert (same pattern you used earlier) */
    private function persistNotification(array $payload): void
    {
        try {
            $title     = (string)($payload['title']    ?? 'Notification');
            $message   = (string)($payload['message']  ?? '');
            $receivers = array_values(array_map(function($x){
                return [
                    'id'   => isset($x['id']) ? (int)$x['id'] : null,
                    'role' => (string)($x['role'] ?? 'unknown'),
                    'read' => (int)($x['read'] ?? 0),
                ];
            }, $payload['receivers'] ?? []));

            $metadata = $payload['metadata'] ?? [];
            $type     = (string)($payload['type'] ?? 'general');
            $linkUrl  = $payload['link_url'] ?? null;

            $priority = in_array(($payload['priority'] ?? 'normal'), ['low','normal','high','urgent'], true)
                        ? $payload['priority'] : 'normal';

            $status   = in_array(($payload['status'] ?? 'active'), ['active','archived','deleted'], true)
                        ? $payload['status'] : 'active';

            DB::table('notifications')->insert([
                'title'      => $title,
                'message'    => $message,
                'receivers'  => json_encode($receivers, JSON_UNESCAPED_UNICODE),
                'metadata'   => $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null,
                'type'       => $type,
                'link_url'   => $linkUrl,
                'priority'   => $priority,
                'status'     => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('notifications insert failed', ['error' => $e->getMessage()]);
        }
    }

    /** Small helper to compute changed fields for update notifications */
    private function changedFields(array $old, array $new, array $ignore = []): array
    {
        $ignoreFlip = array_flip($ignore);
        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
        $out = [];

        foreach ($keys as $k) {
            if (isset($ignoreFlip[$k])) continue;

            $ov = $old[$k] ?? null;
            $nv = $new[$k] ?? null;

            if (is_string($ov) && is_string($nv)) {
                if (trim($ov) !== trim($nv)) $out[] = $k;
            } else {
                if ($ov !== $nv) $out[] = $k;
            }
        }

        return array_values($out);
    }

    /* ============================================================
     | ✅ Resolve campaign by id OR uuid
     *============================================================ */
    private function resolveCampaignId(int|string|null $id): ?int
    {
        $raw = trim((string)($id ?? ''));

        if ($raw === '' || $raw === 'null' || $raw === 'undefined') {
            return null;
        }

        if (ctype_digit($raw)) {
            return (int)$raw;
        }

        $row = DB::table('interview_registration_campaigns')
            ->select('id')
            ->where('uuid', $raw)
            ->whereNull('deleted_at')
            ->first();

        return $row ? (int)$row->id : null;
    }

    /**
     * ✅ Build public registration URL
     * /register/{campaign_uuid}
     */
    private function buildUrl(string $uuid): string
    {
        return url('/register/' . $uuid);
    }

    /**
     * ✅ LIST campaigns
     */
    public function index(Request $request)
    {
        $q = DB::table('interview_registration_campaigns as c')
            ->leftJoin('user_folders as f', function ($j) {
                $j->on('f.id', '=', 'c.user_folder_id')
                  ->whereNull('f.deleted_at');
            })
            ->whereNull('c.deleted_at')
            ->orderByDesc('c.id');

        if ($request->filled('status')) {
            $q->where('c.status', $request->query('status'));
        }

        if ($request->filled('user_folder_id')) {
            $fid = trim((string)$request->query('user_folder_id'));
            if (ctype_digit($fid)) {
                $q->where('c.user_folder_id', (int)$fid);
            }
        }

        $xDropdown  = strtolower(trim((string)$request->header('X-dropdown', '')));
        $isDropdown = in_array($xDropdown, ['1', 'true', 'yes'], true) || $request->query('show') === 'all';

        if ($isDropdown) {
            $rows = $q->select(
                    'c.id',
                    'c.uuid',
                    'c.title',
                    'c.status',
                    'c.start_date',
                    'c.end_date',
                    'c.user_folder_id',
                    DB::raw("COALESCE(f.title, '—') as folder_title")
                )
                ->get()
                ->map(function ($r) {
                    $r->url = url('/register/' . $r->uuid);
                    return $r;
                });

            return response()->json([
                'success' => true,
                'data'    => $rows,
                'meta'    => [
                    'dropdown' => true,
                    'total'    => $rows->count(),
                ],
            ]);
        }

        $perPage = (int)$request->query('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        $p = $q->select(
                'c.*',
                DB::raw("COALESCE(f.title, '—') as folder_title")
            )
            ->paginate($perPage);

        $items = collect($p->items())->map(function ($r) {
            $r->url = url('/register/' . $r->uuid);
            return $r;
        })->values();

        return response()->json([
            'success' => true,
            'data'    => $items,
            'meta'    => [
                'current_page' => $p->currentPage(),
                'per_page'     => $p->perPage(),
                'total'        => $p->total(),
                'total_pages'  => $p->lastPage(),
            ],
        ]);
    }

    /**
     * ✅ SHOW campaign (Admin)
     */
    public function show(Request $request, $id)
    {
        $campaignId = $this->resolveCampaignId($id);

        if (!$campaignId) {
            return response()->json(['success' => false, 'message' => 'Campaign not found'], 404);
        }

        $row = DB::table('interview_registration_campaigns as c')
            ->leftJoin('user_folders as f', function ($j) {
                $j->on('f.id', '=', 'c.user_folder_id')
                  ->whereNull('f.deleted_at');
            })
            ->where('c.id', $campaignId)
            ->whereNull('c.deleted_at')
            ->select('c.*', DB::raw("COALESCE(f.title, '—') as folder_title"))
            ->first();

        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Campaign not found'], 404);
        }

        $row->url = $this->buildUrl($row->uuid);

        return response()->json([
            'success' => true,
            'data'    => $row,
        ]);
    }

    /**
     * ✅ PUBLIC SHOW (Register page)
     */
    public function publicShow(Request $request, $uid)
    {
        $raw = trim((string)($uid ?? ''));

        if ($raw === '' || $raw === 'null' || $raw === 'undefined') {
            return response()->json(['success' => false, 'message' => 'Campaign not found'], 404);
        }

        $row = DB::table('interview_registration_campaigns as c')
            ->leftJoin('user_folders as f', function ($j) {
                $j->on('f.id', '=', 'c.user_folder_id')
                  ->whereNull('f.deleted_at');
            })
            ->whereNull('c.deleted_at')
            ->where('c.uuid', $raw)
            ->select(
                'c.id',
                'c.uuid',
                'c.title',
                'c.description',
                'c.start_date',
                'c.end_date',
                'c.status',
                'c.user_folder_id',
                DB::raw("COALESCE(f.title, '—') as folder_title")
            )
            ->first();

        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Campaign not found'], 404);
        }

        if (strtolower((string)$row->status) !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'This registration campaign is inactive.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => $row,
        ]);
    }

    /**
     * ✅ CREATE campaign
     * POST /api/interview-registration-campaigns
     * ✅ Added: activity log + admin notification
     */
    public function store(Request $request)
    {
        if ($request->has('user_folder_id')) {
            $raw = $request->input('user_folder_id');
            if ($raw === '' || $raw === null || $raw === 'null' || $raw === 'undefined') {
                $request->merge(['user_folder_id' => null]);
            } elseif (is_numeric($raw)) {
                $request->merge(['user_folder_id' => (int)$raw]);
            }
        }

        $validator = Validator::make($request->all(), [
            'user_folder_id' => [
                'required',
                'integer',
                Rule::exists('user_folders', 'id')->whereNull('deleted_at'),
            ],
            'title'        => ['required', 'string', 'max:180'],
            'description'  => ['nullable', 'string'],
            'start_date'   => ['required', 'date'],
            'end_date'     => ['required', 'date', 'after_or_equal:start_date'],
            'status'       => ['nullable', 'in:active,inactive'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $actor = $this->actor($request);
        $actorId = optional($request->user())->id;

        do { $uuid = (string) Str::uuid(); }
        while (DB::table('interview_registration_campaigns')->where('uuid', $uuid)->exists());

        DB::beginTransaction();
        try {
            $id = DB::table('interview_registration_campaigns')->insertGetId([
                'uuid'          => $uuid,
                'user_folder_id'=> (int)$request->user_folder_id,
                'title'         => $request->title,
                'description'   => $request->description,
                'start_date'    => $request->start_date,
                'end_date'      => $request->end_date,
                'status'        => $request->status ?? 'active',
                'created_by'    => $actorId,
                'created_at_ip' => $request->ip(),
                'updated_at_ip' => $request->ip(),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            $row = DB::table('interview_registration_campaigns')->where('id', $id)->first();
            $row->url = $this->buildUrl($row->uuid);

            DB::commit();

            // ✅ Activity log (after commit)
            $this->logActivity(
                $request,
                'store',
                'InterviewRegistrationCampaigns',
                "Created campaign \"{$row->title}\"",
                'interview_registration_campaigns',
                (int)$id,
                ['user_folder_id','title','description','start_date','end_date','status'],
                null,
                $row ? (array)$row : null
            );

            // ✅ Notify admins (after commit)
            $this->persistNotification([
                'title'     => 'Interview registration campaign created',
                'message'   => "Campaign \"{$row->title}\" created.",
                'receivers' => $this->adminReceivers(),
                'metadata'  => [
                    'action'      => 'created',
                    'campaign_id' => (int)$id,
                    'campaign'    => $row ? (array)$row : ['id'=>$id,'uuid'=>$uuid,'title'=>$request->title],
                    'actor'       => $actor,
                ],
                'type'      => 'interview_registration_campaign',
                'link_url'  => $row->url ?? $this->buildUrl($uuid),
                'priority'  => 'normal',
                'status'    => 'active',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Campaign created successfully',
                'data'    => $row,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Campaign create failed: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create campaign',
            ], 500);
        }
    }

    /**
     * ✅ UPDATE campaign
     * PUT /api/interview-registration-campaigns/{id OR uuid}
     * ✅ Added: activity log + admin notification
     */
    public function update(Request $request, $id)
    {
        $campaignId = $this->resolveCampaignId($id);

        if (!$campaignId) {
            // optional log attempt
            $this->logActivity($request, 'update', 'InterviewRegistrationCampaigns', 'Campaign not found', 'interview_registration_campaigns', (int)$campaignId);
            return response()->json(['success' => false, 'message' => 'Campaign not found'], 404);
        }

        $campaign = DB::table('interview_registration_campaigns')
            ->where('id', $campaignId)
            ->whereNull('deleted_at')
            ->first();

        if (!$campaign) {
            $this->logActivity($request, 'update', 'InterviewRegistrationCampaigns', 'Campaign not found', 'interview_registration_campaigns', (int)$campaignId);
            return response()->json(['success' => false, 'message' => 'Campaign not found'], 404);
        }

        if ($request->has('user_folder_id')) {
            $raw = $request->input('user_folder_id');
            if ($raw === '' || $raw === null || $raw === 'null' || $raw === 'undefined') {
                $request->merge(['user_folder_id' => null]);
            } elseif (is_numeric($raw)) {
                $request->merge(['user_folder_id' => (int)$raw]);
            }
        }

        $validator = Validator::make($request->all(), [
            'user_folder_id' => [
                'nullable',
                'integer',
                Rule::exists('user_folders', 'id')->whereNull('deleted_at'),
            ],
            'title'        => ['nullable', 'string', 'max:180'],
            'description'  => ['nullable', 'string'],
            'start_date'   => ['nullable', 'date'],
            'end_date'     => ['nullable', 'date'],
            'status'       => ['nullable', 'in:active,inactive'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $oldRow = (array)$campaign;

        $payload = [
            'updated_at'    => now(),
            'updated_at_ip' => $request->ip(),
        ];

        if ($request->filled('user_folder_id')) $payload['user_folder_id'] = (int)$request->user_folder_id;
        if ($request->filled('title')) $payload['title'] = $request->title;
        if ($request->has('description')) $payload['description'] = $request->description;
        if ($request->filled('status')) $payload['status'] = $request->status;
        if ($request->filled('start_date')) $payload['start_date'] = $request->start_date;
        if ($request->filled('end_date')) $payload['end_date'] = $request->end_date;

        $finalStart = $payload['start_date'] ?? $campaign->start_date;
        $finalEnd   = $payload['end_date'] ?? $campaign->end_date;

        if ($finalStart && $finalEnd && strtotime($finalEnd) < strtotime($finalStart)) {
            return response()->json([
                'success' => false,
                'message' => 'end_date must be after or equal to start_date',
                'errors'  => ['end_date' => ['End date cannot be before start date']],
            ], 422);
        }

        DB::beginTransaction();
        try {
            DB::table('interview_registration_campaigns')->where('id', $campaignId)->update($payload);

            $row = DB::table('interview_registration_campaigns')->where('id', $campaignId)->first();
            $row->url = $this->buildUrl($row->uuid);

            DB::commit();

            $newRow = (array)$row;
            $changed = $this->changedFields($oldRow, $newRow, ['updated_at','updated_at_ip','created_at','created_at_ip','created_by','deleted_at']);

            // ✅ Activity log (after commit)
            $this->logActivity(
                $request,
                'update',
                'InterviewRegistrationCampaigns',
                $changed ? ('Campaign updated: '.implode(', ', $changed)) : 'Campaign updated',
                'interview_registration_campaigns',
                (int)$campaignId,
                $changed ?: array_keys($payload),
                $oldRow,
                $newRow
            );

            // ✅ Notify admins (after commit)
            $this->persistNotification([
                'title'     => 'Interview registration campaign updated',
                'message'   => $changed
                    ? ("Campaign \"{$row->title}\" updated (".implode(', ', $changed).").")
                    : ("Campaign \"{$row->title}\" updated."),
                'receivers' => $this->adminReceivers(),
                'metadata'  => [
                    'action'      => 'updated',
                    'campaign_id' => (int)$campaignId,
                    'changed'     => $changed,
                    'campaign'    => $newRow,
                    'actor'       => $this->actor($request),
                ],
                'type'      => 'interview_registration_campaign',
                'link_url'  => $row->url,
                'priority'  => 'normal',
                'status'    => 'active',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Campaign updated successfully',
                'data'    => $row,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Campaign update failed: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update campaign',
            ], 500);
        }
    }

    /**
     * ✅ DELETE campaign (Soft delete)
     * DELETE /api/interview-registration-campaigns/{id OR uuid}
     * ✅ Added: activity log + admin notification
     */
    public function destroy(Request $request, $id)
    {
        $campaignId = $this->resolveCampaignId($id);

        if (!$campaignId) {
            $this->logActivity($request, 'destroy', 'InterviewRegistrationCampaigns', 'Campaign not found', 'interview_registration_campaigns', null, null, null, [
                'id_or_uuid' => $id
            ]);
            return response()->json(['success' => false, 'message' => 'Campaign not found'], 404);
        }

        $campaign = DB::table('interview_registration_campaigns')
            ->where('id', $campaignId)
            ->whereNull('deleted_at')
            ->first();

        if (!$campaign) {
            $this->logActivity($request, 'destroy', 'InterviewRegistrationCampaigns', 'Campaign not found', 'interview_registration_campaigns', (int)$campaignId);
            return response()->json(['success' => false, 'message' => 'Campaign not found'], 404);
        }

        $oldRow = (array)$campaign;

        DB::beginTransaction();
        try {
            DB::table('interview_registration_campaigns')->where('id', $campaignId)->update([
                'deleted_at'    => now(),
                'updated_at'    => now(),
                'updated_at_ip' => $request->ip(),
            ]);

            DB::commit();

            // ✅ Activity log (after commit)
            $this->logActivity(
                $request,
                'destroy',
                'InterviewRegistrationCampaigns',
                "Campaign \"{$campaign->title}\" deleted",
                'interview_registration_campaigns',
                (int)$campaignId,
                ['deleted_at'],
                $oldRow,
                null
            );

            // ✅ Notify admins (after commit)
            $this->persistNotification([
                'title'     => 'Interview registration campaign deleted',
                'message'   => "Campaign \"{$campaign->title}\" was deleted.",
                'receivers' => $this->adminReceivers(),
                'metadata'  => [
                    'action'      => 'deleted',
                    'campaign_id' => (int)$campaignId,
                    'campaign'    => $oldRow,
                    'actor'       => $this->actor($request),
                ],
                'type'      => 'interview_registration_campaign',
                'link_url'  => null,
                'priority'  => 'normal',
                'status'    => 'active',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Campaign deleted successfully',
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Campaign delete failed: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete campaign',
            ], 500);
        }
    }
}
