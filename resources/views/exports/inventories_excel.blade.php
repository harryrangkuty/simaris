<table>
    <thead>
        <tr>
            <th>QR Code</th>
            <th>Nama Barang</th>
            <th>Deskripsi</th>
            <th>Kategori</th>
            <th>Kondisi</th>
            <th>Unit</th>
            <th>Gedung</th>
            <th>Lantai</th>
            <th>Penanggung Jawab</th>
            <th>Jabatan PJ</th>
            <th>Last Print By</th>
            <th>Serah Terima</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($inventories as $index => $inv)
            <tr>
                <td>{{ $inv->qr_code_no }}</td>
                <td>{{ $inv->item->name ?? '-' }}</td>
                <td>{{ $inv->description }}</td>
                <td>{{ $inv->category_name }}</td>
                <td>{{ $inv->condition }}</td>
                <td>{{ $inv->unit->name ?? ($inv->unit_legacy ?? '-') }}</td>
                <td>{{ $inv->building->name ?? '-' }}</td>
                <td>{{ $inv->floor ?? '-' }}</td>
                <td>{{ $inv->bUser->name ?? '-' }}</td>
                <td>{{ $inv->bUser->position ?? '-' }}</td>
                <td>{{ $inv->lastPrintBy->name ?? '-' }}</td>
                <td>{{ $inv->is_handed_over ? 'Sudah' : 'Belum' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
