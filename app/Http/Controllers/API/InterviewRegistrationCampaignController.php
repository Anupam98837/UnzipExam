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
    /**
     * ✅ Resolve campaign by id OR uuid
     */
    private function resolveCampaignId(int|string|null $id): ?int
    {
        $raw = trim((string)($id ?? ''));

        if ($raw === '' || $raw === 'null' || $raw === 'undefined') {
            return null;
        }

        // numeric -> id
        if (ctype_digit($raw)) {
            return (int)$raw;
        }

        // uuid -> id
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
     * GET /api/interview-registration-campaigns
     *
     * Supports:
     * - ?status=active|inactive
     * - ?user_folder_id=ID
     * - Dropdown mode: header X-dropdown:1 OR ?show=all
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

        // Optional filter by status
        if ($request->filled('status')) {
            $q->where('c.status', $request->query('status'));
        }

        // Optional filter by folder
        if ($request->filled('user_folder_id')) {
            $fid = trim((string)$request->query('user_folder_id'));
            if (ctype_digit($fid)) {
                $q->where('c.user_folder_id', (int)$fid);
            }
        }

        // ✅ Dropdown mode
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

        // ✅ Paginated mode
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
     * GET /api/interview-registration-campaigns/{id OR uuid}
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
            ->select(
                'c.*',
                DB::raw("COALESCE(f.title, '—') as folder_title")
            )
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
     * GET /api/interview-registration-campaigns/public/{uuid}
     *
     * This is the one your register blade will call.
     * It returns campaign title + folder id + folder title ✅
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

        // Optional: if campaign is inactive, block registration
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
     */
    public function store(Request $request)
    {
        // Normalize folder id
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

        $actorId = optional($request->user())->id;

        // ✅ unique uuid
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
     */
    public function update(Request $request, $id)
    {
        $campaignId = $this->resolveCampaignId($id);

        if (!$campaignId) {
            return response()->json(['success' => false, 'message' => 'Campaign not found'], 404);
        }

        $campaign = DB::table('interview_registration_campaigns')
            ->where('id', $campaignId)
            ->whereNull('deleted_at')
            ->first();

        if (!$campaign) {
            return response()->json(['success' => false, 'message' => 'Campaign not found'], 404);
        }

        // normalize folder id if present
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

        // ✅ check only if both exist
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
     */
    public function destroy(Request $request, $id)
    {
        $campaignId = $this->resolveCampaignId($id);

        if (!$campaignId) {
            return response()->json(['success' => false, 'message' => 'Campaign not found'], 404);
        }

        $campaign = DB::table('interview_registration_campaigns')
            ->where('id', $campaignId)
            ->whereNull('deleted_at')
            ->first();

        if (!$campaign) {
            return response()->json(['success' => false, 'message' => 'Campaign not found'], 404);
        }

        DB::beginTransaction();
        try {
            DB::table('interview_registration_campaigns')->where('id', $campaignId)->update([
                'deleted_at'    => now(),
                'updated_at'    => now(),
                'updated_at_ip' => $request->ip(),
            ]);

            DB::commit();

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
