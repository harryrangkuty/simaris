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

        .header {
            position: absolute;
            top: 0;
            width: 100%;
            border-bottom: 0.5px solid #000;
            font-size: 5pt;
            text-align: center;
            padding: 0;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .header table {
            width: 100%;
            border-collapse: collapse;
        }

        .header td {
            border-right: 0.5px solid #000;
            padding: 1.5px;
            text-align: center;
            width: 50%;
        }

        .header td:last-child {
            border-right: none;
        }

        .content {
            text-align: center;
            margin: 0;
            padding: 0;
            line-height: 1;
        }

        .qr-code {
            width: 30mm;
            height: 30mm;
            display: block;
            margin: 0 auto;
        }

        .footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            border-top: 0.5px solid #000;
            font-size: 5pt;
            text-align: center;
            padding: 0;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .footer table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer td {
            border-right: 0.5px solid #000;
            padding: 1.5px;
            text-align: center;
            width: 50%;
        }

        .footer td:last-child {
            border-right: none;
        }
    </style>
</head>

<body>
    <div>
        <div class="header">
            <table cellspacing="0">
                <tr>
                    <td>
                        {{ $data->qr_code_no }}

                        @if ($data->data_source === 'system' && !empty($data->category_name))
                            - {{ $data->category_name }}
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <div class="content">
            <img src="data:image/png;base64,{{ $qrBase64 }}" class="qr-code">
        </div>

        <div class="footer">
            <table cellspacing="0">
                <tr>
                    <td>RSU BUNDA THAMRIN</td>
                </tr>
            </table>
        </div>
    </div>
</body>

</html>
