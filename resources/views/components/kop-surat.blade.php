<style>
    .kop-surat {
        width: 100%;
        border-bottom: 3px solid #000;
        padding-bottom: 5px;
        margin-bottom: 15px;
    }
    .kop-surat table {
        width: 100%;
        border-collapse: collapse;
    }
    .kop-surat td {
        text-align: center;
        vertical-align: middle;
    }
    .kop-surat .logo-pemda {
        width: 80px;
    }
    .kop-surat .logo-sekolah {
        width: 80px;
    }
    .kop-surat .text-kop {
        line-height: 1.2;
        font-family: Tahoma, sans-serif;
    }
    .kop-surat .text-kop .pemprov {
        font-size: 14pt;
        font-weight: normal;
    }
    .kop-surat .text-kop .dinas {
        font-size: 14pt;
        font-weight: bold;
    }
    .kop-surat .text-kop .sekolah {
        font-size: 16pt;
        font-weight: bold;
        white-space: nowrap;
    }
    .kop-surat .text-kop .kontak {
        font-size: 9pt;
        font-weight: normal;
        margin-top: 5px;
    }
</style>

<div class="kop-surat">
    <table>
        <tr>
            <td class="logo-pemda">
                @if(isset($kop['site_logo_pemda']) && $kop['site_logo_pemda'])
                    @php
                        $pathPemda = storage_path('app/public/' . $kop['site_logo_pemda']);
                        if (file_exists($pathPemda)) {
                            $typePemda = pathinfo($pathPemda, PATHINFO_EXTENSION);
                            $dataPemda = file_get_contents($pathPemda);
                            $base64Pemda = 'data:image/' . $typePemda . ';base64,' . base64_encode($dataPemda);
                            echo '<img src="' . $base64Pemda . '" width="80" />';
                        }
                    @endphp
                @endif
            </td>
            <td class="text-kop">
                <div class="pemprov">{{ strtoupper($kop['kop_pemprov'] ?? 'PEMERINTAH PROVINSI JAWA TENGAH') }}</div>
                <div class="dinas">{{ strtoupper($kop['kop_dinas'] ?? 'DINAS PENDIDIKAN') }}</div>
                <div class="sekolah">{{ strtoupper($kop['school_name'] ?? 'NAMA SEKOLAH') }}</div>
                <div class="kontak">
                    {{ $kop['school_address'] ?? '' }} {{ $kop['school_city'] ?? '' }} {{ $kop['school_province'] ?? '' }} {{ !empty($kop['school_postal_code']) ? 'Kode Pos ' . $kop['school_postal_code'] : '' }}<br>
                    Telepon {{ $kop['school_phone'] ?? '' }} Laman {{ $kop['school_website'] ?? '' }}<br>
                    Pos-el {{ $kop['school_email'] ?? '' }}
                </div>
            </td>
            <td class="logo-sekolah">
                @if(isset($kop['site_logo']) && $kop['site_logo'])
                    @php
                        $pathLogo = storage_path('app/public/' . $kop['site_logo']);
                        if (file_exists($pathLogo)) {
                            $typeLogo = pathinfo($pathLogo, PATHINFO_EXTENSION);
                            $dataLogo = file_get_contents($pathLogo);
                            $base64Logo = 'data:image/' . $typeLogo . ';base64,' . base64_encode($dataLogo);
                            echo '<img src="' . $base64Logo . '" width="80" />';
                        }
                    @endphp
                @endif
            </td>
        </tr>
    </table>
</div>
<div style="border-top: 1px solid #000; margin-top: -12px; margin-bottom: 15px;"></div>
