<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Dompdf;
use App\Models\User;
use App\Models\Daerah;
use App\Models\KeluargaKlien;
use App\Models\Negeri;
use App\Models\Klien;
use App\Models\PekerjaanKlien;
use App\Models\RawatanKlien;
use App\Models\WarisKlien;
use App\Models\KlienUpdateRequest;
use App\Models\KeluargaKlienUpdateRequest;
use App\Models\PekerjaanKlienUpdateRequest;
use App\Models\RawatanKlienUpdateRequest;
use App\Models\SejarahProfilKlien;
use App\Models\WarisKlienUpdateRequest;
use Illuminate\Support\Facades\Log;

class ProfilKlienController extends Controller
{
    // PENTADBIR & STAF
    public function senaraiKlien()
    {
        $klien = Klien::select('klien.*', 'users.name as pengemaskini_name')
                        ->leftJoin('sejarah_profil_klien', function($join) {
                            $join->on('klien.id', '=', 'sejarah_profil_klien.klien_id')
                                ->whereRaw('sejarah_profil_klien.id = (SELECT MAX(id) FROM sejarah_profil_klien WHERE klien_id = klien.id)');
                        })
                        ->leftJoin('users', 'sejarah_profil_klien.pengemaskini', '=', 'users.id')
                        ->get();

        return view('profil_klien.pentadbir_pegawai.senarai', compact('klien'));
    }

    public function maklumatKlien($id)
    {
        $negeri = Negeri::all()->sortBy('negeri');
        $daerah = Daerah::all()->sortBy('daerah');
        $negeriKerja = Negeri::all()->sortBy('negeri');
        $daerahKerja = Daerah::all()->sortBy('daerah');
        $negeriWaris = Negeri::all()->sortBy('negeri');
        $daerahWaris = Daerah::all()->sortBy('daerah');
        $negeriPasangan = Negeri::all()->sortBy('negeri');
        $daerahPasangan = Daerah::all()->sortBy('daerah');
        $negeriKerjaPasangan = Negeri::all()->sortBy('negeri');
        $daerahKerjaPasangan = Daerah::all()->sortBy('daerah');

        // PERIBADI
        $klien = Klien::where('id', $id)->first();
        $requestKlien = KlienUpdateRequest::where('klien_id', $id)->where('status', 'Kemaskini')->first();
        $updateRequestKlien = KlienUpdateRequest::where('klien_id', $id)->first();
        $requestedDataKlien = $updateRequestKlien ? json_decode($updateRequestKlien->requested_data, true) : [];  // Decode the requested data updates                  

        // PEKERJAAN  
        $pekerjaan = PekerjaanKlien::where('klien_id', $id)->first();
        $requestPekerjaan = PekerjaanKlienUpdateRequest::where('klien_id', $id)->where('status', 'Kemaskini')->first();
        $updateRequestPekerjaan = PekerjaanKlienUpdateRequest::where('klien_id', $id)->first();
        $requestedDataPekerjaan = $updateRequestPekerjaan ? json_decode($updateRequestPekerjaan->requested_data, true) : [];

        // WARIS
        $waris = WarisKlien::where('klien_id',$id)->first();
        $requestWaris = WarisKlienUpdateRequest::where('klien_id', $id)->where('status', 'Kemaskini')->first();

        $updateRequestBapa = WarisKlienUpdateRequest::where('klien_id', $id)->where('waris', 1)->first();
        $requestedDataBapa = $updateRequestBapa ? json_decode($updateRequestBapa->requested_data, true) : [];
        $statusBapa = $updateRequestBapa ? $updateRequestBapa->status : null;

        $updateRequestIbu = WarisKlienUpdateRequest::where('klien_id', $id)->where('waris', 2)->first();
        $requestedDataIbu = $updateRequestIbu ? json_decode($updateRequestIbu->requested_data, true) : [];
        $statusIbu = $updateRequestIbu ? $updateRequestIbu->status : null;

        $updateRequestPenjaga = WarisKlienUpdateRequest::where('klien_id', $id)->where('waris', 3)->first();
        $requestedDataPenjaga = $updateRequestPenjaga ? json_decode($updateRequestPenjaga->requested_data, true) : [];
        $statusPenjaga = $updateRequestPenjaga ? $updateRequestPenjaga->status : null;

        // PASANGAN
        $pasangan = KeluargaKlien::where('klien_id',$id)->first();
        $requestPasangan = KeluargaKlienUpdateRequest::where('klien_id', $id)->where('status', 'Kemaskini')->first();
        $updateRequestPasangan = KeluargaKlienUpdateRequest::where('klien_id', $id)->first();
        $requestedDataPasangan = $updateRequestPasangan ? json_decode($updateRequestPasangan->requested_data, true) : [];

        // RAWATAN
        $rawatan = RawatanKlien::where('klien_id',$id)->first();

        return view('profil_klien.pentadbir_pegawai.kemaskini', compact('daerah','negeri','daerahKerja','negeriKerja','negeriWaris','daerahWaris','negeriPasangan','daerahPasangan','negeriKerjaPasangan','daerahKerjaPasangan',
                                                                        'klien', 'requestKlien', 'updateRequestKlien','requestedDataKlien',
                                                                        'pekerjaan','requestPekerjaan', 'updateRequestPekerjaan','requestedDataPekerjaan', 
                                                                        'waris', 'requestWaris', 'updateRequestBapa','requestedDataBapa','statusBapa','updateRequestIbu','requestedDataIbu','statusIbu','updateRequestPenjaga','requestedDataPenjaga','statusPenjaga',
                                                                        'pasangan', 'requestPasangan', 'updateRequestPasangan','requestedDataPasangan',
                                                                        'rawatan'));
    }

