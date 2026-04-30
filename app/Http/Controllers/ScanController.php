<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

class ScanController extends AuthController
{
    public function resolve(Request $request)
    {
        try {

            $request->validate([
                'target' => 'required|string',
                'code'   => 'required|string',
            ]);

            $barcode = $request->code;
            $target  = $request->target;

            switch ($target) {

                case 'iis-inventaris':
                    $model = \App\Models\Iis\IisInventory::where('qr_code_no', $barcode)->firstOrFail();
                    $redirect = '/iis/inventories-list';
                    break;

                case 'inventaris':
                    $model = \App\Models\Inventory::where('qr_code_no', $barcode)->firstOrFail();
                    $redirect = '/inventories-list';
                    break;

                case 'iis-alkes':
                    $model = \App\Models\Iis\IisAlkes::where('qr_code_no', $barcode)->firstOrFail();
                    $redirect = '/iis/alkes-list';
                    break;

                case 'alkes':
                    $model = \App\Models\Alkes::where('qr_code_no', $barcode)->firstOrFail();
                    $redirect = '/alkes-list';
                    break;

                default:
                    return $this->vueNotFound();
            }

            $encrypted = Crypt::encryptString($model->qr_code_no);

            return redirect()->to(
                "{$redirect}?req=open&code={$encrypted}"
            );

        }
        catch (ModelNotFoundException $e) {
            return $this->vueNotFound();
        }
        catch (\Illuminate\Validation\ValidationException $e) {
            return $this->vueNotFound();
        }
        catch (Throwable $e) {
            return $this->vueNotFound();
        }
    }

    public function vueNotFound()
    {
        $vue = '<not-found/>';
        return response()->view('layouts.antd', compact('vue'), 404);
    }
}
