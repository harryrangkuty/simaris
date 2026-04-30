<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        .container {
            width: 5cm;
        }

        table {
            width: 5cm;
            border-collapse: collapse;
            border: 2px solid #000;
            table-layout: fixed;
        }

        td {
            border: 1px solid #000;
            padding: 3px;
            text-align: center;
            vertical-align: middle;
            font-size: 10px;
        }

        .barcode {
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 0.8px;
            line-height: 1.1;
        }

        /* QR FIX */
        .qr-cell {
            padding: 0 !important;
            line-height: 0;
        }

        .qr img {
            width: 165px;
            display: block;
            margin: 0 auto;
        }

        .asset-no {
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1px;
            line-height: 1.1;
        }

        .category {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.1;
            word-break: break-word;
        }

        .qr-table,
        .qr-table tr,
        .qr-table td {
            border: none !important;
            padding: 0 !important;
            line-height: 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <table>
            <tr>
                <td colspan="2">
                    <div class="barcode">{{ $data->qr_code_no }}</div>
                </td>
            </tr>

            <tr>
                <td colspan="2" class="qr-cell">
                    <table class="qr-table" width="100%">
                        <tr>
                            <td class="qr">
                                <img src="data:image/png;base64, {{ $qrBase64 }}">
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr>
                <td style="width:50%;">
                    <div class="asset-no">
                        {{ str_pad($data->asset_number, 5, 0, STR_PAD_LEFT) }}
                    </div>
                </td>
                <td>
                    <div class="category">
                        {{ $data->category_name }}
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
