<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\SimpleSpreadsheetReader;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    private function createAdminUser(): User
    {
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);

        return User::create([
            'role_id' => $adminRole->id,
            'full_name' => 'Administrator Utama',
            'username' => 'admin_test',
            'password' => 'secret123',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_download_user_import_template(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get(route('admin.users.template'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename="template_import_siswa_kasir.csv"');
        $this->assertStringContainsString('Nama Siswa', $response->streamedContent());
        $this->assertStringContainsString('NIS', $response->streamedContent());
        $this->assertStringContainsString('Kelas', $response->streamedContent());
    }

    public function test_admin_can_import_students_as_cashiers_from_csv(): void
    {
        $admin = $this->createAdminUser();
        $kasirRole = Role::firstOrCreate(['name' => 'Kasir']);

        $csvContent = "Nama Siswa,NIS,Kelas,Username,Password\n".
                      "Doni Prasetyo,12345601,XI RPL 1,doni.p,rahasia123\n".
                      "Eka Wahyuni,12345602,XI RPL 2,eka.w,password123\n";

        $file = UploadedFile::fake()->createWithContent('siswa.csv', $csvContent);

        $response = $this->actingAs($admin)->post(route('admin.users.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.users'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'full_name' => 'Doni Prasetyo',
            'username' => 'doni.p',
            'student_number' => '12345601',
            'class_group' => 'XI RPL 1',
            'role_id' => $kasirRole->id,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'full_name' => 'Eka Wahyuni',
            'username' => 'eka.w',
            'student_number' => '12345602',
            'class_group' => 'XI RPL 2',
            'role_id' => $kasirRole->id,
            'is_active' => true,
        ]);
    }

    public function test_import_generates_username_and_default_password_when_empty(): void
    {
        $admin = $this->createAdminUser();
        $kasirRole = Role::firstOrCreate(['name' => 'Kasir']);

        $csvContent = "Nama Siswa,NIS,Kelas,Username,Password\n".
                      "Farhan Maulana,12345603,XI TKJ 1,,\n";

        $file = UploadedFile::fake()->createWithContent('siswa_auto.csv', $csvContent);

        $response = $this->actingAs($admin)->post(route('admin.users.import'), [
            'file' => $file,
            'default_password' => 'defaultpass123',
        ]);

        $response->assertRedirect(route('admin.users'));

        // Username otomatis pakai NIS jika username kosong
        $user = User::where('student_number', '12345603')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Farhan Maulana', $user->full_name);
        $this->assertEquals('12345603', $user->username);
        $this->assertEquals('XI TKJ 1', $user->class_group);
        $this->assertEquals($kasirRole->id, $user->role_id);
        $this->assertTrue(Hash::check('defaultpass123', $user->password));
    }

    public function test_import_updates_existing_user_when_username_matches(): void
    {
        $admin = $this->createAdminUser();
        $kasirRole = Role::firstOrCreate(['name' => 'Kasir']);

        User::create([
            'full_name' => 'Nama Lama',
            'username' => 'siswa_lama',
            'student_number' => '111111',
            'class_group' => 'X RPL 1',
            'password' => 'secret123',
            'role_id' => $kasirRole->id,
            'is_active' => true,
        ]);

        $csvContent = "Nama Siswa,NIS,Kelas,Username,Password\n".
                      "Nama Baru Diperbarui,111111,XI RPL 1,siswa_lama,\n";

        $file = UploadedFile::fake()->createWithContent('update.csv', $csvContent);

        $response = $this->actingAs($admin)->post(route('admin.users.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.users'));

        $user = User::where('username', 'siswa_lama')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Nama Baru Diperbarui', $user->full_name);
        $this->assertEquals('XI RPL 1', $user->class_group);
    }

    public function test_simple_spreadsheet_reader_reads_csv_and_semicolon_delimited(): void
    {
        $content = "Nama;NIS;Kelas\nAndi;999;XI RPL 1\n";
        $tempFile = tempnam(sys_get_temp_dir(), 'test_csv_').'.csv';
        file_put_contents($tempFile, $content);

        $rows = SimpleSpreadsheetReader::read($tempFile);
        $this->assertCount(2, $rows);
        $this->assertEquals(['Nama', 'NIS', 'Kelas'], $rows[0]);
        $this->assertEquals(['Andi', '999', 'XI RPL 1'], $rows[1]);

        @unlink($tempFile);
    }

    public function test_import_handles_school_attendance_sheet_format(): void
    {
        $admin = $this->createAdminUser();
        $kasirRole = Role::firstOrCreate(['name' => 'Kasir']);

        $csvContent = "DAFTAR HADIR SISWA KELAS XI RPL 1\n".
                      "SMK NEGERI 46 JAKARTA\n".
                      "\n".
                      "No,NIS,Nama Siswa,L/P,1,2,3,4\n".
                      "1,10201,Aditya Pratama,L,,,,\n".
                      "2,10202,Budi Santoso,L,,,,\n".
                      "Mengetahui,,,,\n".
                      "Wali Kelas,,,,\n";

        $file = UploadedFile::fake()->createWithContent('daftar_hadir.csv', $csvContent);

        $response = $this->actingAs($admin)->post(route('admin.users.import'), [
            'file' => $file,
            'default_password' => 'pass12345',
        ]);

        $response->assertRedirect(route('admin.users'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'full_name' => 'Aditya Pratama',
            'student_number' => '10201',
            'class_group' => 'XI RPL 1',
            'role_id' => $kasirRole->id,
        ]);

        $this->assertDatabaseHas('users', [
            'full_name' => 'Budi Santoso',
            'student_number' => '10202',
            'class_group' => 'XI RPL 1',
            'role_id' => $kasirRole->id,
        ]);

        $this->assertDatabaseMissing('users', [
            'full_name' => 'Mengetahui',
        ]);

        $this->assertDatabaseMissing('users', [
            'full_name' => 'Wali Kelas',
        ]);
    }

    public function test_import_handles_complex_multi_table_school_dataset(): void
    {
        $admin = $this->createAdminUser();
        $kasirRole = Role::firstOrCreate(['name' => 'Kasir']);

        $complexCsv = "NO.,NIS,NISN,NAMA,JK,RAYON,ROMBEL MPLS,ROMBEL MATRIKULASI,ROMBEL REGULER,KELAS,PKL,PROGRAM KEAHLIAN\n".
                      "1,12510784,3108298154,Ade Solihin,L,Taj 4,P-9,M-9,#REF!,X,,DKV\n".
                      "109,12410148,0098680292,Afifah Syahira Wardiana,P,Cib 3,DKV XI-1,M-DKV XI-1,DKV XI-1,XI,,DKV\n".
                      "Laporan Pengisian Kuesioner Gaya Belajar ,,,,,,,\n".
                      "No ,NIS ,Nama ,JK ,Rombel ,Rayon ,Keterangan ,Gaya belajar \n".
                      "1,12510778,Abdee Munzie Alazkha,Laki-Laki,P-1,Cia 1,Belum,Kinestetik\n".
                      "Email,Nama,NIS,Rombel\n".
                      "afdhal@smkwikrama.sch.id,Afdhal Hadi Solahudin,12410147,P 1\n".
                      "Rombel,,: MPLB XII-1,,,,,,,,,,,\n".
                      "No,NIS,Nama,Gaya Belajar,Rayon,Tanggal\n".
                      "1,12410178,Alnyra Aisyah Rachman,Audiotori Visual,Cis 3,\n".
                      "\"Nunuk Mujiana, S.Pd.\",,,,,\n";

        $file = UploadedFile::fake()->createWithContent('complex_school.csv', $complexCsv);

        $response = $this->actingAs($admin)->post(route('admin.users.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.users'));
        $response->assertSessionHas('success');

        // Siswa 1: Kolom rombel bernilai #REF!, berhasil disintesis jadi 'X DKV'
        $this->assertDatabaseHas('users', [
            'full_name' => 'Ade Solihin',
            'username' => '12510784',
            'student_number' => '12510784',
            'class_group' => 'X DKV',
            'role_id' => $kasirRole->id,
        ]);

        // Siswa 2: Kolom rombel reguler 'DKV XI-1'
        $this->assertDatabaseHas('users', [
            'full_name' => 'Afifah Syahira Wardiana',
            'username' => '12410148',
            'student_number' => '12410148',
            'class_group' => 'DKV XI-1',
        ]);

        // Siswa 3: Format kuesioner dengan kolom JK di index 3, nama tidak tertukar jadi 'Laki-Laki'
        $this->assertDatabaseHas('users', [
            'full_name' => 'Abdee Munzie Alazkha',
            'username' => '12510778',
            'student_number' => '12510778',
        ]);
        $this->assertDatabaseMissing('users', [
            'full_name' => 'Laki-Laki',
        ]);

        // Siswa 4: Format Email di index 0, Nama di index 1, NIS di index 2
        $this->assertDatabaseHas('users', [
            'full_name' => 'Afdhal Hadi Solahudin',
            'username' => '12410147',
            'student_number' => '12410147',
        ]);

        // Siswa 5: Format Daftar Hadir per Rombel 'MPLB XII-1'
        $this->assertDatabaseHas('users', [
            'full_name' => 'Alnyra Aisyah Rachman',
            'username' => '12410178',
            'student_number' => '12410178',
            'class_group' => 'MPLB XII-1',
        ]);

        // Guru / footer tidak ikut masuk ke tabel user
        $this->assertDatabaseMissing('users', [
            'full_name' => 'Nunuk Mujiana, S.Pd.',
        ]);
    }
}