    // PEGAWAI/PENTADBIR : APPROVAL CLIENT'S REQUEST
    public function approveUpdateKlien(Request $request, $id)
    {
        $updateRequest = KlienUpdateRequest::where('klien_id', $id)->first();
        $klien = Klien::where('id', $id)->first();
        $sejarahProfil = SejarahProfilKlien::where('klien_id', $klien->id)->first();

        if ($request->status == 'Lulus') 
        {
            $requestedDataKlien = json_decode($updateRequest->requested_data, true);

            // Update the _klien with the requested data
            $klien->update($requestedDataKlien);
            $updateRequest->update(['status' => $request->status]);
            $klien->update(['status_kemaskini' => $request->status]);

            if (!$sejarahProfil) {
                SejarahProfilKlien::create([
                    'klien_id' => $klien->id,
                    'status_kemaskini' => 'Lulus',
                    'bahagian_kemaskini' => 'Klien',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }
            else{
                $sejarahProfil->update([
                    'status_kemaskini' => 'Lulus',
                    'bahagian_kemaskini' => 'Klien',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }

            return redirect()->back()->with('success', 'Maklumat peribadi klien telah berjaya dikemaskini.');
        }
        else
        {
            $updateRequest->update(['status' => $request->status]);
            $klien->update(['status_kemaskini' => $request->status]);

            if (!$sejarahProfil) {
                SejarahProfilKlien::create([
                    'klien_id' => $klien->id,
                    'status_kemaskini' => 'Ditolak',
                    'bahagian_kemaskini' => 'Klien',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }
            else{
                $sejarahProfil->update([
                    'status_kemaskini' => 'Ditolak',
                    'bahagian_kemaskini' => 'Klien',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }

            return redirect()->back()->with('error', 'Maklumat peribadi klien tidak berjaya dikemaskini.');
        }   
    }

    public function approveUpdatePekerjaan(Request $request, $id)
    {
        $updateRequestPekerjaan = PekerjaanKlienUpdateRequest::where('klien_id', $id)->first();
        $pekerjaanKlien = PekerjaanKlien::where('klien_id', $id)->first();
        $sejarahProfil = SejarahProfilKlien::where('klien_id', $pekerjaanKlien->klien_id)->first();

        if ($request->status == 'Lulus') 
        {
            $requestedData = json_decode($updateRequestPekerjaan->requested_data, true);

            // Update the pekerjaan_klien with the requested data
            $pekerjaanKlien->update($requestedData);
            $updateRequestPekerjaan->update(['status' => $request->status]);
            $pekerjaanKlien->update(['status_kemaskini' => $request->status]);

            if (!$sejarahProfil) {
                SejarahProfilKlien::create([
                    'klien_id' => $pekerjaanKlien->klien_id,
                    'status_kemaskini' => 'Lulus',
                    'bahagian_kemaskini' => 'Pekerjaan',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }
            else{
                $sejarahProfil->update([
                    'status_kemaskini' => 'Lulus',
                    'bahagian_kemaskini' => 'Pekerjaan',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }

            return redirect()->back()->with('success', 'Maklumat pekerjaan klien telah berjaya dikemaskini.');
        }
        else
        {
            $updateRequestPekerjaan->update(['status' => $request->status]);
            $pekerjaanKlien->update(['status_kemaskini' => $request->status]);

            if (!$sejarahProfil) {
                SejarahProfilKlien::create([
                    'klien_id' => $pekerjaanKlien->klien_id,
                    'status_kemaskini' => 'Ditolak',
                    'bahagian_kemaskini' => 'Pekerjaan',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }
            else{
                $sejarahProfil->update([
                    'status_kemaskini' => 'Ditolak',
                    'bahagian_kemaskini' => 'Pekerjaan',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }

            return redirect()->back()->with('error', 'Maklumat pekerjaan klien tidak berjaya dikemaskini.');
        }   
    }

    public function approveUpdateKeluarga(Request $request, $id)
    {
        $updateRequestPasangan = KeluargaKlienUpdateRequest::where('klien_id', $id)->first();
        $pasanganKlien = KeluargaKlien::where('klien_id', $id)->first();
        $sejarahProfil = SejarahProfilKlien::where('klien_id', $pasanganKlien->klien_id)->first();

        if ($request->status == 'Lulus') 
        {
            $requestedDataPasangan = json_decode($updateRequestPasangan->requested_data, true);

            // Update the keluarga_klien with the requested data
            $pasanganKlien->update($requestedDataPasangan);
            $updateRequestPasangan->update(['status' => $request->status]);
            $pasanganKlien->update(['status_kemaskini' => $request->status]);

            if (!$sejarahProfil) {
                SejarahProfilKlien::create([
                    'klien_id' => $pasanganKlien->klien_id,
                    'status_kemaskini' => 'Lulus',
                    'bahagian_kemaskini' => 'Keluarga',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }
            else{
                $sejarahProfil->update([
                    'status_kemaskini' => 'Lulus',
                    'bahagian_kemaskini' => 'Keluarga',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }

            return redirect()->back()->with('success', 'Maklumat pasangan klien telah berjaya dikemaskini.');
        }
        else
        {
            $updateRequestPasangan->update(['status' => $request->status]);
            $pasanganKlien->update(['status_kemaskini' => $request->status]);

            if (!$sejarahProfil) {
                SejarahProfilKlien::create([
                    'klien_id' => $pasanganKlien->klien_id,
                    'status_kemaskini' => 'Ditolak',
                    'bahagian_kemaskini' => 'Keluarga',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }
            else{
                $sejarahProfil->update([
                    'status_kemaskini' => 'Ditolak',
                    'bahagian_kemaskini' => 'Keluarga',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }

            return redirect()->back()->with('error', 'Maklumat pasangan klien tidak berjaya dikemaskini.');
        }   
    }

    public function approveUpdateBapa(Request $request, $id)
    {
        $updateRequestBapa = WarisKlienUpdateRequest::where('klien_id', $id)->where('waris', 1)->first();
        $warisKlien = WarisKlien::where('klien_id', $id)->first();
        $sejarahProfil = SejarahProfilKlien::where('klien_id', $warisKlien->klien_id)->first();

        if ($request->status == 'Lulus') 
        {
            $requestedDataWaris = json_decode($updateRequestBapa->requested_data, true);

            // Update the Waris_klien with the requested data
            $warisKlien->update($requestedDataWaris);
            $updateRequestBapa->update(['status' => $request->status]);
            $warisKlien->update(['status_kemaskini' => $request->status]);

            if (!$sejarahProfil) {
                SejarahProfilKlien::create([
                    'klien_id' => $warisKlien->klien_id,
                    'status_kemaskini' => 'Lulus',
                    'bahagian_kemaskini' => 'Waris',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }
            else{
                $sejarahProfil->update([
                    'status_kemaskini' => 'Lulus',
                    'bahagian_kemaskini' => 'Waris',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }

            return redirect()->back()->with('success', 'Maklumat bapa klien telah berjaya dikemaskini.');
        }
        else
        {
            $updateRequestBapa->update(['status' => $request->status]);
            $warisKlien->update(['status_kemaskini' => $request->status]);

            if (!$sejarahProfil) {
                SejarahProfilKlien::create([
                    'klien_id' => $warisKlien->klien_id,
                    'status_kemaskini' => 'Ditolak',
                    'bahagian_kemaskini' => 'Waris',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }
            else{
                $sejarahProfil->update([
                    'status_kemaskini' => 'Ditolak',
                    'bahagian_kemaskini' => 'Waris',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }

            return redirect()->back()->with('error', 'Maklumat bapa klien tidak berjaya dikemaskini.');
        }    
    }

    public function approveUpdateIbu(Request $request, $id)
    {
        $updateRequestIbu = WarisKlienUpdateRequest::where('klien_id', $id)->where('waris', 2)->first();
        $warisKlien = WarisKlien::where('klien_id', $id)->first();
        $sejarahProfil = SejarahProfilKlien::where('klien_id', $warisKlien->klien_id)->first();

        if ($request->status == 'Lulus') 
        {
            $requestedDataWaris = json_decode($updateRequestIbu->requested_data, true);

            // Update the Waris_klien with the requested data
            $warisKlien->update($requestedDataWaris);
            $updateRequestIbu->update(['status' => $request->status]);
            $warisKlien->update(['status_kemaskini' => $request->status]);

            if (!$sejarahProfil) {
                SejarahProfilKlien::create([
                    'klien_id' => $warisKlien->klien_id,
                    'status_kemaskini' => 'Lulus',
                    'bahagian_kemaskini' => 'Waris',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }
            else{
                $sejarahProfil->update([
                    'status_kemaskini' => 'Lulus',
                    'bahagian_kemaskini' => 'Waris',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }

            return redirect()->back()->with('success', 'Maklumat ibu klien telah berjaya dikemaskini.');
        }
        else
        {
            $updateRequestIbu->update(['status' => $request->status]);
            $warisKlien->update(['status_kemaskini' => $request->status]);

            if (!$sejarahProfil) {
                SejarahProfilKlien::create([
                    'klien_id' => $warisKlien->klien_id,
                    'status_kemaskini' => 'Ditolak',
                    'bahagian_kemaskini' => 'Waris',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }
            else{
                $sejarahProfil->update([
                    'status_kemaskini' => 'Ditolak',
                    'bahagian_kemaskini' => 'Waris',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }

            return redirect()->back()->with('error', 'Maklumat ibu klien tidak berjaya dikemaskini.');
        }    
    }

    public function approveUpdatePenjaga(Request $request, $id)
    {
        $updateRequestPenjaga = WarisKlienUpdateRequest::where('klien_id', $id)->where('waris', 3)->first();
        $warisKlien = WarisKlien::where('klien_id', $id)->first();
        $sejarahProfil = SejarahProfilKlien::where('klien_id', $warisKlien->klien_id)->first();

        if ($request->status == 'Lulus') 
        {
            $requestedDataWaris = json_decode($updateRequestPenjaga->requested_data, true);

            // Update the Waris_klien with the requested data
            $warisKlien->update($requestedDataWaris);
            $updateRequestPenjaga->update(['status' => $request->status]);
            $warisKlien->update(['status_kemaskini' => $request->status]);

            if (!$sejarahProfil) {
                SejarahProfilKlien::create([
                    'klien_id' => $warisKlien->klien_id,
                    'status_kemaskini' => 'Lulus',
                    'bahagian_kemaskini' => 'Waris',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }
            else{
                $sejarahProfil->update([
                    'status_kemaskini' => 'Lulus',
                    'bahagian_kemaskini' => 'Waris',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }

            return redirect()->back()->with('success', 'Maklumat penjaga klien telah berjaya dikemaskini.');
        }
        else
        {
            $updateRequestPenjaga->update(['status' => $request->status]);
            $warisKlien->update(['status_kemaskini' => $request->status]);

            if (!$sejarahProfil) {
                SejarahProfilKlien::create([
                    'klien_id' => $warisKlien->klien_id,
                    'status_kemaskini' => 'Ditolak',
                    'bahagian_kemaskini' => 'Waris',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }
            else{
                $sejarahProfil->update([
                    'status_kemaskini' => 'Ditolak',
                    'bahagian_kemaskini' => 'Waris',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }

            return redirect()->back()->with('error', 'Maklumat penjaga klien tidak berjaya dikemaskini.');
        }    
    }


    // PENTADBIR/PEGAWAI : UPDATE WITHOUT REQUEST
    public function kemaskiniMaklumatPeribadiKlien(Request $request, $id)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'no_tel'                    => 'required|string|max:11',
            'emel'                      => 'required|email',
            'alamat_rumah_klien'        => 'required|string|max:255',
            'poskod_klien'              => 'required|string|max:5',
            'daerah_klien'              => 'required|string|max:255',
            'negeri_klien'              => 'required|string|max:255',
            'tahap_pendidikan'          => 'required|string|max:255',
            'status_kesihatan_mental'   => 'required|string|max:255',
            'status_oku'                => 'required|string|max:255',
        ]);

        // Map the validated data to the original field names
        $updateData = [
            'no_tel'                   => $validatedData['no_tel'],
            'emel'                     => $validatedData['emel'],
            'alamat_rumah'             => $validatedData['alamat_rumah_klien'],
            'poskod'                   => $validatedData['poskod_klien'],
            'daerah'                   => $validatedData['daerah_klien'],
            'negeri'                   => $validatedData['negeri_klien'],
            'tahap_pendidikan'         => $validatedData['tahap_pendidikan'],
            'status_kesihatan_mental'  => $validatedData['status_kesihatan_mental'],
            'status_oku'               => $validatedData['status_oku'],
        ];

        // Find the client
        $klien = Klien::find($id);
        $sejarahProfil = SejarahProfilKlien::where('klien_id', $klien->id)->first();

        if ($klien) {
            // Update the client with the mapped data
            $klien->update($updateData);
            $klien->update(['status_kemaskini' => 'Lulus','updated_at' => now()]);

            if (!$sejarahProfil) {
                SejarahProfilKlien::create([
                    'klien_id' => $klien->id,
                    'status_kemaskini' => 'Lulus',
                    'bahagian_kemaskini' => 'Klien',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }
            else{
                $sejarahProfil->update([
                    'status_kemaskini' => 'Lulus',
                    'bahagian_kemaskini' => 'Klien',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }

            return redirect()->back()->with('success', 'Maklumat profil telah berjaya dikemaskini.');
        } 
        else {
            return redirect()->back()->with('error', 'Klien tidak dijumpai.');
        }
    }

    public function kemaskiniMaklumatPekerjaanKlien(Request $request, $id)
    {
        $validatedData = $request->validate([
            'status_kerja'      => 'required|string|max:11',
            'bidang_kerja'      => 'nullable|string|max:255',
            'nama_kerja'        => 'nullable|string|max:255',
            'pendapatan'        => 'nullable|string|max:255',
            'kategori_majikan'  => 'nullable|string|max:255',
            'nama_majikan'      => 'nullable|string|max:255',
            'no_tel_majikan'    => 'nullable|string|max:11',
            'alamat_kerja'      => 'nullable|string|max:255',
            'poskod_kerja'      => 'nullable|string|max:5',
            'daerah_kerja'      => 'nullable|string|max:255',
            'negeri_kerja'      => 'nullable|string|max:255',
        ]);

        $pekerjaanKlien = PekerjaanKlien::where('klien_id',$id)->first();
        $sejarahProfil = SejarahProfilKlien::where('klien_id', $pekerjaanKlien->klien_id)->first();

        if ($pekerjaanKlien) {
            $pekerjaanKlien->update($validatedData);
            $pekerjaanKlien->update(['status_kemaskini' => 'Lulus','updated_at' => now()]);

            if (!$sejarahProfil) {
                SejarahProfilKlien::create([
                    'klien_id' => $pekerjaanKlien->id,
                    'status_kemaskini' => 'Lulus',
                    'bahagian_kemaskini' => 'Pekerjaan',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }
            else{
                $sejarahProfil->update([
                    'status_kemaskini' => 'Lulus',
                    'bahagian_kemaskini' => 'Pekerjaan',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }
    
            return redirect()->back()->with('success', 'Maklumat pekerjaan klien berjaya dikemaskini.');
        } 
        else {
            return redirect()->back()->with('error', 'Klien tidak dijumpai.');
        }
    }

    public function kemaskiniMaklumatKeluargaKlien(Request $request, $id)
    {
        $validatedData = $request->validate([
            'status_perkahwinan'    => 'required|string|max:255',
            'nama_pasangan'         => 'nullable|string|max:255',
            'no_tel_pasangan'       => 'nullable|string|max:11',
            'bilangan_anak'         => 'nullable|integer',
            'alamat_pasangan'       => 'nullable|string|max:255',
            'poskod_pasangan'       => 'nullable|string|max:5',
            'daerah_pasangan'       => 'nullable|string|max:255',
            'negeri_pasangan'       => 'nullable|string|max:255',
            'alamat_kerja_pasangan' => 'nullable|string|max:255',
            'poskod_kerja_pasangan' => 'nullable|string|max:5',
            'daerah_kerja_pasangan' => 'nullable|string|max:255',
            'negeri_kerja_pasangan' => 'nullable|string|max:255',
        ]);

        // Set default values to null if they match "Pilih Daerah" or "Pilih Negeri"
        if ($validatedData['daerah_pasangan'] === 'Pilih Daerah') {
            $validatedData['daerah_pasangan'] = null;
        }
        if ($validatedData['negeri_pasangan'] === 'Pilih Negeri') {
            $validatedData['negeri_pasangan'] = null;
        }
        if ($validatedData['daerah_kerja_pasangan'] === 'Pilih Daerah') {
            $validatedData['daerah_kerja_pasangan'] = null;
        }
        if ($validatedData['negeri_kerja_pasangan'] === 'Pilih Negeri') {
            $validatedData['negeri_kerja_pasangan'] = null;
        }

        $pasangan = KeluargaKlien::where('id', $id)->first();
        $sejarahProfil = SejarahProfilKlien::where('klien_id', $pasangan->klien_id)->first();

        if ($pasangan) {
            $pasangan->update($validatedData);
            $pasangan->update(['status_kemaskini' => 'Lulus','updated_at' => now()]);

            if (!$sejarahProfil) {
                SejarahProfilKlien::create([
                    'klien_id' => $pasangan->klien_id,
                    'status_kemaskini' => 'Lulus',
                    'bahagian_kemaskini' => 'Keluarga',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }
            else{
                $sejarahProfil->update([
                    'status_kemaskini' => 'Lulus',
                    'bahagian_kemaskini' => 'Keluarga',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }

            return redirect()->back()->with('success', 'Maklumat pasangan klien berjaya dikemaskini.');
        } 
        else {
            return redirect()->back()->with('error', 'Klien tidak dijumpai.');
        }
    }

    public function kemaskiniMaklumatBapaKlien(Request $request, $id)
    {
        $validatedData = $request->validate([
            'nama_bapa' => 'nullable|string|max:255',
            'no_kp_bapa'  => 'nullable|string|max:255',
            'no_tel_bapa' => 'nullable|string|max:11',
            'status_bapa' => 'nullable|string|max:255',
            'alamat_bapa' => 'nullable|string|max:255',
            'poskod_bapa' => 'nullable|string|max:5',
            'daerah_bapa' => 'nullable|string|max:255',
            'negeri_bapa' => 'nullable|string|max:255',
        ]);

        $waris = WarisKlien::where('id', $id)->first();
        $sejarahProfil = SejarahProfilKlien::where('klien_id', $waris->klien_id)->first();

        if ($waris) {
            $waris->update($validatedData);
            $waris->update(['status_kemaskini' => 'Lulus','updated_at' => now()]);

            if (!$sejarahProfil) {
                SejarahProfilKlien::create([
                    'klien_id' => $waris->klien_id,
                    'status_kemaskini' => 'Lulus',
                    'bahagian_kemaskini' => 'Waris',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }
            else{
                $sejarahProfil->update([
                    'status_kemaskini' => 'Lulus',
                    'bahagian_kemaskini' => 'Waris',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }

            return redirect()->back()->with('success', 'Maklumat bapa klien berjaya dikemaskini.');
        } 
        else {
            return redirect()->back()->with('error', 'Klien tidak dijumpai.');
        }
    }

    public function kemaskiniMaklumatIbuKlien(Request $request, $id)
    {
        $validatedData = $request->validate([
            'nama_ibu'  => 'nullable|string|max:255',
            'no_kp_ibu' => 'nullable|string|max:255',
            'no_tel_ibu' => 'nullable|string|max:11',
            'status_ibu' => 'nullable|string|max:255',
            'alamat_ibu' => 'nullable|string|max:255',
            'poskod_ibu' => 'nullable|string|max:5',
            'daerah_ibu' => 'nullable|string|max:255',
            'negeri_ibu' => 'nullable|string|max:255',
        ]);

        $waris = WarisKlien::where('id', $id)->first();
        $sejarahProfil = SejarahProfilKlien::where('klien_id', $waris->klien_id)->first();

        if ($waris) {
            $waris->update($validatedData);
            $waris->update(['status_kemaskini' => 'Lulus', 'updated_at' => now()]);

            if (!$sejarahProfil) {
                SejarahProfilKlien::create([
                    'klien_id' => $waris->klien_id,
                    'status_kemaskini' => 'Lulus',
                    'bahagian_kemaskini' => 'Waris',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }
            else{
                $sejarahProfil->update([
                    'status_kemaskini' => 'Lulus',
                    'bahagian_kemaskini' => 'Waris',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }

            return redirect()->back()->with('success', 'Maklumat ibu klien berjaya dikemaskini.');
        } 
        else {
            return redirect()->back()->with('error', 'Klien tidak dijumpai.');
        }
    }

    public function kemaskiniMaklumatPenjagaKlien(Request $request, $id)
    {
        $validatedData = $request->validate([
            'hubungan_penjaga' => 'nullable|string|max:255',
            'nama_penjaga' => 'nullable|string|max:255',
            'no_kp_penjaga' => 'nullable|string|max:255',
            'no_tel_penjaga' => 'nullable|string|max:11',
            'status_penjaga' => 'nullable|string|max:255',
            'alamat_penjaga' => 'nullable|string|max:255',
            'poskod_penjaga' => 'nullable|string|max:5',
            'daerah_penjaga' => 'nullable|string|max:255',
            'negeri_penjaga' => 'nullable|string|max:255',
        ]);

        // dd($validatedData);

        $waris = WarisKlien::where('id', $id)->first();
        $sejarahProfil = SejarahProfilKlien::where('klien_id', $waris->klien_id)->first();

        if ($waris) {
            $waris->update($validatedData);
            $waris->update(['status_kemaskini' => 'Lulus', 'updated_at' => now()]);

            if (!$sejarahProfil) {
                SejarahProfilKlien::create([
                    'klien_id' => $waris->klien_id,
                    'status_kemaskini' => 'Lulus',
                    'bahagian_kemaskini' => 'Waris',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }
            else{
                $sejarahProfil->update([
                    'status_kemaskini' => 'Lulus',
                    'bahagian_kemaskini' => 'Waris',
                    'pengemaskini' => Auth::id(),
                    'updated_at' => now(),
                ]);
            }

            return redirect()->back()->with('success', 'Maklumat penjaga klien berjaya dikemaskini.');
        } 
        else {
            return redirect()->back()->with('error', 'Klien tidak dijumpai.');
        }
    }

    public function kemaskiniMaklumatRawatanKlien(Request $request, $id)
    {
        $rawatan = RawatanKlien::where('id',$id)->first();

        if ($rawatan) {
            $rawatan->update([
                'status_kesihatan_mental' => $request->status_kesihatan_mental,
                'status_oku' => $request->status_oku,
                'seksyen_okp' => $request->seksyen_okp,
                'tarikh_tamat_pengawasan' => $request->tarikh_tamat_pengawasan,
                'skor_ccri' => $request->skor_ccri,
            ]);
    
            return redirect()->back()->with('success', 'Maklumat rawatan dan pemulihan klien berjaya dikemaskini.');
        } 
        else {
            return redirect()->back()->with('error', 'Klien tidak dijumpai.');
        }
    }

    // KLIEN
    public function pengurusanProfil()
    {
        $negeri = Negeri::all()->sortBy('negeri');
        $daerah = Daerah::all()->sortBy('daerah');
        $negeriKerja = Negeri::all()->sortBy('negeri');
        $daerahKerja = Daerah::all()->sortBy('daerah');
        $negeriWaris = Negeri::all()->sortBy('negeri');
        $daerahWaris = Daerah::all()->sortBy('daerah');
        $negeriPasangan = Negeri::all()->sortBy('negeri');
        $daerahPasangan = Daerah::all()->sortBy('daerah');
        $negeriKerjaPasangan = Negeri::all()->sortBy('negeri');
        $daerahKerjaPasangan = Daerah::all()->sortBy('daerah');

        // Retrieve the client's id based on their no_kp
        $clientId = Klien::where('no_kp', Auth::user()->no_kp)->value('id');

        // Join tables and get the client's details
        $butiranKlien = Klien::leftJoin('pekerjaan_klien', 'klien.id', '=', 'pekerjaan_klien.klien_id')
            ->leftJoin('waris_klien', 'klien.id', '=', 'waris_klien.klien_id')
            ->leftJoin('keluarga_klien', 'klien.id', '=', 'keluarga_klien.klien_id')
            ->leftJoin('rawatan_klien', 'klien.id', '=', 'rawatan_klien.klien_id')
            ->where('klien.id', $clientId)
            ->first();
                
        $resultRequestPasangan = KeluargaKlienUpdateRequest::where('klien_id', $clientId)->first();
        $resultRequestPekerjaan = PekerjaanKlienUpdateRequest::where('klien_id', $clientId)->first();
        $resultRequestKlien = KlienUpdateRequest::where('klien_id', $clientId)->first();
        $resultRequestBapa = WarisKlienUpdateRequest::where('klien_id', $clientId)->where('waris', 1)->first();
        $resultRequestIbu = WarisKlienUpdateRequest::where('klien_id', $clientId)->where('waris', 2)->first();
        $resultRequestPenjaga = WarisKlienUpdateRequest::where('klien_id', $clientId)->where('waris', 3)->first();

        return view('profil_klien.klien.view',compact   ('daerah','negeri','daerahKerja','negeriKerja','negeriWaris','daerahWaris','negeriPasangan','daerahPasangan','negeriKerjaPasangan','daerahKerjaPasangan',
                                                        'butiranKlien','resultRequestPasangan','resultRequestPekerjaan','resultRequestKlien','resultRequestBapa','resultRequestIbu','resultRequestPenjaga'));
    }

    public function muatTurunProfilDiri()
    {
        $klien_id = Klien::where('no_kp', Auth::user()->no_kp)->value('id');

        $klien = Klien::where('id',$klien_id)->first();
        $pekerjaan = PekerjaanKlien::where('id',$klien_id)->first();
        $waris = WarisKlien::where('id',$klien_id)->first();
        $pasangan = KeluargaKlien::where('id',$klien_id)->first();
        $rawatan = RawatanKlien::where('id',$klien_id)->first();

        $pdf = PDF::loadView('profil_klien.klien.export_profil', compact('klien', 'pekerjaan','waris','pasangan','rawatan'));

        $no_kp = Auth::user()->no_kp;

        return $pdf->stream($no_kp . '-profil-peribadi.pdf');
    }

    public function KlienRequestUpdate(Request $request)
    {
        // Validation rules for fields that users can update
        $validatedData = $request->validate([
            'no_tel'           => 'required|string|max:11',
            'emel'             => 'required|email',
            'alamat_rumah'     => 'required|string|max:255',
            'daerah'           => 'required|string|max:255',
            'negeri'           => 'required|string|max:255',
            'poskod'           => 'required|string|max:5',
            'tahap_pendidikan' => 'required|string|max:255',
        ]);

        // Retrieve the existing data that cannot be updated by the user
        $klienId = Klien::where('no_kp', Auth::user()->no_kp)->value('id');
        $existingData = Klien::where('id', $klienId)->first([
            'nama', 
            'no_kp', 
            'jantina', 
            'agama', 
            'bangsa', 
            'status_kesihatan_mental', 
            'status_oku', 
            'skor_ccri'
        ])->toArray();

        // Merge the existing data with the validated data from the request
        $mergedData = array_merge($existingData, $validatedData);

        // Check if there is an existing update request
        $updateRequest = KlienUpdateRequest::where('klien_id', $klienId)->first();
        $sejarahProfil = SejarahProfilKlien::where('klien_id', $klienId)->first();

        if ($updateRequest && $sejarahProfil) 
        {
            // Both $updateRequest and $sejarahProfil exist, update them
            $updateRequest->update([
                'requested_data' => json_encode($mergedData),
                'status' => 'Kemaskini',
                'updated_at' => now(),
            ]);

            Klien::where('id', $klienId)->update(['status_kemaskini' => 'Kemaskini']);

            $sejarahProfil->update([
                'status_kemaskini' => 'Kemaskini',
                'bahagian_kemaskini' => 'Klien',
                'updated_at' => now(),
            ]);
        } 
        else {
            // If one or both do not exist, create new records
            KlienUpdateRequest::create([
                'klien_id' => $klienId,
                'requested_data' => json_encode($mergedData),
                'status' => 'Kemaskini',
            ]);

            Klien::where('id', $klienId)->update(['status_kemaskini' => 'Kemaskini']);

            if (!$sejarahProfil) {
                SejarahProfilKlien::create([
                    'klien_id' => $klienId,
                    'status_kemaskini' => 'Kemaskini',
                    'bahagian_kemaskini' => 'Klien',
                    'pengemaskini' => null, 
                ]);
            }
        }

        return redirect()->back()->with('success', 'Permohonan kemaskini profil diri telah dihantar untuk semakan.');
    }

    public function pekerjaanKlienRequestUpdate(Request $request)
    {
        $validatedData = $request->validate([
            'status_kerja'      => 'required|string|max:255',
            'bidang_kerja'      => 'nullable|string|max:255',
            'nama_kerja'        => 'nullable|string|max:255',
            'pendapatan'        => 'nullable|string|max:255',
            'kategori_majikan'  => 'nullable|string|max:255',
            'nama_majikan'      => 'nullable|string|max:255',
            'no_tel_majikan'    => 'nullable|string|max:11',
            'alamat_kerja'      => 'nullable|string|max:255',
            'poskod_kerja'      => 'nullable|integer',
            'daerah_kerja'      => 'nullable|string|max:255',
            'negeri_kerja'      => 'nullable|string|max:255',
        ]);    
        
        // Set default values to null if they match "Pilih Daerah" or "Pilih Negeri"
        if ($validatedData['daerah_kerja'] === 'Pilih Daerah') {
            $validatedData['daerah_kerja'] = null;
        }
        if ($validatedData['negeri_kerja'] === 'Pilih Negeri') {
            $validatedData['negeri_kerja'] = null;
        }
        
        $klienId = Klien::where('no_kp',Auth::user()->no_kp)->value('id');
        $updateRequest = PekerjaanKlienUpdateRequest::where('klien_id', $klienId)->first();
        $sejarahProfil = SejarahProfilKlien::where('klien_id', $klienId)->first();

        if ($updateRequest && $sejarahProfil) 
        {
            // Update existing request
            $updateRequest->update([
                'requested_data' => json_encode($validatedData),
                'status' => 'Kemaskini', 
                'updated_at' => now(),
            ]);

            PekerjaanKlien::where('id', $klienId)->update(['status_kemaskini' => 'Kemaskini']);

            $sejarahProfil->update([
                'status_kemaskini' => 'Kemaskini',
                'bahagian_kemaskini' => 'Pekerjaan',
                'updated_at' => now(),
            ]);
        } 
        else {
            // Create new request
            PekerjaanKlienUpdateRequest::create([
                'klien_id' => $klienId,
                'requested_data' => json_encode($validatedData),
                'status' => 'Kemaskini',
            ]);

            PekerjaanKlien::where('id', $klienId)->update(['status_kemaskini' => 'Kemaskini']);

            if (!$sejarahProfil) {
                SejarahProfilKlien::create([
                    'klien_id' => $klienId,
                    'status_kemaskini' => 'Kemaskini',
                    'bahagian_kemaskini' => 'Pekerjaan',
                    'pengemaskini' => null, 
                ]);
            }
        }

        return redirect()->back()->with('success', 'Permohonan kemaskini maklumat pekerjaan telah dihantar untuk semakan.');
    }

    public function keluargaKlienRequestUpdate(Request $request)
    {
        // Validate the incoming request
        $validatedData = $request->validate([
            'status_perkahwinan'    => 'required|string|max:255',
            'nama_pasangan'         => 'nullable|string|max:255',
            'no_tel_pasangan'       => 'nullable|string|max:11',
            'bilangan_anak'         => 'nullable|string|max:255',
            'alamat_pasangan'       => 'nullable|string|max:255',
            'poskod_pasangan'       => 'nullable|string|max:5',
            'daerah_pasangan'       => 'nullable|string|max:255',
            'negeri_pasangan'       => 'nullable|string|max:255',
            'alamat_kerja_pasangan' => 'nullable|string|max:255',
            'poskod_kerja_pasangan' => 'nullable|string|max:5',
            'daerah_kerja_pasangan' => 'nullable|string|max:255',
            'negeri_kerja_pasangan' => 'nullable|string|max:255',
        ]);

        // Check for default select values and set to null if needed
        $validatedData['daerah_pasangan'] = $validatedData['daerah_pasangan'] === 'Pilih Daerah' ? null : $validatedData['daerah_pasangan'];
        $validatedData['negeri_pasangan'] = $validatedData['negeri_pasangan'] === 'Pilih Negeri' ? null : $validatedData['negeri_pasangan'];
        $validatedData['daerah_kerja_pasangan'] = $validatedData['daerah_kerja_pasangan'] === 'Pilih Daerah' ? null : $validatedData['daerah_kerja_pasangan'];
        $validatedData['negeri_kerja_pasangan'] = $validatedData['negeri_kerja_pasangan'] === 'Pilih Negeri' ? null : $validatedData['negeri_kerja_pasangan'];

        // Proceed with the existing logic
        $klienId = Klien::where('no_kp', Auth::user()->no_kp)->value('id');
        $updateRequest = KeluargaKlienUpdateRequest::where('klien_id', $klienId)->first();
        $sejarahProfil = SejarahProfilKlien::where('klien_id', $klienId)->first();

        if ($updateRequest && $sejarahProfil) {
            // Update existing request
            $updateRequest->update([
                'requested_data' => json_encode($validatedData, JSON_FORCE_OBJECT), // Ensure NULL values are handled
                'status' => 'Kemaskini', 
                'updated_at' => now(),
            ]);

            KeluargaKlien::where('id', $klienId)->update(['status_kemaskini' => 'Kemaskini']);

            $sejarahProfil->update([
                'status_kemaskini' => 'Kemaskini',
                'bahagian_kemaskini' => 'Keluarga',
                'updated_at' => now(),
            ]);
        } 
        else {
            // Create new request
           KeluargaKlienUpdateRequest::create([
                'klien_id' => $klienId,
                'requested_data' => json_encode($validatedData, JSON_FORCE_OBJECT), // Ensure NULL values are handled
                'status' => 'Kemaskini',
            ]);

            KeluargaKlien::where('id', $klienId)->update(['status_kemaskini' => 'Kemaskini']);

            if (!$sejarahProfil) {
                SejarahProfilKlien::create([
                    'klien_id' => $klienId,
                    'status_kemaskini' => 'Kemaskini',
                    'bahagian_kemaskini' => 'Keluarga',
                    'pengemaskini' => null, 
                ]);
            }
        }

        return redirect()->back()->with('success', 'Permohonan kemaskini maklumat keluarga telah dihantar untuk semakan.');
    }

    public function bapaKlienRequestUpdate(Request $request)
    {
        $validatedData = $request->validate([
            'nama_bapa'     => 'nullable|string|max:255',
            'no_kp_bapa'    => 'nullable|string|max:255',
            'no_tel_bapa'   => 'nullable|string|max:11',
            'status_bapa'   => 'nullable|string|max:255',
            'alamat_bapa'   => 'nullable|string|max:255',
            'poskod_bapa'   => 'nullable|string|max:5',
            'daerah_bapa'   => 'nullable|string|max:255',
            'negeri_bapa'   => 'nullable|string|max:255',
        ]);
        
        $klienId = Klien::where('no_kp', Auth::user()->no_kp)->value('id');
        $updateRequestBapa = WarisKlienUpdateRequest::where('klien_id', $klienId)->where('waris','1')->first();
        $sejarahProfil = SejarahProfilKlien::where('klien_id', $klienId)->first();

        if ($updateRequestBapa && $sejarahProfil) {
            // Update existing request
            $updateRequestBapa->update([
                'requested_data' => json_encode($validatedData),
                'status' => 'Kemaskini', 
                'updated_at' => now(),
            ]);

            WarisKlien::where('id', $klienId)->update(['status_kemaskini' => 'Kemaskini']);

            $sejarahProfil->update([
                'status_kemaskini' => 'Kemaskini',
                'bahagian_kemaskini' => 'Waris',
                'updated_at' => now(),
            ]);
        } 
        else {
            // Create new request
            WarisKlienUpdateRequest::create([
                'klien_id' => $klienId,
                'waris' => 1,
                'requested_data' => json_encode($validatedData),
                'status' => 'Kemaskini',
            ]);

            WarisKlien::where('id', $klienId)->update(['status_kemaskini' => 'Kemaskini']);

            if (!$sejarahProfil) {
                SejarahProfilKlien::create([
                    'klien_id' => $klienId,
                    'status_kemaskini' => 'Kemaskini',
                    'bahagian_kemaskini' => 'Waris',
                    'pengemaskini' => null, 
                ]);
            }
        }

        return redirect()->back()->with('success', 'Permohonan kemaskini maklumat bapa telah dihantar untuk semakan.');
    }

    public function ibuKlienRequestUpdate(Request $request)
    {
        $validatedData = $request->validate([
            'nama_ibu'   => 'nullable|string|max:255',
            'no_kp_ibu'  => 'nullable|string|max:255',
            'no_tel_ibu' => 'nullable|string|max:11',
            'status_ibu' => 'nullable|string|max:255',
            'alamat_ibu' => 'nullable|string|max:255',
            'poskod_ibu' => 'nullable|string|max:5',
            'daerah_ibu' => 'nullable|string|max:255',
            'negeri_ibu' => 'nullable|string|max:255',
        ]);
        
        $klienId = Klien::where('no_kp',Auth::user()->no_kp)->value('id');
        $updateRequestIbu = WarisKlienUpdateRequest::where('klien_id', $klienId)->where('waris','2')->first();
        $sejarahProfil = SejarahProfilKlien::where('klien_id', $klienId)->first();

        if ($updateRequestIbu && $sejarahProfil) {
            // Update existing request
            $updateRequestIbu->update([
                'requested_data' => json_encode($validatedData),
                'status' => 'Kemaskini', 
                'updated_at' => now(),
            ]);

            WarisKlien::where('id', $klienId)->update(['status_kemaskini' => 'Kemaskini']);

            $sejarahProfil->update([
                'status_kemaskini' => 'Kemaskini',
                'bahagian_kemaskini' => 'Waris',
                'updated_at' => now(),
            ]);
        } 
        else {
            // Create new request
            WarisKlienUpdateRequest::create([
                'klien_id' => $klienId,
                'waris' => 2, 
                'requested_data' => json_encode($validatedData),
                'status' => 'Kemaskini',
            ]);

            WarisKlien::where('id', $klienId)->update(['status_kemaskini' => 'Kemaskini']);

            if (!$sejarahProfil) {
                SejarahProfilKlien::create([
                    'klien_id' => $klienId,
                    'status_kemaskini' => 'Kemaskini',
                    'bahagian_kemaskini' => 'Waris',
                    'pengemaskini' => null, 
                ]);
            }
        }

        return redirect()->back()->with('success', 'Permohonan kemaskini maklumat ibu telah dihantar untuk semakan.');
    }

    public function penjagaKlienRequestUpdate(Request $request)
    {
        $validatedData = $request->validate([
            'hubungan_penjaga'  => 'nullable|string|max:255',
            'nama_penjaga'      => 'nullable|string|max:255',
            'no_kp_penjaga'     => 'nullable|string|max:255',
            'no_tel_penjaga'    => 'nullable|string|max:11',
            'status_penjaga'    => 'nullable|string|max:255',
            'alamat_penjaga'    => 'nullable|string|max:255',
            'poskod_penjaga'    => 'nullable|string|max:5',
            'daerah_penjaga'    => 'nullable|string|max:255',
            'negeri_penjaga'    => 'nullable|string|max:255',
        ]);

        $klienId = Klien::where('no_kp', Auth::user()->no_kp)->value('id');
        $updateRequestPenjaga = WarisKlienUpdateRequest::where('klien_id', $klienId)->where('waris','3')->first();
        $sejarahProfil = SejarahProfilKlien::where('klien_id', $klienId)->first();

        if ($updateRequestPenjaga && $sejarahProfil) {
            // Update existing request
            $updateRequestPenjaga->update([
                'requested_data' => json_encode($validatedData),
                'status' => 'Kemaskini', 
                'updated_at' => now(),
            ]);

            WarisKlien::where('id', $klienId)->update(['status_kemaskini' => 'Kemaskini']);

            $sejarahProfil->update([
                'status_kemaskini' => 'Kemaskini',
                'bahagian_kemaskini' => 'Waris',
                'updated_at' => now(),
            ]);
        } 
        else {
            // Create new request
            WarisKlienUpdateRequest::create([
                'klien_id' => $klienId,
                'waris' => 3,
                'requested_data' => json_encode($validatedData),
                'status' => 'Kemaskini',
            ]);

            WarisKlien::where('id', $klienId)->update(['status_kemaskini' => 'Kemaskini']);

            if (!$sejarahProfil) {
                SejarahProfilKlien::create([
                    'klien_id' => $klienId,
                    'status_kemaskini' => 'Kemaskini',
                    'bahagian_kemaskini' => 'Waris',
                    'pengemaskini' => null, 
                ]);
            }
        }

        return redirect()->back()->with('success', 'Permohonan kemaskini maklumat penjaga telah dihantar untuk semakan.');
    }

}
