<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Check if current user is guest (read-only mode).
     */
    protected function isGuest()
    {
        return auth()->check() && auth()->user()->username === 'guest';
    }

    /**
     * Abort if user is guest (for write operations).
     */
    protected function abortIfGuest()
    {
        if ($this->isGuest()) {
            abort(403, 'Akses ditolak. Guest Mode hanya dapat melihat data, tidak dapat melakukan perubahan.');
        }
    }
}
