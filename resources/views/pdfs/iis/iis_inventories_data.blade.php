<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Data Inventaris IIS</title>
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
            margin-top: 20px;
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
                <h2>DATA INVENTARIS</h2>
            </td>

            <td style="width:90px; text-align:center;">
                <img src="{{ public_path('images/logo-rsbt.png') }}" style="height:70px">
            </td>
        </tr>
    </table>
    
    {{-- ================= TABLE ITEM ================= --}}
    <table class="item-table">
        <thead>
            <tr>
                <th width="40">No</th>
                <th>QR Code</th>
                <th>Kategori</th>
                <th>Lokasi</th>
                <th>Deskripsi</th>
                <th>Kondisi</th>
                <th>Penanggung Jawab</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($inventories as $i => $inv)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $inv->qr_code_no ?? '-' }}</td>
                    <td>{{ $inv->category_name ?? '-' }}</td>
                    <td>
                        {{ collect([
                            $inv->building->name ?? null,
                            $inv->floor ? 'Lantai ' . $inv->floor : null,
                            $inv->unit->name ?? ($inv->unit_legacy ?? null),
                            $inv->room->name ?? ($inv->room_legacy ?? null),
                        ])->filter()->join(' / ') }}
                    </td>
                    <td>{{ $inv->description ?? '-' }}</td>
                    <td class="text-center">{{ $inv->condition ?? '-' }}</td>
                    <td>{{ $inv->bUser->name ?? ($inv->pj_nik ?? '-') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>
