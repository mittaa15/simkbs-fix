<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;
use Dompdf\Dompdf;
use Dompdf\Options;

$koneksi = mysqli_connect("localhost", "root", "", "db_simkbs3");

if (!$koneksi) {
    die("Koneksi Gagal: " . mysqli_connect_error());
}

$recaptcha_secret = "6Ld4edsoAAAAAFEgP66J5N3Z3xC70d8JTJ3njPr9"; 
$recaptcha_response = $_POST['g-recaptcha-response'];

$url = 'https://www.google.com/recaptcha/api/siteverify';
$data = array(
    'secret' => $recaptcha_secret,
    'response' => $recaptcha_response
);

$options = array(
    'http' => array (
        'method' => 'POST',
        'content' => http_build_query($data)
    )
);

$context  = stream_context_create($options);
$verify = file_get_contents($url, false, $context);
$captcha_success = json_decode($verify);

if ($captcha_success->success == false) {
    // Jika reCAPTCHA tidak terverifikasi, munculkan pesan atau lakukan tindakan lain.
    die("reCAPTCHA tidak terverifikasi. Silakan kembali dan coba lagi.");
} else if ($captcha_success->success == true) {
    // Jika reCAPTCHA terverifikasi, lanjutkan dengan pemrosesan formulir.

    $jenis_surat = $_POST['jenis_surat'];
    $nama = $_POST['nama'];
    $nik = $_POST['nik']; // Sesuaikan dengan nama input di formulir
    $tempat_lahir = $_POST['tempat_lahir']; // Sesuaikan dengan nama input di formulir
    $tanggal_lahir = $_POST['tanggal_lahir']; // Sesuaikan dengan nama input di formulir
    $alamat = $_POST['alamat'];
    $email = $_POST['email'];
    $pekerjaan = $_POST['pekerjaan'];
    $agama = $_POST['agama'];
    $status_perkawinan = $_POST['status_perkawinan']; // Sesuaikan dengan nama input di formulir
    $jenis_kelamin = $_POST['jenis_kelamin']; // Sesuaikan dengan nama input di formulir
    $submit = $_POST['submit'];

    // Modifikasi query untuk mengizinkan NIK yang sama
    $query = "INSERT INTO tabel_form VALUES(NULL,'$jenis_surat','$nama','$nik','$tempat_lahir','$tanggal_lahir',
    '$alamat','$email','$pekerjaan','$agama','$status_perkawinan','$jenis_kelamin','$submit')
    ON DUPLICATE KEY UPDATE nama='$nama', tempat_lahir='$tempat_lahir', tanggal_lahir='$tanggal_lahir',
    alamat='$alamat', email='$email', pekerjaan='$pekerjaan', agama='$agama', 
    status_perkawinan='$status_perkawinan', jenis_kelamin='$jenis_kelamin', submit='$submit'";
    
    if ($koneksi->query($query) === TRUE) {
        // Jika operasi INSERT berhasil, dapatkan ID yang baru saja di-generate
        $newID = $koneksi->insert_id;
        echo "Data berhasil ditambahkan. ID baru: " . $newID;
    } else {
        echo "Error: " . $query . "<br>" . $koneksi->error;
    }

    mysqli_query($koneksi, $query);

    mysqli_close($koneksi);

    // Buka templat dokumen Word
    $templateProcessor = new TemplateProcessor('template_surat.docx');
    $currentDate = date('j F, Y ');
    // Isi variabel pada templat dengan data formulir
    $templateProcessor->setValue('jenis_surat', $jenis_surat);
    $templateProcessor->setValue('nama', $nama);
    $templateProcessor->setValue('nik', $nik);
    $templateProcessor->setValue('tempat_lahir', $tempat_lahir);
    $templateProcessor->setValue('tanggal_lahir', $tanggal_lahir);
    $templateProcessor->setValue('alamat', $alamat);
    $templateProcessor->setValue('email', $email);
    $templateProcessor->setValue('pekerjaan', $pekerjaan);
    $templateProcessor->setValue('agama', $agama);
    $templateProcessor->setValue('status_perkawinan', $status_perkawinan);
    $templateProcessor->setValue('jenis_kelamin', $jenis_kelamin);
    $templateProcessor->setValue('tanggal', $currentDate);
    $templateProcessor->setValue('submit', $submit);

    // NOMOR SURAT
    if ($jenis_surat == "SKCK") {
        $templateProcessor->setValue('kode_surat', '01');
    }
    elseif ($jenis_surat == "Surat Keterangan Belum Menikah"){
        $templateProcessor->setValue('kode_surat', '02');
    }
    elseif ($jenis_surat == "Surat Keterangan Bantuan"){
        $templateProcessor->setValue('kode_surat', '03');
    } 
    elseif ($jenis_surat == "Surat Keterangan Domisili"){
        $templateProcessor->setValue('kode_surat', '04');
    } 
    elseif ($jenis_surat == "Surat Keterangan Kelahiran"){
        $templateProcessor->setValue('kode_surat', '05');
    } 
    elseif ($jenis_surat == "Surat Keterangan Kematian"){
        $templateProcessor->setValue('kode_surat', '06');
    } 
    elseif ($jenis_surat == "Surat Keterangan Kepemilikan Kendaraan"){
        $templateProcessor->setValue('kode_surat', '07');
    } 
    elseif ($jenis_surat == "Surat Keterangan Kepindahan Penduduk"){
        $templateProcessor->setValue('kode_surat', '08');
    } 
    elseif ($jenis_surat == "Surat Keterangan Penghasilan Orang Tua"){
        $templateProcessor->setValue('kode_surat', '09');
    }
    elseif ($jenis_surat == "Surat Keterangan Tidak Mampu"){
        $templateProcessor->setValue('kode_surat', '10');
    } 
    elseif ($jenis_surat == "Surat Keterangan Usaha"){
        $templateProcessor->setValue('kode_surat', '11');
    } 
    else {
        $templateProcessor->setValue('kode_surat', '00');
    }

    $templateProcessor->setValue('id_surat', $newID);
    $templateProcessor->setValue('tahun', date('Y'));

    

    // Simpan dokumen Word yang telah diisi
    $templateProcessor->saveAs('surat_terisi.docx');

    // Setelah data berhasil disimpan, arahkan pengguna ke halaman pemberitahuan
    header("Location: notifikasi.php");
    
    exit(); // Pastikan untuk keluar dari skrip setelah melakukan pengalihan
}
?>
