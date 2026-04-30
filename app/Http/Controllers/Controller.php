<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\Configuration;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function per_page()
    {
        $request = request();
        $per_page = 20;

        if (isset($request->per_page))
            $per_page = $request->per_page;

        if (isset($request->results))
            $per_page = $request->results;

        return $per_page;
    }


    public function dynamic_config($key = null, $field = 'value')
    {
        $configurations = Configuration::all()->keyBy('key')->toArray();

        if ($key) {
            return $configurations[$key][$field] ?? null;
        } else {
            return $configurations;
        }
    }

    /**
     * Generate QR Code SVG Base64
     */
    // ===============================
    // QR CODE BACON v3 (SVG)
    // ===============================
    protected function makeQrBase64(string $payload, int $size = 300): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size),
            new SvgImageBackEnd
        );

        $writer = new Writer($renderer);
        $svg = $writer->writeString($payload);

        return base64_encode($svg);
    }

    public function makeQrBase64WithLogo(string $payload, ?string $logoPath = null)
    {
        $qrSize = 300;

        $renderer = new ImageRenderer(
            new RendererStyle($qrSize),
            new SvgImageBackEnd
        );

        $writer = new Writer($renderer);
        $qrSvg = $writer->writeString($payload);

        // Kalau tidak pakai logo
        if (!$logoPath || !file_exists($logoPath)) {
            return base64_encode($qrSvg);
        }

        // ===============================
        // Tambah logo ke tengah QR
        // ===============================
        $logoSize = 60;
        $logoX = ($qrSize - $logoSize) / 2;
        $logoY = ($qrSize - $logoSize) / 2;

        $logoBase64 = base64_encode(file_get_contents($logoPath));

        $logoSvg = <<<SVG
        <image 
            x="{$logoX}" 
            y="{$logoY}" 
            width="{$logoSize}" 
            height="{$logoSize}" 
            xlink:href="data:image/png;base64,{$logoBase64}" 
        />
        SVG;

        $qrSvgWithLogo = str_replace('</svg>', $logoSvg . '</svg>', $qrSvg);

        return base64_encode($qrSvgWithLogo);
    }
}
