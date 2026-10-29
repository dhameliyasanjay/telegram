<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSettingController extends Controller
{
    public function __construct(
        private readonly SettingService $settingService,
    ) {}

    /**
     * Show centralized settings form.
     */
    public function index(Request $request): View|JsonResponse
    {
        $groupedSettings = $this->settingService->allGrouped();

        if ($request->wantsJson()) {
            return response()->json($groupedSettings);
        }

        return view('admin.settings.index', [
            'groupedSettings' => $groupedSettings,
        ]);
    }

    /**
     * Update application settings.
     */
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $inputs = $request->except(['_token', '_method']);
        $updatedKeys = [];

        foreach ($inputs as $key => $value) {
            $this->settingService->set($key, $value);
            $updatedKeys[] = $key;
        }

        // Special handling for boolean checkboxes that might be unchecked (absent from request)
        if (!$request->has('enable_public_registration') && $request->has('form_submitted_security')) {
            $this->settingService->set('enable_public_registration', '0', 'boolean');
            $updatedKeys[] = 'enable_public_registration';
        }

        AdminActivityLog::record(
            'settings_updated',
            "Updated application settings: " . implode(', ', $updatedKeys),
            null,
            $inputs
        );

        $msg = "Settings saved successfully.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }
}

