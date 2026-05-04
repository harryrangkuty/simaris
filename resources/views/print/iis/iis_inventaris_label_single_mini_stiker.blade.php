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
            width: 50mm;
            height: 30mm;
            background-color: white;
            box-sizing: border-box;
            padding-top: 1mm;
            padding-bottom: 1mm;
        }

        table {
            width: 50mm;
            height: 28mm;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 0;
            padding: 0;
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
            position: relative;
        }

        .qr-wrapper {
            display: block;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
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
    <table>
        <!-- Header Section -->
        <tr>
            <td class="header">
                {{ $data->qr_code_no }}
                @if ($data->data_source === 'system' && !empty($data->category_name))
                    - {{ $data->category_name }}
                @endif
            </td>
        </tr>

        <!-- Top Divider -->
        <tr class="divider-row">
            <td class="divider"></td>
        </tr>

        <!-- QR Code Section -->
        <tr>
            <td class="content">
                <img src="data:image/png;base64,{{ $qrBase64 }}" class="qr-code">
            </td>
        </tr>

        <!-- Bottom Divider -->
        <tr class="divider-row">
            <td class="divider"></td>
        </tr>

        <!-- Footer Section -->
        <tr>
            <td class="footer">
                RSU BUNDA THAMRIN
            </td>
        </tr>
    </table>
</body>

</html>
