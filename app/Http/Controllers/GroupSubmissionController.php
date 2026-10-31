<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use App\Models\GroupSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupSubmissionController extends Controller
{
    /**
     * Submit a new Telegram group/channel link from User Dashboard.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invite_link' => ['required', 'string', 'min:4', 'max:500'],
            'notes'       => ['nullable', 'string', 'max:500'],
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user() ?? \App\Models\User::first();
        $userId = $user ? $user->id : 1;
        $userName = $user ? $user->name : 'Mobile User';
        $userEmail = $user ? $user->email : 'mobile@priceanalyzer.app';

        $link = trim($validated['invite_link']);

        // Normalize username (e.g. @surat_market -> https://t.me/surat_market)
        if (str_starts_with($link, '@')) {
            $link = 'https://t.me/' . ltrim($link, '@');
        } elseif (!str_starts_with($link, 'http://') && !str_starts_with($link, 'https://') && str_starts_with($link, 't.me/')) {
            $link = 'https://' . $link;
        }

        // Check if this user already submitted this link and it is pending
        $existing = GroupSubmission::where('user_id', $userId)
            ->where('invite_link', $link)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return response()->json([
                'success'           => true,
                'already_submitted' => true,
                'message'           => 'You have already submitted this group link. It is currently under review by our admin.',
                'submission'        => $existing,
            ], 200);
        }

        $submission = GroupSubmission::create([
            'user_id'     => $userId,
            'invite_link' => $link,
            'notes'       => $validated['notes'] ?? null,
            'status'      => 'pending',
        ]);

        AdminActivityLog::record(
            'group_submission_created',
            "User [{$userName}] submitted Telegram group link for review: {$link}",
            $submission,
            [
                'link'    => $link,
                'user_id' => $userId,
                'email'   => $userEmail,
                'notes'   => $submission->notes,
            ]
        );

        return response()->json([
            'success'    => true,
            'message'    => 'Telegram group link submitted successfully! Admin will review and connect it to Price Monitor.',
            'submission' => $submission,
        ], 201);
    }

    /**
     * Get list of group links submitted by the authenticated user.
     */
    public function index(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user() ?? \App\Models\User::first();
        $userId = $user ? $user->id : 1;

        $submissions = GroupSubmission::with('telegramGroup:id,title,member_count')
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        return response()->json([
            'success'     => true,
            'submissions' => $submissions,
        ]);
    }
}

