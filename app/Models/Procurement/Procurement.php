<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Model;
use App\Models\MasterData\TransactionType;
use App\Models\User;
use App\Models\MasterData\Branch;
use App\Models\MasterData\Supplier;

class Procurement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'code',
        'supplier_id',
        'procurement_request_id',
        'transaction_type_code',
        'funding_source',
        'letter_date',
        'letter_number',
        'status',
        'purchasing_officer_id',
        'notes',
        'estimated_arrival_date',
        'book_date',
        'po_date',
        'approved_at',
        'received_at',
        'registered_at',
        'etc',
        'created_by',
        'updated_by',
        'delete_message',
    ];

    protected $casts = [
        'etc' => 'object'
    ];

    protected $appends = ['total', 'total_item'];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function detail()
    {
        return $this->hasMany(ProcurementDetail::class, 'procurement_id');
    }

    public function profile()
    {
        return $this->hasMany(AssetProfile::class, 'perolehan_id');
    }

    public function transaction()
    {
        return $this->hasMany(AssetTransaction::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function transactionType()
    {
        return $this->belongsTo(TransactionType::class, 'transaction_type_code', 'code');
    }

    public function getTotalAttribute()
    {
        return $this->detail->sum('sub_total');
    }

    public function getTotalItemAttribute()
    {
        return $this->detail->sum('kuantitas');
    }

    public function restore()
    {
        $this->deleted_at = null;
        $this->delete_message = null;
        $this->editor_id = auth()->user()->id;
        return $this->save();
    }

    public function reference()
    {
        return $this->morphMany(AssetTransaction::class, 'reference');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'editor_id');
    }
}
