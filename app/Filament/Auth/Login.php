<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    protected function getRedirectUrl(): string
    {
        return route('admin.dashboard');
    }

    public function getTitle(): string | Htmlable
    {
        return 'Login Admin';
    }

    public function getHeading(): string | Htmlable | null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getHeading();
        }

        return 'Ready To Pict';
    }

    public function getSubheading(): string | Htmlable | null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getSubheading();
        }

        return 'Gunakan akun admin untuk mengelola booking Ready to Pict.';
    }
}
