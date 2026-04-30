<!DOCTYPE html>
<html>
<head>
<style>
    body {
        margin: 10px;
        font-family: Arial, Helvetica, sans-serif;
    }

    /* === GARIS POTONG === */
    .cut-box {
        border: 1px dashed #000;
        padding: 8px;
        margin-bottom: 12px;
        box-sizing: border-box;
    }

    .container {
        width: 100%;
        box-sizing: border-box;
    }

    .sticker {
        width: 100%;
        border: 2px solid #000;
        border-collapse: collapse;
    }

    .sticker td {
        border: 1px solid #000;
        padding: 4px 5px;
        text-align: center;
        vertical-align: middle;
    }

    .qr-table,
    .qr-table tr,
    .qr-table td {
        border: none !important;
        padding: 0 !important;
    }

    .qr img {
        width: 150px;
        display: block;
        margin: auto;
    }

    .barcode {
        font-size: 17px;
        font-weight: bold;
        letter-spacing: 1px;
        line-height: 1.1;
    }

    .asset-no {
        font-size: 18px;
        font-weight: bold;
        letter-spacing: 1px;
        line-height: 1.1;
    }

    .category {
        font-size: 12px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        line-height: 1.1;
        word-break: break-word;
    }

    .label {
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 2px;
        display: block;
    }
</style>
</head>

<body>

@foreach ($items as $item)
    <div class="cut-box">
        <div class="container">
            <table class="sticker">
                <tr>
                    <td rowspan="2" style="width:22%;">
                        <table class="qr-table">
                            <tr>
                                <td class="qr">
                                    <img src="data:image/png;base64, {{ $item['qrBase64'] }}">
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td colspan="2">
                        <span class="label">No QR Code</span>
                        <div class="barcode">
                            {{ $item['data']->qr_code_no }}
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="width:35%;">
                        <span class="label">No Urut Aset</span>
                        <div class="asset-no">
                            {{ str_pad($item['data']->asset_number, 5, '0', STR_PAD_LEFT) }}
                        </div>
                    </td>
                    <td>
                        <span class="label">Kategori</span>
                        <div class="category">
                            {{ $item['data']->category_name }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
@endforeach

</body>
</html>
