<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminLoginRequest;
use App\Models\User;
use App\Support\Auth\AuthEventLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(AdminLoginRequest $request, AuthEventLogger $events): RedirectResponse
    {
        $user = User::query()
            ->where('email', $request->string('email')->toString())
            ->first();

        if (! $this->canLoginToAdmin($user, $request->string('password')->toString())) {
            $events->log(
                action: 'admin.login_failed',
                actor: $this->isInternalUser($user) ? $user : null,
                request: $request,
                description: 'Đăng nhập khu vực quản trị không thành công.',
                metadata: [
                    'email' => $request->string('email')->toString(),
                    'reason' => 'invalid_credentials',
                ],
            );

            return back()
                ->withErrors(['email' => 'Thông tin đăng nhập không chính xác.'])
                ->onlyInput('email');
        }

        auth()->guard('web')->login($user);
        $request->session()->regenerate();

        $events->log(
            action: 'admin.login_succeeded',
            actor: $user,
            subject: $user,
            request: $request,
            description: 'Đăng nhập khu vực quản trị thành công.',
        );

        return redirect()->intended(route('admin.dashboard', absolute: false));
    }

    public function destroy(Request $request, AuthEventLogger $events): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            $events->log(
                action: 'admin.logout',
                actor: $user,
                subject: $user,
                request: $request,
                description: 'Đăng xuất khỏi khu vực quản trị.',
            );
        }

        auth()->guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        auth()->forgetGuards();

        return redirect()->route('admin.login');
    }

    private function canLoginToAdmin(?User $user, string $password): bool
    {
        return $this->isInternalUser($user)
            && $user->canAccessProtectedResources()
            && Hash::check($password, $user->password);
    }

    private function isInternalUser(?User $user): bool
    {
        return $user !== null
            && ($user->isStaff() || $user->isManager() || $user->isSuperAdmin());
    }
}
