<?php

namespace App\Models;

use App\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A person in the CRM — the reusable identity behind leads and consultations,
 * so an admin picks an existing contact instead of re-typing details. A contact
 * is NOT a login: the authenticatable {@see User} (client) is provisioned only
 * on payment and linked here via {@see $user_id} (see ProvisionClientFromConsultation).
 */
class Contact extends Model
{
    use HasFactory;
    use HasUuidKey;

    protected $fillable = [
        'name',
        'email',
        'company',
        'country',
        'phone',
        'user_id',
    ];

    /**
     * Find the contact for an email and refresh its details, or create one.
     * Provided (non-empty) fields overwrite; blanks never wipe existing values.
     *
     * @param  array{email: string, name?: ?string, company?: ?string, country?: ?string, phone?: ?string}  $attributes
     */
    public static function resolve(array $attributes): self
    {
        $contact = static::firstOrNew(['email' => $attributes['email']]);

        foreach (['name', 'company', 'country', 'phone'] as $field) {
            if (! empty($attributes[$field])) {
                $contact->{$field} = $attributes[$field];
            }
        }

        $contact->name = $contact->name ?: 'Contact';
        $contact->save();

        return $contact;
    }

    public function consultationRequests(): HasMany
    {
        return $this->hasMany(ConsultationRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
