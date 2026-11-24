<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    /**
     * Menampilkan halaman root SIAP46:
     * - Jika user sudah login sebagai Admin/Guru: tampilkan Dashboard Admin langsung di URL '/'
     * - Jika user sudah login sebagai Kasir: tampilkan Dashboard Kasir langsung di URL '/'
     * - Jika guest (belum login): tampilkan Landing Page Beranda Publik
     */
    public function index(AdminController $adminController, CashierController $cashierController): View
    {
        if (auth()->check()) {
            $roleName = auth()->user()->role?->name;
            if ($roleName === 'Admin' || $roleName === 'Guru') {
                return $adminController->dashboard();
            }

            return $cashierController->dashboard();
        }

        return view('home');
    }
}
