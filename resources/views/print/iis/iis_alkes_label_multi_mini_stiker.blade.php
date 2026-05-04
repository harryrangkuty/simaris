<!DOCTYPE html>
<html>

<head>
    <style>
        @page {
            size: 50mm 30mm;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Helvetica, Arial, sans-serif;
        }

        .page {
            width: 50mm;
            height: 28mm;
            background-color: white;
            box-sizing: border-box;
            padding-top: 1mm;
            padding-bottom: 1mm;
            page-break-after: always;
        }

        .page:last-child {
            page-break-after: auto;
        }

        table {
            width: 50mm;
            height: 28mm;
            border-collapse: collapse;
            table-layout: fixed;
        }

        td {
            padding: 0;
            margin: 0;
            text-align: center;
            vertical-align: middle;
            overflow: hidden;
        }

        .header {
            height: 4.5mm;
            font-size: 6.5pt;
            font-weight: normal;
            color: #000;
        }

        .divider-row {
            height: 0.2mm;
            line-height: 0;
            font-size: 0;
        }

        .divider {
            background: #000;
            height: 0.2mm;
        }

        .content {
            height: 18.6mm;
        }

        .qr-code {
            width: 18mm;
            height: 18mm;
            display: block;
            margin: -2mm auto;
            transform: scale(1.2);
            transform-origin: center;
        }

        .footer {
            height: 4.5mm;
            font-size: 6.5pt;
            font-weight: normal;
            color: #000;
        }
    </style>
</head>

<body>

@foreach ($items as $item)
    <div class="page">
        <table>
            <!-- Header -->
            <tr>
                <td class="header">
                    {{ $item['data']->qr_code_no }}
                    @if ($item['data']->data_source === 'system' && !empty($item['data']->category_name))
                        - {{ $item['data']->category_name }}
                    @endif
                </td>
            </tr>

            <!-- Divider -->
            <tr class="divider-row">
                <td class="divider"></td>
            </tr>

            <!-- QR -->
            <tr>
                <td class="content">
                    <img src="data:image/png;base64,{{ $item['qrBase64'] }}" class="qr-code">
                </td>
            </tr>

            <!-- Divider -->
            <tr class="divider-row">
                <td class="divider"></td>
            </tr>

            <!-- Footer -->
            <tr>
                <td class="footer">
                    RSU BUNDA THAMRIN
                </td>
            </tr>
        </table>
    </div>
@endforeach

</body>

</html>