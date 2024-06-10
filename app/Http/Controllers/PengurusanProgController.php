<?php

namespace App\Http\Controllers;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Http\Request;

class PengurusanProgController extends Controller
{
    //TRY
    public function tryQR()
    {
        return view('pengurusan_program.tryQR');
    }

    public function try()
    {
        return view('pengurusan_program.try');
    }

    //QR CODE
    public function qrCode()
    {
        $qrCode = QrCode::size(400)->generate('https://laravel.com/'); // Replace with your URL or data

        $pdf = PDF::loadView('pengurusan_program.qr_code', ['qrCode' => $qrCode]);

        return $pdf->download('qr_code.pdf');
    }

//    public function share()
//    {
//        $share_buttons = \Share::page(
//            'https://www.laravelclick.com/post/laravel-10-social-media-share-buttons-integration-tutorial',
//            'How to Add Social Media Share Button in Laravel 10 App?'
//        )
//            ->facebook()
//            ->twitter()
//            ->linkedin()
//            ->whatsapp()
//            ->telegram()
//            ->reddit();
//
//        $view_data['share_buttons'] = $share_buttons;
//
//        return view('post')->with($view_data);
//    }

    //PEGAWAI AADK
    public function daftarProgPA()
    {
        return view('pengurusan_program.pegawai_aadk.daftar_prog');
    }

    public function kemaskiniProgPA()
    {
        return view('pengurusan_program.pegawai_aadk.kemaskini_prog');
    }

    public function maklumatProgPA()
    {
        return view('pengurusan_program.pegawai_aadk.maklumat_prog');
    }

    public function senaraiProgPA()
    {
        return view('pengurusan_program.pegawai_aadk.senarai_prog');
    }

    //PEGAWAI SISTEM
    public function daftarProgPS()
    {
        return view('pengurusan_program.pentadbir_sistem.daftar_prog');
    }

    public function kemaskiniProgPS()
    {
        return view('pengurusan_program.pentadbir_sistem.kemaskini_prog');
    }

    public function maklumatProgPS()
    {
        return view('pengurusan_program.pentadbir_sistem.maklumat_prog');
    }

    public function senaraiProgPS()
    {
        return view('pengurusan_program.pentadbir_sistem.senarai_prog');
    }

    //KLIEN
    public function daftarKehadiran()
    {
        return view('pengurusan_program.klien.daftar_kehadiran');
    }

    public function pengesahanKehadiran()
    {
        return view('pengurusan_program.klien.pengesahan_kehadiran');
    }

}
