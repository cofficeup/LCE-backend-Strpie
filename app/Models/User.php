<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    protected $connection = 'client';
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Legacy table name.
     */
    protected $table = 'lce_user_info';

    /**
     * All legacy columns from lce_user_info.
     */
    protected $fillable = [
        'user_id',
        'user_md',
        'email',
        'password',
        'last_name',
        'first_name',
        'phone_1',
        'phone_2',
        'phone_3',
        'cell_phone_1',
        'opt_in',
        'opt_in_log',
        'address_1',
        'address_2',
        'city',
        'state',
        'zip',
        'country',
        'nearest_cross_street',
        'geo_address',
        'geo_lat',
        'geo_lng',
        'payment_type',
        'payment_cc_number',
        'payment_cc_edate_month',
        'payment_cc_edate_year',
        'customerProfileId',
        'customerPaymentProfileId',
        'payment_phone',
        'payment_address_1',
        'payment_address_2',
        'payment_city',
        'payment_state',
        'payment_zip',
        'payment_country',
        'driver_instructions',
        'driver_instructions_mdate',
        'driver_comments',
        'driver_comments_mdate',
        'laundry_instructions',
        'laundry_instructions_mdate',
        'attendant_comments',
        'attendant_comments_mdate',
        'laundry_pref_detergent',
        'laundry_pref_softener',
        'laundry_pref_bleach',
        'laundry_pref_hanging',
        'laundry_pref_starch',
        'laundry_pref_shirts',
        'price_list_id',
        'pd_upcharge',
        'nolaundry_charge',
        'asd_monday',
        'asd_tuesday',
        'asd_wednesday',
        'asd_thursday',
        'asd_friday',
        'asd_saturday',
        'asd_sunday',
        'hold_date',
        'suspention_date',
        'suspention_note',
        'trust_level',
        'hear_about',
        'email_invoice_on_delivery',
        'wash_fold_instructions',
        'customer_type',
        'custom_minimum_charge',
    ];

    protected $hidden = [
        'user_md',
        'payment_cc_number',
    ];

    /**
     * Get the password for authentication (uses legacy user_md with MD5).
     */
    public function getAuthPassword()
    {
        return $this->user_md;
    }

    /**
     * Legacy timestamps.
     */
    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'mdate';

    /**
     * Relationships
     */
    public function pickups()
    {
        return $this->hasMany(Pickup::class, 'user_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'user_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }

    public function credits()
    {
        return $this->hasMany(Credit::class, 'user_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class, 'user_id');
    }

    public function recurringSchedule()
    {
        return $this->hasOne(RecurringSchedule::class, 'user_id');
    }

    public function communicationSettings()
    {
        return $this->hasOne(CommunicationSettings::class, 'user_id');
    }

    public function vacationLogs()
    {
        return $this->hasMany(VacationLog::class, 'user_id');
    }

    /**
     * Get full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Get primary phone.
     */
    public function getPhoneAttribute(): ?string
    {
        return $this->phone_1 ?: $this->cell_phone_1;
    }
}
