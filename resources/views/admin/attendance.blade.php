@extends('layouts.admin')

@section('page_title', 'Rekap Presensi')

@section('admin_content')
@if(session('success'))
<div class="mb-4 p-4 rounded-xl bg-green-50 dark:bg-emerald-500/10 border border-green-200 dark:border-emerald-500/25 text-green-700 dark:text-emerald-300 text-sm flex items-center justify-between">
    <div class="flex items-center">
        <svg class="w-5 h-5 mr-3 text-green-500 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <span>{{ session('success') }}</span>
    </div>
</div>
@endif

@if($errors->any())
<div class="mb-4 p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/25 text-red-700 dark:text-red-300 text-sm">
    <ul class="list-disc list-inside space-y-1 text-xs text-red-600 dark:text-red-400">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
    <div class="p-6">
        <table class="w-full text-sm text-left">
            <thead class="text-xs text-gray-500 dark:text-zinc-400 uppercase bg-gray-50 dark:bg-zinc-800/70">
                <tr>
                    <th class="px-4 py-3 rounded-l-lg">Tanggal</th>
                    <th class="px-4 py-3">Nama Petugas / Kasir</th>
                    <th class="px-4 py-3">Kelas</th>
                    <th class="px-4 py-3">Catatan Siswa</th>
                    <th class="px-4 py-3">Catatan Guru</th>
                    <th class="px-4 py-3 rounded-r-lg">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $att)
                <tr class="border-b dark:border-zinc-800 hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors">
                    <td class="px-4 py-3 font-medium">{{ \Carbon\Carbon::parse($att->attendance_date)->format('d/m/Y') }}</td>
                    <td class="px-4 py-3">{{ $att->user?->full_name ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $att->user?->class_group ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $att->student_note ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $att->teacher_note ?? '-' }}</td>
                    <td class="px-4 py-3">
                        <button
                            type="button"
                            onclick='openAttendanceModal(@json($att->id), @json($att->user?->full_name ?? "-"), @json(\Carbon\Carbon::parse($att->attendance_date)->format("d/m/Y")), @json($att->student_note ?? ""), @json($att->teacher_note ?? ""))'
                            class="text-teal-600 dark:text-teal-400 hover:underline font-medium cursor-pointer"
                        >
                            Edit Catatan
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-zinc-500">Belum ada data presensi</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        <div class="mt-4">
            {{ $attendances->links() }}
        </div>
    </div>
</div>

<!-- Modal Edit Catatan Presensi -->
<div id="attendanceEditModal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black/40 dark:bg-black/70 backdrop-blur-sm transition-opacity p-4">
    <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-xl p-6 max-w-md w-full mx-4 border border-transparent dark:border-zinc-800">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-zinc-800 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-zinc-100">Edit Catatan Presensi</h3>
            <button type="button" onclick="closeAttendanceModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-zinc-200 text-xl leading-none cursor-pointer">&times;</button>
        </div>

        <form id="attendanceEditForm" method="POST" action="">
            @csrf
            @method('PUT')

            <div class="bg-gray-50 dark:bg-zinc-800/70 rounded-lg p-3 text-xs text-gray-600 dark:text-zinc-300 space-y-1 mb-4 border border-gray-100 dark:border-zinc-700">
                <div class="flex justify-between">
                    <span class="text-gray-400">Petugas Kasir:</span>
                    <span id="attModalName" class="font-semibold text-gray-800 dark:text-zinc-100"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Tanggal:</span>
                    <span id="attModalDate" class="font-medium text-gray-800 dark:text-zinc-100"></span>
                </div>
            </div>

            <div class="mb-4">
                <label for="att_student_note" class="block text-xs font-medium text-gray-700 dark:text-zinc-300 mb-1">Catatan Siswa</label>
                <textarea name="student_note" id="att_student_note" rows="2" maxlength="255" class="w-full text-xs rounded-lg bg-white dark:bg-zinc-950/60 border-gray-300 dark:border-zinc-700 text-gray-900 dark:text-zinc-100 dark:placeholder-zinc-500 shadow-sm focus:border-teal-500 focus:ring-teal-500 p-2.5 border" placeholder="Catatan shift siswa..."></textarea>
            </div>

            <div class="mb-5">
                <label for="att_teacher_note" class="block text-xs font-medium text-gray-700 dark:text-zinc-300 mb-1">Catatan Guru Pembimbing</label>
                <textarea name="teacher_note" id="att_teacher_note" rows="2" maxlength="255" class="w-full text-xs rounded-lg bg-white dark:bg-zinc-950/60 border-gray-300 dark:border-zinc-700 text-gray-900 dark:text-zinc-100 dark:placeholder-zinc-500 shadow-sm focus:border-teal-500 focus:ring-teal-500 p-2.5 border" placeholder="Evaluasi atau catatan guru..."></textarea>
            </div>

            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeAttendanceModal()" class="px-4 py-2 bg-white dark:bg-zinc-800 border border-gray-300 dark:border-zinc-700 rounded-lg text-xs font-medium text-gray-700 dark:text-zinc-200 hover:bg-gray-50 dark:hover:bg-zinc-700 cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-xs font-medium shadow-sm transition-colors cursor-pointer">
                    Simpan Catatan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAttendanceModal(id, name, date, studentNote, teacherNote) {
        document.getElementById('attModalName').textContent = name;
        document.getElementById('attModalDate').textContent = date;
        document.getElementById('att_student_note').value = studentNote || '';
        document.getElementById('att_teacher_note').value = teacherNote || '';
        document.getElementById('attendanceEditForm').action = '/admin/attendance/' + id;
        document.getElementById('attendanceEditModal').classList.remove('hidden');
    }

    function closeAttendanceModal() {
        document.getElementById('attendanceEditModal').classList.add('hidden');
    }

    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('attendanceEditModal');
        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    closeAttendanceModal();
                }
            });
        }
    });
</script>
@endsection
