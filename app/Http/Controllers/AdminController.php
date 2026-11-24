<?php

namespace App\Http\Controllers;

use App\Models\DailyRecap;
use App\Models\Role;
use App\Models\StaffAttendance;
use App\Models\StampDutyRecord;
use App\Models\Transaction;
use App\Models\User;
use App\Services\SimpleSpreadsheetReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        $today = today();

        $todayTransactionsCount = Transaction::whereDate('transaction_date', $today)->count();
        $todayTotalNominal = (float) Transaction::whereDate('transaction_date', $today)->sum('total_payment');

        $latestBniRecap = DailyRecap::latest('recap_date')->first();
        $latestBniBalance = (float) ($latestBniRecap?->bni_balance_remaining ?? 0);

        $maxCashiersCount = 3;
        $totalCashiersCount = $maxCashiersCount;
        $presentCashiersCount = min($maxCashiersCount, StaffAttendance::whereDate('attendance_date', $today)->distinct('user_id')->count('user_id'));

        $recentTransactions = Transaction::with(['customer', 'transactionType'])
            ->latest('id')
            ->limit(5)
            ->get();

        $todayAttendances = StaffAttendance::with('user')
            ->whereDate('attendance_date', $today)
            ->latest('id')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'todayTransactionsCount',
            'todayTotalNominal',
            'latestBniBalance',
            'maxCashiersCount',
            'totalCashiersCount',
            'presentCashiersCount',
            'recentTransactions',
            'todayAttendances'
        ));
    }

    public function transactions(): View
    {
        $transactions = Transaction::with(['customer', 'cashier', 'transactionType'])->latest()->paginate(15);

        return view('admin.transactions', compact('transactions'));
    }

    public function destroyTransaction(Transaction $transaction): RedirectResponse
    {
        $isMaterai = $transaction->transactionType && (
            str_contains(strtolower($transaction->transactionType->code), 'materai') ||
            str_contains(strtolower($transaction->transactionType->code), 'mtr') ||
            str_contains(strtolower($transaction->transactionType->name), 'materai')
        );

        if ($isMaterai) {
            $relatedStamp = StampDutyRecord::whereDate('record_date', $transaction->transaction_date)
                ->where('quantity_sold', '>', 0)
                ->where('notes', 'like', '%Transaksi #'.$transaction->transaction_number.'%')
                ->latest('id')
                ->first();

            $quantityToRestore = $relatedStamp ? (int) $relatedStamp->quantity_sold : 0;
            if ($quantityToRestore <= 0) {
                $quantityToRestore = (int) max(1, round($transaction->amount / 11000));
            }

            if ($relatedStamp) {
                // Perbarui sisa stok pada record-record setelah record penjualan ini jika ada
                StampDutyRecord::where('id', '>', $relatedStamp->id)
                    ->increment('remaining_stock', $quantityToRestore);

                // Hapus catatan penjualan materai yang terkait
                $relatedStamp->delete();
            } else {
                // Fallback jika record penjualan lama tidak ditemukan
                $latestStamp = StampDutyRecord::latest('id')->first();
                $currentStock = $latestStamp ? (int) $latestStamp->remaining_stock : 0;
                StampDutyRecord::create([
                    'record_date' => today(),
                    'quantity_sold' => 0,
                    'quantity_purchased' => 0,
                    'remaining_stock' => $currentStock + $quantityToRestore,
                    'amount' => 0,
                    'cashier_id' => auth()->id(),
                    'notes' => "Pengembalian Stok (Pembatalan Transaksi #{$transaction->transaction_number} - {$quantityToRestore} pcs)",
                ]);
            }
        }

        $customer = $transaction->customer;
        $trxNumber = $transaction->transaction_number;
        $transaction->delete();

        if ($customer && $customer->transactions()->count() === 0) {
            $customer->delete();
        }

        return redirect()->route('admin.transactions')
            ->with('success', "Transaksi #{$trxNumber} berhasil dihapus.");
    }

    public function attendance()
    {
        $attendances = StaffAttendance::with('user')->latest('attendance_date')->paginate(15);

        return view('admin.attendance', compact('attendances'));
    }

    public function users(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $selectedRole = trim((string) $request->query('role', ''));
        $selectedStatus = $request->query('status');

        $roles = Role::all();
        $adminRoleId = $roles->firstWhere('name', 'Admin')?->id;
        $guruRoleId = $roles->firstWhere('name', 'Guru')?->id;

        $users = User::with('role')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('student_number', 'like', "%{$search}%")
                        ->orWhere('class_group', 'like', "%{$search}%");
                });
            })
            ->when($selectedRole !== '', function ($query) use ($selectedRole) {
                $query->whereRelation('role', 'name', $selectedRole);
            })
            ->when($selectedStatus !== null && $selectedStatus !== '', function ($query) use ($selectedStatus) {
                $query->where('is_active', (bool) $selectedStatus);
            })
            ->orderByRaw('
                CASE 
                    WHEN users.id = ? THEN 0
                    WHEN role_id = ? THEN 1
                    WHEN role_id = ? THEN 2
                    ELSE 3
                END
            ', [auth()->id() ?? 0, $adminRoleId ?? 0, $guruRoleId ?? 0])
            ->latest('id')
            ->get();

        $cashierCount = User::whereRelation('role', 'name', 'Kasir')->count();

        return view('admin.users', compact('users', 'search', 'selectedRole', 'selectedStatus', 'roles', 'cashierCount'));
    }

    public function createUser(): View
    {
        $roles = Role::all();

        return view('admin.users-create', compact('roles'));
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'role_id' => ['required', 'exists:roles,id'],
            'student_number' => ['nullable', 'string', 'max:50'],
            'class_group' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:6'],
            'is_active' => ['required', 'boolean'],
        ], [
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan, silakan pilih username lain.',
            'role_id.required' => 'Role wajib dipilih.',
            'role_id.exists' => 'Role yang dipilih tidak valid.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal harus 6 karakter.',
        ]);

        User::create($validated);

        return redirect()->route('admin.users')->with('success', 'User '.$validated['full_name'].' berhasil ditambahkan!');
    }

    public function downloadUserTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_import_siswa_kasir.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            // Tulis UTF-8 BOM agar Excel membukanya langsung rapi terpisah kolom
            fwrite($handle, "\xEF\xBB\xBF");

            // Header kolom
            fputcsv($handle, ['Nama Siswa', 'NIS', 'Kelas', 'Username', 'Password'], ',');

            // Contoh data
            fputcsv($handle, ['Agus Setiawan', '123456789', 'XI RPL 1', 'agus.setiawan', 'password'], ',');
            fputcsv($handle, ['Budi Santoso', '123456790', 'XI RPL 1', 'budi.santoso', 'password'], ',');
            fputcsv($handle, ['Dewi Lestari', '123456791', 'XI RPL 2', 'dewi.lestari', 'password'], ',');

            fclose($handle);
        }, 200, $headers);
    }

    public function importUsers(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:5120'],
            'default_password' => ['nullable', 'string', 'min:6'],
        ], [
            'file.required' => 'File spreadsheet (Excel / CSV) wajib dipilih.',
            'file.mimes' => 'Format file harus berupa Excel (.xlsx) atau .csv.',
            'file.max' => 'Ukuran file maksimal adalah 5MB.',
            'default_password.min' => 'Password default minimal harus 6 karakter.',
        ]);

        $file = $request->file('file');
        $rows = SimpleSpreadsheetReader::read($file->getRealPath(), $file->getClientOriginalExtension());

        if (empty($rows)) {
            return back()->with('error', 'File Excel kosong atau tidak dapat dibaca.');
        }

        $kasirRole = Role::firstOrCreate(
            ['name' => 'Kasir'],
            ['description' => 'Siswa / Kasir BNI']
        );

        $fallbackPassword = $request->filled('default_password')
            ? (string) $request->input('default_password')
            : null;

        $corruptedTokens = ['#ref!', '#n/a', '#value!', '#name?', 'nan', '-', 'null', 'undefined', 'belum', 'sudah', 'abstain'];

        $exactSkipKeywords = [
            'nama', 'nama siswa', 'nama lengkap', 'nis', 'nisn', 'no', 'nomor',
            'no.', 'no urut', 'nomor urut', 'l/p', 'jk', 'jenis kelamin',
        ];

        $prefixSkipKeywords = [
            'mengetahui', 'wali kelas', 'wali', 'guru', 'wakasek', 'kurikulum', 'kepala sekolah', 'rekapitulasi',
            'keterangan', 'daftar hadir', 'tahun pelajaran', 'tahun ajaran', 'bulan',
            'jumlah', 'total', 'catatan', 'gaya belajar',
            'yayasan', 'smk', 'smk wikrama', 'jl.', 'jalan', 'telp', 'email', 'website', 'http', 'www', 'rombel', 'kelas',
        ];

        $detectHeaderMap = function (array $row): ?array {
            $map = [
                'name' => null,
                'nis' => null,
                'class' => null,
                'jurusan' => null,
                'grade' => null,
                'username' => null,
                'password' => null,
            ];
            $hasExplicitNis = false;

            foreach ($row as $idx => $cell) {
                $norm = strtolower(trim((string) $cell));
                if ($norm === '') {
                    continue;
                }

                if (in_array($norm, ['nama', 'nama siswa', 'nama lengkap', 'full_name', 'name', 'student_name', 'nama peserta didik'], true)) {
                    $map['name'] = $idx;
                } elseif (in_array($norm, ['nis', 'no induk', 'nomor induk', 'nipd', 'nis/nisn'], true)) {
                    $map['nis'] = $idx;
                    $hasExplicitNis = true;
                } elseif (! $hasExplicitNis && in_array($norm, ['nisn', 'student_number', 'no_induk'], true)) {
                    $map['nis'] = $idx;
                } elseif (in_array($norm, ['rombel reguler', 'rombel', 'class_group', 'kelas / rombel', 'rombongan belajar'], true)) {
                    $map['class'] = $idx;
                } elseif (in_array($norm, ['kelas', 'tingkat', 'class'], true)) {
                    $map['grade'] = $idx;
                    if ($map['class'] === null) {
                        $map['class'] = $idx;
                    }
                } elseif (in_array($norm, ['program keahlian', 'kompetensi keahlian', 'jurusan'], true)) {
                    $map['jurusan'] = $idx;
                } elseif (in_array($norm, ['username', 'user', 'id login', 'id_login'], true)) {
                    $map['username'] = $idx;
                } elseif (in_array($norm, ['password', 'pass', 'kata sandi', 'kata_sandi'], true)) {
                    $map['password'] = $idx;
                }
            }

            if ($map['name'] !== null || $map['nis'] !== null) {
                if ($map['name'] === null && $map['nis'] === 1 && isset($row[2])) {
                    $map['name'] = 2;
                }
                if ($map['nis'] === null && $map['name'] === 2 && isset($row[1])) {
                    $map['nis'] = 1;
                }

                return $map;
            }

            return null;
        };

        // Deteksi letak baris header awal (scan hingga 15 baris pertama)
        $headerRowIndex = null;
        $colMap = [
            'name' => null,
            'nis' => null,
            'class' => null,
            'jurusan' => null,
            'grade' => null,
            'username' => null,
            'password' => null,
        ];
        $detectedClassFromTitle = null;

        $maxScan = min(15, count($rows));
        for ($r = 0; $r < $maxScan; $r++) {
            $parsedMap = $detectHeaderMap($rows[$r]);
            if ($parsedMap !== null && ($parsedMap['name'] !== null || $parsedMap['nis'] !== null)) {
                $headerRowIndex = $r;
                $colMap = $parsedMap;
                break;
            }

            $rowCombinedText = implode(' ', $rows[$r]);
            if ($detectedClassFromTitle === null && preg_match('/(?:rombel|kelas)\s*[:\s-]?\s*([A-Za-z0-9\-\.\/]+(?:\s+(?!tahun|semester|tp|ta)[A-Za-z0-9\-\.\/]+)*)/i', $rowCombinedText, $m)) {
                $cand = trim($m[1]);
                if (! in_array(strtolower($cand), ['campuran', 'mpls', 'matrikulasi', '#ref!', 'nan'], true)) {
                    $detectedClassFromTitle = $cand;
                }
            }
        }

        if ($headerRowIndex !== null) {
            $startIndex = $headerRowIndex + 1;
        } else {
            $startIndex = 0;
            $firstRow = $rows[0];
            $isCol0Numeric = isset($firstRow[0]) && is_numeric(trim($firstRow[0]));
            $isCol1Numeric = isset($firstRow[1]) && is_numeric(trim($firstRow[1]));

            if ($isCol0Numeric && $isCol1Numeric && isset($firstRow[2])) {
                $colMap = [
                    'name' => 2,
                    'nis' => 1,
                    'class' => 3,
                    'jurusan' => null,
                    'grade' => null,
                    'username' => null,
                    'password' => null,
                ];
            } else {
                $colMap = [
                    'name' => 0,
                    'nis' => 1,
                    'class' => 2,
                    'jurusan' => null,
                    'grade' => null,
                    'username' => 3,
                    'password' => 4,
                ];
            }
        }

        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        DB::transaction(function () use (
            $rows,
            $startIndex,
            $colMap,
            $detectedClassFromTitle,
            $detectHeaderMap,
            $corruptedTokens,
            $fallbackPassword,
            $kasirRole,
            $exactSkipKeywords,
            $prefixSkipKeywords,
            &$createdCount,
            &$updatedCount,
            &$skippedCount
        ) {
            $totalRows = count($rows);
            $activeClassTitle = $detectedClassFromTitle;

            for ($i = $startIndex; $i < $totalRows; $i++) {
                $row = $rows[$i];

                // 1. Cek apakah baris ini merupakan header tabel baru (skenario multi-table dalam 1 file)
                $dynamicMap = $detectHeaderMap($row);
                if ($dynamicMap !== null && ($dynamicMap['name'] !== null || $dynamicMap['nis'] !== null)) {
                    $colMap = $dynamicMap;

                    continue;
                }

                // 2. Cek apakah ada penanda kelas/rombel baru pada judul tabel (hindari Wali Kelas)
                $rowCombinedText = implode(' ', $row);
                if (! preg_match('/wali\s*kelas/i', $rowCombinedText) && preg_match('/(?:rombel|(?<!wali\s)kelas)\s*[:\s-]?\s*([A-Za-z0-9\-\.\/]+(?:\s+(?!tahun|semester|tp|ta|guru|s\.pd|s\.m|m\.pd)[A-Za-z0-9\-\.\/]+)*)/i', $rowCombinedText, $m)) {
                    $cand = ltrim(trim($m[1]), ": \t\n\r\0\x0B");
                    if (! in_array(strtolower($cand), ['campuran', 'mpls', 'matrikulasi', '#ref!', 'nan'], true)) {
                        $activeClassTitle = $cand;
                    }
                }

                // 3. Ekstrak dan sanitasi Nama Lengkap
                $fullName = isset($colMap['name'], $row[$colMap['name']]) ? trim((string) $row[$colMap['name']]) : '';

                if ($fullName === '' || is_numeric($fullName)) {
                    continue;
                }

                // Abaikan jika cell diawali tanda baca atau berupa informasi kontak/link/yayasan
                if (str_starts_with($fullName, ':') || str_starts_with($fullName, '-') || str_starts_with($fullName, '.')) {
                    continue;
                }

                $lowerName = strtolower($fullName);

                if (preg_match('/(?:@|www\.|\.sch\.id|\.com|telp\.|0251-)/i', $lowerName)) {
                    continue;
                }

                // Abaikan jika cell berisi token formula rusak Excel (#REF!, NaN, dst)
                if (in_array($lowerName, $corruptedTokens, true)) {
                    continue;
                }

                // Abaikan jika cell ternyata berisi jenis kelamin (L/P/Laki-Laki/Perempuan)
                if (in_array($lowerName, ['l', 'p', 'laki-laki', 'perempuan', 'pria', 'wanita'], true)) {
                    if (isset($row[2]) && ! in_array(strtolower(trim($row[2])), ['l', 'p', 'laki-laki', 'perempuan'], true) && ! is_numeric(trim($row[2]))) {
                        $fullName = trim((string) $row[2]);
                        $lowerName = strtolower($fullName);
                    } else {
                        continue;
                    }
                }

                if (in_array($lowerName, $exactSkipKeywords, true)) {
                    continue;
                }

                $isHeaderOrSummary = false;
                foreach ($prefixSkipKeywords as $prefix) {
                    if (str_starts_with($lowerName, $prefix)) {
                        $isHeaderOrSummary = true;
                        break;
                    }
                }
                if ($isHeaderOrSummary) {
                    continue;
                }

                // Abaikan jika baris nama guru / kepsek / wakasek di tanda tangan
                if (preg_match('/^(?:nunuk mujiana|irma rohima|siti syarifah|zulfa tri astuti|komalasari|wakasek|guru mata pelajaran)/i', $lowerName)) {
                    continue;
                }

                // 4. Ekstrak dan sanitasi NIS
                $nis = isset($colMap['nis'], $row[$colMap['nis']]) ? trim((string) $row[$colMap['nis']]) : null;
                if ($nis !== null) {
                    $lowerNis = strtolower($nis);
                    if (in_array($lowerNis, $corruptedTokens, true) || ! is_numeric(preg_replace('/[^0-9]/', '', $nis))) {
                        $nis = null;
                    }
                }

                // 5. Ekstrak dan sanitasi Kelas / Rombel
                $class = isset($colMap['class'], $row[$colMap['class']]) ? trim((string) $row[$colMap['class']]) : null;
                if ($class !== null && in_array(strtolower($class), $corruptedTokens, true)) {
                    $class = null;
                }

                // Jika kolom rombel bernilai kosong / #REF!, coba bentuk dari kelas (tingkat) + program keahlian jika ada
                if ($class === null || $class === '') {
                    $grade = isset($colMap['grade'], $row[$colMap['grade']]) ? trim((string) $row[$colMap['grade']]) : '';
                    $jurusan = isset($colMap['jurusan'], $row[$colMap['jurusan']]) ? trim((string) $row[$colMap['jurusan']]) : '';
                    if ($grade !== '' && ! in_array(strtolower($grade), $corruptedTokens, true)) {
                        $class = $grade.($jurusan !== '' && ! in_array(strtolower($jurusan), $corruptedTokens, true) ? ' '.$jurusan : '');
                    } elseif ($jurusan !== '' && ! in_array(strtolower($jurusan), $corruptedTokens, true)) {
                        $class = $jurusan;
                    }
                }

                if (($class === null || $class === '') && $activeClassTitle !== null) {
                    $class = $activeClassTitle;
                }

                $userSpecified = isset($colMap['username'], $row[$colMap['username']]) ? trim((string) $row[$colMap['username']]) : '';
                $passSpecified = isset($colMap['password'], $row[$colMap['password']]) ? trim((string) $row[$colMap['password']]) : '';

                $username = $userSpecified;
                if ($username === '') {
                    if ($nis !== null && $nis !== '') {
                        $username = $nis;
                    } else {
                        $username = strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '', $fullName));
                    }
                }

                if ($username === '') {
                    $skippedCount++;

                    continue;
                }

                if ($passSpecified !== '') {
                    $passwordToUse = $passSpecified;
                } elseif ($nis !== null && $nis !== '') {
                    $passwordToUse = $nis;
                } elseif ($fallbackPassword !== null) {
                    $passwordToUse = $fallbackPassword;
                } else {
                    $passwordToUse = $username;
                }

                $existing = User::where('username', $username)->first();
                if ($existing) {
                    $updateData = [
                        'full_name' => $fullName,
                        'role_id' => $kasirRole->id,
                        'is_active' => true,
                    ];
                    if ($nis !== null && $nis !== '') {
                        $updateData['student_number'] = $nis;
                    }
                    if ($class !== null && $class !== '') {
                        $updateData['class_group'] = $class;
                    }
                    if ($passSpecified !== '') {
                        $updateData['password'] = $passSpecified;
                    }
                    $existing->update($updateData);
                    $updatedCount++;
                } else {
                    User::create([
                        'full_name' => $fullName,
                        'username' => $username,
                        'student_number' => $nis,
                        'class_group' => $class,
                        'password' => $passwordToUse,
                        'role_id' => $kasirRole->id,
                        'is_active' => true,
                    ]);
                    $createdCount++;
                }
            }
        });

        $totalProcessed = $createdCount + $updatedCount;
        if ($totalProcessed === 0) {
            return back()->with('error', 'Tidak ada data siswa yang valid untuk diimpor.');
        }

        $message = "Import berhasil! {$createdCount} siswa baru berhasil ditambahkan sebagai Kasir";
        if ($updatedCount > 0) {
            $message .= ", {$updatedCount} data siswa diperbarui";
        }
        if ($skippedCount > 0) {
            $message .= ", {$skippedCount} baris dilewati";
        }
        $message .= '.';

        return redirect()->route('admin.users')->with('success', $message);
    }

    public function editUser(User $user): View
    {
        $roles = Role::all();

        return view('admin.users-edit', compact('user', 'roles'));
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,'.$user->id],
            'role_id' => ['required', 'exists:roles,id'],
            'student_number' => ['nullable', 'string', 'max:50'],
            'class_group' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:6'],
            'is_active' => ['required', 'boolean'],
        ], [
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan, silakan pilih username lain.',
            'role_id.required' => 'Role wajib dipilih.',
            'role_id.exists' => 'Role yang dipilih tidak valid.',
            'password.min' => 'Password minimal harus 6 karakter.',
        ]);

        // Cegah perubahan yang membahayakan jika user ini satu-satunya Admin tersisa
        $user->loadMissing('role');
        if ($user->role && $user->role->name === 'Admin') {
            $totalAdmin = User::whereRelation('role', 'name', 'Admin')->count();
            if ($totalAdmin <= 1) {
                $adminRole = Role::where('name', 'Admin')->first();
                if ($adminRole && (int) $request->role_id !== (int) $adminRole->id) {
                    return back()->withErrors(['role_id' => 'Tidak dapat mengubah role karena user ini adalah satu-satunya Admin yang tersisa di sistem.'])->withInput();
                }

                if (! $request->boolean('is_active')) {
                    return back()->withErrors(['is_active' => 'Tidak dapat menonaktifkan satu-satunya Admin yang tersisa di sistem.'])->withInput();
                }
            }
        }

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('admin.users')->with('success', 'Data user '.$user->full_name.' berhasil diperbarui!');
    }

    public function deleteUser(User $user): View|RedirectResponse
    {
        // 1. Cegah menghapus akun yang sedang digunakan (diri sendiri)
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users')->with('error', 'Anda tidak dapat menghapus akun Anda sendiri demi keamanan sistem.');
        }

        // 2. Cegah menghapus jika merupakan satu-satunya Admin
        $user->loadMissing('role');
        if ($user->role && $user->role->name === 'Admin') {
            $totalAdmin = User::whereRelation('role', 'name', 'Admin')->count();
            if ($totalAdmin <= 1) {
                return redirect()->route('admin.users')->with('error', 'Tidak dapat menghapus user ini karena merupakan satu-satunya Admin yang tersisa di sistem.');
            }
        }

        return view('admin.users-delete', compact('user'));
    }

    public function destroyUser(User $user): RedirectResponse
    {
        // 1. Cegah menghapus akun yang sedang digunakan (diri sendiri)
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users')->with('error', 'Anda tidak dapat menghapus akun Anda sendiri demi keamanan sistem.');
        }

        // 2. Cegah menghapus jika merupakan satu-satunya Admin
        $user->loadMissing('role');
        if ($user->role && $user->role->name === 'Admin') {
            $totalAdmin = User::whereRelation('role', 'name', 'Admin')->count();
            if ($totalAdmin <= 1) {
                return redirect()->route('admin.users')->with('error', 'Tidak dapat menghapus user ini karena merupakan satu-satunya Admin yang tersisa di sistem.');
            }
        }

        $fullName = $user->full_name;
        $user->delete();

        return redirect()->route('admin.users')->with('success', 'User '.$fullName.' berhasil dihapus secara permanen.');
    }

    public function deleteAllCashiers(): RedirectResponse
    {
        $kasirRole = Role::where('name', 'Kasir')->first();
        if (! $kasirRole) {
            return redirect()->route('admin.users')->with('error', 'Role Kasir tidak ditemukan di sistem.');
        }

        $cashiersQuery = User::where('role_id', $kasirRole->id)->where('id', '!=', auth()->id());
        $count = $cashiersQuery->count();

        if ($count === 0) {
            return redirect()->route('admin.users')->with('error', 'Tidak ada data user dengan role Kasir untuk dihapus.');
        }

        DB::transaction(function () use ($cashiersQuery) {
            $cashierIds = $cashiersQuery->pluck('id');

            StaffAttendance::whereIn('user_id', $cashierIds)->delete();
            Transaction::whereIn('cashier_id', $cashierIds)->delete();
            StampDutyRecord::whereIn('cashier_id', $cashierIds)->update(['cashier_id' => null]);
            DailyRecap::whereIn('recorded_by', $cashierIds)->update(['recorded_by' => null]);
            DailyRecap::whereIn('verified_by', $cashierIds)->update(['verified_by' => null]);

            User::whereIn('id', $cashierIds)->delete();
        });

        return redirect()->route('admin.users')->with('success', "Berhasil menghapus seluruh user dengan role Kasir ({$count} user) secara permanen.");
    }

    public function eod()
    {
        $recaps = DailyRecap::with(['recordedBy', 'verifiedBy'])->latest('recap_date')->paginate(15);

        return view('admin.eod', compact('recaps'));
    }

    public function verifyEod(Request $request, DailyRecap $dailyRecap)
    {
        $dailyRecap->update([
            'verified_by' => auth()->id(),
            'notes' => $request->filled('teacher_note')
                ? ($dailyRecap->notes ? $dailyRecap->notes.' | Catatan Guru: '.$request->teacher_note : 'Catatan Guru: '.$request->teacher_note)
                : $dailyRecap->notes,
        ]);

        return back()->with('success', 'Laporan Rekap Harian tanggal '.$dailyRecap->recap_date->format('d/m/Y').' berhasil diverifikasi!');
    }
}
