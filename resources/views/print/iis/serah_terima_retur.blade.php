<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Serah Terima Retur Barang Gudang | Kode : {{ $data->code }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0 10px;
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* HEADER */
        .header {
            border-bottom: 3px solid #004aad;
            background: #e0ebff;
        }

        .header h1 {
            margin: 0;
            font-size: 20px;
            color: #004aad;
        }

        .header h2 {
            margin: 0;
            font-size: 14px;
            font-weight: normal;
        }

        /* INFO BOX */
        .info-box {
            border: 1px solid #004aad;
            border-radius: 5px;
            padding: 10px;
            background: #f5f8ff;
            margin-top: 15px;
        }

        /* TABLE ITEM */
        .item-table {
            margin-top: 20px;
        }

        .item-table th {
            background: #ffd54f;
            padding: 6px;
            border: 1px solid #000;
            text-align: center;
        }

        .item-table td {
            padding: 6px;
            border: 1px solid #ddd;
        }

        .text-center {
            text-align: center;
        }

        /* SIGNATURE */
        .signature {
            margin-top: 50px;
            text-align: center;
        }

        .signature img {
            width: 110px;
            height: 110px;
        }

        .line {
            width: 160px;
            border-top: 1px solid #000;
            margin: 6px auto;
        }

        .notice-box {
            border: 1px solid #004aad;
            border-radius: 5px;
            background: #f9fbff;
            padding: 12px 15px;
            margin: 20px 0;
        }

        .paragraph {
            margin: 20px 0;
            line-height: 1.7;
            text-align: justify;
            text-indent: 35px;
            letter-spacing: 0.2px;
        }
    </style>
</head>

<body>

    {{-- ================= HEADER ================= --}}
    <table class="header">
        <tr>
            <td style="width:90px; text-align:center;">
                <img src="{{ public_path('images/simaris_logo_full_no_bg.png') }}" style="height:70px">
            </td>

            <td style="text-align:center;">
                <h1>RSU BUNDA THAMRIN</h1>
                <h2>SURAT SERAH TERIMA RETUR BARANG</h2>
                <h2>APLIKASI SIMARIS</h2>
            </td>

            <td style="width:90px; text-align:center;">
                <img src="{{ public_path('images/logo-rsbt.png') }}" style="height:70px">
            </td>
        </tr>
    </table>

    {{-- ================= INFO ================= --}}
    <div class="info-box">
        <table>
            <tr>
                <td style="width:25%"><strong>Kode Serah Terima</strong></td>
                <td>: {{ $data->code }}</td>
            </tr>
            <tr>
                <td><strong>Penanggung Jawab Lama</strong></td>
                <td>: {{ $data->fromPj->name }} ({{ $data->fromPj->identifier }})</td>
            </tr>
            <tr>
                <td><strong>Pengaju Retur</strong></td>
                <td>: {{ $data->operator->name }} ({{ $data->operator->identifier }})</td>
            </tr>
            <tr>
                <td><strong>Tanggal Dibuat</strong></td>
                <td>
                    : {{ \Carbon\Carbon::parse($data->created_at)->translatedFormat('d F Y H:i') }}
                </td>
            </tr>
            <tr>
                <td><strong>Tanggal Disubmit</strong></td>
                <td>
                    :
                    @if ($data->submitted_at)
                        {{ \Carbon\Carbon::parse($data->submitted_at)->translatedFormat('d F Y H:i') }}
                    @else
                        -
                    @endif
                </td>
            </tr>
            @php
                $statusLabel =
                    [
                        'verified' => 'DIVERIFIKASI PJ GUDANG',
                        'submitted' => 'DISUBMIT PENGAJU / PJ LAMA',
                        'draft' => 'DRAFT',
                        'rejected' => 'DITOLAK PJ',
                    ][$data->status] ?? strtoupper($data->status);
            @endphp
            <tr>
                <td><strong>Status</strong></td>
                <td>: {{ $statusLabel }}</td>
            </tr>
            @if ($data->verified_at)
                <tr>
                    <td><strong>Tanggal Verifikasi</strong></td>
                    <td>: {{ $data->verified_at->translatedFormat('d F Y H:i') }}</td>
                </tr>
            @endif
        </table>
    </div>

    {{-- Pernyataan Persetujuan --}}
    <div>
        <p class="paragraph">
            Dengan ini dinyatakan bahwa PJ terkait dan PJ gudang telah melakukan pemeriksaan terhadap seluruh barang
            inventaris yang dikembalikan, serta memastikan bahwa barang dimaksud telah terbarcode dan sesuai dengan
            daftar pada dokumen ini.
            Sejak dilaksanakannya serah terima pengembalian, tanggung jawab pengelolaan dan pengamanan barang inventaris
            tersebut sepenuhnya berada pada PJ gudang.
            Setiap perubahan kepemilikan atau pemanfaatan barang selanjutnya wajib dilaksanakan melalui prosedur mutasi
            atau pengembalian sesuai peraturan yang berlaku.
        </p>
    </div>

    {{-- ================= TABLE ITEM ================= --}}
    <table class="item-table">
        <thead>
            <tr>
                <th width="40">No</th>
                <th>QR Code / Barcode</th>
                <th>Deskripsi</th>
                <th>Kondisi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data->items as $i => $item)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $item->qr_code_no ?? '-' }}</td>
                    <td>{{ $item->description ?? '-' }}</td>
                    <td class="text-center">{{ $item->condition ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ================= SIGNATURE ================= --}}
    <table class="signature">
        <tr>
            {{-- OPERATOR --}}
            <td width="50%">
                <div>Diserahkan oleh</div>
                <div><strong>Pengaju Retur/ PJ Lama</strong></div>

                {{-- QR OPERATOR (muncul kalau sudah submit) --}}
                @if ($data->created_at)
                    <div style="margin:15px 0">
                        <img src="data:image/svg+xml;base64,{{ $qrOperatorBase64 }}">
                    </div>
                @endif

                <div class="line"></div>
                <div><strong>{{ $data->operator->name }}</strong></div>
                <div>NIK: {{ $data->operator->identifier }}</div>
                <div>{{ $data->operator->position }}</div>
            </td>

            {{-- PJ --}}
            <td width="50%">
                <div>Diterima oleh</div>
                <div><strong>Admin Gudang</strong></div>

                {{-- QR VERIFIKASI (muncul kalau sudah verified) --}}
                @if ($data->verified_at)
                    <div style="margin:15px 0">
                        <img src="data:image/svg+xml;base64,{{ $qrVerifyBase64 }}">
                    </div>
                @endif

                <div class="line"></div>
                <div><strong>{{ $data->verifier?->name }}</strong></div>
                <div>NIK: {{ $data->verifier?->identifier }}</div>
                <div>{{ $data->verifier?->position }}</div>
            </td>
        </tr>
    </table>

</body>

</html>
